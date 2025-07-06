<?php

namespace Tests\Unit\Services;

use App\Services\DocumentService;
use App\Services\FileManagementServiceInterface;
use App\Repositories\DocumentRepositoryInterface;
use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\DocumentViewRepositoryInterface;
use App\Repositories\DocumentFeedbackRepositoryInterface;
use App\Models\Document;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentService $service;
    private DocumentRepositoryInterface $documentRepository;
    private CompanyRepositoryInterface $companyRepository;
    private DocumentViewRepositoryInterface $documentViewRepository;
    private DocumentFeedbackRepositoryInterface $documentFeedbackRepository;
    private FileManagementServiceInterface $fileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = Mockery::mock(DocumentRepositoryInterface::class);
        $this->companyRepository = Mockery::mock(CompanyRepositoryInterface::class);
        $this->documentViewRepository = Mockery::mock(DocumentViewRepositoryInterface::class);
        $this->documentFeedbackRepository = Mockery::mock(DocumentFeedbackRepositoryInterface::class);
        $this->fileService = Mockery::mock(FileManagementServiceInterface::class);

        $this->service = new DocumentService(
            $this->documentRepository,
            $this->companyRepository,
            $this->documentViewRepository,
            $this->documentFeedbackRepository,
            $this->fileService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_getDocumentsByCompany_returns_paginated_documents()
    {
        // Arrange
        $companyId = 'company-id';
        $perPage = 10;
        $page = 1;
        $sortBy = 'created_at';
        $sortDirection = 'desc';

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $this->documentRepository->shouldReceive('getDocumentsByCompany')
            ->with($companyId, $perPage, $page, $sortBy, $sortDirection)
            ->once()
            ->andReturn($mockPaginator);

        // Act
        $result = $this->service->getDocumentsByCompany($companyId, $perPage, $page, $sortBy, $sortDirection);

        // Assert
        $this->assertSame($mockPaginator, $result);
    }

    public function test_getDocumentById_returns_document_with_company()
    {
        // Arrange
        $documentId = 'document-id';
        $document = new Document(['title' => 'Test Document']);
        $document->id = $documentId;

        $this->documentRepository->shouldReceive('findWithCompany')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->service->getDocumentById($documentId);

        // Assert
        $this->assertSame($document, $result);
    }

    public function test_createDocument_successfully_creates_document_with_file()
    {
        // Arrange
        $companyId = 'company-id';
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $data = [
            'title' => 'Test Document',
            'description' => 'Test Description'
        ];

        $company = new Company(['name' => 'Test Company']);
        $company->id = $companyId;

        $this->companyRepository->shouldReceive('findById')
            ->with($companyId)
            ->once()
            ->andReturn($company);

        $this->fileService->shouldReceive('uploadFile')
            ->with($file, 'documents/' . $companyId)
            ->once()
            ->andReturn([
                'path' => 'documents/company-id/test.pdf',
                'size' => 1024
            ]);

        $expectedDocumentData = [
            'company_id' => $companyId,
            'title' => 'Test Document',
            'description' => 'Test Description',
            'file_path' => 'documents/company-id/test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => 'active'
        ];

        $document = new Document($expectedDocumentData);
        $document->id = 'new-document-id';

        $this->documentRepository->shouldReceive('create')
            ->with($expectedDocumentData)
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->service->createDocument($companyId, $data, $file);

        // Assert
        $this->assertSame($document, $result);
    }

    public function test_updateDocument_successfully_updates_document()
    {
        // Arrange
        $documentId = 'document-id';
        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ];

        $document = new Document(['title' => 'Original Title']);
        $document->id = $documentId;

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        $this->documentRepository->shouldReceive('update')
            ->with($documentId, $data)
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->service->updateDocument($documentId, $data);

        // Assert
        $this->assertSame($document, $result);
    }

    public function test_updateDocument_with_file_uploads_new_file()
    {
        // Arrange
        $documentId = 'document-id';
        $data = ['title' => 'Updated Title'];
        $file = UploadedFile::fake()->create('updated.pdf', 2048, 'application/pdf');

        $document = new Document([
            'title' => 'Original Title',
            'file_path' => 'old/path/file.pdf'
        ]);
        $document->id = $documentId;
        $document->company_id = 'company-id';

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        $this->fileService->shouldReceive('deleteFile')
            ->with('old/path/file.pdf')
            ->once()
            ->andReturn(true);

        $this->fileService->shouldReceive('uploadFile')
            ->with($file, 'documents/company-id')
            ->once()
            ->andReturn([
                'path' => 'documents/company-id/updated.pdf',
                'size' => 2048
            ]);

        $expectedData = [
            'title' => 'Updated Title',
            'file_path' => 'documents/company-id/updated.pdf',
            'file_name' => 'updated.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf'
        ];

        $this->documentRepository->shouldReceive('update')
            ->with($documentId, $expectedData)
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->service->updateDocument($documentId, $data, $file);

        // Assert
        $this->assertSame($document, $result);
    }

    public function test_deleteDocument_successfully_deletes_document_and_file()
    {
        // Arrange
        $documentId = 'document-id';

        $document = new Document([
            'title' => 'Test Document',
            'file_path' => 'documents/company-id/test.pdf'
        ]);
        $document->id = $documentId;

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        $this->fileService->shouldReceive('deleteFile')
            ->with('documents/company-id/test.pdf')
            ->once()
            ->andReturn(true);

        $this->documentRepository->shouldReceive('delete')
            ->with($documentId)
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->deleteDocument($documentId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_getDocumentPreviewUrl_returns_preview_url()
    {
        // Arrange
        $documentId = 'document-id';
        $document = new Document([
            'title' => 'Test Document',
            'file_path' => 'documents/company-id/test.pdf'
        ]);
        $document->id = $documentId;

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        $this->fileService->shouldReceive('getFileUrl')
            ->with('documents/company-id/test.pdf')
            ->once()
            ->andReturn('https://example.com/preview/test.pdf');

        // Act
        $result = $this->service->getDocumentPreviewUrl($documentId);

        // Assert
        $this->assertEquals('https://example.com/preview/test.pdf', $result);
    }

    public function test_getDocumentDownloadUrl_returns_download_url()
    {
        // Arrange
        $documentId = 'document-id';
        $document = new Document([
            'title' => 'Test Document',
            'file_path' => 'documents/company-id/test.pdf'
        ]);
        $document->id = $documentId;

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        $this->fileService->shouldReceive('getDownloadUrl')
            ->with('documents/company-id/test.pdf')
            ->once()
            ->andReturn('https://example.com/download/test.pdf');

        // Act
        $result = $this->service->getDocumentDownloadUrl($documentId);

        // Assert
        $this->assertEquals('https://example.com/download/test.pdf', $result);
    }

    public function test_getDocumentsByStatus_returns_documents_with_status()
    {
        // Arrange
        $status = 'active';
        $perPage = 10;
        $page = 1;

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $this->documentRepository->shouldReceive('getDocumentsByStatus')
            ->with($status, $perPage, $page)
            ->once()
            ->andReturn($mockPaginator);

        // Act
        $result = $this->service->getDocumentsByStatus($status, $perPage, $page);

        // Assert
        $this->assertSame($mockPaginator, $result);
    }

    public function test_updateDocumentStatus_successfully_updates_status()
    {
        // Arrange
        $documentId = 'document-id';
        $status = 'archived';

        $document = new Document(['title' => 'Test Document']);
        $document->id = $documentId;

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        $this->documentRepository->shouldReceive('updateStatus')
            ->with($documentId, $status)
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->service->updateDocumentStatus($documentId, $status);

        // Assert
        $this->assertSame($document, $result);
    }

    public function test_searchDocuments_returns_search_results()
    {
        // Arrange
        $companyId = 'company-id';
        $query = 'search term';
        $perPage = 10;
        $page = 1;

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $this->documentRepository->shouldReceive('searchDocuments')
            ->with($companyId, $query, $perPage, $page)
            ->once()
            ->andReturn($mockPaginator);

        // Act
        $result = $this->service->searchDocuments($companyId, $query, $perPage, $page);

        // Assert
        $this->assertSame($mockPaginator, $result);
    }

    public function test_getDocumentMetrics_returns_document_metrics()
    {
        // Arrange
        $documentId = 'document-id';
        $expectedMetrics = [
            'views' => 100,
            'downloads' => 50,
            'feedback_count' => 10
        ];

        $this->documentRepository->shouldReceive('getDocumentMetrics')
            ->with($documentId)
            ->once()
            ->andReturn($expectedMetrics);

        // Act
        $result = $this->service->getDocumentMetrics($documentId);

        // Assert
        $this->assertSame($expectedMetrics, $result);
    }

    public function test_validateFileUpload_returns_true_for_valid_file()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        // Act
        $result = $this->service->validateFileUpload($file);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validateFileUpload_returns_false_for_invalid_file()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.exe', 1024, 'application/exe');

        // Act
        $result = $this->service->validateFileUpload($file);

        // Assert
        $this->assertFalse($result);
    }

    public function test_canUserAccessDocument_returns_true_for_valid_access()
    {
        // Arrange
        $documentId = 'document-id';
        $companyId = 'company-id';

        $document = new Document(['title' => 'Test Document']);
        $document->id = $documentId;
        $document->company_id = $companyId;

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->service->canUserAccessDocument($documentId, $companyId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_canUserAccessDocument_returns_false_for_invalid_access()
    {
        // Arrange
        $documentId = 'document-id';
        $companyId = 'different-company-id';

        $document = new Document(['title' => 'Test Document']);
        $document->id = $documentId;
        $document->company_id = 'company-id';

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->service->canUserAccessDocument($documentId, $companyId);

        // Assert
        $this->assertFalse($result);
    }
}
