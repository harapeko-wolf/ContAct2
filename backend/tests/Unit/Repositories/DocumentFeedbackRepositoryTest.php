<?php

namespace Tests\Unit\Repositories;

use App\Models\DocumentFeedback;
use App\Models\Document;
use App\Models\Company;
use App\Repositories\DocumentFeedbackRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Tests\TestCase;

class DocumentFeedbackRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DocumentFeedbackRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DocumentFeedbackRepository();
    }

    public function test_create_creates_new_feedback()
    {
        // Arrange
        $document = Document::factory()->create();
        $data = [
            'document_id' => $document->id,
            'feedback_type' => 'rating',
            'content' => 'Great document!',
            'feedbacker_ip' => '192.168.1.1',
            'feedbacker_user_agent' => 'Mozilla/5.0',
            'feedback_metadata' => json_encode(['rating' => 5]),
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(DocumentFeedback::class, $result);
        $this->assertEquals($data['document_id'], $result->document_id);
        $this->assertEquals($data['feedback_type'], $result->feedback_type);
        $this->assertDatabaseHas('document_feedback', ['content' => $data['content']]);
    }

    public function test_getFeedbackByDocument_returns_feedback_for_document()
    {
        // Arrange
        $document = Document::factory()->create();
        $feedback = DocumentFeedback::factory()->count(3)->create(['document_id' => $document->id]);
        $otherDocumentFeedback = DocumentFeedback::factory()->create();

        // Act
        $result = $this->repository->getFeedbackByDocument($document->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals($feedback->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_getFeedbackByDocument_returns_empty_collection_for_nonexistent_document()
    {
        // Arrange
        $nonExistentDocumentId = 'non-existent-id';

        // Act
        $result = $this->repository->getFeedbackByDocument($nonExistentDocumentId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getFeedbackByCompany_returns_feedback_for_company()
    {
        // Arrange
        $company = Company::factory()->create();
        $documents = Document::factory()->count(2)->create(['company_id' => $company->id]);
        $feedback1 = DocumentFeedback::factory()->count(2)->create(['document_id' => $documents->first()->id]);
        $feedback2 = DocumentFeedback::factory()->count(3)->create(['document_id' => $documents->last()->id]);
        $otherCompanyFeedback = DocumentFeedback::factory()->create();

        // Act
        $result = $this->repository->getFeedbackByCompany($company->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
        $expectedIds = $feedback1->pluck('id')->merge($feedback2->pluck('id'))->sort();
        $this->assertEquals($expectedIds, $result->pluck('id')->sort());
    }

    public function test_getFeedbackByCompany_returns_empty_collection_for_nonexistent_company()
    {
        // Arrange
        $nonExistentCompanyId = 'non-existent-id';

        // Act
        $result = $this->repository->getFeedbackByCompany($nonExistentCompanyId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getFeedbackByType_returns_feedback_for_type()
    {
        // Arrange
        $ratingFeedback = DocumentFeedback::factory()->count(3)->create(['feedback_type' => 'rating']);
        $commentFeedback = DocumentFeedback::factory()->count(2)->create(['feedback_type' => 'comment']);

        // Act
        $result = $this->repository->getFeedbackByType('rating');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals($ratingFeedback->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_getFeedbackByType_returns_empty_collection_for_nonexistent_type()
    {
        // Arrange
        DocumentFeedback::factory()->count(3)->create(['feedback_type' => 'rating']);

        // Act
        $result = $this->repository->getFeedbackByType('nonexistent');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_getFeedbackByDocumentAndType_returns_filtered_feedback()
    {
        // Arrange
        $document = Document::factory()->create();
        $ratingFeedback = DocumentFeedback::factory()->count(2)->create([
            'document_id' => $document->id,
            'feedback_type' => 'rating'
        ]);
        $commentFeedback = DocumentFeedback::factory()->count(3)->create([
            'document_id' => $document->id,
            'feedback_type' => 'comment'
        ]);
        $otherDocumentFeedback = DocumentFeedback::factory()->create(['feedback_type' => 'rating']);

        // Act
        $result = $this->repository->getFeedbackByDocumentAndType($document->id, 'rating');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals($ratingFeedback->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_countFeedbackByDocument_returns_correct_count()
    {
        // Arrange
        $document = Document::factory()->create();
        DocumentFeedback::factory()->count(5)->create(['document_id' => $document->id]);
        DocumentFeedback::factory()->count(3)->create(); // Other document feedback

        // Act
        $result = $this->repository->countFeedbackByDocument($document->id);

        // Assert
        $this->assertEquals(5, $result);
    }

    public function test_countFeedbackByDocument_returns_zero_for_nonexistent_document()
    {
        // Arrange
        $nonExistentDocumentId = 'non-existent-id';

        // Act
        $result = $this->repository->countFeedbackByDocument($nonExistentDocumentId);

        // Assert
        $this->assertEquals(0, $result);
    }

    public function test_countFeedbackByCompany_returns_correct_count()
    {
        // Arrange
        $company = Company::factory()->create();
        $documents = Document::factory()->count(2)->create(['company_id' => $company->id]);
        DocumentFeedback::factory()->count(3)->create(['document_id' => $documents->first()->id]);
        DocumentFeedback::factory()->count(2)->create(['document_id' => $documents->last()->id]);
        DocumentFeedback::factory()->count(4)->create(); // Other company feedback

        // Act
        $result = $this->repository->countFeedbackByCompany($company->id);

        // Assert
        $this->assertEquals(5, $result);
    }

    public function test_countFeedbackByCompany_returns_zero_for_nonexistent_company()
    {
        // Arrange
        $nonExistentCompanyId = 'non-existent-id';

        // Act
        $result = $this->repository->countFeedbackByCompany($nonExistentCompanyId);

        // Assert
        $this->assertEquals(0, $result);
    }

    public function test_countTotalFeedback_returns_correct_count()
    {
        // Arrange
        DocumentFeedback::factory()->count(7)->create();

        // Act
        $result = $this->repository->countTotalFeedback();

        // Assert
        $this->assertEquals(7, $result);
    }

    public function test_countTotalFeedback_returns_zero_when_no_feedback()
    {
        // Act
        $result = $this->repository->countTotalFeedback();

        // Assert
        $this->assertEquals(0, $result);
    }

    public function test_getAverageRatingByDocument_returns_correct_average()
    {
        // Arrange
        $document = Document::factory()->create();
        DocumentFeedback::factory()->create([
            'document_id' => $document->id,
            'feedback_type' => 'rating',
            'feedback_metadata' => json_encode(['rating' => 4])
        ]);
        DocumentFeedback::factory()->create([
            'document_id' => $document->id,
            'feedback_type' => 'rating',
            'feedback_metadata' => json_encode(['rating' => 5])
        ]);
        DocumentFeedback::factory()->create([
            'document_id' => $document->id,
            'feedback_type' => 'rating',
            'feedback_metadata' => json_encode(['rating' => 3])
        ]);

        // Act
        $result = $this->repository->getAverageRatingByDocument($document->id);

        // Assert
        $this->assertEquals(4.0, $result);
    }

    public function test_getAverageRatingByDocument_returns_zero_for_no_ratings()
    {
        // Arrange
        $document = Document::factory()->create();
        DocumentFeedback::factory()->create([
            'document_id' => $document->id,
            'feedback_type' => 'comment'
        ]);

        // Act
        $result = $this->repository->getAverageRatingByDocument($document->id);

        // Assert
        $this->assertEquals(0, $result);
    }

    public function test_getRecentFeedback_returns_recent_feedback()
    {
        // Arrange
        DocumentFeedback::factory()->count(15)->create();

        // Act
        $result = $this->repository->getRecentFeedback(5);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
        // Check if documents are eager loaded
        $this->assertTrue($result->first()->relationLoaded('document'));
    }

    public function test_getRecentFeedback_returns_all_feedback_when_limit_exceeds_count()
    {
        // Arrange
        DocumentFeedback::factory()->count(3)->create();

        // Act
        $result = $this->repository->getRecentFeedback(10);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_getFeedbackStatsByCompany_returns_grouped_statistics()
    {
        // Arrange
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);
        DocumentFeedback::factory()->count(3)->create([
            'document_id' => $document->id,
            'feedback_type' => 'rating'
        ]);
        DocumentFeedback::factory()->count(2)->create([
            'document_id' => $document->id,
            'feedback_type' => 'comment'
        ]);

        // Act
        $result = $this->repository->getFeedbackStatsByCompany($company->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $ratingStats = collect($result)->firstWhere('feedback_type', 'rating');
        $commentStats = collect($result)->firstWhere('feedback_type', 'comment');

        $this->assertEquals(3, $ratingStats['count']);
        $this->assertEquals(2, $commentStats['count']);
    }

    public function test_getFeedbackStatsByCompany_returns_empty_array_for_nonexistent_company()
    {
        // Arrange
        $nonExistentCompanyId = 'non-existent-id';

        // Act
        $result = $this->repository->getFeedbackStatsByCompany($nonExistentCompanyId);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
