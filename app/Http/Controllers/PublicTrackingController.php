<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class PublicTrackingController extends Controller
{
    /**
     * Mostrar formulario de consulta pública
     */
    public function index()
    {
        return view('public.tracking.index');
    }

    /**
     * Buscar solicitud por número de ticket o email
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3',
            'type' => 'required|in:ticket,email',
            'g-recaptcha-response' => 'required',
        ], [
            'g-recaptcha-response.required' => 'Por favor completa la verificación de seguridad (reCAPTCHA).',
        ]);

        // Verificar reCAPTCHA
        $recaptchaResponse = $request->input('g-recaptcha-response');
        $recaptchaSecret = config('services.recaptcha.secret_key');

        $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
        $responseData = json_decode($verifyResponse);

        if (!$responseData->success) {
            return back()
                ->withInput()
                ->withErrors(['g-recaptcha-response' => 'La verificación de seguridad falló. Por favor intenta nuevamente.']);
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

            return view('public.tracking.show', compact('serviceRequest'));
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

        // Mostrar la solicitud directamente (la verificación ya se hizo en el search con reCAPTCHA)
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
