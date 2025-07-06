<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AppSetting;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppSetting>
 */
class AppSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = AppSetting::class;

        /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate unique key with timestamp and random string to avoid duplicates
        $baseKey = $this->faker->randomElement([
            'site_name',
            'site_description',
            'contact_email',
            'max_file_size',
            'allowed_file_types',
            'enable_notifications',
            'enable_analytics',
            'maintenance_mode'
        ]);

        $uniqueKey = $baseKey . '_' . time() . '_' . $this->faker->randomNumber(5);

        return [
            'key' => $uniqueKey,
            'value' => $this->getValueForKey($baseKey),
            'is_public' => $this->faker->boolean(30), // 30% chance of being public
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the setting is public.
     */
    public function public()
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the setting is private.
     */
    public function private()
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Set a specific key for the setting.
     */
    public function key(string $key)
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $this->getValueForKey($key),
        ]);
    }

    /**
     * Get appropriate value for a given key.
     */
    private function getValueForKey(string $key): string
    {
        switch ($key) {
            case 'site_name':
                return $this->faker->company();
            case 'site_description':
                return $this->faker->sentence();
            case 'contact_email':
                return $this->faker->safeEmail();
            case 'max_file_size':
                return (string) $this->faker->numberBetween(1, 100);
            case 'allowed_file_types':
                return 'pdf,doc,docx,xls,xlsx';
            case 'enable_notifications':
                return $this->faker->boolean() ? 'true' : 'false';
            case 'enable_analytics':
                return $this->faker->boolean() ? 'true' : 'false';
            case 'maintenance_mode':
                return $this->faker->boolean(10) ? 'true' : 'false'; // 10% chance
            default:
                return $this->faker->word();
        }
    }
}
