<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StopFollowupTimerRequest extends FormRequest
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
            'reason' => 'required|string|in:user_dismissed,timerex_booked,user_cancelled,timer_reset',
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
            'reason.required' => '停止理由は必須です。',
            'reason.string' => '停止理由は文字列で入力してください。',
            'reason.in' => '停止理由は有効な値を選択してください。',
        ];
    }
}
