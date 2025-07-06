<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\DocumentViewRepositoryInterface;
use App\Repositories\DocumentFeedbackRepositoryInterface;
use App\Repositories\AppSettingRepositoryInterface;
use App\Domain\ValueObjects\Score;

class ScoreCalculationService implements ScoreCalculationServiceInterface
{
    private DocumentViewRepositoryInterface $viewRepository;
    private DocumentFeedbackRepositoryInterface $feedbackRepository;
    private AppSettingRepositoryInterface $settingRepository;

    public function __construct(
        DocumentViewRepositoryInterface $viewRepository,
        DocumentFeedbackRepositoryInterface $feedbackRepository,
        AppSettingRepositoryInterface $settingRepository
    ) {
        $this->viewRepository = $viewRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->settingRepository = $settingRepository;
    }

    public function calculateCompanyScore(string $companyId): array
    {
        $settings = $this->getScoringSettings();

        $engagementScore = $this->calculateEngagementScore($companyId);
        $surveyScore = $this->calculateSurveyScore($companyId);

        $totalScore = ($engagementScore['score'] * $settings['engagement_weight']) +
                     ($surveyScore['score'] * $settings['survey_weight']);

        return [
            'total_score' => round($totalScore, 2),
            'engagement_score' => $engagementScore['score'],
            'survey_score' => $surveyScore['score']
        ];
    }

    public function calculateBatchCompanyScores(array $companyIds): array
    {
        $settings = $this->getScoringSettings();

        $viewsData = $this->viewRepository->getBatchViewStats($companyIds);
        $feedbacksData = $this->feedbackRepository->getBatchFeedbackStats($companyIds);

        $engagementScores = $this->calculateBatchEngagementScores($companyIds, $viewsData);
        $surveyScores = $this->calculateBatchSurveyScores($companyIds, $feedbacksData);

        $result = [];
        foreach ($companyIds as $companyId) {
            $engagementScore = $engagementScores[$companyId] ?? ['score' => 0];
            $surveyScore = $surveyScores[$companyId] ?? ['score' => 0];

            $totalScore = ($engagementScore['score'] * $settings['engagement_weight']) +
                         ($surveyScore['score'] * $settings['survey_weight']);

            $result[$companyId] = [
                'total_score' => round($totalScore, 2),
                'engagement_score' => $engagementScore['score'],
                'survey_score' => $surveyScore['score']
            ];
        }

        return $result;
    }

    public function calculateSurveyScore(string $companyId): array
    {
        $stats = $this->feedbackRepository->getFeedbackStatsByCompany($companyId);
        $settings = $this->getScoringSettings();

        if ($stats['total_feedback'] === 0) {
            return [
                'score' => 0,
                'rating_score' => 0,
                'sentiment_score' => 0,
                'volume_bonus' => 0
            ];
        }

        // レーティングスコア（1-5の評価を20倍して0-100スケールに）
        $ratingScore = min(100, ($stats['average_rating'] ?? 0) * $settings['rating_multiplier']);

        // センチメントスコア（ポジティブフィードバックの割合）
        $sentimentScore = ($stats['positive_feedback'] / $stats['total_feedback']) * 100;

        // ボリュームボーナス（フィードバック数に応じたボーナス）
        $volumeBonus = min(20, $stats['total_feedback'] * 0.5);

        $totalScore = min(100, ($ratingScore * 0.5) + ($sentimentScore * 0.3) + ($volumeBonus * 0.2));

        return [
            'score' => round($totalScore, 2),
            'rating_score' => round($ratingScore, 2),
            'sentiment_score' => round($sentimentScore, 2),
            'volume_bonus' => round($volumeBonus, 2)
        ];
    }

    public function calculateEngagementScore(string $companyId): array
    {
        $stats = $this->viewRepository->getViewStatsByCompany($companyId);
        $settings = $this->getScoringSettings();

        if ($stats['total_views'] === 0) {
            return [
                'score' => 0,
                'view_score' => 0,
                'duration_score' => 0,
                'engagement_rate' => 0
            ];
        }

        // ビュースコア（閲覧数に基づく）
        $viewScore = $this->calculateTimeBasedScore($stats['total_views'], $settings['view_tiers']);

        // 滞在時間スコア
        $durationScore = $this->calculateTimeBasedScore($stats['average_duration'], $settings['duration_tiers']);

        // エンゲージメント率（ユニーク視聴者/総閲覧数）
        $engagementRate = ($stats['unique_viewers'] / $stats['total_views']) * 100;

        $totalScore = ($viewScore * 0.4) + ($durationScore * 0.4) + ($engagementRate * 0.2);

        return [
            'score' => round($totalScore, 2),
            'view_score' => round($viewScore, 2),
            'duration_score' => round($durationScore, 2),
            'engagement_rate' => round($engagementRate, 2)
        ];
    }

