<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DocumentView;
use App\Models\Document;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentView>
 */
class DocumentViewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = DocumentView::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'viewer_ip' => $this->faker->ipv4(),
            'page_number' => $this->faker->numberBetween(1, 20),
            'viewed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'viewer_user_agent' => $this->faker->userAgent(),
            'view_duration' => $this->faker->numberBetween(5, 300), // 5秒 - 5分
            'viewer_metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate long view duration.
     */
    public function longView()
    {
        return $this->state(fn (array $attributes) => [
            'view_duration' => $this->faker->numberBetween(180, 600), // 3分 - 10分
        ]);
    }

    /**
     * Indicate specific page number.
     */
    public function page(int $pageNumber)
    {
        return $this->state(fn (array $attributes) => [
            'page_number' => $pageNumber,
        ]);
    }
} 