<?php

namespace Tests\Unit\Repositories;

use App\Models\Document;
use App\Models\Company;
use App\Repositories\DocumentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class DocumentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DocumentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DocumentRepository();
    }

    public function test_getPaginatedDocumentsByCompany_returns_paginated_documents()
    {
        // Arrange
        $company = Company::factory()->create();
        $documents = Document::factory()->count(15)->create(['company_id' => $company->id]);
        $otherCompanyDocument = Document::factory()->create();

        // Act
        $result = $this->repository->getPaginatedDocumentsByCompany($company->id, 10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->items()->count());
        $this->assertEquals(15, $result->total());
    }

    public function test_getPaginatedDocumentsByCompany_applies_status_filter()
    {
        // Arrange
        $company = Company::factory()->create();
        $activeDocuments = Document::factory()->count(3)->create(['company_id' => $company->id, 'status' => 'active']);
        $inactiveDocuments = Document::factory()->count(2)->create(['company_id' => $company->id, 'status' => 'inactive']);

        // Act
        $result = $this->repository->getPaginatedDocumentsByCompany($company->id, 10, ['status' => 'active']);

        // Assert
        $this->assertEquals(3, count($result->items()));
        $this->assertEquals(3, $result->total());
    }

    public function test_getPaginatedDocumentsByCompany_applies_search_filter()
    {
        // Arrange
        $company = Company::factory()->create();
        $document1 = Document::factory()->create(['company_id' => $company->id, 'title' => 'Test Document']);
        $document2 = Document::factory()->create(['company_id' => $company->id, 'title' => 'Another Title']);
        $document3 = Document::factory()->create(['company_id' => $company->id, 'title' => 'Test Report']);

        // Act
        $result = $this->repository->getPaginatedDocumentsByCompany($company->id, 10, ['search' => 'Test']);

        // Assert
        $this->assertEquals(2, $result->count());
        $this->assertEquals(2, $result->total());
    }

    public function test_findByCompanyAndId_returns_document_when_exists()
    {
        // Arrange
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);
        $otherCompanyDocument = Document::factory()->create();

        // Act
        $result = $this->repository->findByCompanyAndId($company->id, $document->id);

        // Assert
        $this->assertInstanceOf(Document::class, $result);
        $this->assertEquals($document->id, $result->id);
    }

    public function test_findByCompanyAndId_returns_null_when_not_exists()
    {
        // Arrange
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);
        $otherCompany = Company::factory()->create();

        // Act
        $result = $this->repository->findByCompanyAndId($otherCompany->id, $document->id);

        // Assert
        $this->assertNull($result);
    }

    public function test_getDocumentsByCompanyWithSort_returns_documents_sorted_by_sort_order()
    {
        // Arrange
        $company = Company::factory()->create();
        $document1 = Document::factory()->create(['company_id' => $company->id, 'sort_order' => 3]);
        $document2 = Document::factory()->create(['company_id' => $company->id, 'sort_order' => 1]);
        $document3 = Document::factory()->create(['company_id' => $company->id, 'sort_order' => 2]);

        // Act
        $result = $this->repository->getDocumentsByCompanyWithSort($company->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals([$document2->id, $document3->id, $document1->id], $result->pluck('id')->toArray());
    }

    public function test_getDocumentsByCompanyWithSort_handles_null_sort_order()
    {
        // Arrange
        $company = Company::factory()->create();
        $document1 = Document::factory()->create(['company_id' => $company->id, 'sort_order' => 2]);
        $document2 = Document::factory()->create(['company_id' => $company->id, 'sort_order' => null]);
        $document3 = Document::factory()->create(['company_id' => $company->id, 'sort_order' => 1]);

        // Act
        $result = $this->repository->getDocumentsByCompanyWithSort($company->id);

        // Assert
        $this->assertCount(3, $result);
        // null values should come last
        $this->assertEquals([$document3->id, $document1->id, $document2->id], $result->pluck('id')->toArray());
    }

    public function test_getActiveDocumentsByCompany_returns_only_active_documents()
    {
        // Arrange
        $company = Company::factory()->create();
        $activeDocuments = Document::factory()->count(3)->create(['company_id' => $company->id, 'status' => 'active']);
        $inactiveDocuments = Document::factory()->count(2)->create(['company_id' => $company->id, 'status' => 'inactive']);

        // Act
        $result = $this->repository->getActiveDocumentsByCompany($company->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertEquals($activeDocuments->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    public function test_updateSortOrder_updates_multiple_documents_sort_order()
    {
        // Arrange
        $document1 = Document::factory()->create(['sort_order' => 1]);
        $document2 = Document::factory()->create(['sort_order' => 2]);
        $sortData = [
            ['id' => $document1->id, 'sort_order' => 3],
            ['id' => $document2->id, 'sort_order' => 1],
        ];

        // Act
        $result = $this->repository->updateSortOrder($sortData);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(3, $document1->fresh()->sort_order);
        $this->assertEquals(1, $document2->fresh()->sort_order);
    }

    public function test_updateStatus_updates_document_status()
    {
        // Arrange
        $document = Document::factory()->create(['status' => 'active']);
        $newStatus = 'inactive';

        // Act
        $result = $this->repository->updateStatus($document->id, $newStatus);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals($newStatus, $document->fresh()->status);
    }

    public function test_updateStatus_returns_false_for_nonexistent_document()
    {
        // Arrange
        $nonExistentDocumentId = 'non-existent-id';

        // Act
        $result = $this->repository->updateStatus($nonExistentDocumentId, 'inactive');

        // Assert
        $this->assertFalse($result);
    }

    public function test_findById_returns_document_when_exists()
    {
        // Arrange
        $document = Document::factory()->create();

        // Act
        $result = $this->repository->findById($document->id);

        // Assert
        $this->assertInstanceOf(Document::class, $result);
        $this->assertEquals($document->id, $result->id);
    }

    public function test_findById_returns_null_when_not_exists()
    {
        // Arrange
        $nonExistentDocumentId = 'non-existent-id';

        // Act
        $result = $this->repository->findById($nonExistentDocumentId);

        // Assert
        $this->assertNull($result);
    }

    public function test_create_creates_new_document()
    {
        // Arrange
        $company = Company::factory()->create();
        $data = [
            'company_id' => $company->id,
            'title' => 'Test Document',
            'file_path' => '/test/path',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => 'active',
            'sort_order' => 1,
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Document::class, $result);
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($data['company_id'], $result->company_id);
        $this->assertDatabaseHas('documents', ['title' => $data['title']]);
    }

    public function test_update_updates_existing_document()
    {
        // Arrange
        $document = Document::factory()->create(['title' => 'Original Title']);
        $updateData = ['title' => 'Updated Title'];

        // Act
        $result = $this->repository->update($document->id, $updateData);

        // Assert
        $this->assertInstanceOf(Document::class, $result);
        $this->assertEquals('Updated Title', $result->title);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'title' => 'Updated Title']);
    }

    public function test_update_throws_exception_for_nonexistent_document()
    {
        // Arrange
        $nonExistentDocumentId = 'non-existent-id';
        $updateData = ['title' => 'Updated Title'];

        // Act & Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->update($nonExistentDocumentId, $updateData);
    }

    public function test_delete_deletes_existing_document()
    {
        // Arrange
        $document = Document::factory()->create();

        // Act
        $result = $this->repository->delete($document->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_delete_returns_false_for_nonexistent_document()
    {
        // Arrange
        $nonExistentDocumentId = 'non-existent-id';

        // Act
        $result = $this->repository->delete($nonExistentDocumentId);

        // Assert
        $this->assertFalse($result);
    }
}
