<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1|max:5',
            'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,txt,xlsx,xls',
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Debe seleccionar al menos un archivo.',
            'files.array' => 'El formato de archivos no es válido.',
            'files.min' => 'Debe seleccionar al menos un archivo.',
            'files.max' => 'No puede subir más de 5 archivos a la vez.',
            'files.*.required' => 'Cada archivo es obligatorio.',
            'files.*.file' => 'Cada elemento debe ser un archivo válido.',
            'files.*.max' => 'Cada archivo no debe exceder los 10MB.',
            'files.*.mimes' => 'Solo se permiten archivos: jpg, jpeg, png, pdf, doc, docx, txt, xlsx, xls.',
        ];
    }
}
