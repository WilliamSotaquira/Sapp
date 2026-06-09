<?php

declare(strict_types=1);

namespace App\Services\SmartParser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Genera tareas y subtareas ITIL usando IA.
 *
 * A partir del título y descripción de la solicitud, produce una tarea principal
 * con subtareas operativas que describan las acciones técnicas a ejecutar.
 */
class LlmTaskGenerator
{
    private const TIMEOUT_SECONDS = 15;

    private const MAX_INPUT_LENGTH = 4000;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un analista ITIL que descompone solicitudes de servicio en tareas ejecutables.

A partir del título y descripción de una solicitud, genera UNA tarea principal con 2-4 subtareas operativas.

Formato de respuesta EXACTO (JSON):
{"title":"Título de la actividad principal","subtasks":[{"title":"Acción operativa 1","minutes":15},{"title":"Acción operativa 2","minutes":20},{"title":"Acción operativa 3","minutes":10}]}

Reglas:
1. El título de la tarea debe describir LA ACTIVIDAD TÉCNICA a realizar (ej: "Publicación de piezas gráficas en portal web"), NO repetir el mensaje del solicitante.
2. Cada subtarea es un paso operativo concreto que ejecutará el técnico (ej: "Validar formato y peso de las piezas recibidas", "Publicar contenido en la sección indicada del portal").
3. La última subtarea siempre es de confirmación/cierre (ej: "Confirmar con el solicitante la correcta publicación").
4. Los minutos estimados son: 10-15 para validaciones, 15-30 para ejecución, 10 para confirmaciones.
5. Títulos de subtareas entre 30 y 120 caracteres, sin puntos finales.
6. NO incluir nombres de personas ni URLs.
7. Responde SOLO con el JSON, sin explicaciones ni formato markdown.
PROMPT;

    /**
     * Genera tareas ITIL basadas en IA.
     *
     * @param  string  $title  El título/asunto de la solicitud
     * @param  string  $description  La descripción de la solicitud
     * @return array|null Array con formato de tareas para el formulario, o null si falla
     */
    public function generate(string $title, string $description): ?array
    {
        if (! config('services.llm.enabled', false)) {
            return null;
        }

        $apiKey = config('services.openrouter.key') ?: config('services.llm.api_key');

        if (empty($apiKey)) {
            return null;
        }

        $userMessage = $this->buildUserMessage($title, $description);

        if (mb_strlen($userMessage) < 10) {
            return null;
        }

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
                    'temperature' => 0.2,
                    'max_tokens' => 500,
                ]);

            if (! $response->successful()) {
                Log::info('LlmTaskGenerator: API request failed', [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();
            $content = trim($data['choices'][0]['message']['content'] ?? '');

            if (empty($content)) {
                return null;
            }

            return $this->parseResponse($content);
        } catch (\Exception $e) {
            Log::info('LlmTaskGenerator: Exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate tasks regardless of LLM_ENABLED config (for use when only tasks need AI).
     * Returns null if API key missing or call fails.
     */
    public function generateWithoutConfig(string $title, string $description): ?array
    {
        $apiKey = config('services.openrouter.key') ?: config('services.llm.api_key');

        if (empty($apiKey)) {
            return null;
        }

        $userMessage = $this->buildUserMessage($title, $description);

        if (mb_strlen($userMessage) < 10) {
            return null;
        }

        $model = config('services.llm.description_model',
            config('services.llm.model',
                config('services.openrouter.model', 'deepseek/deepseek-chat')
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
                    'temperature' => 0.2,
                    'max_tokens' => 500,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = trim($response->json('choices.0.message.content') ?? '');
            if (empty($content)) {
                return null;
            }

            return $this->parseResponse($content);
        } catch (\Exception $e) {
            Log::info('LlmTaskGenerator::generateWithoutConfig failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parsea la respuesta JSON del LLM en el formato esperado por el formulario.
     */
    private function parseResponse(string $content): ?array
    {
        // Limpiar posible markdown wrapping
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);

        $parsed = json_decode($content, true);

        if (!is_array($parsed) || empty($parsed['title'])) {
            return null;
        }

        $taskTitle = mb_substr(trim($parsed['title']), 0, 255);
        if (mb_strlen($taskTitle) < 5) {
            return null;
        }

        $subtasks = [];
        $totalMinutes = 0;

        if (!empty($parsed['subtasks']) && is_array($parsed['subtasks'])) {
            foreach ($parsed['subtasks'] as $sub) {
                if (!is_array($sub) || empty($sub['title'])) {
                    continue;
                }

                $subTitle = mb_substr(trim($sub['title']), 0, 255);
                $minutes = max(5, min(480, (int) ($sub['minutes'] ?? 25)));
                $totalMinutes += $minutes;

                $subtasks[] = [
                    'title' => $subTitle,
                    'priority' => 'medium',
                    'estimated_minutes' => $minutes,
                ];
            }
        }

        if (empty($subtasks)) {
            $totalMinutes = 30;
        }

        return [
            [
                'title' => $taskTitle,
                'description' => null,
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_minutes' => $totalMinutes,
                'estimated_hours' => round($totalMinutes / 60, 2),
                'subtasks' => $subtasks,
            ],
        ];
    }

    private function buildUserMessage(string $title, string $description): string
    {
        $desc = mb_substr(trim($description), 0, self::MAX_INPUT_LENGTH);

        $parts = ["Solicitud: {$title}"];

        if (!empty($desc) && $desc !== $title) {
            $parts[] = "Descripción: {$desc}";
        }

        return implode("\n", $parts);
    }
}
