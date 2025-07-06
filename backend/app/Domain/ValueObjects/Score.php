<?php

namespace App\Domain\ValueObjects;

class Score
{
    private float $value;
    private int $feedbackCount;
    private int $viewCount;
    private ?float $surveyScore;
    private ?float $engagementScore;

    public function __construct(
        float $value,
        int $feedbackCount = 0,
        int $viewCount = 0,
        ?float $surveyScore = null,
        ?float $engagementScore = null
    ) {
        $this->validateScore($value);
        $this->value = $value;
        $this->feedbackCount = $feedbackCount;
        $this->viewCount = $viewCount;
        $this->surveyScore = $surveyScore;
        $this->engagementScore = $engagementScore;
    }

    /**
     * スコアを検証
     */
    private function validateScore(float $value): void
    {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('スコアは0から100の範囲で指定してください');
        }
    }

    /**
     * スコアの値を取得
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * 四捨五入されたスコアを取得
     */
    public function getRoundedValue(int $precision = 1): float
    {
        return round($this->value, $precision);
    }

    /**
     * フィードバック数を取得
     */
    public function getFeedbackCount(): int
    {
        return $this->feedbackCount;
    }

    /**
     * ビュー数を取得
     */
    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    /**
     * アンケートスコアを取得
     */
    public function getSurveyScore(): ?float
    {
        return $this->surveyScore;
    }

    /**
     * エンゲージメントスコアを取得
     */
    public function getEngagementScore(): ?float
    {
        return $this->engagementScore;
    }

    /**
     * スコアの品質を判定
     */
    public function getQualityLevel(): string
    {
        if ($this->value >= 80) {
            return 'excellent';
        } elseif ($this->value >= 60) {
            return 'good';
        } elseif ($this->value >= 40) {
            return 'average';
        } elseif ($this->value >= 20) {
            return 'poor';
        } else {
            return 'very_poor';
        }
    }

    /**
     * 高品質スコアかどうか
     */
    public function isHighQuality(): bool
    {
        return $this->value >= 70;
    }

    /**
     * 十分なデータがあるかどうか
     */
    public function hasSufficientData(): bool
    {
        return $this->feedbackCount >= 1 || $this->viewCount >= 3;
    }

    /**
     * 配列形式で取得
     */
    public function toArray(): array
    {
        return [
            'total_score' => $this->getRoundedValue(),
            'survey_score' => $this->surveyScore,
            'engagement_score' => $this->engagementScore,
            'feedback_count' => $this->feedbackCount,
            'view_count' => $this->viewCount,
            'quality_level' => $this->getQualityLevel(),
            'is_high_quality' => $this->isHighQuality(),
            'has_sufficient_data' => $this->hasSufficientData(),
        ];
    }

    /**
     * 文字列表現
     */
    public function __toString(): string
    {
        return (string)$this->getRoundedValue();
    }

    /**
     * 同等性の比較
     */
    public function equals(Score $other): bool
    {
        return abs($this->value - $other->value) < 0.001;
    }
}
