<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class OpenRouterService
{
    public function chat(string $message): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openrouter.key'),
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-OpenRouter-Title' => config('services.openrouter.app_name'),
        ])
        ->timeout(60)
        ->post(config('services.openrouter.base_url') . '/chat/completions', [
            'model' => config('services.openrouter.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un asistente útil.'
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return data_get($response->json(), 'choices.0.message.content', 'Sin respuesta');
    }
}
