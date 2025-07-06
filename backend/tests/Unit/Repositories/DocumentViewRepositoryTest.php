<?php

namespace Tests\Unit\Repositories;

use App\Models\DocumentView;
use App\Models\Document;
use App\Models\Company;
use App\Repositories\DocumentViewRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Tests\TestCase;

class DocumentViewRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DocumentViewRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DocumentViewRepository();
    }

    public function test_create_creates_new_view()
    {
        // Arrange
        $document = Document::factory()->create();
        $data = [
            'document_id' => $document->id,
            'viewer_ip' => '192.168.1.1',
            'page_number' => 1,
            'viewed_at' => Carbon::now(),
            'view_duration' => 120,
            'viewer_user_agent' => 'Mozilla/5.0',
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(DocumentView::class, $result);
        $this->assertEquals($data['document_id'], $result->document_id);
        $this->assertEquals($data['viewer_ip'], $result->viewer_ip);
        $this->assertDatabaseHas('document_views', ['viewer_ip' => $data['viewer_ip']]);
    }

    public function test_getViewsByDocument_returns_views_for_document()
    {
        // Arrange
        $document = Document::factory()->create();
        $views = DocumentView::factory()->count(3)->create(['document_id' => $document->id]);
        $otherDocumentView = DocumentView::factory()->create();

        // Act
        $result = $this->repository->getViewsByDocument($document->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals($views->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_getViewsByDocument_returns_empty_collection_for_nonexistent_document()
    {
        // Arrange
        $nonExistentDocumentId = 'non-existent-id';

        // Act
        $result = $this->repository->getViewsByDocument($nonExistentDocumentId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getViewsByCompany_returns_views_for_company()
    {
        // Arrange
        $company = Company::factory()->create();
        $documents = Document::factory()->count(2)->create(['company_id' => $company->id]);
        $views = DocumentView::factory()->count(3)->create(['document_id' => $documents->first()->id]);
        $otherCompanyView = DocumentView::factory()->create();

        // Act
        $result = $this->repository->getViewsByCompany($company->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals($views->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_getViewsByCompany_returns_empty_collection_for_nonexistent_company()
    {
        // Arrange
        $nonExistentCompanyId = 'non-existent-id';

        // Act
        $result = $this->repository->getViewsByCompany($nonExistentCompanyId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getBatchViewsByCompanies_returns_views_grouped_by_company()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $document1 = Document::factory()->create(['company_id' => $company1->id]);
        $document2 = Document::factory()->create(['company_id' => $company2->id]);
        $views1 = DocumentView::factory()->count(2)->create(['document_id' => $document1->id]);
        $views2 = DocumentView::factory()->count(3)->create(['document_id' => $document2->id]);
        $otherCompanyView = DocumentView::factory()->create();

        // Act
        $result = $this->repository->getBatchViewsByCompanies([$company1->id, $company2->id]);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey($company1->id, $result);
        $this->assertArrayHasKey($company2->id, $result);
        $this->assertCount(2, $result[$company1->id]);
        $this->assertCount(3, $result[$company2->id]);
    }

    public function test_getBatchViewsByCompanies_applies_min_duration_filter()
    {
        // Arrange
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);
        $shortView = DocumentView::factory()->create(['document_id' => $document->id, 'view_duration' => 60]);
        $longView = DocumentView::factory()->create(['document_id' => $document->id, 'view_duration' => 180]);

        // Act
        $result = $this->repository->getBatchViewsByCompanies([$company->id], 120);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($company->id, $result);
        $this->assertCount(1, $result[$company->id]);
        $this->assertEquals($longView->id, $result[$company->id][0]->id);
    }

    public function test_countViewsBetween_returns_correct_count()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now()->subDays(1);
        $viewInRange = DocumentView::factory()->create(['viewed_at' => $startDate->copy()->addDays(3)]);
        $viewOutOfRange = DocumentView::factory()->create(['viewed_at' => $startDate->copy()->subDays(1)]);

        // Act
        $result = $this->repository->countViewsBetween($startDate, $endDate);

        // Assert
        $this->assertEquals(1, $result);
    }

    public function test_countViewsBetween_returns_zero_for_empty_range()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now()->subDays(8); // Invalid range
        DocumentView::factory()->create(['viewed_at' => Carbon::now()->subDays(5)]);

        // Act
        $result = $this->repository->countViewsBetween($startDate, $endDate);

        // Assert
        $this->assertEquals(0, $result);
    }

    public function test_countTotalViews_returns_correct_count()
    {
        // Arrange
        DocumentView::factory()->count(5)->create();

        // Act
        $result = $this->repository->countTotalViews();

        // Assert
        $this->assertEquals(5, $result);
    }

    public function test_countTotalViews_returns_zero_when_no_views()
    {
        // Act
        $result = $this->repository->countTotalViews();

        // Assert
        $this->assertEquals(0, $result);
    }

    public function test_getRecentViews_returns_recent_views()
    {
        // Arrange
        DocumentView::factory()->count(15)->create();

        // Act
        $result = $this->repository->getRecentViews(5);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
        // Check if documents are eager loaded
        $this->assertTrue($result->first()->relationLoaded('document'));
    }

    public function test_getRecentViews_returns_all_views_when_limit_exceeds_count()
    {
        // Arrange
        DocumentView::factory()->count(3)->create();

        // Act
        $result = $this->repository->getRecentViews(10);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_getViewsByCompanyWithMinDuration_returns_views_above_duration()
    {
        // Arrange
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);
        $shortView = DocumentView::factory()->create(['document_id' => $document->id, 'view_duration' => 60]);
        $longView = DocumentView::factory()->create(['document_id' => $document->id, 'view_duration' => 180]);

        // Act
        $result = $this->repository->getViewsByCompanyWithMinDuration($company->id, 120);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($longView->id, $result->first()->id);
    }

    public function test_getViewsByCompanyWithMinDuration_returns_empty_collection_when_no_views_meet_criteria()
    {
        // Arrange
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);
        $shortView = DocumentView::factory()->create(['document_id' => $document->id, 'view_duration' => 60]);

        // Act
        $result = $this->repository->getViewsByCompanyWithMinDuration($company->id, 120);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }
}
