<?php

namespace Tests\Unit\Repositories;

use App\Models\AppSetting;
use App\Repositories\AppSettingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class AppSettingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AppSettingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AppSettingRepository();
    }

    public function test_create_creates_new_setting()
    {
        // Arrange
        $data = [
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
            'is_public' => true,
            'description' => 'Test setting description',
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(AppSetting::class, $result);
        $this->assertEquals($data['key'], $result->key);
        $this->assertEquals($data['value'], $result->value);
        $this->assertDatabaseHas('app_settings', ['key' => $data['key']]);
    }

    public function test_findByKey_returns_setting_when_exists()
    {
        // Arrange
        $setting = AppSetting::factory()->create(['key' => 'test_key']);

        // Act
        $result = $this->repository->findByKey('test_key');

        // Assert
        $this->assertInstanceOf(AppSetting::class, $result);
        $this->assertEquals($setting->id, $result->id);
    }

    public function test_findByKey_returns_null_when_not_exists()
    {
        // Arrange
        $nonExistentKey = 'non_existent_key';

        // Act
        $result = $this->repository->findByKey($nonExistentKey);

        // Assert
        $this->assertNull($result);
    }

    public function test_findByType_returns_settings_for_type()
    {
        // Arrange
        $stringSettings = AppSetting::factory()->count(3)->create(['type' => 'string']);
        $intSettings = AppSetting::factory()->count(2)->create(['type' => 'integer']);

        // Act
        $result = $this->repository->findByType('string');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals($stringSettings->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_findByType_returns_empty_collection_for_nonexistent_type()
    {
        // Arrange
        AppSetting::factory()->count(3)->create(['type' => 'string']);

        // Act
        $result = $this->repository->findByType('nonexistent');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getPublicSettings_returns_only_public_settings()
    {
        // Arrange
        $publicSettings = AppSetting::factory()->count(3)->create(['is_public' => true]);
        $privateSettings = AppSetting::factory()->count(2)->create(['is_public' => false]);

        // Act
        $result = $this->repository->getPublicSettings();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals($publicSettings->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_getPublicSettings_returns_empty_collection_when_no_public_settings()
    {
        // Arrange
        AppSetting::factory()->count(2)->create(['is_public' => false]);

        // Act
        $result = $this->repository->getPublicSettings();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getPublicSettingsKeyValue_returns_key_value_array()
    {
        // Arrange
        AppSetting::factory()->create(['key' => 'setting1', 'value' => 'value1', 'is_public' => true]);
        AppSetting::factory()->create(['key' => 'setting2', 'value' => 'value2', 'is_public' => true]);
        AppSetting::factory()->create(['key' => 'private_setting', 'value' => 'private_value', 'is_public' => false]);

        // Act
        $result = $this->repository->getPublicSettingsKeyValue();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('value1', $result['setting1']);
        $this->assertEquals('value2', $result['setting2']);
        $this->assertArrayNotHasKey('private_setting', $result);
    }

    public function test_getPublicSettingsKeyValue_returns_empty_array_when_no_public_settings()
    {
        // Arrange
        AppSetting::factory()->count(2)->create(['is_public' => false]);

        // Act
        $result = $this->repository->getPublicSettingsKeyValue();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_getAllSettings_returns_all_settings()
    {
        // Arrange
        $settings = AppSetting::factory()->count(5)->create();

        // Act
        $result = $this->repository->getAllSettings();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
        $this->assertEquals($settings->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_getAllSettings_returns_empty_collection_when_no_settings()
    {
        // Act
        $result = $this->repository->getAllSettings();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getSettingsKeyValue_returns_key_value_array()
    {
        // Arrange
        AppSetting::factory()->create(['key' => 'setting1', 'value' => 'value1']);
        AppSetting::factory()->create(['key' => 'setting2', 'value' => 'value2']);

        // Act
        $result = $this->repository->getSettingsKeyValue();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('value1', $result['setting1']);
        $this->assertEquals('value2', $result['setting2']);
    }

    public function test_getSettingsKeyValue_returns_empty_array_when_no_settings()
    {
        // Act
        $result = $this->repository->getSettingsKeyValue();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_update_updates_existing_setting()
    {
        // Arrange
        $setting = AppSetting::factory()->create(['value' => 'old_value']);
        $updateData = ['value' => 'new_value'];

        // Act
        $result = $this->repository->update($setting->key, $updateData);

        // Assert
        $this->assertInstanceOf(AppSetting::class, $result);
        $this->assertEquals('new_value', $result->value);
        $this->assertDatabaseHas('app_settings', ['key' => $setting->key, 'value' => 'new_value']);
    }

    public function test_update_returns_null_for_nonexistent_setting()
    {
        // Arrange
        $nonExistentKey = 'non_existent_key';
        $updateData = ['value' => 'new_value'];

        // Act
        $result = $this->repository->update($nonExistentKey, $updateData);

        // Assert
        $this->assertNull($result);
    }

    public function test_updateOrCreate_updates_existing_setting()
    {
        // Arrange
        $setting = AppSetting::factory()->create(['key' => 'test_key', 'value' => 'old_value']);
        $data = ['key' => 'test_key', 'value' => 'new_value'];

        // Act
        $result = $this->repository->updateOrCreate($data);

        // Assert
        $this->assertInstanceOf(AppSetting::class, $result);
        $this->assertEquals('new_value', $result->value);
        $this->assertDatabaseHas('app_settings', ['key' => 'test_key', 'value' => 'new_value']);
        $this->assertDatabaseMissing('app_settings', ['key' => 'test_key', 'value' => 'old_value']);
    }

    public function test_updateOrCreate_creates_new_setting()
    {
        // Arrange
        $data = [
            'key' => 'new_key',
            'value' => 'new_value',
            'type' => 'string',
            'is_public' => true,
        ];

        // Act
        $result = $this->repository->updateOrCreate($data);

        // Assert
        $this->assertInstanceOf(AppSetting::class, $result);
        $this->assertEquals('new_value', $result->value);
        $this->assertDatabaseHas('app_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    public function test_delete_deletes_existing_setting()
    {
        // Arrange
        $setting = AppSetting::factory()->create();

        // Act
        $result = $this->repository->delete($setting->key);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('app_settings', ['key' => $setting->key]);
    }

    public function test_delete_returns_false_for_nonexistent_setting()
    {
        // Arrange
        $nonExistentKey = 'non_existent_key';

        // Act
        $result = $this->repository->delete($nonExistentKey);

        // Assert
        $this->assertFalse($result);
    }

    public function test_exists_returns_true_for_existing_setting()
    {
        // Arrange
        $setting = AppSetting::factory()->create(['key' => 'test_key']);

        // Act
        $result = $this->repository->exists('test_key');

        // Assert
        $this->assertTrue($result);
    }

    public function test_exists_returns_false_for_nonexistent_setting()
    {
        // Arrange
        $nonExistentKey = 'non_existent_key';

        // Act
        $result = $this->repository->exists($nonExistentKey);

        // Assert
        $this->assertFalse($result);
    }

    public function test_countTotalSettings_returns_correct_count()
    {
        // Arrange
        AppSetting::factory()->count(7)->create();

        // Act
        $result = $this->repository->countTotalSettings();

        // Assert
        $this->assertEquals(7, $result);
    }

    public function test_countTotalSettings_returns_zero_when_no_settings()
    {
        // Act
        $result = $this->repository->countTotalSettings();

        // Assert
        $this->assertEquals(0, $result);
    }
}
