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
        $rules = [
            'evidence_type' => 'nullable|string|in:PASO_A_PASO,ARCHIVO,COMENTARIO,ENLACE,ACTA',
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,txt,xlsx,xls',
        ];

        // When evidence_type is ACTA, restrict allowed MIME types
        if ($this->input('evidence_type') === 'ACTA') {
            $rules['files.*'] = 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Debe seleccionar al menos un archivo.',
            'files.array' => 'El formato de archivos no es válido.',
            'files.min' => 'Debe seleccionar al menos un archivo.',
            'files.max' => 'No puede subir más de 10 archivos a la vez.',
            'files.*.required' => 'Cada archivo es obligatorio.',
            'files.*.file' => 'Cada elemento debe ser un archivo válido.',
            'files.*.max' => 'Cada archivo no debe exceder los 10MB.',
            'files.*.mimes' => 'Solo se permiten archivos: jpg, jpeg, png, pdf, doc, docx, txt, xlsx, xls.',
            'files.*.mimetypes' => 'El tipo de archivo para actas debe ser PDF, DOCX, JPG, JPEG o PNG.',
            'evidence_type.in' => 'El tipo de evidencia seleccionado no es válido.',
        ];
    }
}
