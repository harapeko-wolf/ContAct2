<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 認証はコントローラーで処理
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'general' => 'sometimes|array',
            'general.defaultExpiration' => 'sometimes|integer|min:0',
            'general.trackPageViews' => 'sometimes|boolean',
            'general.requireSurvey' => 'sometimes|boolean',
            'general.showBookingOption' => 'sometimes|boolean',

            'survey' => 'sometimes|array',
            'survey.title' => 'sometimes|string|max:255',
            'survey.description' => 'sometimes|string|max:500',
            'survey.options' => 'sometimes|array|min:2',
            'survey.options.*.id' => 'required_with:survey.options|integer',
            'survey.options.*.label' => 'required_with:survey.options|string|max:255',

            'scoring' => 'sometimes|array',
            'scoring.timeThreshold' => 'sometimes|integer|min:0',
            'scoring.completionBonus' => 'sometimes|integer|min:0',
            'scoring.tiers' => 'sometimes|array|min:1',
            'scoring.tiers.*.timeThreshold' => 'required_with:scoring.tiers|integer|min:0',
            'scoring.tiers.*.points' => 'required_with:scoring.tiers|integer|min:0',

            'followupEmail' => 'sometimes|array',
            'followupEmail.enabled' => 'sometimes|boolean',
            'followupEmail.delayMinutes' => 'sometimes|integer|min:1|max:1440',
            'followupEmail.subject' => 'sometimes|string|max:255',

            'account' => 'sometimes|array',
            'account.fullName' => 'sometimes|string|max:255',
            'account.email' => 'sometimes|email|max:255',
            'account.companyName' => 'sometimes|string|max:255',
            'account.currentPassword' => 'sometimes|nullable|string',
            'account.newPassword' => 'sometimes|nullable|string|min:8',
            'account.confirmPassword' => 'sometimes|nullable|string|same:account.newPassword',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validatePasswordChange($validator);
        });
    }

    /**
     * パスワード変更時の追加バリデーション
     */
    private function validatePasswordChange($validator)
    {
        $accountData = $this->input('account', []);

        if (!empty($accountData['newPassword'])) {
            // 新しいパスワードが入力されている場合は、現在のパスワードも必須
            if (empty($accountData['currentPassword'])) {
                $validator->errors()->add('account.currentPassword', '新しいパスワードを設定する場合は、現在のパスワードが必要です。');
                return;
            }

            // 現在のパスワードが正しいかチェック
            if (!Hash::check($accountData['currentPassword'], $this->user()->password)) {
                $validator->errors()->add('account.currentPassword', '現在のパスワードが正しくありません。');
            }
        }
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'general.defaultExpiration.integer' => 'デフォルト有効期限は数値で入力してください。',
            'general.defaultExpiration.min' => 'デフォルト有効期限は0以上の値で入力してください。',
            'general.trackPageViews.boolean' => 'ページビュー追跡は真偽値で入力してください。',
            'general.requireSurvey.boolean' => 'アンケート必須設定は真偽値で入力してください。',
            'general.showBookingOption.boolean' => '予約オプション表示設定は真偽値で入力してください。',

            'survey.title.string' => 'アンケートタイトルは文字列で入力してください。',
            'survey.title.max' => 'アンケートタイトルは255文字以内で入力してください。',
            'survey.description.string' => 'アンケート説明は文字列で入力してください。',
            'survey.description.max' => 'アンケート説明は500文字以内で入力してください。',
            'survey.options.array' => 'アンケート選択肢は配列で入力してください。',
            'survey.options.min' => 'アンケート選択肢は最低2つ必要です。',
            'survey.options.*.id.required_with' => '選択肢IDは必須です。',
            'survey.options.*.id.integer' => '選択肢IDは数値で入力してください。',
            'survey.options.*.label.required_with' => '選択肢ラベルは必須です。',
            'survey.options.*.label.string' => '選択肢ラベルは文字列で入力してください。',
            'survey.options.*.label.max' => '選択肢ラベルは255文字以内で入力してください。',

            'scoring.timeThreshold.integer' => '時間閾値は数値で入力してください。',
            'scoring.timeThreshold.min' => '時間閾値は0以上の値で入力してください。',
            'scoring.completionBonus.integer' => '完了ボーナスは数値で入力してください。',
            'scoring.completionBonus.min' => '完了ボーナスは0以上の値で入力してください。',
            'scoring.tiers.array' => 'スコア階層は配列で入力してください。',
            'scoring.tiers.min' => 'スコア階層は最低1つ必要です。',

            'followupEmail.enabled.boolean' => 'フォローアップメール有効設定は真偽値で入力してください。',
            'followupEmail.delayMinutes.integer' => '遅延時間は数値で入力してください。',
            'followupEmail.delayMinutes.min' => '遅延時間は1分以上で入力してください。',
            'followupEmail.delayMinutes.max' => '遅延時間は1440分（24時間）以内で入力してください。',
            'followupEmail.subject.string' => 'メール件名は文字列で入力してください。',
            'followupEmail.subject.max' => 'メール件名は255文字以内で入力してください。',

            'account.fullName.string' => '氏名は文字列で入力してください。',
            'account.fullName.max' => '氏名は255文字以内で入力してください。',
            'account.email.email' => '有効なメールアドレスを入力してください。',
            'account.email.max' => 'メールアドレスは255文字以内で入力してください。',
            'account.companyName.string' => '会社名は文字列で入力してください。',
            'account.companyName.max' => '会社名は255文字以内で入力してください。',
            'account.newPassword.string' => '新しいパスワードは文字列で入力してください。',
            'account.newPassword.min' => '新しいパスワードは8文字以上で入力してください。',
            'account.confirmPassword.same' => 'パスワード確認が一致しません。',
        ];
    }
}
