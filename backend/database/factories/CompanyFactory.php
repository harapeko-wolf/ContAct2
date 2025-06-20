<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'website' => $this->faker->url(),
            'description' => $this->faker->paragraph(),
            'industry' => $this->faker->randomElement(['IT', '製造業', 'サービス業', '小売業']),
            'employee_count' => $this->faker->numberBetween(1, 1000),
            'status' => $this->faker->randomElement(['active', 'considering', 'inactive']),
        ];
    }
}
