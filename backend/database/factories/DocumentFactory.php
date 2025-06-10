<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Document;
use App\Models\Company;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => $this->faker->sentence(3),
            'file_path' => 'test-documents/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'file_size' => $this->faker->numberBetween(100000, 10000000), // 100KB - 10MB
            'mime_type' => 'application/pdf',
            'page_count' => $this->faker->numberBetween(1, 50),
            'status' => 'active',
            'sort_order' => $this->faker->numberBetween(1, 100),
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the document is inactive.
     */
    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the document has specific page count.
     */
    public function pages(int $count)
    {
        return $this->state(fn (array $attributes) => [
            'page_count' => $count,
        ]);
    }
} 