<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCompanyRequest extends FormRequest
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
        $companyId = $this->route('id') ?? $this->route('company');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:companies,email,' . $companyId . ',id,user_id,' . $this->user()->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer',
            'status' => 'sometimes|in:active,considering,inactive',
            'booking_link' => 'nullable|url|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => '会社名は必須です。',
            'name.string' => '会社名は文字列で入力してください。',
            'name.max' => '会社名は255文字以内で入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'phone.string' => '電話番号は文字列で入力してください。',
            'phone.max' => '電話番号は20文字以内で入力してください。',
            'address.string' => '住所は文字列で入力してください。',
            'address.max' => '住所は500文字以内で入力してください。',
            'website.url' => '有効なURLを入力してください。',
            'website.max' => 'ウェブサイトURLは255文字以内で入力してください。',
            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は1000文字以内で入力してください。',
            'industry.string' => '業界は文字列で入力してください。',
            'industry.max' => '業界は100文字以内で入力してください。',
            'employee_count.integer' => '従業員数は整数で入力してください。',
            'status.in' => 'ステータスは有効な値を選択してください。',
            'booking_link.url' => '有効な予約リンクURLを入力してください。',
            'booking_link.max' => '予約リンクは255文字以内で入力してください。',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $validator->errors()
                ]
            ], 422)
        );
    }
}
