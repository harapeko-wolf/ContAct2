<?php

namespace Tests\Unit\Services;

use App\Services\ScoreCalculationService;
use App\Repositories\DocumentViewRepositoryInterface;
use App\Repositories\DocumentFeedbackRepositoryInterface;
use App\Repositories\AppSettingRepositoryInterface;
use App\Models\Company;
use App\Domain\ValueObjects\Score;
use App\Domain\ValueObjects\ViewDuration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ScoreCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScoreCalculationService $service;
    private DocumentViewRepositoryInterface $viewRepository;
    private DocumentFeedbackRepositoryInterface $feedbackRepository;
    private AppSettingRepositoryInterface $settingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewRepository = Mockery::mock(DocumentViewRepositoryInterface::class);
        $this->feedbackRepository = Mockery::mock(DocumentFeedbackRepositoryInterface::class);
        $this->settingRepository = Mockery::mock(AppSettingRepositoryInterface::class);

        $this->service = new ScoreCalculationService(
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

    public function test_calculateCompanyScore_returns_complete_score_data()
    {
        // Arrange
        $companyId = 'company-id';

        $this->mockScoringSettings();
        $this->mockViewStats($companyId, [
            'total_views' => 150,
            'unique_viewers' => 75,
            'average_duration' => 120,
            'total_duration' => 18000
        ]);
        $this->mockFeedbackStats($companyId, [
            'total_feedback' => 25,
            'average_rating' => 4.2,
            'positive_feedback' => 22,
            'negative_feedback' => 3
        ]);

        // Act
        $result = $this->service->calculateCompanyScore($companyId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('engagement_score', $result);
        $this->assertArrayHasKey('survey_score', $result);
        $this->assertGreaterThanOrEqual(0, $result['total_score']);
        $this->assertLessThanOrEqual(100, $result['total_score']);
    }

    public function test_calculateBatchCompanyScores_handles_multiple_companies()
    {
        // Arrange
        $companyIds = ['company1', 'company2', 'company3'];

        $this->mockScoringSettings();

        // Mock view stats for all companies
        $this->viewRepository->shouldReceive('getBatchViewStats')
            ->with($companyIds)
            ->once()
            ->andReturn([
                'company1' => ['total_views' => 100, 'unique_viewers' => 50, 'average_duration' => 90, 'total_duration' => 9000],
                'company2' => ['total_views' => 200, 'unique_viewers' => 100, 'average_duration' => 150, 'total_duration' => 30000],
                'company3' => ['total_views' => 50, 'unique_viewers' => 25, 'average_duration' => 60, 'total_duration' => 3000]
            ]);

        // Mock feedback stats for all companies
        $this->feedbackRepository->shouldReceive('getBatchFeedbackStats')
            ->with($companyIds)
            ->once()
            ->andReturn([
                'company1' => ['total_feedback' => 10, 'average_rating' => 4.0, 'positive_feedback' => 8, 'negative_feedback' => 2],
                'company2' => ['total_feedback' => 30, 'average_rating' => 4.5, 'positive_feedback' => 28, 'negative_feedback' => 2],
                'company3' => ['total_feedback' => 5, 'average_rating' => 3.5, 'positive_feedback' => 3, 'negative_feedback' => 2]
            ]);

        // Act
        $result = $this->service->calculateBatchCompanyScores($companyIds);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('company1', $result);
        $this->assertArrayHasKey('company2', $result);
        $this->assertArrayHasKey('company3', $result);

        foreach ($result as $companyScore) {
            $this->assertArrayHasKey('total_score', $companyScore);
            $this->assertArrayHasKey('engagement_score', $companyScore);
            $this->assertArrayHasKey('survey_score', $companyScore);
        }
    }

    public function test_calculateSurveyScore_calculates_feedback_based_score()
    {
        // Arrange
        $companyId = 'company-id';

        $this->mockScoringSettings();
        $this->mockFeedbackStats($companyId, [
            'total_feedback' => 20,
            'average_rating' => 4.3,
            'positive_feedback' => 18,
            'negative_feedback' => 2
        ]);

        // Act
        $result = $this->service->calculateSurveyScore($companyId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('rating_score', $result);
        $this->assertArrayHasKey('sentiment_score', $result);
        $this->assertArrayHasKey('volume_bonus', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_calculateEngagementScore_calculates_view_based_score()
    {
        // Arrange
        $companyId = 'company-id';

        $this->mockScoringSettings();
        $this->mockViewStats($companyId, [
            'total_views' => 150,
            'unique_viewers' => 75,
            'average_duration' => 120,
            'total_duration' => 18000
        ]);

        // Act
        $result = $this->service->calculateEngagementScore($companyId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('view_score', $result);
        $this->assertArrayHasKey('duration_score', $result);
        $this->assertArrayHasKey('engagement_rate', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_calculateBatchSurveyScores_handles_multiple_companies()
    {
        // Arrange
        $companyIds = ['company1', 'company2'];
        $feedbacksData = [
            'company1' => ['total_feedback' => 10, 'average_rating' => 4.0, 'positive_feedback' => 8, 'negative_feedback' => 2],
            'company2' => ['total_feedback' => 25, 'average_rating' => 4.5, 'positive_feedback' => 23, 'negative_feedback' => 2]
        ];

        $this->mockScoringSettings();

        // Act
        $result = $this->service->calculateBatchSurveyScores($companyIds, $feedbacksData);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('company1', $result);
        $this->assertArrayHasKey('company2', $result);

        foreach ($result as $score) {
            $this->assertArrayHasKey('score', $score);
            $this->assertArrayHasKey('rating_score', $score);
            $this->assertArrayHasKey('sentiment_score', $score);
        }
    }

    public function test_calculateBatchEngagementScores_handles_multiple_companies()
    {
        // Arrange
        $companyIds = ['company1', 'company2'];
        $viewsData = [
            'company1' => ['total_views' => 100, 'unique_viewers' => 50, 'average_duration' => 90, 'total_duration' => 9000],
            'company2' => ['total_views' => 200, 'unique_viewers' => 100, 'average_duration' => 150, 'total_duration' => 30000]
        ];

        $this->mockScoringSettings();

        // Act
        $result = $this->service->calculateBatchEngagementScores($companyIds, $viewsData);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('company1', $result);
        $this->assertArrayHasKey('company2', $result);

        foreach ($result as $score) {
            $this->assertArrayHasKey('score', $score);
            $this->assertArrayHasKey('view_score', $score);
            $this->assertArrayHasKey('duration_score', $score);
        }
    }

    public function test_calculateTimeBasedScore_returns_correct_score_for_duration()
    {
        // Arrange
        $duration = 150; // seconds
        $tiers = [
            ['min' => 0, 'max' => 60, 'score' => 20],
            ['min' => 60, 'max' => 120, 'score' => 50],
            ['min' => 120, 'max' => 300, 'score' => 80],
            ['min' => 300, 'max' => null, 'score' => 100]
        ];

        // Act
        $result = $this->service->calculateTimeBasedScore($duration, $tiers);

        // Assert
        $this->assertEquals(80.0, $result);
    }

    public function test_calculateTimeBasedScore_returns_max_score_for_unlimited_tier()
    {
        // Arrange
        $duration = 500; // seconds
        $tiers = [
            ['min' => 0, 'max' => 60, 'score' => 20],
            ['min' => 60, 'max' => 120, 'score' => 50],
            ['min' => 120, 'max' => 300, 'score' => 80],
            ['min' => 300, 'max' => null, 'score' => 100]
        ];

        // Act
        $result = $this->service->calculateTimeBasedScore($duration, $tiers);

        // Assert
        $this->assertEquals(100.0, $result);
    }

    public function test_getScoringSettings_returns_default_settings()
    {
        // Arrange
        $this->mockScoringSettings();

        // Act
        $result = $this->service->getScoringSettings();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('engagement_weight', $result);
        $this->assertArrayHasKey('survey_weight', $result);
        $this->assertArrayHasKey('view_tiers', $result);
        $this->assertArrayHasKey('duration_tiers', $result);
        $this->assertArrayHasKey('rating_multiplier', $result);
    }

    public function test_getCompanyScoreDetails_returns_detailed_breakdown()
    {
        // Arrange
        $companyId = 'company-id';

        $this->mockScoringSettings();
        $this->mockViewStats($companyId, [
            'total_views' => 150,
            'unique_viewers' => 75,
            'average_duration' => 120,
            'total_duration' => 18000
        ]);
        $this->mockFeedbackStats($companyId, [
            'total_feedback' => 25,
            'average_rating' => 4.2,
            'positive_feedback' => 22,
            'negative_feedback' => 3
        ]);

        // Act
        $result = $this->service->getCompanyScoreDetails($companyId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('engagement_score', $result);
        $this->assertArrayHasKey('survey_score', $result);
        $this->assertArrayHasKey('view_metrics', $result);
        $this->assertArrayHasKey('feedback_metrics', $result);
        $this->assertArrayHasKey('breakdown', $result);
    }

    public function test_calculateBatchScores_returns_score_objects()
    {
        // Arrange
        $companyIds = ['company1', 'company2'];

        $this->mockScoringSettings();
        $this->viewRepository->shouldReceive('getBatchViewStats')
            ->with($companyIds)
            ->once()
            ->andReturn([
                'company1' => ['total_views' => 100, 'unique_viewers' => 50, 'average_duration' => 90, 'total_duration' => 9000],
                'company2' => ['total_views' => 200, 'unique_viewers' => 100, 'average_duration' => 150, 'total_duration' => 30000]
            ]);

        $this->feedbackRepository->shouldReceive('getBatchFeedbackStats')
            ->with($companyIds)
            ->once()
            ->andReturn([
                'company1' => ['total_feedback' => 10, 'average_rating' => 4.0, 'positive_feedback' => 8, 'negative_feedback' => 2],
                'company2' => ['total_feedback' => 30, 'average_rating' => 4.5, 'positive_feedback' => 28, 'negative_feedback' => 2]
            ]);

        // Act
        $result = $this->service->calculateBatchScores($companyIds);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Score::class, $result['company1']);
        $this->assertInstanceOf(Score::class, $result['company2']);
        $this->assertGreaterThanOrEqual(0, $result['company1']->getValue());
        $this->assertLessThanOrEqual(100, $result['company1']->getValue());
    }

    public function test_calculateDetailedScore_returns_comprehensive_score_data()
    {
        // Arrange
        $company = new Company(['name' => 'Test Company']);
        $company->id = 'company-id';

        $this->mockScoringSettings();
        $this->mockViewStats($company->id, [
            'total_views' => 150,
            'unique_viewers' => 75,
            'average_duration' => 120,
            'total_duration' => 18000
        ]);
        $this->mockFeedbackStats($company->id, [
            'total_feedback' => 25,
            'average_rating' => 4.2,
            'positive_feedback' => 22,
            'negative_feedback' => 3
        ]);

        // Act
        $result = $this->service->calculateDetailedScore($company);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('engagement_score', $result);
        $this->assertArrayHasKey('survey_score', $result);
        $this->assertArrayHasKey('view_metrics', $result);
        $this->assertArrayHasKey('feedback_metrics', $result);
        $this->assertIsArray($result['view_metrics']);
        $this->assertIsArray($result['feedback_metrics']);
        $this->assertEquals(150, $result['view_metrics']['total_views']);
        $this->assertEquals(75, $result['view_metrics']['unique_viewers']);
        $this->assertEquals(25, $result['feedback_metrics']['total_feedback']);
        $this->assertEquals(4.2, $result['feedback_metrics']['average_rating']);
    }

    private function mockScoringSettings(): void
    {
        $scoringSettings = [
            'engagement_weight' => 0.6,
            'survey_weight' => 0.4,
            'view_tiers' => [
                ['min' => 0, 'max' => 50, 'score' => 30],
                ['min' => 50, 'max' => 100, 'score' => 60],
                ['min' => 100, 'max' => null, 'score' => 100]
            ],
            'duration_tiers' => [
                ['min' => 0, 'max' => 60, 'score' => 20],
                ['min' => 60, 'max' => 120, 'score' => 50],
                ['min' => 120, 'max' => 300, 'score' => 80],
                ['min' => 300, 'max' => null, 'score' => 100]
            ],
            'rating_multiplier' => 20
        ];

        // Create a mock collection with AppSetting-like objects
        $mockSetting = (object) ['key' => 'scoring', 'value' => $scoringSettings];
        $mockCollection = new Collection([$mockSetting]);

        $this->settingRepository->shouldReceive('getPublicSettings')
            ->andReturn($mockCollection);
    }

    private function mockViewStats(string $companyId, array $stats): void
    {
        $this->viewRepository->shouldReceive('getViewStatsByCompany')
            ->with($companyId)
            ->andReturn($stats);
    }

    private function mockFeedbackStats(string $companyId, array $stats): void
    {
        $this->feedbackRepository->shouldReceive('getFeedbackStatsByCompany')
            ->with($companyId)
            ->andReturn($stats);
    }
}
