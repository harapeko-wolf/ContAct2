<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DocumentFeedback;
use App\Models\Document;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentFeedback>
 */
class DocumentFeedbackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = DocumentFeedback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'feedback_type' => $this->faker->randomElement(['survey', 'comment', 'rating']),
            'content' => $this->faker->optional()->sentence(),
            'feedbacker_ip' => $this->faker->ipv4(),
            'feedbacker_user_agent' => $this->faker->userAgent(),
            'feedback_metadata' => [
                'selected_option' => [
                    'score' => $this->faker->numberBetween(1, 5),
                    'text' => $this->faker->randomElement([
                        '非常に良い',
                        '良い',
                        '普通',
                        '悪い',
                        '非常に悪い'
                    ])
                ],
                'timestamp' => now()->toISOString()
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate survey type feedback.
     */
    public function survey()
    {
        return $this->state(fn (array $attributes) => [
            'feedback_type' => 'survey',
        ]);
    }

    /**
     * Indicate comment type feedback.
     */
    public function comment()
    {
        return $this->state(fn (array $attributes) => [
            'feedback_type' => 'comment',
            'content' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate specific score.
     */
    public function score(int $score)
    {
        return $this->state(fn (array $attributes) => [
            'feedback_metadata' => [
                'selected_option' => [
                    'score' => $score,
                    'text' => $this->getScoreText($score)
                ],
                'timestamp' => now()->toISOString()
            ],
        ]);
    }

    /**
     * Get score text based on score value.
     */
    private function getScoreText(int $score): string
    {
        switch($score) {
            case 5:
                return '非常に良い';
            case 4:
                return '良い';
            case 3:
                return '普通';
            case 2:
                return '悪い';
            case 1:
                return '非常に悪い';
            default:
                return '普通';
        }
    }
} 