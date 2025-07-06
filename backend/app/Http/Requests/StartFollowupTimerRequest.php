<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartFollowupTimerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 認証不要の公開API
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'viewer_ip' => 'required|ip',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'viewer_ip.required' => '閲覧者のIPアドレスは必須です。',
            'viewer_ip.ip' => '有効なIPアドレスを入力してください。',
        ];
    }
}
