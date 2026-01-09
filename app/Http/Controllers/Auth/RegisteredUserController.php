<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $siteKey = config('services.recaptcha.site_key');
        $secretKey = config('services.recaptcha.secret_key');
        $shouldVerifyRecaptcha = !app()->environment('testing') && !empty($siteKey) && !empty($secretKey);

        if ($shouldVerifyRecaptcha) {
            $rules['g-recaptcha-response'] = ['required'];
        }

        $request->validate($rules, [
            'g-recaptcha-response.required' => 'Por favor completa la verificación de seguridad (reCAPTCHA).',
        ]);

        if ($shouldVerifyRecaptcha) {
            // Verificar reCAPTCHA v2 estándar
            $recaptchaResponse = $request->input('g-recaptcha-response');
            $recaptchaSecret = $secretKey;

            $verifyResponse = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");

            if ($verifyResponse === false) {
                return back()
                    ->withInput()
                    ->withErrors(['g-recaptcha-response' => 'No se pudo verificar el reCAPTCHA. Por favor intenta nuevamente.']);
            }

            $responseData = json_decode($verifyResponse);

            if (!$responseData || !$responseData->success) {
                return back()
                    ->withInput()
                    ->withErrors(['g-recaptcha-response' => 'La verificación de seguridad falló. Por favor intenta nuevamente.']);
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
