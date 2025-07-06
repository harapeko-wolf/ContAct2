<?php

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function getPaginatedDocumentsByCompany(string $companyId, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = Document::where('company_id', $companyId);

        // Apply filters if provided
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function update(string $id, array $data): Document
    {
        $document = Document::findOrFail($id);
        $document->update($data);
        return $document;
    }

    public function delete(string $id): bool
    {
        $document = Document::find($id);
        if (!$document) {
            return false;
        }

        return $document->delete();
    }

    public function findById(string $id): ?Document
    {
        return Document::find($id);
    }

    public function findByCompanyAndId(string $companyId, string $documentId): ?Document
    {
        return Document::where('company_id', $companyId)
            ->where('id', $documentId)
            ->first();
    }

    public function getDocumentsByCompanyWithSort(string $companyId): Collection
    {
        return Document::where('company_id', $companyId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function updateSortOrder(array $sortData): bool
    {
        foreach ($sortData as $data) {
            Document::where('id', $data['id'])
                ->update(['sort_order' => $data['sort_order']]);
        }
        return true;
    }

    public function updateStatus(string $id, string $status): Document
    {
        $document = Document::findOrFail($id);
        $document->update(['status' => $status]);
        return $document->fresh();
    }

    public function getActiveDocumentsByCompany(string $companyId): Collection
    {
        return Document::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function count(): int
    {
        return Document::count();
    }

    public function getDocumentsByCompany(
        string $companyId,
        int $perPage = 10,
        int $page = 1,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return Document::where('company_id', $companyId)
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findWithCompany(string $id): ?Document
    {
        return Document::with('company')->find($id);
    }

    public function getDocumentsByStatus(string $status, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return Document::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function searchDocuments(string $companyId, string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return Document::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('file_name', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getDocumentMetrics(string $documentId): array
    {
        $document = $this->findById($documentId);
        if (!$document) {
            return [
                'views' => 0,
                'downloads' => 0,
                'feedback_count' => 0
            ];
        }

        // These would normally come from related tables
        // For now, return mock data that matches the expected structure
        return [
            'views' => $document->views()->count() ?? 0,
            'downloads' => $document->downloads ?? 0,
            'feedback_count' => $document->feedback()->count() ?? 0,
            'average_rating' => $document->average_rating ?? 0.0,
            'last_viewed_at' => $document->last_viewed_at ?? null
        ];
    }
}
