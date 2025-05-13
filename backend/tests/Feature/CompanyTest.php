<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var \App\Models\User
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // テストユーザー作成＆認証
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_company_index(): void
    {
        Company::factory()->count(3)->create();
        $response = $this->getJson('/api/companies');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'email', 'status',
                        'phone', 'address', 'website', 'description',
                        'industry', 'employee_count', 'created_at', 'updated_at'
                    ]
                ],
                'current_page',
                'last_page',
                'per_page',
                'total'
            ]);
    }

    public function test_company_create(): void
    {
        $payload = [
            'name' => 'テスト株式会社',
            'email' => 'test@example.com',
            'status' => 'active',
        ];
        $response = $this->postJson('/api/companies', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'status',
                    'phone', 'address', 'website', 'description',
                    'industry', 'employee_count', 'created_at', 'updated_at'
                ]
            ])
            ->assertJsonPath('data.name', 'テスト株式会社');
        $this->assertDatabaseHas('companies', ['email' => 'test@example.com']);
    }

    public function test_company_show(): void
    {
        $company = Company::factory()->create();
        $response = $this->getJson('/api/companies/' . $company->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'status',
                    'phone', 'address', 'website', 'description',
                    'industry', 'employee_count', 'created_at', 'updated_at'
                ]
            ])
            ->assertJsonPath('data.id', $company->id);
    }

    public function test_company_update(): void
    {
        $company = Company::factory()->create();
        $payload = [
            'name' => '更新後株式会社',
            'email' => $company->email,
            'status' => 'inactive',
        ];
        $response = $this->putJson('/api/companies/' . $company->id, $payload);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'status',
                    'phone', 'address', 'website', 'description',
                    'industry', 'employee_count', 'created_at', 'updated_at'
                ]
            ])
            ->assertJsonPath('data.name', '更新後株式会社')
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_company_delete(): void
    {
        $company = Company::factory()->create();
        $response = $this->deleteJson('/api/companies/' . $company->id);
        $response->assertStatus(204);
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_company_create_validation_error(): void
    {
        $response = $this->postJson('/api/companies', []);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'name',
                        'email',
                        'status'
                    ]
                ]
            ]);
    }
}
