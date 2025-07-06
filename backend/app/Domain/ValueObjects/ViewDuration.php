<?php

namespace App\Domain\ValueObjects;

class ViewDuration
{
    private int $seconds;

    public function __construct(int $seconds)
    {
        $this->validateDuration($seconds);
        $this->seconds = $seconds;
    }

    /**
     * 閲覧時間を検証
     */
    private function validateDuration(int $seconds): void
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('閲覧時間は0以上である必要があります');
        }

        if ($seconds > 86400) { // 24時間以上は異常
            throw new \InvalidArgumentException('閲覧時間が異常に長すぎます');
        }
    }

    /**
     * 秒数を取得
     */
    public function getSeconds(): int
    {
        return $this->seconds;
    }

    /**
     * 分数を取得
     */
    public function getMinutes(): float
    {
        return $this->seconds / 60;
    }

    /**
     * 時間数を取得
     */
    public function getHours(): float
    {
        return $this->seconds / 3600;
    }

    /**
     * 有効な閲覧かどうか（最低閲覧時間をクリア）
     */
    public function isValidView(int $minimumSeconds = 5): bool
    {
        return $this->seconds >= $minimumSeconds;
    }

    /**
     * 短時間閲覧かどうか
     */
    public function isShortView(int $shortThreshold = 30): bool
    {
        return $this->seconds < $shortThreshold;
    }

    /**
     * 中程度の閲覧かどうか
     */
    public function isMediumView(int $shortThreshold = 30, int $longThreshold = 300): bool
    {
        return $this->seconds >= $shortThreshold && $this->seconds < $longThreshold;
    }

    /**
     * 長時間閲覧かどうか
     */
    public function isLongView(int $longThreshold = 300): bool
    {
        return $this->seconds >= $longThreshold;
    }

    /**
     * エンゲージメントレベルを取得
     */
    public function getEngagementLevel(): string
    {
        if ($this->isLongView()) {
            return 'high';
        } elseif ($this->isMediumView()) {
            return 'medium';
        } elseif ($this->isValidView()) {
            return 'low';
        } else {
            return 'invalid';
        }
    }

    /**
     * 時間ベースのスコア計算
     */
    public function calculateTimeScore(array $tiers): float
    {
        $score = 0;

        foreach ($tiers as $tier) {
            if (!isset($tier['min_duration']) || !isset($tier['score'])) {
                continue;
            }

            if ($this->seconds >= $tier['min_duration']) {
                $score = max($score, (float)$tier['score']);
            }
        }

        return $score;
    }

    /**
     * 人間可読な形式で取得
     */
    public function toHumanReadable(): string
    {
        if ($this->seconds < 60) {
            return $this->seconds . '秒';
        } elseif ($this->seconds < 3600) {
            $minutes = floor($this->seconds / 60);
            $remainingSeconds = $this->seconds % 60;
            return $minutes . '分' . ($remainingSeconds > 0 ? $remainingSeconds . '秒' : '');
        } else {
            $hours = floor($this->seconds / 3600);
            $remainingMinutes = floor(($this->seconds % 3600) / 60);
            return $hours . '時間' . ($remainingMinutes > 0 ? $remainingMinutes . '分' : '');
        }
    }

    /**
     * 配列形式で取得
     */
    public function toArray(): array
    {
        return [
            'seconds' => $this->seconds,
            'minutes' => $this->getMinutes(),
            'hours' => $this->getHours(),
            'engagement_level' => $this->getEngagementLevel(),
            'is_valid_view' => $this->isValidView(),
            'human_readable' => $this->toHumanReadable(),
        ];
    }

    /**
     * 文字列表現
     */
    public function __toString(): string
    {
        return $this->toHumanReadable();
    }

    /**
     * 同等性の比較
     */
    public function equals(ViewDuration $other): bool
    {
        return $this->seconds === $other->seconds;
    }
}
