<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DocumentFeedback;
use App\Models\DocumentView;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * 会社一覧を取得
     */
    public function index(Request $request)
    {
        try {
            // ページネーションパラメータ
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $sortBy = $request->get('sort_by', 'created_at'); // created_at, name, score
            $sortOrder = $request->get('sort_order', 'desc'); // asc, desc

            // 基本的な会社情報を取得
            $query = Company::select('companies.*');

            // ソート処理（basic columns）
            switch ($sortBy) {
                case 'name':
                    $query->orderBy('companies.name', $sortOrder);
                    break;
                case 'feedback_count':
                case 'score':
                    // スコアとフィードバック数は後で追加する
                    break;
                default:
                    $query->orderBy('companies.created_at', $sortOrder);
                    break;
            }

            // ページネーション実行
            $companies = $query->paginate($perPage, ['*'], 'page', $page);

            // 会社IDsを取得
            $companyIds = $companies->getCollection()->pluck('id')->toArray();

            // 全会社のフィードバックとビューを一括取得（N+1問題回避）
            $feedbacksData = $this->getFeedbacksDataBatch($companyIds);
            $viewsData = $this->getViewsDataBatch($companyIds);

            // 各会社の詳細スコア計算（事前取得したデータを使用）
            $data = $companies->getCollection()->map(function ($company) use ($feedbacksData, $viewsData) {
                $scores = $this->calculateCompanyScoreBatch($company->id, $feedbacksData, $viewsData);

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'address' => $company->address,
                    'website' => $company->website,
                    'description' => $company->description,
                    'industry' => $company->industry,
                    'employee_count' => $company->employee_count,
                    'status' => $company->status,
                    'booking_link' => $company->booking_link,
                    'created_at' => $company->created_at,
                    'updated_at' => $company->updated_at,
                    'average_score' => $scores['total_score'],
                    'feedback_count' => $scores['feedback_count'],
                    'engagement_score' => $scores['engagement_score'],
                    'survey_score' => $scores['survey_score'],
                    'booking_status' => $this->getBookingStatus($company),
                    'timerex_stats' => $company->timeRexStats,
                ];
            });

            // スコア・フィードバック数ソート（後処理）
            if ($sortBy === 'score') {
                $data = $data->sortBy('average_score', SORT_REGULAR, $sortOrder === 'desc');
            } elseif ($sortBy === 'feedback_count') {
                $data = $data->sortBy('feedback_count', SORT_REGULAR, $sortOrder === 'desc');
            }

            // Collection を array に変換
            $sortedData = $data->values()->toArray();

            return response()->json([
                'current_page' => $companies->currentPage(),
                'data' => $sortedData,
                'first_page_url' => $companies->url(1),
                'from' => $companies->firstItem(),
                'last_page' => $companies->lastPage(),
                'last_page_url' => $companies->url($companies->lastPage()),
                'next_page_url' => $companies->nextPageUrl(),
                'path' => $companies->path(),
                'per_page' => $companies->perPage(),
                'prev_page_url' => $companies->previousPageUrl(),
                'to' => $companies->lastItem(),
                'total' => $companies->total(),
            ]);

        } catch (\Exception $e) {
            Log::error('会社一覧取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_LIST_ERROR',
                    'message' => '会社一覧の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 全会社のフィードバックデータを一括取得（N+1問題回避）
     */
    private function getFeedbacksDataBatch(array $companyIds)
    {
        if (empty($companyIds)) {
            return [];
        }

        return DocumentFeedback::select('document_feedback.feedback_metadata', 'documents.company_id')
            ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
            ->whereIn('documents.company_id', $companyIds)
            ->get()
            ->groupBy('company_id')
            ->toArray();
    }

    /**
     * 全会社のビューデータを一括取得（N+1問題回避）
     */
    private function getViewsDataBatch(array $companyIds)
    {
        if (empty($companyIds)) {
            return [];
        }

        $settings = $this->getScoringSettings();

        return DocumentView::select([
                'document_views.page_number',
                'document_views.view_duration',
                'document_views.viewed_at',
                'documents.id as document_id',
                'documents.company_id'
            ])
            ->join('documents', 'document_views.document_id', '=', 'documents.id')
            ->whereIn('documents.company_id', $companyIds)
            ->where('document_views.view_duration', '>=', $settings['timeThreshold'])
            ->get()
            ->groupBy('company_id')
            ->toArray();
    }

    /**
     * 一括取得したデータを使用してスコア計算（N+1問題回避版）
     */
    private function calculateCompanyScoreBatch($companyId, $feedbacksData, $viewsData)
    {
        try {
            // 1. アンケートフィードバックスコア計算
            $surveyScore = $this->calculateSurveyScoreBatch($companyId, $feedbacksData);

            // 2. PDF閲覧エンゲージメントスコア計算
            $engagementScore = $this->calculateEngagementScoreBatch($companyId, $viewsData);

            // 3. 総合スコア計算
            $totalScore = 0;

            // アンケートスコアがある場合はそれをベースとして使用
            if ($surveyScore['average'] > 0) {
                $totalScore = $surveyScore['average'];

                // エンゲージメントスコアがある場合は加算（上限100点）
                if ($engagementScore['total'] > 0) {
                    $totalScore = min(100, $totalScore + $engagementScore['total']);
                }
            } else {
                // アンケートスコアがない場合はエンゲージメントスコアのみ
                $totalScore = $engagementScore['total'];
            }

            return [
                'total_score' => round($totalScore, 1),
                'survey_score' => $surveyScore['average'],
                'engagement_score' => $engagementScore['total'],
                'feedback_count' => $surveyScore['count'],
                'view_count' => $engagementScore['view_count'],
            ];

        } catch (\Exception $e) {
            Log::error('スコア計算エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [
                'total_score' => 0,
                'survey_score' => 0,
                'engagement_score' => 0,
                'feedback_count' => 0,
                'view_count' => 0,
            ];
        }
    }

    /**
     * アンケートフィードバックスコア計算（バッチ版）
     */
    private function calculateSurveyScoreBatch($companyId, $feedbacksData)
    {
        $companyFeedbacks = $feedbacksData[$companyId] ?? [];

        $scores = [];
        foreach ($companyFeedbacks as $feedback) {
            $metadata = $feedback['feedback_metadata'] ?? [];
            $score = null;

            if (isset($metadata['selected_option']['score'])) {
                $score = intval($metadata['selected_option']['score']);
            } elseif (isset($metadata['interest_level'])) {
                $score = intval($metadata['interest_level']);
            }

            if ($score !== null) {
                $scores[] = $score;
            }
        }

        return [
            'average' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0,
            'count' => count($scores),
            'total_responses' => count($companyFeedbacks),
        ];
    }

    /**
     * PDF閲覧エンゲージメントスコア計算（バッチ版）
     */
    private function calculateEngagementScoreBatch($companyId, $viewsData)
    {
        $settings = $this->getScoringSettings();
        $companyViews = $viewsData[$companyId] ?? [];

        $totalScore = 0;
        $viewCount = count($companyViews);
        $completedDocuments = [];

        // ドキュメントごとの最大ページ数を事前計算
        $documentMaxPages = [];
        $documentViewedMaxPages = [];

        foreach ($companyViews as $view) {
            $documentId = $view['document_id'];
            
            // 各ドキュメントの最大ページ数と閲覧ページ数を記録
            if (!isset($documentMaxPages[$documentId])) {
                $documentMaxPages[$documentId] = 0;
                $documentViewedMaxPages[$documentId] = 0;
            }
            
            $documentMaxPages[$documentId] = max($documentMaxPages[$documentId], $view['page_number']);
            $documentViewedMaxPages[$documentId] = max($documentViewedMaxPages[$documentId], $view['page_number']);

            // 時間ベースのスコア計算
            $timeScore = $this->calculateTimeBasedScore($view['view_duration'], $settings['tiers']);
            $totalScore += $timeScore;
        }

        // 完了ボーナスの判定
        foreach ($documentMaxPages as $documentId => $maxPage) {
            $viewedMaxPage = $documentViewedMaxPages[$documentId] ?? 0;
            
            if ($maxPage && $viewedMaxPage && $viewedMaxPage >= $maxPage) {
                $totalScore += $settings['completionBonus'];
                $completedDocuments[] = $documentId;
            }
        }

        return [
            'total' => round($totalScore, 1),
            'view_count' => $viewCount,
            'completed_documents' => count($completedDocuments),
        ];
    }

    /**
     * TimeRex予約ステータスを判定
     */
    private function getBookingStatus($company)
    {
        $bookings = $company->timerex_bookings;

        if (!$bookings || empty($bookings['bookings'])) {
            return 'considering'; // 予約検討中（予約なし）
        }

        // 最新の予約を取得
        $latestBooking = collect($bookings['bookings'])
            ->sortByDesc('created_at')
            ->first();

        if (!$latestBooking) {
            return 'considering';
        }

        // ステータスに基づいて判定
        switch ($latestBooking['status']) {
            case 'confirmed':
                return 'confirmed'; // 予約確定
            case 'cancelled':
                return 'cancelled'; // 予約キャンセル
            default:
                return 'considering'; // 予約検討中
        }
    }

    /**
     * 会社の総合エンゲージメントスコアを計算
     */
    private function calculateCompanyScore($companyId)
    {
        try {
            // 1. アンケートフィードバックスコア計算
            $surveyScore = $this->calculateSurveyScore($companyId);

            // 2. PDF閲覧エンゲージメントスコア計算
            $engagementScore = $this->calculateEngagementScore($companyId);

            // 3. 総合スコア計算
            $totalScore = 0;

            // アンケートスコアがある場合はそれをベースとして使用
            if ($surveyScore['average'] > 0) {
                $totalScore = $surveyScore['average'];

                // エンゲージメントスコアがある場合は加算（上限100点）
                if ($engagementScore['total'] > 0) {
                    $totalScore = min(100, $totalScore + $engagementScore['total']);
                }
            } else {
                // アンケートスコアがない場合はエンゲージメントスコアのみ
                $totalScore = $engagementScore['total'];
            }

            return [
                'total_score' => round($totalScore, 1),
                'survey_score' => $surveyScore['average'],
                'engagement_score' => $engagementScore['total'],
                'feedback_count' => $surveyScore['count'],
                'view_count' => $engagementScore['view_count'],
            ];

        } catch (\Exception $e) {
            Log::error('スコア計算エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [
                'total_score' => 0,
                'survey_score' => 0,
                'engagement_score' => 0,
                'feedback_count' => 0,
                'view_count' => 0,
            ];
        }
    }

    /**
     * アンケートフィードバックスコア計算
     */
    private function calculateSurveyScore($companyId)
    {
        $feedbacks = DocumentFeedback::select('document_feedback.feedback_metadata')
            ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
            ->where('documents.company_id', $companyId)
            ->get();

        $scores = [];
        foreach ($feedbacks as $feedback) {
            $metadata = $feedback->feedback_metadata;
            $score = null;

            if (isset($metadata['selected_option']['score'])) {
                $score = intval($metadata['selected_option']['score']);
            } elseif (isset($metadata['interest_level'])) {
                $score = intval($metadata['interest_level']);
            }

            if ($score !== null) {
                $scores[] = $score;
            }
        }

        return [
            'average' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0,
            'count' => count($scores),
            'total_responses' => $feedbacks->count(),
        ];
    }

    /**
     * PDF閲覧エンゲージメントスコア計算（N+1問題修正版）
     */
    private function calculateEngagementScore($companyId)
    {
        // スコアリング設定を取得
        $settings = $this->getScoringSettings();

        // 会社のドキュメント閲覧ログを取得
        $views = DocumentView::select([
                'document_views.page_number',
                'document_views.view_duration',
                'document_views.viewed_at',
                'documents.id as document_id'
            ])
            ->join('documents', 'document_views.document_id', '=', 'documents.id')
            ->where('documents.company_id', $companyId)
            ->where('document_views.view_duration', '>=', $settings['timeThreshold'])
            ->get();

        $totalScore = 0;
        $viewCount = $views->count();
        $completedDocuments = [];

        // ドキュメントごとの最大ページ数を事前に一括取得（N+1問題回避）
        $documentIds = $views->pluck('document_id')->unique()->toArray();
        $documentMaxPages = [];
        $documentViewedMaxPages = [];

        if (!empty($documentIds)) {
            // 全ドキュメントの最大ページ数を一括取得
            $maxPages = DocumentView::selectRaw('document_id, MAX(page_number) as max_page')
                ->whereIn('document_id', $documentIds)
                ->groupBy('document_id')
                ->pluck('max_page', 'document_id')
                ->toArray();

            // 会社別の閲覧最大ページ数を一括取得
            $viewedMaxPages = DocumentView::selectRaw('document_views.document_id, MAX(document_views.page_number) as viewed_max_page')
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->whereIn('document_views.document_id', $documentIds)
                ->groupBy('document_views.document_id')
                ->pluck('viewed_max_page', 'document_id')
                ->toArray();

            $documentMaxPages = $maxPages;
            $documentViewedMaxPages = $viewedMaxPages;
        }

        foreach ($views as $view) {
            // 時間ベースのスコア計算
            $timeScore = $this->calculateTimeBasedScore($view->view_duration, $settings['tiers']);
            $totalScore += $timeScore;

            // 完了ボーナスの判定（ドキュメントの最後のページまで閲覧）
            if (!in_array($view->document_id, $completedDocuments)) {
                $maxPage = $documentMaxPages[$view->document_id] ?? 0;
                $viewedMaxPage = $documentViewedMaxPages[$view->document_id] ?? 0;

                if ($maxPage && $viewedMaxPage && $viewedMaxPage >= $maxPage) {
                    $totalScore += $settings['completionBonus'];
                    $completedDocuments[] = $view->document_id;
                }
            }
        }

        return [
            'total' => round($totalScore, 1),
            'view_count' => $viewCount,
            'completed_documents' => count($completedDocuments),
        ];
    }

    /**
     * 時間ベースのスコア計算
     */
    private function calculateTimeBasedScore($duration, $tiers)
    {
        $score = 0;

        // 時間層を降順でソート
        usort($tiers, function($a, $b) {
            return $b['timeThreshold'] - $a['timeThreshold'];
        });

        foreach ($tiers as $tier) {
            if ($duration >= $tier['timeThreshold']) {
                $score = $tier['points'];
                break;
            }
        }

        return $score;
    }

    /**
     * スコアリング設定を取得
     */
    private function getScoringSettings()
    {
        try {
            return [
                'timeThreshold' => AppSetting::get('scoring.time_threshold', 5),
                'completionBonus' => AppSetting::get('scoring.completion_bonus', 20),
                'tiers' => AppSetting::get('scoring.tiers', [
                    ['timeThreshold' => 10, 'points' => 1],
                    ['timeThreshold' => 30, 'points' => 3],
                    ['timeThreshold' => 60, 'points' => 5],
                ]),
            ];
        } catch (\Exception $e) {
            // デフォルト設定を返す
            return [
                'timeThreshold' => 5,
                'completionBonus' => 20,
                'tiers' => [
                    ['timeThreshold' => 10, 'points' => 1],
                    ['timeThreshold' => 30, 'points' => 3],
                    ['timeThreshold' => 60, 'points' => 5],
                ],
            ];
        }
    }

    /**
     * 会社のスコア詳細を取得
     */
    public function getScoreDetails($id)
    {
        try {
            $company = Company::findOrFail($id);

            // 総合スコア計算詳細を取得
            $scores = $this->calculateCompanyScore($id);

            // アンケートフィードバック詳細を取得
            $feedbackDetails = DocumentFeedback::select([
                'document_feedback.*',
                'documents.title as document_title'
            ])
            ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
            ->where('documents.company_id', $id)
            ->orderBy('document_feedback.created_at', 'desc')
            ->get()
            ->map(function ($feedback) {
                $metadata = $feedback->feedback_metadata;
                $score = null;

                // スコア抽出
                if (isset($metadata['selected_option']['score'])) {
                    $score = intval($metadata['selected_option']['score']);
                } elseif (isset($metadata['interest_level'])) {
                    $score = intval($metadata['interest_level']);
                }

                return [
                    'id' => $feedback->id,
                    'document_title' => $feedback->document_title,
                    'feedback_type' => $feedback->feedback_type,
                    'content' => $feedback->content,
                    'score' => $score,
                    'created_at' => $feedback->created_at,
                ];
            });

            // アンケートスコア統計計算
            $surveyScores = $feedbackDetails->pluck('score')->filter();
            $averageSurveyScore = $surveyScores->count() > 0 ? round($surveyScores->avg(), 1) : 0;

            return response()->json([
                'data' => [
                    'company' => $company,
                    'score_summary' => [
                        'total_score' => $scores['total_score'],
                        'survey_score' => $scores['survey_score'],
                        'engagement_score' => $scores['engagement_score'],
                        'feedback_count' => $scores['feedback_count'],
                        'view_count' => $scores['view_count'],
                    ],
                    'survey_details' => [
                        'average_score' => $averageSurveyScore,
                        'feedback_count' => $surveyScores->count(),
                        'recent_feedback' => $feedbackDetails->take(10),
                    ],
                ],
                'meta' => [
                    'timestamp' => now()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('会社スコア詳細取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_SCORE_ERROR',
                    'message' => '会社スコア詳細の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社詳細を取得
     */
    public function show($id)
    {
        try {
            $company = Company::findOrFail($id);

            // フロントエンドに合わせてdataラッパーなしで返す
            return response()->json($company);
        } catch (\Exception $e) {
            Log::error('会社詳細取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_NOT_FOUND',
                    'message' => '会社が見つかりませんでした',
                    'details' => $e->getMessage()
                ]
            ], 404);
        }
    }

    /**
     * 会社を作成
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:companies,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string|max:1000',
                'industry' => 'nullable|string|max:100',
                'employee_count' => 'nullable|integer',
                'status' => 'nullable|in:active,considering,inactive',
                'booking_link' => 'nullable|url|max:255',
            ]);

            // 認証済みユーザーのIDを追加
            $validated['user_id'] = $request->user()->id;

            // statusのデフォルト値を設定
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }

            $company = Company::create($validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('会社作成エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_CREATE_ERROR',
                    'message' => '会社の作成に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社を更新
     */
    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:companies,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string|max:1000',
                'industry' => 'nullable|string|max:100',
                'employee_count' => 'nullable|integer',
                'status' => 'sometimes|in:active,considering,inactive',
                'booking_link' => 'nullable|url|max:255',
            ]);

            $company->update($validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('会社更新エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_UPDATE_ERROR',
                    'message' => '会社の更新に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社を削除
     */
    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->delete();

            return response()->json([
                'message' => '会社が正常に削除されました',
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('会社削除エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_DELETE_ERROR',
                    'message' => '会社の削除に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
