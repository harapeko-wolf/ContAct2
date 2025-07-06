<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Company;
use App\Repositories\DocumentRepositoryInterface;
use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\DocumentViewRepositoryInterface;
use App\Repositories\DocumentFeedbackRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class DocumentService implements DocumentServiceInterface
{
    private DocumentRepositoryInterface $documentRepository;
    private CompanyRepositoryInterface $companyRepository;
    private DocumentViewRepositoryInterface $documentViewRepository;
    private DocumentFeedbackRepositoryInterface $documentFeedbackRepository;
    private FileManagementServiceInterface $fileService;

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CompanyRepositoryInterface $companyRepository,
        DocumentViewRepositoryInterface $documentViewRepository,
        DocumentFeedbackRepositoryInterface $documentFeedbackRepository,
        FileManagementServiceInterface $fileService
    ) {
        $this->documentRepository = $documentRepository;
        $this->companyRepository = $companyRepository;
        $this->documentViewRepository = $documentViewRepository;
        $this->documentFeedbackRepository = $documentFeedbackRepository;
        $this->fileService = $fileService;
    }

    public function getDocumentsPaginated(array $filters, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $companyId = $filters['company_id'] ?? null;
        $status = $filters['status'] ?? 'active';
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if ($search) {
            return $this->documentRepository->searchDocuments($companyId, $search, $perPage, $page);
        }

        if ($companyId) {
            return $this->documentRepository->getDocumentsByCompany($companyId, $perPage, $page, $sortBy, $sortDirection);
        }

        return $this->documentRepository->getDocumentsByStatus($status, $perPage, $page);
    }

    public function uploadDocument(UploadedFile $file, string $title, string $companyId, string $userId): Document
    {
        return $this->createDocument($companyId, ['title' => $title], $file);
    }

    public function getDocumentsByCompany(
        string $companyId,
        int $perPage = 10,
        int $page = 1,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->documentRepository->getDocumentsByCompany(
            $companyId,
            $perPage,
            $page,
            $sortBy,
            $sortDirection
        );
    }

    public function getDocumentById(string $documentId): Document
    {
        return $this->documentRepository->findWithCompany($documentId);
    }

    public function createDocument(string $companyId, array $data, UploadedFile $file): Document
    {
        // Validate company exists
        $company = $this->companyRepository->findById($companyId);
        if (!$company) {
            throw new InvalidArgumentException("Company not found: {$companyId}");
        }

        // Validate file
        if (!$this->validateFileUpload($file)) {
            throw new InvalidArgumentException("Invalid file type or size");
        }

        // Upload file
        $uploadResult = $this->fileService->uploadFile($file, 'documents/' . $companyId);

        // Prepare document data
        $documentData = array_merge($data, [
            'company_id' => $companyId,
            'file_path' => $uploadResult['path'],
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $uploadResult['size'],
            'mime_type' => $file->getMimeType(),
            'status' => 'active'
        ]);

        return $this->documentRepository->create($documentData);
    }

    public function updateDocument(string $documentId, array $data, ?UploadedFile $file = null): Document
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new InvalidArgumentException("Document not found: {$documentId}");
        }

        // Handle file update if provided
        if ($file) {
            // Validate new file
            if (!$this->validateFileUpload($file)) {
                throw new InvalidArgumentException("Invalid file type or size");
            }

            // Delete old file
            if ($document->file_path) {
                $this->fileService->deleteFile($document->file_path);
            }

            // Upload new file
            $uploadResult = $this->fileService->uploadFile($file, 'documents/' . $document->company_id);

            // Update data with new file info
            $data = array_merge($data, [
                'file_path' => $uploadResult['path'],
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $uploadResult['size'],
                'mime_type' => $file->getMimeType()
            ]);
        }

        return $this->documentRepository->update($documentId, $data);
    }

    public function deleteDocument(string $documentId): bool
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new InvalidArgumentException("Document not found: {$documentId}");
        }

        // Delete file from storage
        if ($document->file_path) {
            $this->fileService->deleteFile($document->file_path);
        }

        return $this->documentRepository->delete($documentId);
    }

    public function getDocumentPreviewUrl(string $documentId): string
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new InvalidArgumentException("Document not found: {$documentId}");
        }

        return $this->fileService->getFileUrl($document->file_path);
    }

    public function getPreviewUrl(string $documentId): string
    {
        return $this->getDocumentPreviewUrl($documentId);
    }

    public function getDocumentDownloadUrl(string $documentId): string
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new InvalidArgumentException("Document not found: {$documentId}");
        }

        return $this->fileService->getDownloadUrl($document->file_path);
    }

    public function getDownloadUrl(string $documentId): string
    {
        return $this->getDocumentDownloadUrl($documentId);
    }

    public function updateSortOrder(string $companyId, array $sortData): bool
    {
        foreach ($sortData as $item) {
            $documentId = $item['id'];
            $sortOrder = $item['sort_order'];

            $this->documentRepository->update($documentId, ['sort_order' => $sortOrder]);
        }

        return true;
    }

    public function getDocumentsByStatus(string $status, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->documentRepository->getDocumentsByStatus($status, $perPage, $page);
    }

    public function updateDocumentStatus(string $documentId, string $status): Document
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new InvalidArgumentException("Document not found: {$documentId}");
        }

        return $this->documentRepository->updateStatus($documentId, $status);
    }

    public function logView(string $companyId, string $documentId, array $viewData): bool
    {
        $viewLogData = array_merge($viewData, [
            'document_id' => $documentId,
            'viewed_at' => now()
        ]);

        $this->documentViewRepository->create($viewLogData);
        return true;
    }

    public function submitFeedback(string $companyId, string $documentId, array $feedbackData): bool
    {
        $feedbackSubmissionData = array_merge($feedbackData, [
            'document_id' => $documentId,
            'created_at' => now()
        ]);

        $this->documentFeedbackRepository->create($feedbackSubmissionData);
        return true;
    }

    public function getActiveDocuments(string $companyId): Collection
    {
        return $this->documentRepository->getActiveDocumentsByCompany($companyId);
    }

    public function canUserAccessDocument(string $documentId, string $companyId): bool
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            return false;
        }

        return $document->company_id === $companyId;
    }

    public function logDocumentView(string $documentId, int $pageNumber, int $viewDuration, string $viewerIp, ?string $userAgent = null): array
    {
        $viewData = [
            'document_id' => $documentId,
            'page_number' => $pageNumber,
            'view_duration' => $viewDuration,
            'viewer_ip' => $viewerIp,
            'viewer_user_agent' => $userAgent,
            'viewed_at' => now()
        ];

        $viewLog = $this->documentViewRepository->create($viewData);

        return [
            'success' => true,
            'view_log_id' => $viewLog->id,
            'message' => 'View logged successfully'
        ];
    }

    public function getDocumentViewLogs(string $documentId): array
    {
        $logs = $this->documentViewRepository->getViewsByDocument($documentId);

        // Get document details to get company_id for stats
        $document = $this->documentRepository->findById($documentId);
        $stats = [];
        if ($document) {
            $stats = $this->documentViewRepository->getViewStatsByCompany($document->company_id);
        }

        return [
            'logs' => $logs,
            'stats' => $stats
        ];
    }

    public function getCompanyViewLogs(string $companyId): array
    {
        $logs = $this->documentViewRepository->getViewsByCompany($companyId);
        $stats = $this->documentViewRepository->getViewStatsByCompany($companyId);

        return [
            'logs' => $logs,
            'stats' => $stats
        ];
    }

    public function submitDocumentFeedback(string $documentId, string $feedbackType, ?string $content, string $feedbackerIp, ?string $userAgent = null, ?array $feedbackMetadata = null): array
    {
        $feedbackData = [
            'document_id' => $documentId,
            'feedback_type' => $feedbackType,
            'content' => $content,
            'feedbacker_ip' => $feedbackerIp,
            'feedbacker_user_agent' => $userAgent,
            'created_at' => now()
        ];

        // Add metadata if provided (for survey responses, etc.)
        if ($feedbackMetadata) {
            $feedbackData['feedback_metadata'] = $feedbackMetadata;
        }

        $feedback = $this->documentFeedbackRepository->create($feedbackData);

        return [
            'success' => true,
            'feedback_id' => $feedback->id,
            'message' => 'Feedback submitted successfully'
        ];
    }

    public function getDocumentFeedback(string $documentId): array
    {
        $feedback = $this->documentFeedbackRepository->getFeedbackByDocument($documentId);

        // Get document details to get company_id for stats
        $document = $this->documentRepository->findById($documentId);
        $stats = [];
        if ($document) {
            $stats = $this->documentFeedbackRepository->getFeedbackStatsByCompany($document->company_id);
        }

        return [
            'feedback' => $feedback,
            'stats' => $stats
        ];
    }

    public function searchDocuments(string $companyId, string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->documentRepository->searchDocuments($companyId, $query, $perPage, $page);
    }

    public function getDocumentMetrics(string $documentId): array
    {
        return $this->documentRepository->getDocumentMetrics($documentId);
    }

    public function validateFileUpload(UploadedFile $file): bool
    {
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return false;
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            return false;
        }

        // Check file extension
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        return true;
    }
}
