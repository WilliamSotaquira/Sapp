<?php

namespace App\Services;

use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;
use Exception;
use Illuminate\Support\Facades\Log;

class RecaptchaEnterpriseService
{
    protected $client;
    protected $projectId;
    protected $siteKey;
    protected $enabled;

    public function __construct()
    {
        $this->enabled = config('services.recaptcha.enterprise.enabled', false);
        $this->projectId = config('services.recaptcha.enterprise.project_id');
        $this->siteKey = config('services.recaptcha.site_key');

        if ($this->enabled) {
            try {
                $this->client = new RecaptchaEnterpriseServiceClient();
            } catch (Exception $e) {
                Log::error('Error al inicializar reCAPTCHA Enterprise: ' . $e->getMessage());
                $this->enabled = false;
            }
        }
    }

    /**
     * Crea una evaluación para analizar el riesgo de una acción de la IU
     *
     * @param string $token El token generado obtenido del cliente
     * @param string $action El nombre de la acción que corresponde al token
     * @return array|null Retorna el resultado de la evaluación o null si falla
     */
    public function createAssessment(string $token, string $action = 'login'): ?array
    {
        if (!$this->enabled || !$this->client) {
            Log::info('reCAPTCHA Enterprise no está habilitado, usando fallback');
            return $this->fallbackVerification($token);
        }

        try {
            $projectName = $this->client->projectName($this->projectId);

            // Establece las propiedades del evento
            $event = (new Event())
                ->setSiteKey($this->siteKey)
                ->setToken($token);

            // Crea la solicitud de evaluación
            $assessment = (new Assessment())
                ->setEvent($event);

            $request = (new CreateAssessmentRequest())
                ->setParent($projectName)
                ->setAssessment($assessment);

            $response = $this->client->createAssessment($request);

            // Verifica si el token es válido
            if (!$response->getTokenProperties()->getValid()) {
                $reason = InvalidReason::name($response->getTokenProperties()->getInvalidReason());
                Log::warning('Token reCAPTCHA inválido: ' . $reason);

                return [
                    'success' => false,
                    'score' => 0,
                    'action' => $action,
                    'reason' => $reason,
                    'message' => 'Token inválido: ' . $reason
                ];
            }

            // Verifica si se ejecutó la acción esperada
            $tokenAction = $response->getTokenProperties()->getAction();
            if ($tokenAction != $action) {
                Log::warning("Acción no coincide. Esperado: {$action}, Recibido: {$tokenAction}");
            }

            $score = $response->getRiskAnalysis()->getScore();
            $reasons = $response->getRiskAnalysis()->getReasons();

            Log::info('reCAPTCHA Enterprise - Score: ' . $score . ' - Action: ' . $tokenAction);

            return [
                'success' => true,
                'score' => $score,
                'action' => $tokenAction,
                'reasons' => iterator_to_array($reasons),
                'valid' => $score >= 0.5, // Umbral de confianza
                'message' => 'Verificación exitosa'
            ];

        } catch (Exception $e) {
            Log::error('Error en createAssessment: ' . $e->getMessage());

            // Fallback a verificación básica
            return $this->fallbackVerification($token);
        }
    }

    /**
     * Verificación básica de reCAPTCHA v2 como fallback
     */
    protected function fallbackVerification(string $token): ?array
    {
        $secretKey = config('services.recaptcha.secret_key');

        if (!$secretKey) {
            return [
                'success' => true,
                'score' => 1.0,
                'action' => 'fallback',
                'message' => 'Verificación deshabilitada'
            ];
        }

        try {
            $verifyResponse = @file_get_contents(
                "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$token}"
            );

            if ($verifyResponse === false) {
                return [
                    'success' => false,
                    'score' => 0,
                    'message' => 'Error al conectar con el servicio de verificación'
                ];
            }

            $responseData = json_decode($verifyResponse);

            return [
                'success' => $responseData->success ?? false,
                'score' => $responseData->success ? 1.0 : 0,
                'action' => 'fallback',
                'message' => $responseData->success ? 'Verificación exitosa' : 'Verificación fallida'
            ];

        } catch (Exception $e) {
            Log::error('Error en fallback verification: ' . $e->getMessage());

            return [
                'success' => false,
                'score' => 0,
                'message' => 'Error de verificación'
            ];
        }
    }

    /**
     * Verifica si el score es aceptable
     */
    public function isScoreAcceptable(float $score, float $threshold = 0.5): bool
    {
        return $score >= $threshold;
    }

    /**
     * Verifica el token y retorna true/false
     */
    public function verify(string $token, string $action = 'login', float $threshold = 0.5): bool
    {
        $assessment = $this->createAssessment($token, $action);

        if (!$assessment || !$assessment['success']) {
            return false;
        }

        return $this->isScoreAcceptable($assessment['score'], $threshold);
    }
}
