<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDocumentFeedbackRequest extends FormRequest
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
            'selected_option' => 'required|array',
            'selected_option.id' => 'required|integer',
            'selected_option.label' => 'required|string',
            'selected_option.score' => 'required|integer',
            'feedback_type' => 'sometimes|in:survey,rating,comment,survey_response',
            'content' => 'nullable|string|max:1000',
            'interest_level' => 'sometimes|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'selected_option.required' => '選択肢は必須です。',
            'selected_option.array' => '選択肢は配列形式で入力してください。',
            'selected_option.id.required' => '選択肢IDは必須です。',
            'selected_option.id.integer' => '選択肢IDは整数で入力してください。',
            'selected_option.label.required' => '選択肢ラベルは必須です。',
            'selected_option.label.string' => '選択肢ラベルは文字列で入力してください。',
            'selected_option.score.required' => '選択肢スコアは必須です。',
            'selected_option.score.integer' => '選択肢スコアは整数で入力してください。',
            'feedback_type.in' => 'フィードバックタイプは有効な値を選択してください。',
            'content.string' => 'コンテンツは文字列で入力してください。',
            'content.max' => 'コンテンツは1000文字以内で入力してください。',
            'interest_level.integer' => '興味レベルは整数で入力してください。',
        ];
    }
}
