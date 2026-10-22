<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingPlanRequest extends FormRequest
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
        return [
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_date.required' => '期限は今日以降の日付を選択してください。',
            'target_date.date' => '期限は今日以降の日付を選択してください。',
            'target_date.after_or_equal' => '期限は今日以降の日付を選択してください。',
        ];
    }
}
