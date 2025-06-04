<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentView;
use App\Models\DocumentFeedback;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * ダッシュボード統計データを取得
     */
    public function getStats(Request $request)
    {
        try {
            // 基本統計
            $totalCompanies = Company::count();
            $totalViews = DocumentView::count();

            // 期間設定
            $now = Carbon::now();
            $thisMonthStart = $now->copy()->startOfMonth();
            $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
            $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

            // 今月の数値
            $companiesThisMonth = Company::where('created_at', '>=', $thisMonthStart)->count();
            $viewsThisMonth = DocumentView::where('created_at', '>=', $thisMonthStart)->count();

            // 先月の数値
            $companiesLastMonth = Company::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $viewsLastMonth = DocumentView::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

            // 先月比成長率の計算：(今月 - 先月) / 先月 * 100
            $companyGrowthRate = $companiesLastMonth > 0
                ? round((($companiesThisMonth - $companiesLastMonth) / $companiesLastMonth) * 100, 1)
                : ($companiesThisMonth > 0 ? 100 : 0);

            $viewGrowthRate = $viewsLastMonth > 0
                ? round((($viewsThisMonth - $viewsLastMonth) / $viewsLastMonth) * 100, 1)
                : ($viewsThisMonth > 0 ? 100 : 0);

            // アンケート設定を取得
            $surveySettings = $this->getSurveySettings();

            // 最新のフィードバック（metadata情報を含む）
            $recentFeedback = DocumentFeedback::with(['document.company'])
                ->orderBy('created_at', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($feedback) {
                    return [
                        'company_name' => $feedback->document->company->name ?? '不明',
                        'feedback_type' => $feedback->feedback_type,
                        'content' => $feedback->content,
                        'metadata' => $feedback->feedback_metadata,
                        'created_at' => $feedback->created_at->toISOString(),
                        'company_id' => $feedback->document->company_id ?? null,
                    ];
                });

            // 最新のアクティビティ（閲覧ログ）
            $recentActivity = DocumentView::with(['document.company'])
                ->orderBy('viewed_at', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($view) {
                    return [
                        'company_name' => $view->document->company->name ?? '不明',
                        'document_title' => $view->document->title ?? '不明なドキュメント',
                        'viewed_at' => $view->viewed_at->toISOString(),
                        'company_id' => $view->document->company_id ?? null,
                    ];
                });

            return response()->json([
                'data' => [
                    'stats' => [
                        'total_companies' => $totalCompanies,
                        'company_growth_rate' => $companyGrowthRate,
                        'total_views' => $totalViews,
                        'view_growth_rate' => $viewGrowthRate,
                    ],
                    'recent_feedback' => $recentFeedback,
                    'recent_activity' => $recentActivity,
                    'survey_settings' => $surveySettings,
                    // デバッグ用（削除予定）
                    'debug' => [
                        'companies_this_month' => $companiesThisMonth,
                        'companies_last_month' => $companiesLastMonth,
                        'views_this_month' => $viewsThisMonth,
                        'views_last_month' => $viewsLastMonth,
                    ],
                ],
                'meta' => [
                    'timestamp' => now(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DASHBOARD_STATS_ERROR',
                    'message' => 'ダッシュボード統計の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * アンケート設定を取得
     */
    private function getSurveySettings()
    {
        try {
            // アンケート設定を取得（設定APIと同じキー形式を使用）
            $surveyTitle = AppSetting::where('key', 'survey.title')->first();
            $surveyDescription = AppSetting::where('key', 'survey.description')->first();
            $surveyOptions = AppSetting::where('key', 'survey.options')->first();

            $settings = [
                'title' => $surveyTitle ? $surveyTitle->value : '資料をご覧になる前に',
                'description' => $surveyDescription ? $surveyDescription->value : '現在の興味度をお聞かせください',
                'options' => [],
            ];

            // アンケート選択肢の解析
            if ($surveyOptions && $surveyOptions->value) {
                $optionsData = is_string($surveyOptions->value)
                    ? json_decode($surveyOptions->value, true)
                    : $surveyOptions->value;

                if (is_array($optionsData)) {
                    $settings['options'] = $optionsData;
                }
            }

            // デフォルト選択肢がない場合は追加
            if (empty($settings['options'])) {
                $settings['options'] = [
                    ['id' => 1, 'label' => '非常に興味がある', 'score' => 100],
                    ['id' => 2, 'label' => 'やや興味がある', 'score' => 75],
                    ['id' => 3, 'label' => '詳しい情報が必要', 'score' => 50],
                    ['id' => 4, 'label' => '興味なし', 'score' => 0],
                ];
            }

            return $settings;

        } catch (\Exception $e) {
            // エラー時はデフォルト設定を返す
            return [
                'title' => '資料をご覧になる前に',
                'description' => '現在の興味度をお聞かせください',
                'options' => [
                    ['id' => 1, 'label' => '非常に興味がある', 'score' => 100],
                    ['id' => 2, 'label' => 'やや興味がある', 'score' => 75],
                    ['id' => 3, 'label' => '詳しい情報が必要', 'score' => 50],
                    ['id' => 4, 'label' => '興味なし', 'score' => 0],
                ],
            ];
        }
    }
}
