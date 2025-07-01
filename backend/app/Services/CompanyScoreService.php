<?php

namespace App\Services;

use App\Models\DocumentFeedback;
use App\Models\DocumentView;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;

class CompanyScoreService
{
    /**
     * 会社のスコアを計算
     */
    public function calculateCompanyScore($companyId): array
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
     * 複数会社のスコアを一括計算（N+1問題回避版）
     */
    public function calculateCompanyScoreBatch(array $companyIds): array
    {
        if (empty($companyIds)) {
            return [];
        }

        try {
            // Repository層から一括でフィードバック・閲覧データを取得
            $companyRepository = app(\App\Repositories\Contracts\CompanyRepositoryInterface::class);
            $feedbacksData = $companyRepository->getFeedbacksDataBatch($companyIds);
            $viewsData = $companyRepository->getViewsDataBatch($companyIds);

            $results = [];

            foreach ($companyIds as $companyId) {
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

                $results[$companyId] = [
                    'total_score' => round($totalScore, 1),
                    'survey_score' => $surveyScore['average'],
                    'engagement_score' => $engagementScore['total'],
                    'feedback_count' => $surveyScore['count'],
                    'view_count' => $engagementScore['view_count'],
                ];
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('バッチスコア計算エラー: ' . $e->getMessage(), ['company_ids' => $companyIds]);
            return [];
        }
    }

    /**
     * アンケートフィードバックスコアを計算
     */
    public function calculateSurveyScore($companyId): array
    {
        try {
            $feedbacks = DocumentFeedback::select('document_feedback.feedback_metadata')
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->get();

            $scores = [];
            foreach ($feedbacks as $feedback) {
                $metadata = is_string($feedback->feedback_metadata)
                    ? json_decode($feedback->feedback_metadata, true)
                    : $feedback->feedback_metadata;

                if (is_array($metadata) && isset($metadata['survey_results'])) {
                    foreach ($metadata['survey_results'] as $result) {
                        if (isset($result['value']) && is_numeric($result['value'])) {
                            $scores[] = (float)$result['value'];
                        }
                    }
                }
            }

            if (empty($scores)) {
                return ['average' => 0, 'count' => 0];
            }

            return [
                'average' => round(array_sum($scores) / count($scores), 1),
                'count' => count($scores)
            ];

        } catch (\Exception $e) {
            Log::error('アンケートスコア計算エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return ['average' => 0, 'count' => 0];
        }
    }

    /**
     * 一括取得したデータを使用してアンケートスコア計算
     */
    public function calculateSurveyScoreBatch($companyId, $feedbacksData): array
    {
        try {
            $companyFeedbacks = $feedbacksData[$companyId] ?? [];

            $scores = [];
            foreach ($companyFeedbacks as $feedback) {
                $metadata = is_string($feedback['feedback_metadata'])
                    ? json_decode($feedback['feedback_metadata'], true)
                    : $feedback['feedback_metadata'];

                if (is_array($metadata) && isset($metadata['survey_results'])) {
                    foreach ($metadata['survey_results'] as $result) {
                        if (isset($result['value']) && is_numeric($result['value'])) {
                            $scores[] = (float)$result['value'];
                        }
                    }
                }
            }

            if (empty($scores)) {
                return ['average' => 0, 'count' => 0];
            }

            return [
                'average' => round(array_sum($scores) / count($scores), 1),
                'count' => count($scores)
            ];

        } catch (\Exception $e) {
            Log::error('アンケートスコア計算エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return ['average' => 0, 'count' => 0];
        }
    }

    /**
     * PDF閲覧エンゲージメントスコアを計算
     */
    public function calculateEngagementScore($companyId): array
    {
        try {
            $settings = $this->getScoringSettings();

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

            if ($views->isEmpty()) {
                return ['total' => 0, 'view_count' => 0];
            }

            $totalScore = 0;
            $documentViewCounts = [];

            foreach ($views as $view) {
                // 閲覧時間に基づくスコア計算
                $timeScore = $this->calculateTimeBasedScore($view->view_duration, $settings['timeTiers']);
                $totalScore += $timeScore;

                // ドキュメントごとの閲覧数カウント
                $documentViewCounts[$view->document_id] = ($documentViewCounts[$view->document_id] ?? 0) + 1;
            }

            // 継続性ボーナス（複数回閲覧）
            foreach ($documentViewCounts as $count) {
                if ($count >= 3) {
                    $totalScore += $settings['continuityBonus'];
                }
            }

            return [
                'total' => round($totalScore, 1),
                'view_count' => $views->count()
            ];

        } catch (\Exception $e) {
            Log::error('エンゲージメントスコア計算エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return ['total' => 0, 'view_count' => 0];
        }
    }

    /**
     * 一括取得したデータを使用してエンゲージメントスコア計算
     */
    public function calculateEngagementScoreBatch($companyId, $viewsData): array
    {
        try {
            $companyViews = $viewsData[$companyId] ?? [];

            if (empty($companyViews)) {
                return ['total' => 0, 'view_count' => 0];
            }

            $settings = $this->getScoringSettings();
            $totalScore = 0;
            $documentViewCounts = [];

            foreach ($companyViews as $view) {
                // 閲覧時間に基づくスコア計算
                $timeScore = $this->calculateTimeBasedScore($view['view_duration'], $settings['timeTiers']);
                $totalScore += $timeScore;

                // ドキュメントごとの閲覧数カウント
                $documentViewCounts[$view['document_id']] = ($documentViewCounts[$view['document_id']] ?? 0) + 1;
            }

            // 継続性ボーナス（複数回閲覧）
            foreach ($documentViewCounts as $count) {
                if ($count >= 3) {
                    $totalScore += $settings['continuityBonus'];
                }
            }

            return [
                'total' => round($totalScore, 1),
                'view_count' => count($companyViews)
            ];

        } catch (\Exception $e) {
            Log::error('エンゲージメントスコア計算エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return ['total' => 0, 'view_count' => 0];
        }
    }

    /**
     * 閲覧時間に基づくスコア計算
     */
    private function calculateTimeBasedScore($duration, $tiers): float
    {
        foreach ($tiers as $tier) {
            if ($duration >= $tier['min_duration']) {
                return $tier['score'];
            }
        }
        return 0;
    }

    /**
     * スコア計算の設定を取得
     */
    public function getScoringSettings(): array
    {
        try {
            $timeThreshold = AppSetting::where('key', 'scoring.time_threshold')->first();
            $timeTiers = AppSetting::where('key', 'scoring.time_tiers')->first();
            $continuityBonus = AppSetting::where('key', 'scoring.continuity_bonus')->first();

            return [
                'timeThreshold' => $timeThreshold ? (int)$timeThreshold->value : 3000,
                'timeTiers' => $timeTiers ? json_decode($timeTiers->value, true) : [
                    ['min_duration' => 30000, 'score' => 5.0],
                    ['min_duration' => 20000, 'score' => 3.0],
                    ['min_duration' => 10000, 'score' => 2.0],
                    ['min_duration' => 5000, 'score' => 1.0],
                ],
                'continuityBonus' => $continuityBonus ? (float)$continuityBonus->value : 2.0,
            ];
        } catch (\Exception $e) {
            Log::error('設定取得エラー: ' . $e->getMessage());
            return [
                'timeThreshold' => 3000,
                'timeTiers' => [
                    ['min_duration' => 30000, 'score' => 5.0],
                    ['min_duration' => 20000, 'score' => 3.0],
                    ['min_duration' => 10000, 'score' => 2.0],
                    ['min_duration' => 5000, 'score' => 1.0],
                ],
                'continuityBonus' => 2.0,
            ];
        }
    }

    /**
     * デフォルトスコアを取得
     */
    public function getDefaultScores(): array
    {
        return [
            'total_score' => 0,
            'survey_score' => 0,
            'engagement_score' => 0,
            'feedback_count' => 0,
            'view_count' => 0,
        ];
    }
}
