<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SettingsController extends BaseApiController
{
    /**
     * すべての設定を取得
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedResponse();
            }

            // カテゴリ別に設定を取得
            $settings = [
                'account' => $this->getAccountSettings(),
            ];

            // 管理者のみシステム設定を取得可能
            if ($user->isAdmin()) {
                $settings['general'] = $this->getGeneralSettings();
                $settings['survey'] = $this->getSurveySettings();
                $settings['scoring'] = $this->getScoringSettings();
                $settings['followupEmail'] = $this->getFollowupEmailSettings();
            }

            return $this->successResponse($settings, [
                'is_admin' => $user->isAdmin(),
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '設定の取得に失敗しました', '設定取得エラー');
        }
    }

    /**
     * 設定を更新
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedResponse();
            }

            $updated = [];

            // 管理者のみがシステム設定を変更可能
            if ($user->isAdmin()) {
                // 一般設定の更新
                if ($request->has('general')) {
                    $this->updateGeneralSettings($request->input('general'));
                    $updated['general'] = true;
                }

                // アンケート設定の更新
                if ($request->has('survey')) {
                    $this->updateSurveySettings($request->input('survey'));
                    $updated['survey'] = true;
                }

                // スコアリング設定の更新
                if ($request->has('scoring')) {
                    $this->updateScoringSettings($request->input('scoring'));
                    $updated['scoring'] = true;
                }

                // フォローアップメール設定の更新
                if ($request->has('followupEmail')) {
                    $this->updateFollowupEmailSettings($request->input('followupEmail'));
                    $updated['followupEmail'] = true;
                }
            } else {
                // 一般ユーザーがシステム設定を変更しようとした場合はエラー
                if ($request->has('general') || $request->has('survey') || $request->has('scoring') || $request->has('followupEmail')) {
                    return $this->forbiddenResponse('システム設定の変更には管理者権限が必要です');
                }
            }

            // アカウント設定の更新（全ユーザー可能）
            if ($request->has('account')) {
                try {
                    $this->updateAccountSettings($request->input('account'));
                    $updated['account'] = true;
                } catch (\Exception $e) {
                    // パスワード関連のエラーは特別に処理
                    if (strpos($e->getMessage(), 'パスワード') !== false) {
                        return $this->validationErrorResponse([
                            'account.currentPassword' => [$e->getMessage()]
                        ]);
                    }

                    // その他のアカウント設定エラー
                    return $this->badRequestResponse('アカウント設定の更新に失敗しました: ' . $e->getMessage());
                }
            }

            return $this->successResponse([
                'message' => '設定が正常に更新されました',
                'updated_sections' => $updated
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '設定の更新に失敗しました', '設定更新エラー');
        }
    }

    /**
     * 公開設定を取得（認証不要）
     */
    public function publicSettings(): JsonResponse
    {
        try {
            $settings = AppSetting::getPublicSettings();

            return $this->successResponse($settings);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '公開設定の取得に失敗しました', '公開設定取得エラー');
        }
    }

    /**
     * 一般設定を取得
     */
    private function getGeneralSettings()
    {
        return [
            'defaultExpiration' => AppSetting::get('general.default_expiration', 30),
            'trackPageViews' => AppSetting::get('general.track_page_views', true),
            'requireSurvey' => AppSetting::get('general.require_survey', true),
            'showBookingOption' => AppSetting::get('general.show_booking_option', true),
        ];
    }

    /**
     * アンケート設定を取得
     */
    private function getSurveySettings()
    {
        return [
            'title' => AppSetting::get('survey.title', '資料をご覧になる前に'),
            'description' => AppSetting::get('survey.description', '現在の興味度をお聞かせください'),
            'options' => AppSetting::get('survey.options', [
                ['id' => 1, 'label' => '非常に興味がある', 'score' => 100],
                ['id' => 2, 'label' => 'やや興味がある', 'score' => 75],
                ['id' => 3, 'label' => '詳しい情報が必要', 'score' => 50],
                ['id' => 4, 'label' => '興味なし', 'score' => 0],
            ]),
        ];
    }

    /**
     * スコアリング設定を取得
     */
    private function getScoringSettings()
    {
        return [
            'timeThreshold' => AppSetting::get('scoring.time_threshold', 5),
            'completionBonus' => AppSetting::get('scoring.completion_bonus', 20),
            'tiers' => AppSetting::get('scoring.tiers', [
                ['timeThreshold' => 10, 'points' => 1],
                ['timeThreshold' => 30, 'points' => 3],
                ['timeThreshold' => 60, 'points' => 5],
            ]),
        ];
    }

    /**
     * フォローアップメール設定を取得
     */
    private function getFollowupEmailSettings()
    {
        return [
            'enabled' => AppSetting::get('email.followup_enabled', true),
            'delayMinutes' => AppSetting::get('email.followup_delay_minutes', 15),
            'subject' => AppSetting::get('email.followup_subject', '資料のご確認ありがとうございました - さらに詳しくご説明いたします'),
        ];
    }

    /**
     * アカウント設定を取得
     */
    private function getAccountSettings()
    {
        $user = Auth::user();

        // ユーザーが認証されていない場合はダミーデータを返す（一時的な対応）
        if (!$user) {
            return [
                'fullName' => '山田 太郎',
                'email' => 'yamada@example.com',
                'companyName' => '',
                'currentPassword' => '',
                'newPassword' => '',
                'confirmPassword' => '',
            ];
        }

        return [
            'fullName' => $user->name ?? '山田 太郎',
            'email' => $user->email ?? 'yamada@example.com',
            'companyName' => $user->company_name ?? '',
            'currentPassword' => '',
            'newPassword' => '',
            'confirmPassword' => '',
        ];
    }

    /**
     * 一般設定を更新
     */
    private function updateGeneralSettings($general)
    {
        if (isset($general['defaultExpiration'])) {
            AppSetting::set('general.default_expiration', $general['defaultExpiration'], 'リンク有効期限（日数）', 'number');
        }
        if (isset($general['trackPageViews'])) {
            AppSetting::set('general.track_page_views', $general['trackPageViews'], 'ページビュー追跡', 'boolean');
        }
        if (isset($general['requireSurvey'])) {
            AppSetting::set('general.require_survey', $general['requireSurvey'], '閲覧前のアンケート要求', 'boolean');
        }
        if (isset($general['showBookingOption'])) {
            AppSetting::set('general.show_booking_option', $general['showBookingOption'], 'ミーティング予約オプション表示', 'boolean');
        }
    }

    /**
     * アンケート設定を更新
     */
    private function updateSurveySettings($survey)
    {
        if (isset($survey['title'])) {
            AppSetting::set('survey.title', $survey['title'], 'アンケートタイトル', 'string', true);
        }
        if (isset($survey['description'])) {
            AppSetting::set('survey.description', $survey['description'], 'アンケート説明', 'string', true);
        }
        if (isset($survey['options'])) {
            AppSetting::set('survey.options', $survey['options'], 'アンケート選択肢', 'array', true);
        }
    }

    /**
     * スコアリング設定を更新
     */
    private function updateScoringSettings($scoring)
    {
        if (isset($scoring['timeThreshold'])) {
            AppSetting::set('scoring.time_threshold', $scoring['timeThreshold'], '最小閲覧時間（秒）', 'number');
        }
        if (isset($scoring['completionBonus'])) {
            AppSetting::set('scoring.completion_bonus', $scoring['completionBonus'], '完了ボーナスポイント', 'number');
        }
        if (isset($scoring['tiers'])) {
            AppSetting::set('scoring.tiers', $scoring['tiers'], 'スコアリング層', 'array');
        }
    }

    /**
     * フォローアップメール設定を更新
     */
    private function updateFollowupEmailSettings($followupEmail)
    {
        if (isset($followupEmail['enabled'])) {
            AppSetting::set('email.followup_enabled', $followupEmail['enabled'], 'フォローアップメール機能の有効/無効', 'boolean');
        }
        if (isset($followupEmail['delayMinutes'])) {
            AppSetting::set('email.followup_delay_minutes', $followupEmail['delayMinutes'], 'フォローアップメール送信までの遅延時間（分）', 'number');
        }
        if (isset($followupEmail['subject'])) {
            AppSetting::set('email.followup_subject', $followupEmail['subject'], 'フォローアップメールの件名', 'string');
        }
    }

    /**
     * アカウント設定を更新
     */
    private function updateAccountSettings($account)
    {
        $user = Auth::user();

        // デバッグログ
        Log::info('アカウント設定更新開始', [
            'user_authenticated' => !!$user,
            'user_id' => $user ? $user->id : null,
            'account_data' => $account,
            'has_new_password' => isset($account['newPassword']) && !empty(trim($account['newPassword'] ?? '')),
            'password_length' => isset($account['newPassword']) ? strlen(trim($account['newPassword'] ?? '')) : 0
        ]);

        // ユーザーが認証されていない場合は何もしない（一時的な対応）
        if (!$user) {
            Log::warning('ユーザーが認証されていないため、アカウント設定更新をスキップ');
            return;
        }

        // 氏名の更新
        if (isset($account['fullName']) && trim($account['fullName']) !== '') {
            Log::info('氏名を更新', ['old_name' => $user->name, 'new_name' => $account['fullName']]);
            $user->update(['name' => $account['fullName']]);
        }

        // メールアドレスの更新
        if (isset($account['email']) && trim($account['email']) !== '') {
            Log::info('メールアドレスを更新', ['old_email' => $user->email, 'new_email' => $account['email']]);
            $user->update(['email' => $account['email']]);
        }

        // 会社名の更新
        if (isset($account['companyName'])) {
            Log::info('会社名を更新', ['old_company' => $user->company_name, 'new_company' => $account['companyName']]);
            $user->update(['company_name' => $account['companyName']]);
        }

        // パスワードの更新（新しいパスワードが実際に入力されている場合のみ）
        if (isset($account['newPassword']) && trim($account['newPassword']) !== '') {
            Log::info('パスワード更新処理開始');

            // 現在のパスワードの確認
            if (!isset($account['currentPassword']) || trim($account['currentPassword']) === '') {
                Log::error('現在のパスワードが提供されていません');
                throw new \Exception('パスワードを変更するには、現在のパスワードの入力が必要です');
            }

            Log::info('現在のパスワードを確認中');
            if (!Hash::check($account['currentPassword'], $user->password)) {
                Log::error('現在のパスワードが一致しません');
                throw new \Exception('現在のパスワードが正しくありません');
            }
            Log::info('現在のパスワード確認OK');

            // 新しいパスワードをハッシュ化して更新
            $newPasswordHash = Hash::make($account['newPassword']);
            $user->update([
                'password' => $newPasswordHash
            ]);
            Log::info('パスワード更新完了', ['user_id' => $user->id]);
        } else {
            Log::info('パスワード更新はスキップされました（新しいパスワードが入力されていません）');
        }

        Log::info('アカウント設定更新完了');
    }
}
