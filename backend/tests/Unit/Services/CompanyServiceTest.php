<?php

namespace Tests\Unit\Services;

use App\Services\CompanyService;
use App\Services\ScoreCalculationServiceInterface;
use App\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use App\Domain\ValueObjects\Score;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyService $service;
    private CompanyRepositoryInterface $companyRepository;
    private ScoreCalculationServiceInterface $scoreCalculationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyRepository = Mockery::mock(CompanyRepositoryInterface::class);
        $this->scoreCalculationService = Mockery::mock(ScoreCalculationServiceInterface::class);

        $this->service = new CompanyService(
            $this->companyRepository,
            $this->scoreCalculationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_getCompaniesPaginatedWithScore_returns_paginated_companies_with_scores()
    {
        // Arrange
        $perPage = 10;
        $page = 1;
        $sortBy = 'created_at';
        $sortOrder = 'desc';

        $companies = collect([
            (object) ['id' => 'company1', 'name' => 'Company 1'],
            (object) ['id' => 'company2', 'name' => 'Company 2']
        ]);

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);
        $mockPaginator->shouldReceive('items')->andReturn($companies);
        $mockPaginator->shouldReceive('total')->andReturn(2);
        $mockPaginator->shouldReceive('perPage')->andReturn($perPage);
        $mockPaginator->shouldReceive('currentPage')->andReturn($page);

        $this->companyRepository->shouldReceive('getPaginatedCompanies')
            ->with($perPage, $page, $sortBy, $sortOrder, null)
            ->once()
            ->andReturn($mockPaginator);

        $this->scoreCalculationService->shouldReceive('calculateBatchScores')
            ->with(['company1', 'company2'])
            ->once()
            ->andReturn([
                'company1' => new Score(85),
                'company2' => new Score(92)
            ]);

        // Act
        $result = $this->service->getCompaniesPaginatedWithScore($perPage, $page, $sortBy, $sortOrder);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
        $this->assertEquals($perPage, $result->perPage());
        $this->assertEquals($page, $result->currentPage());
    }

    public function test_createCompany_creates_and_returns_company()
    {
        // Arrange
        $data = [
            'name' => 'New Company',
            'email' => 'new@company.com',
            'phone' => '123-456-7890',
            'address' => '123 Main St',
            'website' => 'https://newcompany.com'
        ];

        $expectedCompany = new Company($data);
        $expectedCompany->id = 'company-id';

        $this->companyRepository->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($expectedCompany);

        // Act
        $result = $this->service->createCompany($data);

        // Assert
        $this->assertInstanceOf(Company::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['email'], $result->email);
        $this->assertEquals('company-id', $result->id);
    }

    public function test_updateCompany_updates_and_returns_company()
    {
        // Arrange
        $id = 'company-id';
        $data = [
            'name' => 'Updated Company',
            'email' => 'updated@company.com'
        ];

        $updatedCompany = new Company($data);
        $updatedCompany->id = $id;

        $this->companyRepository->shouldReceive('update')
            ->with($id, $data)
            ->once()
            ->andReturn($updatedCompany);

        // Act
        $result = $this->service->updateCompany($id, $data);

        // Assert
        $this->assertInstanceOf(Company::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['email'], $result->email);
        $this->assertEquals($id, $result->id);
    }

    public function test_deleteCompany_deletes_company_and_returns_true()
    {
        // Arrange
        $id = 'company-id';

        $this->companyRepository->shouldReceive('delete')
            ->with($id)
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->deleteCompany($id);

        // Assert
        $this->assertTrue($result);
    }

    public function test_deleteCompany_returns_false_when_company_not_found()
    {
        // Arrange
        $id = 'non-existent-id';

        $this->companyRepository->shouldReceive('delete')
            ->with($id)
            ->once()
            ->andReturn(false);

        // Act
        $result = $this->service->deleteCompany($id);

        // Assert
        $this->assertFalse($result);
    }

    public function test_getCompanyById_returns_company_when_found()
    {
        // Arrange
        $id = 'company-id';
        $company = new Company(['name' => 'Test Company']);
        $company->id = $id;

        $this->companyRepository->shouldReceive('findById')
            ->with($id)
            ->once()
            ->andReturn($company);

        // Act
        $result = $this->service->getCompanyById($id);

        // Assert
        $this->assertInstanceOf(Company::class, $result);
        $this->assertEquals($id, $result->id);
        $this->assertEquals('Test Company', $result->name);
    }

    public function test_getCompanyById_returns_null_when_not_found()
    {
        // Arrange
        $id = 'non-existent-id';

        $this->companyRepository->shouldReceive('findById')
            ->with($id)
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->service->getCompanyById($id);

        // Assert
        $this->assertNull($result);
    }

    public function test_getCompanyScoreDetails_returns_score_details()
    {
        // Arrange
        $id = 'company-id';
        $company = new Company(['name' => 'Test Company']);
        $company->id = $id;

        $expectedScoreDetails = [
            'total_score' => 85,
            'engagement_score' => 80,
            'survey_score' => 90,
            'view_metrics' => [
                'total_views' => 150,
                'unique_viewers' => 75,
                'average_duration' => 120
            ],
            'feedback_metrics' => [
                'total_feedback' => 25,
                'average_rating' => 4.2,
                'positive_feedback' => 22
            ]
        ];

        $this->companyRepository->shouldReceive('findById')
            ->with($id)
            ->once()
            ->andReturn($company);

        $this->scoreCalculationService->shouldReceive('calculateDetailedScore')
            ->with($company)
            ->once()
            ->andReturn($expectedScoreDetails);

        // Act
        $result = $this->service->getCompanyScoreDetails($id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(85, $result['total_score']);
        $this->assertEquals(80, $result['engagement_score']);
        $this->assertEquals(90, $result['survey_score']);
        $this->assertArrayHasKey('view_metrics', $result);
        $this->assertArrayHasKey('feedback_metrics', $result);
    }

    public function test_getCompanyScoreDetails_throws_exception_when_company_not_found()
    {
        // Arrange
        $id = 'non-existent-id';

        $this->companyRepository->shouldReceive('findById')
            ->with($id)
            ->once()
            ->andReturn(null);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Company not found');

        $this->service->getCompanyScoreDetails($id);
    }

    public function test_getBookingStatus_returns_booking_status()
    {
        // Arrange
        $company = new Company(['name' => 'Test Company']);
        $company->id = 'company-id';

        $expectedStatus = [
            'has_booking' => true,
            'booking_date' => '2024-03-15',
            'booking_time' => '14:00',
            'status' => 'confirmed',
            'meeting_url' => 'https://example.com/meeting'
        ];

        $this->companyRepository->shouldReceive('getBookingStatus')
            ->with($company)
            ->once()
            ->andReturn($expectedStatus);

        // Act
        $result = $this->service->getBookingStatus($company);

        // Assert
        $this->assertIsArray($result);
        $this->assertTrue($result['has_booking']);
        $this->assertEquals('2024-03-15', $result['booking_date']);
        $this->assertEquals('14:00', $result['booking_time']);
        $this->assertEquals('confirmed', $result['status']);
        $this->assertEquals('https://example.com/meeting', $result['meeting_url']);
    }

    public function test_getCompaniesByUserId_returns_user_companies()
    {
        // Arrange
        $userId = 'user-id';
        $company1 = new Company(['name' => 'Company 1']);
        $company2 = new Company(['name' => 'Company 2']);
        $companies = new Collection([$company1, $company2]);

        $this->companyRepository->shouldReceive('findByUserId')
            ->with($userId)
            ->once()
            ->andReturn($companies);

        // Act
        $result = $this->service->getCompaniesByUserId($userId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('Company 1', $result->first()->name);
        $this->assertEquals('Company 2', $result->last()->name);
    }

    public function test_getCompaniesByUserId_returns_empty_collection_when_no_companies()
    {
        // Arrange
        $userId = 'user-id';
        $companies = new Collection([]);

        $this->companyRepository->shouldReceive('findByUserId')
            ->with($userId)
            ->once()
            ->andReturn($companies);

        // Act
        $result = $this->service->getCompaniesByUserId($userId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getCompanyStats_returns_company_statistics()
    {
        // Arrange
        $expectedStats = [
            'total_companies' => 150,
            'active_companies' => 120,
            'inactive_companies' => 30,
            'companies_with_documents' => 100,
            'companies_with_bookings' => 80,
            'average_score' => 82.5,
            'top_performing_companies' => [
                ['id' => 'company1', 'name' => 'Company 1', 'score' => 95],
                ['id' => 'company2', 'name' => 'Company 2', 'score' => 92]
            ]
        ];

        $this->companyRepository->shouldReceive('getCompanyStats')
            ->once()
            ->andReturn($expectedStats);

        // Act
        $result = $this->service->getCompanyStats();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(150, $result['total_companies']);
        $this->assertEquals(120, $result['active_companies']);
        $this->assertEquals(30, $result['inactive_companies']);
        $this->assertEquals(100, $result['companies_with_documents']);
        $this->assertEquals(80, $result['companies_with_bookings']);
        $this->assertEquals(82.5, $result['average_score']);
        $this->assertArrayHasKey('top_performing_companies', $result);
        $this->assertCount(2, $result['top_performing_companies']);
    }

    public function test_getCompanyStats_returns_empty_stats_when_no_companies()
    {
        // Arrange
        $expectedStats = [
            'total_companies' => 0,
            'active_companies' => 0,
            'inactive_companies' => 0,
            'companies_with_documents' => 0,
            'companies_with_bookings' => 0,
            'average_score' => 0,
            'top_performing_companies' => []
        ];

        $this->companyRepository->shouldReceive('getCompanyStats')
            ->once()
            ->andReturn($expectedStats);

        // Act
        $result = $this->service->getCompanyStats();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_companies']);
        $this->assertEquals(0, $result['active_companies']);
        $this->assertEquals(0, $result['inactive_companies']);
        $this->assertEquals(0, $result['companies_with_documents']);
        $this->assertEquals(0, $result['companies_with_bookings']);
        $this->assertEquals(0, $result['average_score']);
        $this->assertEmpty($result['top_performing_companies']);
    }
}
