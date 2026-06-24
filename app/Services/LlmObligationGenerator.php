<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Genera párrafos de actividad para obligaciones contractuales usando IA.
 *
 * A partir de las solicitudes resueltas agrupadas por familia de servicio,
 * produce un resumen profesional que describe las actividades realizadas
 * durante el periodo del corte, listo para el informe de supervisión.
 */
class LlmObligationGenerator
{
    private const TIMEOUT_SECONDS = 30;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un profesional de gestión contractual que redacta informes de cumplimiento de obligaciones para contratos de prestación de servicios del Estado colombiano.

Tu tarea: dado un listado de solicitudes de servicio atendidas durante un periodo, redactar UN SOLO PÁRRAFO que describa las actividades realizadas para cumplir con la obligación contractual.

Reglas estrictas:
1. Redacta en TERCERA PERSONA PASIVA: "Se realizó...", "Se atendieron...", "Se apoyó..."
2. Inicia con "Se atendieron N solicitud(es)" indicando la cantidad exacta.
3. Menciona las actividades más relevantes de forma ESPECÍFICA (nombres de micrositios, boletines, campañas, etc.).
4. NO inventes actividades que no estén en el listado.
5. Si hay muchas solicitudes similares, agrúpalas (ej: "publicación de 5 boletines de circuitos culturales").
6. Máximo 500 caracteres. Sé conciso pero completo.
7. NO uses viñetas ni listas — solo prosa fluida en un párrafo.
8. NO incluyas fechas específicas de cada solicitud.
9. Si la solicitud menciona nombres de personas, NO los incluyas.
10. Cierra mencionando que todas las actividades se realizaron cumpliendo los lineamientos y tiempos establecidos.

Responde ÚNICAMENTE con el párrafo, sin comillas, sin explicaciones adicionales.
PROMPT;

    /**
     * Generate activity text for a single obligation/family.
     */
    public function generateForObligation(
        string $familyName,
        Collection $requests,
        string $period
    ): ?string {
        if (!config('services.llm.enabled', false)) {
            return null;
        }

        $apiKey = config('services.openrouter.key') ?: config('services.llm.api_key');
        if (empty($apiKey)) {
            return null;
        }

        if ($requests->isEmpty()) {
            return null;
        }

        $userMessage = $this->buildUserMessage($familyName, $requests, $period);

        $model = config('services.llm.description_model',
            config('services.llm.model',
                config('services.openrouter.model', 'deepseek/deepseek-chat-v3-0324')
            )
        );
        $baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $endpoint = $baseUrl . '/chat/completions';

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-OpenRouter-Title' => config('services.openrouter.app_name', config('app.name', 'SAPP')),
                ])
                ->post($endpoint, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ]);

            if (!$response->successful()) {
                Log::warning('LlmObligationGenerator: API failed', [
                    'status' => $response->status(),
                    'family' => $familyName,
                ]);
                return null;
            }

            $data = $response->json();
            $content = trim($data['choices'][0]['message']['content'] ?? '');

            if (empty($content) || mb_strlen($content) < 20) {
                return null;
            }

            return mb_substr($content, 0, 2000);
        } catch (\Exception $e) {
            Log::warning('LlmObligationGenerator: Exception', [
                'error' => $e->getMessage(),
                'family' => $familyName,
            ]);
            return null;
        }
    }

    /**
     * Generate activity texts for ALL obligations in batch.
     * Returns array keyed by family_id => generated text.
     */
    public function generateBatch(array $obligations, string $period): array
    {
        $results = [];

        foreach ($obligations as $obligation) {
            if ($obligation['request_count'] === 0) {
                $results[$obligation['family_id']] = 'No se registraron solicitudes para esta obligación en el periodo.';
                continue;
            }

            $generated = $this->generateForObligation(
                $obligation['family_name'],
                $obligation['requests'],
                $period
            );

            $results[$obligation['family_id']] = $generated ?? $obligation['activity_text'];
        }

        return $results;
    }

    private function buildUserMessage(string $familyName, Collection $requests, string $period): string
    {
        $lines = [];
        $lines[] = "OBLIGACIÓN: {$familyName}";
        $lines[] = "PERIODO: {$period}";
        $lines[] = "TOTAL SOLICITUDES: {$requests->count()}";
        $lines[] = "";
        $lines[] = "SOLICITUDES ATENDIDAS:";

        foreach ($requests->take(30) as $sr) {
            $title = $sr->title ?? 'Sin título';
            $service = $sr->subService ? ($sr->subService->service ? $sr->subService->service->name : '') : '';
            $subService = $sr->subService ? $sr->subService->name : '';
            $line = "- {$title}";
            if ($service) $line .= " [{$service}";
            if ($subService) $line .= " > {$subService}";
            if ($service) $line .= "]";
            $lines[] = $line;
        }

        if ($requests->count() > 30) {
            $remaining = $requests->count() - 30;
            $lines[] = "... y {$remaining} solicitudes adicionales.";
        }

        return implode("\n", $lines);
    }
}
