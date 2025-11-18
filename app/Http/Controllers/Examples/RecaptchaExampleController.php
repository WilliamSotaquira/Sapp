<?php

namespace App\Http\Controllers\Examples;

use App\Services\RecaptchaEnterpriseService;
use Illuminate\Http\Request;

/**
 * Ejemplo de uso de reCAPTCHA Enterprise
 * Este archivo es solo para referencia y puede eliminarse en producción
 */
class RecaptchaExampleController
{
    /**
     * Ejemplo 1: Verificación simple (recomendado)
     */
    public function simpleVerification(Request $request)
    {
        $token = $request->input('g-recaptcha-response');
        $action = 'example_action';

        $recaptchaService = new RecaptchaEnterpriseService();

        // Verificación directa con umbral 0.5
        if (!$recaptchaService->verify($token, $action, 0.5)) {
            return response()->json([
                'success' => false,
                'message' => 'Verificación de seguridad fallida'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verificación exitosa'
        ]);
    }

    /**
     * Ejemplo 2: Evaluación detallada con score
     */
    public function detailedAssessment(Request $request)
    {
        $token = $request->input('g-recaptcha-response');
        $action = 'checkout';

        $recaptchaService = new RecaptchaEnterpriseService();
        $assessment = $recaptchaService->createAssessment($token, $action);

        if (!$assessment || !$assessment['success']) {
            return response()->json([
                'error' => $assessment['message'] ?? 'Error de verificación'
            ], 500);
        }

        $score = $assessment['score'];

        // Lógica basada en score
        if ($score >= 0.8) {
            // Alta confianza - Procesar inmediatamente
            return $this->processCheckout($request);

        } elseif ($score >= 0.5) {
            // Confianza media - Agregar verificación adicional
            return response()->json([
                'requires_2fa' => true,
                'message' => 'Se requiere verificación adicional'
            ]);

        } elseif ($score >= 0.3) {
            // Baja confianza - Modo revisión manual
            return response()->json([
                'manual_review' => true,
                'message' => 'Su solicitud está en revisión'
            ]);

        } else {
            // Muy bajo - Bloquear
            return response()->json([
                'blocked' => true,
                'message' => 'Solicitud bloqueada por seguridad'
            ], 403);
        }
    }

    /**
     * Ejemplo 3: Con logging detallado
     */
    public function withDetailedLogging(Request $request)
    {
        $token = $request->input('g-recaptcha-response');
        $action = 'sensitive_operation';

        $recaptchaService = new RecaptchaEnterpriseService();
        $assessment = $recaptchaService->createAssessment($token, $action);

        // Log detallado para análisis
        \Log::channel('security')->info('reCAPTCHA Assessment', [
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'action' => $action,
            'score' => $assessment['score'] ?? null,
            'valid' => $assessment['valid'] ?? false,
            'reasons' => $assessment['reasons'] ?? [],
            'timestamp' => now()
        ]);

        if ($assessment['score'] < 0.6) {
            // Log de alerta de seguridad
            \Log::channel('security')->warning('Low reCAPTCHA Score Detected', [
                'user_id' => auth()->id(),
                'score' => $assessment['score'],
                'ip' => $request->ip()
            ]);
        }

        return response()->json($assessment);
    }

    /**
     * Ejemplo 4: Integración con Rate Limiting
     */
    public function withRateLimiting(Request $request)
    {
        $token = $request->input('g-recaptcha-response');

        $recaptchaService = new RecaptchaEnterpriseService();
        $assessment = $recaptchaService->createAssessment($token, 'api_call');

        if (!$assessment['success']) {
            return response()->json(['error' => 'Verification failed'], 403);
        }

        // Ajustar rate limit según score
        $score = $assessment['score'];

        if ($score >= 0.8) {
            // Alta confianza - 100 requests/hora
            $maxAttempts = 100;
        } elseif ($score >= 0.5) {
            // Media confianza - 50 requests/hora
            $maxAttempts = 50;
        } else {
            // Baja confianza - 10 requests/hora
            $maxAttempts = 10;
        }

        // Aplicar rate limiting dinámico
        $key = 'api_rate_limit:' . $request->ip();
        $attempts = cache()->get($key, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => 3600
            ], 429);
        }

        cache()->put($key, $attempts + 1, now()->addHour());

        return response()->json([
            'success' => true,
            'remaining_requests' => $maxAttempts - $attempts - 1
        ]);
    }

    /**
     * Ejemplo 5: Validación en Formularios
     */
    public function formValidation(Request $request)
    {
        // Validación normal de Laravel
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string',
            'g-recaptcha-response' => 'required'
        ]);

        // Verificación de reCAPTCHA
        $recaptchaService = new RecaptchaEnterpriseService();

        if (!$recaptchaService->verify($validated['g-recaptcha-response'], 'contact_form', 0.4)) {
            return back()
                ->withInput()
                ->withErrors(['g-recaptcha-response' => 'Verificación de seguridad fallida']);
        }

        // Procesar formulario...

        return redirect()->back()->with('success', 'Mensaje enviado correctamente');
    }

    /**
     * Mock de proceso de checkout
     */
    private function processCheckout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Checkout procesado'
        ]);
    }
}
