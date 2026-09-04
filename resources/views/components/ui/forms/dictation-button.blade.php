@props([
    // ID del <textarea> o <input> donde se escribirá el texto dictado.
    'target',
    // Idioma de reconocimiento (por defecto español de Colombia).
    'lang' => 'es-CO',
    // Etiqueta accesible del botón.
    'label' => 'Dictar por voz',
])

@php
    // ID único para este control de dictado (permite varios en la misma página).
    $dictationId = 'dictation-' . \Illuminate\Support\Str::random(8);
@endphp

<button type="button"
    data-dictation-button
    data-dictation-target="{{ $target }}"
    data-dictation-lang="{{ $lang }}"
    id="{{ $dictationId }}"
    {{ $attributes->merge(['class' => 'hidden inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-200 text-gray-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-amber-400 transition-colors']) }}
    aria-label="{{ $label }}"
    title="{{ $label }}">
    <i class="fas fa-microphone text-[11px]" data-dictation-icon aria-hidden="true"></i>
    <span data-dictation-text>Dictar</span>
</button>

@once
    @push('scripts')
        <script>
            (function () {
                // Web Speech API: disponible en Chrome/Edge (y navegadores basados en Chromium).
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const supported = !!SpeechRecognition;

                // Estado global: solo un dictado activo a la vez.
                let activeButton = null;
                let recognition = null;

                function stopActive() {
                    if (recognition) {
                        try { recognition.stop(); } catch (e) { /* noop */ }
                    }
                }

                function setButtonState(button, listening) {
                    const icon = button.querySelector('[data-dictation-icon]');
                    const text = button.querySelector('[data-dictation-text]');

                    if (listening) {
                        button.classList.add('bg-red-50', 'border-red-300', 'text-red-600');
                        if (icon) icon.classList.add('fa-beat');
                        if (text) text.textContent = 'Escuchando…';
                    } else {
                        button.classList.remove('bg-red-50', 'border-red-300', 'text-red-600');
                        if (icon) icon.classList.remove('fa-beat');
                        if (text) text.textContent = 'Dictar';
                    }
                }

                function appendText(field, chunk) {
                    if (!chunk) return;
                    const existing = field.value || '';
                    const needsSpace = existing.length > 0 && !/\s$/.test(existing);
                    field.value = existing + (needsSpace ? ' ' : '') + chunk;
                    // Notificar a cualquier listener (validaciones, contadores, etc.)
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }

                function startDictation(button) {
                    const targetId = button.getAttribute('data-dictation-target');
                    const field = document.getElementById(targetId);
                    if (!field) {
                        console.warn('Dictado: no se encontró el campo destino', targetId);
                        return;
                    }

                    recognition = new SpeechRecognition();
                    recognition.lang = button.getAttribute('data-dictation-lang') || 'es-CO';
                    recognition.interimResults = false;
                    recognition.continuous = true;

                    activeButton = button;
                    setButtonState(button, true);

                    recognition.onresult = function (event) {
                        let finalChunk = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            if (event.results[i].isFinal) {
                                finalChunk += event.results[i][0].transcript;
                            }
                        }
                        appendText(field, finalChunk.trim());
                    };

                    recognition.onerror = function (event) {
                        if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                            alert('No se pudo acceder al micrófono. Revisa los permisos del navegador.');
                        }
                    };

                    recognition.onend = function () {
                        if (activeButton) setButtonState(activeButton, false);
                        activeButton = null;
                        recognition = null;
                    };

                    try {
                        recognition.start();
                    } catch (e) {
                        setButtonState(button, false);
                        activeButton = null;
                        recognition = null;
                    }
                }

                document.addEventListener('click', function (e) {
                    const button = e.target.closest('[data-dictation-button]');
                    if (!button) return;

                    e.preventDefault();

                    // Si este botón ya está escuchando, detener.
                    if (activeButton === button) {
                        stopActive();
                        return;
                    }

                    // Si hay otro dictado activo, detenerlo antes de empezar.
                    if (activeButton) {
                        stopActive();
                    }

                    startDictation(button);
                });

                // Mostrar los botones de dictado solo si el navegador lo soporta.
                function revealSupportedButtons() {
                    if (!supported) return;
                    document.querySelectorAll('[data-dictation-button]').forEach(function (btn) {
                        btn.classList.remove('hidden');
                    });
                }

                if (document.readyState !== 'loading') {
                    revealSupportedButtons();
                } else {
                    document.addEventListener('DOMContentLoaded', revealSupportedButtons);
                }

                // Reexponer por si se inyectan botones dinámicamente más tarde.
                window.__revealDictationButtons = revealSupportedButtons;
            })();
        </script>
    @endpush
@endonce
