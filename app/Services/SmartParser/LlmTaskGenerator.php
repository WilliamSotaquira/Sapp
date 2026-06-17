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
Eres un analista ITIL que descompone solicitudes de servicio en tareas ejecutables para un administrador/programador/analista web.

CONTEXTO IMPORTANTE:
- El usuario (ejecutor) es un webmaster que gestiona portales web institucionales.
- Las solicitudes llegan como correos, chats o textos pegados que pueden contener hilos de conversación, respuestas previas y contexto que NO son tareas.
- TU trabajo es identificar LA ACCIÓN TÉCNICA PRINCIPAL que el webmaster debe ejecutar y descomponerla en pasos operativos.

A partir del título y descripción de una solicitud, genera UNA tarea principal con 2-5 subtareas operativas.

Formato de respuesta EXACTO (JSON):
{"title":"Título de la actividad técnica principal","subtasks":[{"title":"Acción operativa 1","minutes":15},{"title":"Acción operativa 2","minutes":20},{"title":"Acción operativa 3","minutes":10}]}

Reglas ESTRICTAS:
1. El título de la tarea debe describir LA ACTIVIDAD TÉCNICA a realizar (ej: "Publicación de piezas gráficas en portal web"), NO repetir el mensaje del solicitante ni su contenido.
2. Cada subtarea es un paso operativo concreto que ejecutará el técnico: subir archivos, editar HTML/CSS, configurar CMS, publicar contenido, validar en navegador, generar URLs, etc.
3. NUNCA uses como subtarea fragmentos de texto del correo original, respuestas de otras personas, contenido legal, párrafos informativos o explicaciones. Esos son CONTEXTO, no acciones.
4. NUNCA generes más de 5 subtareas. Si la solicitud es simple, 2-3 subtareas es suficiente.
5. La última subtarea siempre es de confirmación/notificación (ej: "Notificar al solicitante la publicación realizada").
6. Los minutos estimados son: 5-15 para validaciones, 15-30 para ejecución técnica, 5-10 para confirmaciones.
7. Títulos de subtareas entre 20 y 100 caracteres, iniciando con verbo de acción: Validar, Publicar, Configurar, Subir, Editar, Crear, Desplegar, Revisar, Optimizar, Notificar.
8. NO incluir nombres de personas, URLs, ni contenido literal del correo.
9. Si la descripción menciona que algo "ya fue resuelto" o "ya se hizo", genera las tareas como registro formal de lo ejecutado (pasado: "Publicación realizada de...", subtareas en pasado o como verificación).
10. Responde SOLO con el JSON, sin explicaciones ni formato markdown.
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

                $subTitle = trim($sub['title']);

                // Rechazar subtareas con títulos demasiado largos (probablemente texto copiado)
                if (mb_strlen($subTitle) > 150) {
                    $subTitle = mb_substr($subTitle, 0, 100);
                }

                // Rechazar subtareas que no parecen acciones (no inician con verbo o son oraciones largas informativas)
                if (mb_strlen($subTitle) < 5) {
                    continue;
                }

                $minutes = max(5, min(60, (int) ($sub['minutes'] ?? 15)));
                $totalMinutes += $minutes;

                $subtasks[] = [
                    'title' => $subTitle,
                    'priority' => 'medium',
                    'estimated_minutes' => $minutes,
                ];
            }

            // Máximo 5 subtareas
            $subtasks = array_slice($subtasks, 0, 5);
        }

        if (empty($subtasks)) {
            $totalMinutes = 30;
        } else {
            // Recalcular total con subtareas limitadas
            $totalMinutes = array_sum(array_column($subtasks, 'estimated_minutes'));
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

        $parts = ["Título de la solicitud ITIL: {$title}"];

        if (!empty($desc) && $desc !== $title) {
            $parts[] = "Descripción técnica (qué debe hacer el webmaster): {$desc}";
        }

        $parts[] = "\nGenera las tareas operativas que el administrador web debe ejecutar para resolver esta solicitud. NO uses el contenido textual como subtareas.";

        return implode("\n", $parts);
    }
}
