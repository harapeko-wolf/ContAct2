<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;
use App\Repositories\CompanyRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CompanyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CompanyRepositoryInterface $repository;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CompanyRepositoryInterface::class);
        $this->user = User::factory()->create();
    }

    public function test_get_paginated_companies_returns_correct_structure()
    {
        // Arrange
        Company::factory()->count(5)->create(['user_id' => $this->user->id]);

        // Act
        $result = $this->repository->getPaginatedCompanies(3, 1);

        // Assert
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(5, $result->total());
    }

    public function test_get_paginated_companies_with_sorting()
    {
        // Arrange
        $company1 = Company::factory()->create(['user_id' => $this->user->id, 'name' => 'Z Company']);
        $company2 = Company::factory()->create(['user_id' => $this->user->id, 'name' => 'A Company']);

        // Act - Sort by name ascending
        $result = $this->repository->getPaginatedCompanies(10, 1, 'name', 'asc');

        // Assert
        $this->assertEquals('A Company', $result->items()[0]->name);
        $this->assertEquals('Z Company', $result->items()[1]->name);
    }

    public function test_create_company_successfully()
    {
        // Arrange
        $companyData = [
            'user_id' => $this->user->id,
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'status' => 'active',
        ];

        // Act
        $company = $this->repository->create($companyData);

        // Assert
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('Test Company', $company->name);
        $this->assertEquals('test@example.com', $company->email);
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'email' => 'test@example.com',
        ]);
    }

    public function test_update_company_successfully()
    {
        // Arrange
        $company = Company::factory()->create(['user_id' => $this->user->id]);
        $updateData = [
            'name' => 'Updated Company',
            'email' => 'updated@example.com',
        ];

        // Act
        $updatedCompany = $this->repository->update($company->id, $updateData);

        // Assert
        $this->assertEquals('Updated Company', $updatedCompany->name);
        $this->assertEquals('updated@example.com', $updatedCompany->email);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company',
        ]);
    }

    public function test_delete_company_successfully()
    {
        // Arrange
        $company = Company::factory()->create(['user_id' => $this->user->id]);

        // Act
        $result = $this->repository->delete($company->id);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_find_by_id_returns_company()
    {
        // Arrange
        $company = Company::factory()->create(['user_id' => $this->user->id]);

        // Act
        $foundCompany = $this->repository->findById($company->id);

        // Assert
        $this->assertInstanceOf(Company::class, $foundCompany);
        $this->assertEquals($company->id, $foundCompany->id);
    }

    public function test_find_by_id_returns_null_when_not_found()
    {
        // Act
        $foundCompany = $this->repository->findById('non-existent-id');

        // Assert
        $this->assertNull($foundCompany);
    }

    public function test_get_companies_by_user_id()
    {
        // Arrange
        $otherUser = User::factory()->create();
        Company::factory()->count(2)->create(['user_id' => $this->user->id]);
        Company::factory()->count(3)->create(['user_id' => $otherUser->id]);

        // Act
        $companies = $this->repository->getCompaniesByUserId($this->user->id);

        // Assert
        $this->assertCount(2, $companies);
        $this->assertTrue($companies->every(fn($company) => $company->user_id === $this->user->id));
    }

    public function test_count_total_companies()
    {
        // Arrange
        Company::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Act
        $count = $this->repository->count();

        // Assert
        $this->assertEquals(3, $count);
    }

    public function test_count_created_between_dates()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(5);
        $endDate = Carbon::now()->subDays(2);

        // Create companies at different times
        Company::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(6) // Before range
        ]);
        Company::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(4) // In range
        ]);
        Company::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(3) // In range
        ]);
        Company::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(1) // After range
        ]);

        // Act
        $count = $this->repository->countCreatedBetween($startDate, $endDate);

        // Assert
        $this->assertEquals(2, $count);
    }

    public function test_get_companies_by_ids()
    {
        // Arrange
        $company1 = Company::factory()->create(['user_id' => $this->user->id]);
        $company2 = Company::factory()->create(['user_id' => $this->user->id]);
        $company3 = Company::factory()->create(['user_id' => $this->user->id]);

        // Act
        $companies = $this->repository->getCompaniesByIds([$company1->id, $company3->id]);

        // Assert
        $this->assertCount(2, $companies);
        $this->assertTrue($companies->contains('id', $company1->id));
        $this->assertTrue($companies->contains('id', $company3->id));
        $this->assertFalse($companies->contains('id', $company2->id));
    }
}
