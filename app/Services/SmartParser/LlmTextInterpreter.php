<?php

declare(strict_types=1);

namespace App\Services\SmartParser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmTextInterpreter
{
    private const TIMEOUT_SECONDS = 60;

    /**
     * Interprets raw text using an LLM with the appropriate ITIL prompt.
     *
     * @param  string  $rawText  The raw email/WhatsApp text
     * @param  string  $workspaceName  The workspace name to select the right prompt
     * @return string|null The structured text response, or null on failure
     */
    public function interpret(string $rawText, string $workspaceName): ?string
    {
        if (! config('services.llm.enabled', false)) {
            return null;
        }

        $systemPrompt = $this->getSystemPrompt($workspaceName);

        if ($systemPrompt === null) {
            Log::warning('LlmTextInterpreter: No ITIL prompt found for workspace', [
                'workspace' => $workspaceName,
            ]);

            return null;
        }

        $apiKey = config('services.openrouter.key') ?: config('services.llm.api_key');

        if (empty($apiKey)) {
            Log::warning('LlmTextInterpreter: No API key configured');

            return null;
        }

        $model = config('services.llm.description_model', config('services.llm.model', 'deepseek/deepseek-chat-v3-0324'));
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
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $rawText],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 2000,
                ]);

            if (! $response->successful()) {
                Log::warning('LlmTextInterpreter: API request failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (empty($content)) {
                Log::warning('LlmTextInterpreter: Empty response from LLM');

                return null;
            }

            return trim($content);
        } catch (\Exception $e) {
            Log::error('LlmTextInterpreter: Exception during API call', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Checks if a prompt file exists for the given workspace.
     */
    public function hasPromptForWorkspace(string $workspaceName): bool
    {
        $normalizedName = mb_strtolower(trim($workspaceName));

        $promptFile = match (true) {
            str_contains($normalizedName, 'cultura') => 'itil-mincultura.txt',
            str_contains($normalizedName, 'movilidad') => 'itil-movilidad.txt',
            default => 'itil-default.txt',
        };

        return file_exists(storage_path("app/prompts/{$promptFile}"));
    }

    /**
     * Gets the ITIL system prompt for the given workspace.
     */
    private function getSystemPrompt(string $workspaceName): ?string
    {
        $normalizedName = mb_strtolower(trim($workspaceName));

        // Map workspace names to prompt files
        $promptFile = match (true) {
            str_contains($normalizedName, 'cultura') => 'itil-mincultura.txt',
            str_contains($normalizedName, 'movilidad') => 'itil-movilidad.txt',
            default => null,
        };

        if ($promptFile === null) {
            $promptFile = 'itil-default.txt';
        }

        $path = storage_path("app/prompts/{$promptFile}");

        if (! file_exists($path)) {
            return null;
        }

        $prompt = file_get_contents($path);

        // Inject the shared executor profile if it exists and isn't already in the prompt
        $profilePath = storage_path('app/prompts/perfil-ejecutor.txt');
        if (file_exists($profilePath) && !str_contains($prompt, 'Contexto del ejecutor:')) {
            $profile = file_get_contents($profilePath);
            // Insert after the first line (entity declaration)
            $firstNewline = strpos($prompt, "\n");
            if ($firstNewline !== false) {
                $prompt = substr($prompt, 0, $firstNewline + 1) . "\n" . $profile . "\n" . substr($prompt, $firstNewline + 1);
            }
        }

        return $prompt;
    }
}
