<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SummaryReportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'required|in:summary,detailed,performance',
        ];
    }

    public function messages()
    {
        return [
            'start_date.required' => 'La fecha de inicio es obligatoria',
            'end_date.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio',
        ];
    }
}
