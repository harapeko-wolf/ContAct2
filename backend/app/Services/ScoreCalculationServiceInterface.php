<?php

namespace App\Services;

interface ScoreCalculationServiceInterface
{
    /**
     * 会社の総合スコアを計算
     *
     * @param string $companyId
     * @return array
     */
    public function calculateCompanyScore(string $companyId): array;

    /**
     * 複数会社のスコアを一括計算（N+1問題対応）
     *
     * @param array $companyIds
     * @return array
     */
    public function calculateBatchCompanyScores(array $companyIds): array;

    /**
     * アンケートスコアを計算
     *
     * @param string $companyId
     * @return array
     */
    public function calculateSurveyScore(string $companyId): array;

    /**
     * エンゲージメントスコアを計算
     *
     * @param string $companyId
     * @return array
     */
    public function calculateEngagementScore(string $companyId): array;

    /**
     * 複数会社のアンケートスコアを一括計算
     *
     * @param array $companyIds
     * @param array $feedbacksData
     * @return array
     */
    public function calculateBatchSurveyScores(array $companyIds, array $feedbacksData): array;

    /**
     * 複数会社のエンゲージメントスコアを一括計算
     *
     * @param array $companyIds
     * @param array $viewsData
     * @return array
     */
    public function calculateBatchEngagementScores(array $companyIds, array $viewsData): array;

    /**
     * 時間ベースのスコアを計算
     *
     * @param int $duration
     * @param array $tiers
     * @return float
     */
    public function calculateTimeBasedScore(int $duration, array $tiers): float;

    /**
     * スコア計算設定を取得
     *
     * @return array
     */
    public function getScoringSettings(): array;

    /**
     * 会社のスコア詳細を取得
     *
     * @param string $companyId
     * @return array
     */
    public function getCompanyScoreDetails(string $companyId): array;

    /**
     * 複数会社のスコアを一括計算（スコアオブジェクト返却）
     *
     * @param array $companyIds
     * @return array
     */
    public function calculateBatchScores(array $companyIds): array;

    /**
     * 会社の詳細スコア情報を計算
     *
     * @param \App\Models\Company $company
     * @return array
     */
    public function calculateDetailedScore(\App\Models\Company $company): array;
}
