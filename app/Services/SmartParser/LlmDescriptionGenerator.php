<?php

declare(strict_types=1);

namespace App\Services\SmartParser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Genera una descripción concisa de la solicitud usando IA.
 *
 * A partir del título identificado y el cuerpo del mensaje (ya limpio de chrome),
 * produce una descripción que resume de forma clara qué se solicita,
 * redactada en tercera persona y orientada a la acción.
 */
class LlmDescriptionGenerator
{
    private const TIMEOUT_SECONDS = 15;

    private const MAX_INPUT_LENGTH = 8000;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un analista de mesa de servicios ITIL. Tu tarea es redactar la descripción formal de un ticket de solicitud de servicio.

A partir del asunto y contenido de un correo/mensaje/reunión, redacta UN SOLO PÁRRAFO formal que:

1. Inicie con "Se solicita..." o "Se requiere..." describiendo la acción técnica a ejecutar.
2. Incluya el QUÉ (acción), el DÓNDE (página, sección, micrositio, sistema) y el PARA QUÉ (objetivo o razón) si están disponibles.
3. Mencione recursos adjuntos relevantes (ej: "conforme a las piezas gráficas suministradas vía Drive" o "según el documento compartido").
4. Si hay una fecha límite o plazo, inclúyelo (ej: "con fecha de publicación el sábado 6 de junio").
5. Sea conciso: entre 150 y 400 caracteres máximo.
6. NO incluya nombres de personas, correos electrónicos, firmas, saludos ni despedidas.
7. NO incluya URLs completas; referéncielas genéricamente (ej: "enlace compartido", "carpeta de Drive suministrada").
8. NO repita el título/asunto textualmente como primera oración.
9. Responde ÚNICAMENTE con el párrafo, sin comillas, sin explicaciones.

Ejemplo:
Título: Publicación de piezas en portal web
Mensaje: Le envío el drive con las piezas que deberán publicarse en la página desde el sábado 6 de junio.
Resultado: Se solicita la publicación de un conjunto de piezas gráficas en el portal web institucional, conforme a los archivos suministrados vía carpeta de Drive, con fecha de implementación a partir del sábado 6 de junio.
PROMPT;

    /**
     * Genera una descripción basada en IA para la solicitud.
     *
     * @param  string  $title  El título/asunto identificado
     * @param  string  $cleanBody  El cuerpo del mensaje ya limpio de chrome
     * @return string|null La descripción generada, o null si no se pudo generar
     */
    public function generate(string $title, string $cleanBody): ?string
    {
        if (! config('services.llm.enabled', false)) {
            return null;
        }

        $apiKey = config('services.openrouter.key') ?: config('services.llm.api_key');

        if (empty($apiKey)) {
            return null;
        }

        $userMessage = $this->buildUserMessage($title, $cleanBody);

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
                    'max_tokens' => 300,
                ]);

            if (! $response->successful()) {
                Log::info('LlmDescriptionGenerator: API request failed', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $content = trim($data['choices'][0]['message']['content'] ?? '');

            if (empty($content) || mb_strlen($content) < 10) {
                return null;
            }

            // Asegurar que no exceda 5000 caracteres (límite del campo)
            return mb_substr($content, 0, 5000);
        } catch (\Exception $e) {
            Log::info('LlmDescriptionGenerator: Exception during generation', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Construye el mensaje del usuario para el LLM.
     */
    private function buildUserMessage(string $title, string $cleanBody): string
    {
        $body = mb_substr(trim($cleanBody), 0, self::MAX_INPUT_LENGTH);

        $parts = [];

        if (!empty($title)) {
            $parts[] = "Título/Asunto: {$title}";
        }

        if (!empty($body)) {
            $parts[] = "Contenido del mensaje:\n{$body}";
        } else {
            $parts[] = "Contenido del mensaje: (solo se recibió el asunto, sin cuerpo adicional)";
        }

        return implode("\n\n", $parts);
    }
}
