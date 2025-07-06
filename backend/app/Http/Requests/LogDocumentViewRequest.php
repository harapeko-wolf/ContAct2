<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogDocumentViewRequest extends FormRequest
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
            'page_number' => 'required|integer|min:1',
            'view_duration' => 'required|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'page_number.required' => 'ページ番号は必須です。',
            'page_number.integer' => 'ページ番号は整数で入力してください。',
            'page_number.min' => 'ページ番号は1以上で入力してください。',
            'view_duration.required' => '閲覧時間は必須です。',
            'view_duration.integer' => '閲覧時間は整数で入力してください。',
            'view_duration.min' => '閲覧時間は0以上で入力してください。',
        ];
    }
}
