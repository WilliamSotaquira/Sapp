<?php

namespace App\Http\Requests\Auth;

use App\Services\RecaptchaEnterpriseService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        // Solo requerir reCAPTCHA si está completamente configurado (ambas claves)
        $siteKey = config('services.recaptcha.site_key');
        $secretKey = config('services.recaptcha.secret_key');

        if (!empty($siteKey) && !empty($secretKey)) {
            $rules['g-recaptcha-response'] = ['required'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'g-recaptcha-response.required' => 'Por favor completa la verificación de seguridad (reCAPTCHA).',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Solo validar reCAPTCHA si está completamente configurado
            $siteKey = config('services.recaptcha.site_key');
            $secretKey = config('services.recaptcha.secret_key');

            if (!empty($siteKey) && !empty($secretKey) && $this->has('g-recaptcha-response')) {
                $recaptchaResponse = $this->input('g-recaptcha-response');

                // Usar reCAPTCHA Enterprise si está habilitado
                if (config('services.recaptcha.enterprise.enabled')) {
                    $recaptchaService = new RecaptchaEnterpriseService();
                    $assessment = $recaptchaService->createAssessment($recaptchaResponse, 'login');

                    if (!$assessment || !$assessment['success']) {
                        $validator->errors()->add('g-recaptcha-response',
                            $assessment['message'] ?? 'La verificación de seguridad falló.');
                        return;
                    }

                    // Verificar score (umbral: 0.5)
                    if (!$recaptchaService->isScoreAcceptable($assessment['score'], 0.5)) {
                        $validator->errors()->add('g-recaptcha-response',
                            'La verificación de seguridad indica un comportamiento sospechoso. Score: ' . $assessment['score']);
                        return;
                    }
                } else {
                    // Fallback a reCAPTCHA v2 estándar
                    $recaptchaSecret = config('services.recaptcha.secret_key');

                    if ($recaptchaSecret) {
                        $verifyResponse = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");

                        if ($verifyResponse === false) {
                            $validator->errors()->add('g-recaptcha-response', 'No se pudo verificar el reCAPTCHA. Por favor intenta nuevamente.');
                            return;
                        }

                        $responseData = json_decode($verifyResponse);

                        if (!$responseData || !$responseData->success) {
                            $validator->errors()->add('g-recaptcha-response', 'La verificación de seguridad falló. Por favor intenta nuevamente.');
                        }
                    }
                }
            }
        });
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
