<?php

namespace Tests\Unit\Services;

use App\Services\DashboardService;
use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\DocumentRepositoryInterface;
use App\Repositories\DocumentViewRepositoryInterface;
use App\Repositories\DocumentFeedbackRepositoryInterface;
use App\Repositories\AppSettingRepositoryInterface;
use App\Models\DocumentFeedback;
use App\Models\DocumentView;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private CompanyRepositoryInterface $companyRepository;
    private DocumentRepositoryInterface $documentRepository;
    private DocumentViewRepositoryInterface $viewRepository;
    private DocumentFeedbackRepositoryInterface $feedbackRepository;
    private AppSettingRepositoryInterface $settingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyRepository = Mockery::mock(CompanyRepositoryInterface::class);
        $this->documentRepository = Mockery::mock(DocumentRepositoryInterface::class);
        $this->viewRepository = Mockery::mock(DocumentViewRepositoryInterface::class);
        $this->feedbackRepository = Mockery::mock(DocumentFeedbackRepositoryInterface::class);
        $this->settingRepository = Mockery::mock(AppSettingRepositoryInterface::class);

        $this->service = new DashboardService(
            $this->companyRepository,
            $this->documentRepository,
            $this->viewRepository,
            $this->feedbackRepository,
            $this->settingRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_getStats_returns_comprehensive_dashboard_statistics()
    {
        // Arrange
        $this->companyRepository->shouldReceive('count')->once()->andReturn(25);
        $this->documentRepository->shouldReceive('count')->once()->andReturn(150);
        $this->viewRepository->shouldReceive('countTotalViews')->once()->andReturn(1200);
        $this->feedbackRepository->shouldReceive('countTotalFeedback')->once()->andReturn(300);

        // Mock period-based counts for growth rate calculation
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $this->companyRepository->shouldReceive('countCreatedBetween')
            ->with(Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->once()
            ->andReturn(5); // This month

        $this->companyRepository->shouldReceive('countCreatedBetween')
            ->with(Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->once()
            ->andReturn(3); // Last month

        $this->viewRepository->shouldReceive('countViewsBetween')
            ->with(Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->once()
            ->andReturn(300); // This month

        $this->viewRepository->shouldReceive('countViewsBetween')
            ->with(Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->once()
            ->andReturn(200); // Last month

        $this->feedbackRepository->shouldReceive('countFeedbackBetween')
            ->with(Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->once()
            ->andReturn(80); // This month

        $this->feedbackRepository->shouldReceive('countFeedbackBetween')
            ->with(Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->once()
            ->andReturn(60); // Last month

        // Act
        $result = $this->service->getStats();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('companies', $result);
        $this->assertArrayHasKey('documents', $result);
        $this->assertArrayHasKey('views', $result);
        $this->assertArrayHasKey('feedback', $result);
        $this->assertArrayHasKey('growth_rates', $result);

        $this->assertEquals(25, $result['companies']['total']);
        $this->assertEquals(150, $result['documents']['total']);
        $this->assertEquals(1200, $result['views']['total']);
        $this->assertEquals(300, $result['feedback']['total']);

        $this->assertArrayHasKey('companies', $result['growth_rates']);
        $this->assertArrayHasKey('views', $result['growth_rates']);
        $this->assertArrayHasKey('feedback', $result['growth_rates']);
    }

    public function test_getRecentFeedback_returns_limited_recent_feedback()
    {
        // Arrange
        $limit = 5;
        $mockFeedback = new Collection([
            new DocumentFeedback(['feedback_type' => 'positive', 'content' => 'Great!']),
            new DocumentFeedback(['feedback_type' => 'rating', 'content' => '5 stars']),
        ]);

        $this->feedbackRepository->shouldReceive('getRecentFeedback')
            ->with($limit)
            ->once()
            ->andReturn($mockFeedback);

        // Act
        $result = $this->service->getRecentFeedback($limit);

        // Assert
        $this->assertSame($mockFeedback, $result);
    }

    public function test_getRecentFeedback_uses_default_limit()
    {
        // Arrange
        $mockFeedback = new Collection([
            new DocumentFeedback(['feedback_type' => 'positive']),
        ]);

        $this->feedbackRepository->shouldReceive('getRecentFeedback')
            ->with(4) // Default limit
            ->once()
            ->andReturn($mockFeedback);

        // Act
        $result = $this->service->getRecentFeedback();

        // Assert
        $this->assertSame($mockFeedback, $result);
    }

    public function test_getRecentActivity_returns_recent_document_views()
    {
        // Arrange
        $limit = 6;
        $mockViews = new Collection([
            new DocumentView(['page_number' => 1, 'viewed_at' => Carbon::now()]),
            new DocumentView(['page_number' => 2, 'viewed_at' => Carbon::now()->subMinutes(5)]),
        ]);

        $this->viewRepository->shouldReceive('getRecentViews')
            ->with($limit)
            ->once()
            ->andReturn($mockViews);

        // Act
        $result = $this->service->getRecentActivity($limit);

        // Assert
        $this->assertSame($mockViews, $result);
    }

    public function test_getRecentActivity_uses_default_limit()
    {
        // Arrange
        $mockViews = new Collection([
            new DocumentView(['page_number' => 1]),
        ]);

        $this->viewRepository->shouldReceive('getRecentViews')
            ->with(4) // Default limit
            ->once()
            ->andReturn($mockViews);

        // Act
        $result = $this->service->getRecentActivity();

        // Assert
        $this->assertSame($mockViews, $result);
    }

    public function test_getSurveySettings_returns_survey_configuration()
    {
        // Arrange
        $expectedSettings = [
            'survey_enabled' => true,
            'rating_scale' => 5,
            'feedback_types' => ['positive', 'negative', 'rating'],
            'auto_survey_trigger' => true
        ];

        // Create a mock collection with AppSetting-like objects
        $mockSetting = (object) ['key' => 'survey', 'value' => $expectedSettings];
        $mockCollection = new Collection([$mockSetting]);

        $this->settingRepository->shouldReceive('getPublicSettings')
            ->once()
            ->andReturn($mockCollection);

        // Act
        $result = $this->service->getSurveySettings();

        // Assert
        $this->assertSame($expectedSettings, $result);
    }

    public function test_getSurveySettings_returns_default_when_no_settings()
    {
        // Arrange
        $mockCollection = new Collection([]);

        $this->settingRepository->shouldReceive('getPublicSettings')
            ->once()
            ->andReturn($mockCollection);

        // Act
        $result = $this->service->getSurveySettings();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('survey_enabled', $result);
        $this->assertArrayHasKey('rating_scale', $result);
        $this->assertArrayHasKey('feedback_types', $result);
        $this->assertEquals(true, $result['survey_enabled']);
        $this->assertEquals(5, $result['rating_scale']);
    }

    public function test_calculateGrowthRate_calculates_positive_growth()
    {
        // Arrange
        $currentCount = 120;
        $previousCount = 100;

        // Act
        $result = $this->service->calculateGrowthRate($currentCount, $previousCount);

        // Assert
        $this->assertEquals(20.0, $result);
    }

    public function test_calculateGrowthRate_calculates_negative_growth()
    {
        // Arrange
        $currentCount = 80;
        $previousCount = 100;

        // Act
        $result = $this->service->calculateGrowthRate($currentCount, $previousCount);

        // Assert
        $this->assertEquals(-20.0, $result);
    }

    public function test_calculateGrowthRate_returns_zero_when_previous_is_zero()
    {
        // Arrange
        $currentCount = 50;
        $previousCount = 0;

        // Act
        $result = $this->service->calculateGrowthRate($currentCount, $previousCount);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    public function test_calculateGrowthRate_returns_zero_when_no_change()
    {
        // Arrange
        $currentCount = 100;
        $previousCount = 100;

        // Act
        $result = $this->service->calculateGrowthRate($currentCount, $previousCount);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    public function test_getStatsByPeriod_returns_period_specific_statistics()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $this->companyRepository->shouldReceive('countCreatedBetween')
            ->with($startDate, $endDate)
            ->once()
            ->andReturn(5);

        $this->viewRepository->shouldReceive('countViewsBetween')
            ->with($startDate, $endDate)
            ->once()
            ->andReturn(150);

        $this->feedbackRepository->shouldReceive('countFeedbackBetween')
            ->with($startDate, $endDate)
            ->once()
            ->andReturn(40);

        // Act
        $result = $this->service->getStatsByPeriod($startDate, $endDate);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('companies_created', $result);
        $this->assertArrayHasKey('total_views', $result);
        $this->assertArrayHasKey('total_feedback', $result);
        $this->assertArrayHasKey('period', $result);

        $this->assertEquals(5, $result['companies_created']);
        $this->assertEquals(150, $result['total_views']);
        $this->assertEquals(40, $result['total_feedback']);
        $this->assertArrayHasKey('start_date', $result['period']);
        $this->assertArrayHasKey('end_date', $result['period']);
    }

    public function test_getStatsByPeriod_includes_average_calculations()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(7); // 7 days
        $endDate = Carbon::now();

        $this->companyRepository->shouldReceive('countCreatedBetween')
            ->with($startDate, $endDate)
            ->once()
            ->andReturn(7); // 1 per day

        $this->viewRepository->shouldReceive('countViewsBetween')
            ->with($startDate, $endDate)
            ->once()
            ->andReturn(210); // 30 per day

        $this->feedbackRepository->shouldReceive('countFeedbackBetween')
            ->with($startDate, $endDate)
            ->once()
            ->andReturn(35); // 5 per day

        // Act
        $result = $this->service->getStatsByPeriod($startDate, $endDate);

        // Assert
        $this->assertArrayHasKey('averages', $result);
        $this->assertArrayHasKey('companies_per_day', $result['averages']);
        $this->assertArrayHasKey('views_per_day', $result['averages']);
        $this->assertArrayHasKey('feedback_per_day', $result['averages']);

        $this->assertEquals(1.0, $result['averages']['companies_per_day']);
        $this->assertEquals(30.0, $result['averages']['views_per_day']);
        $this->assertEquals(5.0, $result['averages']['feedback_per_day']);
    }

    public function test_getStats_handles_empty_data_gracefully()
    {
        // Arrange - All repositories return 0
        $this->companyRepository->shouldReceive('count')->once()->andReturn(0);
        $this->documentRepository->shouldReceive('count')->once()->andReturn(0);
        $this->viewRepository->shouldReceive('countTotalViews')->once()->andReturn(0);
        $this->feedbackRepository->shouldReceive('countTotalFeedback')->once()->andReturn(0);

        // Mock all period-based counts as 0
        $this->companyRepository->shouldReceive('countCreatedBetween')
            ->twice()
            ->andReturn(0);

        $this->viewRepository->shouldReceive('countViewsBetween')
            ->twice()
            ->andReturn(0);

        $this->feedbackRepository->shouldReceive('countFeedbackBetween')
            ->twice()
            ->andReturn(0);

        // Act
        $result = $this->service->getStats();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['companies']['total']);
        $this->assertEquals(0, $result['documents']['total']);
        $this->assertEquals(0, $result['views']['total']);
        $this->assertEquals(0, $result['feedback']['total']);

        // Growth rates should be 0 when no data
        $this->assertEquals(0.0, $result['growth_rates']['companies']);
        $this->assertEquals(0.0, $result['growth_rates']['views']);
        $this->assertEquals(0.0, $result['growth_rates']['feedback']);
    }
}
