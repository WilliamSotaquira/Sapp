<?php

use App\Services\RecaptchaEnterpriseService;

if (!function_exists('verify_recaptcha')) {
    /**
     * Verifica un token de reCAPTCHA
     *
     * @param string $token Token de reCAPTCHA
     * @param string $action Acción a verificar
     * @param float $threshold Umbral mínimo de score (0.0 - 1.0)
     * @return bool
     */
    function verify_recaptcha(string $token, string $action = 'action', float $threshold = 0.5): bool
    {
        $service = new RecaptchaEnterpriseService();
        return $service->verify($token, $action, $threshold);
    }
}

if (!function_exists('recaptcha_assessment')) {
    /**
     * Obtiene una evaluación completa de reCAPTCHA
     *
     * @param string $token Token de reCAPTCHA
     * @param string $action Acción a verificar
     * @return array|null
     */
    function recaptcha_assessment(string $token, string $action = 'action'): ?array
    {
        $service = new RecaptchaEnterpriseService();
        return $service->createAssessment($token, $action);
    }
}

if (!function_exists('recaptcha_enabled')) {
    /**
     * Verifica si reCAPTCHA está habilitado
     *
     * @return bool
     */
    function recaptcha_enabled(): bool
    {
        return config('services.recaptcha.site_key') &&
               (config('services.recaptcha.secret_key') || config('services.recaptcha.enterprise.enabled'));
    }
}

if (!function_exists('recaptcha_enterprise_enabled')) {
    /**
     * Verifica si reCAPTCHA Enterprise está habilitado
     *
     * @return bool
     */
    function recaptcha_enterprise_enabled(): bool
    {
        return config('services.recaptcha.enterprise.enabled', false);
    }
}
