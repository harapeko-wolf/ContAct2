<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * すべての設定を取得
     */
    public function index()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => '認証が必要です',
                    ]
                ], 401);
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
            }

            return response()->json([
                'data' => $settings,
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'is_admin' => $user->isAdmin(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SETTINGS_FETCH_ERROR',
                    'message' => '設定の取得に失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * 設定を更新
     */
    public function update(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => '認証が必要です',
                    ]
                ], 401);
            }

            $validator = Validator::make($request->all(), [
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

                'account' => 'sometimes|array',
                'account.fullName' => 'sometimes|string|max:255',
                'account.email' => 'sometimes|email|max:255',
                'account.companyName' => 'sometimes|string|max:255',
                'account.currentPassword' => 'sometimes|nullable|string',
                'account.newPassword' => 'sometimes|nullable|string|min:8',
                'account.confirmPassword' => 'sometimes|nullable|string|same:account.newPassword',
            ]);

            // パスワード変更時の追加バリデーション
            $accountData = $request->input('account', []);
            if (!empty($accountData['newPassword'])) {
                // 新しいパスワードが入力されている場合は、現在のパスワードも必須
                $passwordValidator = Validator::make($request->all(), [
                    'account.currentPassword' => 'required|string',
                    'account.newPassword' => 'required|string|min:8',
                    'account.confirmPassword' => 'required|string|same:account.newPassword',
                ]);

                if ($passwordValidator->fails()) {
                    return response()->json([
                        'error' => [
                            'code' => 'PASSWORD_VALIDATION_ERROR',
                            'message' => 'パスワード変更の入力内容に誤りがあります',
                            'details' => $passwordValidator->errors()
                        ]
                    ], 422);
                }
            }

            if ($validator->fails()) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => '入力内容に誤りがあります',
                        'details' => $validator->errors()
                    ]
                ], 422);
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
            } else {
                // 一般ユーザーがシステム設定を変更しようとした場合はエラー
                if ($request->has('general') || $request->has('survey') || $request->has('scoring')) {
                    return response()->json([
                        'error' => [
                            'code' => 'FORBIDDEN',
                            'message' => 'システム設定の変更には管理者権限が必要です',
                        ]
                    ], 403);
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
                        return response()->json([
                            'error' => [
                                'code' => 'PASSWORD_ERROR',
                                'message' => $e->getMessage(),
                                'details' => ['field' => 'account.currentPassword']
                            ]
                        ], 422);
                    }

                    // その他のアカウント設定エラー
                    return response()->json([
                        'error' => [
                            'code' => 'ACCOUNT_UPDATE_ERROR',
                            'message' => 'アカウント設定の更新に失敗しました: ' . $e->getMessage(),
                            'details' => ['error' => $e->getMessage()]
                        ]
                    ], 422);
                }
            }

            return response()->json([
                'data' => [
                    'message' => '設定が正常に更新されました',
                    'updated_sections' => $updated
                ],
                'meta' => [
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SETTINGS_UPDATE_ERROR',
                    'message' => '設定の更新に失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * 公開設定を取得（認証不要）
     */
    public function publicSettings()
    {
        try {
            $settings = AppSetting::getPublicSettings();

            return response()->json([
                'data' => $settings,
                'meta' => [
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'PUBLIC_SETTINGS_ERROR',
                    'message' => '公開設定の取得に失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
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
                ['id' => 1, 'label' => '非常に興味がある'],
                ['id' => 2, 'label' => 'やや興味がある'],
                ['id' => 3, 'label' => '詳しい情報が必要'],
                ['id' => 4, 'label' => '興味なし'],
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
