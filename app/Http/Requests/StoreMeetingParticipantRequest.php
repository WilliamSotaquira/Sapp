<?php

namespace App\Http\Requests;

use App\Models\MeetingParticipant;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingParticipantRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|in:organizador,participante,invitado',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del participante es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'role.required' => 'El rol del participante es obligatorio.',
            'role.in' => 'El rol debe ser uno de: organizador, participante, invitado.',
        ];
    }

    /**
     * Add custom validation for email uniqueness within the meeting.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $email = $this->input('email');
            if (empty($email)) {
                return;
            }

            $serviceRequest = $this->route('service_request');
            if (!$serviceRequest) {
                return;
            }

            $meetingDetail = $serviceRequest->meetingDetail;
            if (!$meetingDetail) {
                return;
            }

            $exists = MeetingParticipant::where('meeting_detail_id', $meetingDetail->id)
                ->where('email', $email)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'email',
                    "El correo {$email} ya está registrado como participante de esta reunión."
                );
            }
        });
    }
}
