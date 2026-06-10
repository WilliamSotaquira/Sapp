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
Eres un analista de mesa de servicios ITIL que trabaja como administrador/programador/analista web. Tu tarea es redactar la descripción formal de un ticket de solicitud de servicio DESDE TU PERSPECTIVA TÉCNICA (lo que TÚ harás).

A partir del asunto y contenido de un correo/mensaje/reunión, redacta UN SOLO PÁRRAFO formal que:

1. Inicie con un verbo de acción técnica describiendo lo que TÚ ejecutarás (ej: "Publicar...", "Configurar...", "Implementar...", "Actualizar...", "Desarrollar...").
2. Describa la ACCIÓN TÉCNICA concreta: publicar en CMS, subir archivos, editar contenido, configurar componente, crear página, etc.
3. Incluya el DÓNDE (portal web, micrositio, sección específica) y los INSUMOS utilizados (archivos, enlace de Drive, piezas gráficas).
4. Si hay fecha límite, inclúyela.
5. Sea conciso: entre 100 y 350 caracteres máximo.
6. NO uses "Se solicita..." ni "Se requiere..." — describe lo que TÚ harás como ejecutor técnico.
7. NO incluya nombres de personas, correos, firmas ni saludos.
8. NO incluya URLs completas; referéncielas genéricamente.
9. NO repitas el título/asunto textualmente.
10. Si el texto incluye confirmaciones de que ya se hizo ("ya quedó publicada", "listo"), genera la descripción de la acción que SE EJECUTÓ (como registro formal del trabajo realizado).

Ejemplo:
Título: Publicación de nota - Pico y placa regional
Mensaje: Chicos me ayudan con la publicación de esta nota en el portal. Ya quedó publicada.
Resultado: Publicar nota informativa sobre pico y placa regional en la sección de noticias del portal web institucional, conforme al contenido suministrado por el área de comunicaciones.

Ejemplo 2:
Título: Actualización micrositio fotodetección
Mensaje: Necesito que actualicen las cifras del micrositio con los datos adjuntos.
Resultado: Actualizar cifras y datos estadísticos en el micrositio de fotodetección, conforme a la información suministrada en archivo adjunto.

Responde ÚNICAMENTE con el párrafo, sin comillas, sin explicaciones.
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