    public function calculateBatchSurveyScores(array $companyIds, array $feedbacksData): array
    {
        $settings = $this->getScoringSettings();
        $result = [];

        foreach ($companyIds as $companyId) {
            $stats = $feedbacksData[$companyId] ?? ['total_feedback' => 0];

            if ($stats['total_feedback'] === 0) {
                $result[$companyId] = [
                    'score' => 0,
                    'rating_score' => 0,
                    'sentiment_score' => 0,
                    'volume_bonus' => 0
                ];
                continue;
            }

            $ratingScore = min(100, ($stats['average_rating'] ?? 0) * $settings['rating_multiplier']);
            $sentimentScore = ($stats['positive_feedback'] / $stats['total_feedback']) * 100;
            $volumeBonus = min(20, $stats['total_feedback'] * 0.5);

            $totalScore = min(100, ($ratingScore * 0.5) + ($sentimentScore * 0.3) + ($volumeBonus * 0.2));

            $result[$companyId] = [
                'score' => round($totalScore, 2),
                'rating_score' => round($ratingScore, 2),
                'sentiment_score' => round($sentimentScore, 2),
                'volume_bonus' => round($volumeBonus, 2)
            ];
        }

        return $result;
    }

    public function calculateBatchEngagementScores(array $companyIds, array $viewsData): array
    {
        $settings = $this->getScoringSettings();
        $result = [];

        foreach ($companyIds as $companyId) {
            $stats = $viewsData[$companyId] ?? ['total_views' => 0];

            if ($stats['total_views'] === 0) {
                $result[$companyId] = [
                    'score' => 0,
                    'view_score' => 0,
                    'duration_score' => 0,
                    'engagement_rate' => 0
                ];
                continue;
            }

            $viewScore = $this->calculateTimeBasedScore($stats['total_views'], $settings['view_tiers']);
            $durationScore = $this->calculateTimeBasedScore($stats['average_duration'], $settings['duration_tiers']);
            $engagementRate = ($stats['unique_viewers'] / $stats['total_views']) * 100;

            $totalScore = ($viewScore * 0.4) + ($durationScore * 0.4) + ($engagementRate * 0.2);

            $result[$companyId] = [
                'score' => round($totalScore, 2),
                'view_score' => round($viewScore, 2),
                'duration_score' => round($durationScore, 2),
                'engagement_rate' => round($engagementRate, 2)
            ];
        }

        return $result;
    }

    public function calculateTimeBasedScore(int $duration, array $tiers): float
    {
        foreach ($tiers as $tier) {
            $min = $tier['min'];
            $max = $tier['max'];

            if ($max === null) {
                // 無制限の最上位ティア
                if ($duration >= $min) {
                    return (float) $tier['score'];
                }
            } else {
                // 範囲指定のティア
                if ($duration >= $min && $duration < $max) {
                    return (float) $tier['score'];
                }
            }
        }

        // どのティアにも該当しない場合は0
        return 0.0;
    }

    public function getScoringSettings(): array
    {
        $settings = $this->settingRepository->getPublicSettings()->pluck('value', 'key')->toArray();

        return $settings['scoring'] ?? [
            'engagement_weight' => 0.6,
            'survey_weight' => 0.4,
            'view_tiers' => [
                ['min' => 0, 'max' => 50, 'score' => 30],
                ['min' => 50, 'max' => 100, 'score' => 60],
                ['min' => 100, 'max' => null, 'score' => 100]
            ],
            'duration_tiers' => [
                ['min' => 0, 'max' => 60, 'score' => 20],
                ['min' => 60, 'max' => 120, 'score' => 50],
                ['min' => 120, 'max' => 300, 'score' => 80],
                ['min' => 300, 'max' => null, 'score' => 100]
            ],
            'rating_multiplier' => 20
        ];
    }

    public function getCompanyScoreDetails(string $companyId): array
    {
        $engagementScore = $this->calculateEngagementScore($companyId);
        $surveyScore = $this->calculateSurveyScore($companyId);
        $companyScore = $this->calculateCompanyScore($companyId);

        $viewMetrics = $this->viewRepository->getViewStatsByCompany($companyId);
        $feedbackMetrics = $this->feedbackRepository->getFeedbackStatsByCompany($companyId);

        return [
            'total_score' => $companyScore['total_score'],
            'engagement_score' => $engagementScore['score'],
            'survey_score' => $surveyScore['score'],
            'view_metrics' => $viewMetrics,
            'feedback_metrics' => $feedbackMetrics,
            'breakdown' => [
                'engagement' => $engagementScore,
                'survey' => $surveyScore
            ]
        ];
    }

    public function calculateBatchScores(array $companyIds): array
    {
        $batchScores = $this->calculateBatchCompanyScores($companyIds);
        $result = [];

        foreach ($batchScores as $companyId => $scoreData) {
            $result[$companyId] = new Score($scoreData['total_score']);
        }

        return $result;
    }

    public function calculateDetailedScore(Company $company): array
    {
        return $this->getCompanyScoreDetails($company->id);
    }
}
