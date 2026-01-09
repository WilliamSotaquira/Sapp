<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Services\RecaptchaEnterpriseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicTrackingController extends Controller
{
    private const TRACKING_TICKET_SESSION_PREFIX = 'public_tracking_ticket_';
    private const TRACKING_EMAIL_SESSION_KEY = 'public_tracking_email';
    private const TRACKING_SESSION_MAX_AGE_MINUTES = 30;

    /**
     * Mostrar formulario de consulta pública
     */
    public function index()
    {
        return view('public.tracking.index');
    }

    private function grantTicketAccess(Request $request, string $ticketNumber): void
    {
        $request->session()->put(self::TRACKING_TICKET_SESSION_PREFIX . $ticketNumber, [
            'granted_at' => now()->timestamp,
        ]);
    }

    private function hasValidTicketAccess(Request $request, string $ticketNumber): bool
    {
        $value = $request->session()->get(self::TRACKING_TICKET_SESSION_PREFIX . $ticketNumber);
        if (!is_array($value) || empty($value['granted_at']) || !is_numeric($value['granted_at'])) {
            return false;
        }

        $grantedAt = Carbon::createFromTimestamp((int) $value['granted_at']);
        return $grantedAt->diffInMinutes(now()) <= self::TRACKING_SESSION_MAX_AGE_MINUTES;
    }

    /**
     * Buscar solicitud por número de ticket o email
     */
    public function search(Request $request)
    {
        $rules = [
            'query' => 'required|string|min:3',
            'type' => 'required|in:ticket,email',
        ];

        $messages = [];

        // No exigir reCAPTCHA durante tests automatizados.
        $siteKey = config('services.recaptcha.site_key');
        $secretKey = config('services.recaptcha.secret_key');

        if (!app()->environment('testing') && !empty($siteKey) && !empty($secretKey)) {
            $rules['g-recaptcha-response'] = 'required';
            $messages['g-recaptcha-response.required'] = 'Por favor completa la verificación de seguridad (reCAPTCHA).';
        }

        $validated = $request->validate($rules, $messages);

        // Verificar reCAPTCHA solo si está completamente configurado
        if (!app()->environment('testing') && !empty($siteKey) && !empty($secretKey) && $request->has('g-recaptcha-response')) {
            $recaptchaResponse = $request->input('g-recaptcha-response');

            // Usar reCAPTCHA Enterprise si está habilitado
            if (config('services.recaptcha.enterprise.enabled')) {
                $recaptchaService = new RecaptchaEnterpriseService();
                $assessment = $recaptchaService->createAssessment($recaptchaResponse, 'search');

                if (!$assessment || !$assessment['success']) {
                    return back()
                        ->withInput()
                        ->withErrors(['g-recaptcha-response' => $assessment['message'] ?? 'La verificación de seguridad falló.']);
                }

                if (!$recaptchaService->isScoreAcceptable($assessment['score'], 0.3)) {
                    return back()
                        ->withInput()
                        ->withErrors(['g-recaptcha-response' => 'Verificación sospechosa. Por favor intenta nuevamente.']);
                }
            } else {
                // Fallback a reCAPTCHA v2
                $recaptchaSecret = config('services.recaptcha.secret_key');

                if ($recaptchaSecret) {
                    $verifyResponse = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");

                    if ($verifyResponse === false) {
                        return back()
                            ->withInput()
                            ->withErrors(['g-recaptcha-response' => 'No se pudo verificar el reCAPTCHA. Por favor intenta nuevamente.']);
                    }

                    $responseData = json_decode($verifyResponse);

                    if (!$responseData || !$responseData->success) {
                        return back()
                            ->withInput()
                            ->withErrors(['g-recaptcha-response' => 'La verificación de seguridad falló. Por favor intenta nuevamente.']);
                    }
                }
            }
        }

        $query = $validated['query'];
        $type = $validated['type'];

        if ($type === 'ticket') {
            // Buscar por número de ticket
            $serviceRequest = ServiceRequest::with([
                'subService.service',
                'sla',
                'requester',
                'statusHistories' => function($q) {
                    $q->with('changedBy')->orderBy('created_at', 'desc');
                },
                'evidences'
            ])
            ->where('ticket_number', 'like', '%' . $query . '%')
            ->first();

            if (!$serviceRequest) {
                return back()->with('error', 'No se encontró ninguna solicitud con ese número de ticket.');
            }

            // Otorgar acceso temporal para ver el detalle por URL
            $this->grantTicketAccess($request, $serviceRequest->ticket_number);

            // Redirigir al detalle usando el ticket exacto
            return redirect()->route('public.tracking.show', $serviceRequest->ticket_number);
        } else {
            // Buscar por email del solicitante
            $serviceRequests = ServiceRequest::with([
                'subService.service',
                'sla',
                'requester'
            ])
            ->whereHas('requester', function($q) use ($query) {
                $q->where('email', 'like', '%' . $query . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

            if ($serviceRequests->isEmpty()) {
                return back()->with('error', 'No se encontraron solicitudes para ese correo electrónico.');
            }

            // Guardar email en sesión para permitir acceder a detalles desde el listado
            $request->session()->put(self::TRACKING_EMAIL_SESSION_KEY, strtolower(trim($query)));

            return view('public.tracking.list', compact('serviceRequests', 'query'));
        }
    }

    /**
     * Ver detalle de solicitud específica (requiere validación)
     */
    public function show(Request $request, $ticketNumber)
    {
        $serviceRequest = ServiceRequest::with([
            'subService.service',
            'sla',
            'requester',
            'assignee',
            'statusHistories' => function($q) {
                $q->with('changedBy')->orderBy('created_at', 'desc');
            },
            'evidences'
        ])
        ->where('ticket_number', $ticketNumber)
        ->firstOrFail();

        // Bloquear acceso directo por URL si no proviene de una búsqueda válida
        $hasTicketAccess = $this->hasValidTicketAccess($request, $ticketNumber);
        $sessionEmail = $request->session()->get(self::TRACKING_EMAIL_SESSION_KEY);
        $requesterEmail = $serviceRequest->requester?->email ? strtolower(trim($serviceRequest->requester->email)) : null;
        $hasEmailAccess = !empty($sessionEmail) && !empty($requesterEmail) && $sessionEmail === $requesterEmail;

        if (!$hasTicketAccess && !$hasEmailAccess) {
            return redirect()
                ->route('public.tracking.index')
                ->with('error', 'Acceso no autorizado. Realiza la consulta desde el formulario de búsqueda.');
        }

        return view('public.tracking.show', compact('serviceRequest'));
    }

    /**
     * Verificar acceso por email
     */
    public function verifyEmail(Request $request, $ticketNumber)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $serviceRequest = ServiceRequest::with('requester')
            ->where('ticket_number', $ticketNumber)
            ->firstOrFail();

        // Verificar que el email coincida con el del solicitante
        if (strtolower($serviceRequest->requester->email) !== strtolower($validated['email'])) {
            return back()->with('error', 'El correo electrónico no coincide con el solicitante de esta solicitud.');
        }

        // Guardar en sesión que el email fue verificado
        $request->session()->put('tracking_email_' . $ticketNumber, $validated['email']);

        return redirect()->route('public.tracking.show', $ticketNumber)
            ->with('success', 'Acceso verificado correctamente.');
    }
}
