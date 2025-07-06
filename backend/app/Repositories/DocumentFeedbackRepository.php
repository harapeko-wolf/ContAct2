<?php

namespace App\Repositories;

use App\Models\DocumentFeedback;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class DocumentFeedbackRepository implements DocumentFeedbackRepositoryInterface
{
    public function create(array $data): DocumentFeedback
    {
        return DocumentFeedback::create($data);
    }

    public function getFeedbackByDocument(string $documentId): Collection
    {
        return DocumentFeedback::where('document_id', $documentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getFeedbackByCompany(string $companyId): Collection
    {
        return DocumentFeedback::whereHas('document', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    }

    public function getBatchFeedbackByCompanies(array $companyIds): array
    {
        $feedback = DocumentFeedback::with('document')
            ->whereHas('document', function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by company_id
        $result = [];
        foreach ($feedback as $fb) {
            $companyId = $fb->document->company_id;
            if (!isset($result[$companyId])) {
                $result[$companyId] = [];
            }
            $result[$companyId][] = $fb;
        }

        return $result;
    }

    public function getRecentFeedback(int $limit = 10): Collection
    {
        return DocumentFeedback::with('document.company')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getFeedbackStatsByCompany(string $companyId): array
    {
        $feedback = $this->getFeedbackByCompany($companyId);

        if ($feedback->isEmpty()) {
            return [
                'total_feedback' => 0,
                'average_rating' => 0,
                'positive_feedback' => 0,
                'negative_feedback' => 0
            ];
        }

        $totalFeedback = $feedback->count();
        $ratings = $feedback->where('feedback_type', 'rating')
            ->map(function ($fb) {
                $metadata = $fb->feedback_metadata;
                return $metadata['rating'] ?? null;
            })
            ->filter()
            ->values();

        $averageRating = $ratings->isEmpty() ? 0 : $ratings->average();
        $positiveFeedback = $feedback->where('feedback_type', 'positive')->count();
        $negativeFeedback = $feedback->where('feedback_type', 'negative')->count();

        return [
            'total_feedback' => $totalFeedback,
            'average_rating' => round($averageRating, 2),
            'positive_feedback' => $positiveFeedback,
            'negative_feedback' => $negativeFeedback
        ];
    }

    public function countFeedbackBetween(Carbon $startDate, Carbon $endDate): int
    {
        return DocumentFeedback::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    public function countTotalFeedback(): int
    {
        return DocumentFeedback::count();
    }

    // Additional methods for test compatibility
    public function getFeedbackByType(string $type): Collection
    {
        return DocumentFeedback::where('feedback_type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getFeedbackByDocumentAndType(string $documentId, string $type): Collection
    {
        return DocumentFeedback::where('document_id', $documentId)
            ->where('feedback_type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function countFeedbackByDocument(string $documentId): int
    {
        return DocumentFeedback::where('document_id', $documentId)->count();
    }

    public function countFeedbackByCompany(string $companyId): int
    {
        return DocumentFeedback::whereHas('document', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->count();
    }

    public function getAverageRatingByDocument(string $documentId): float
    {
        $ratings = DocumentFeedback::where('document_id', $documentId)
            ->where('feedback_type', 'rating')
            ->whereNotNull('feedback_metadata')
            ->get()
            ->map(function ($feedback) {
                $metadata = $feedback->feedback_metadata;
                // Handle both JSON string and array cases
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true);
                }
                return $metadata['rating'] ?? null;
            })
            ->filter()
            ->values();

        if ($ratings->isEmpty()) {
            return 0;
        }

        return $ratings->average();
    }

    public function getBatchFeedbackStats(array $companyIds): array
    {
        $batchFeedback = $this->getBatchFeedbackByCompanies($companyIds);
        $result = [];

        foreach ($companyIds as $companyId) {
            $feedback = collect($batchFeedback[$companyId] ?? []);

            if ($feedback->isEmpty()) {
                $result[$companyId] = [
                    'total_feedback' => 0,
                    'average_rating' => 0,
                    'positive_feedback' => 0,
                    'negative_feedback' => 0
                ];
                continue;
            }

            $totalFeedback = $feedback->count();
            $ratings = $feedback->where('feedback_type', 'rating')
                ->map(function ($fb) {
                    $metadata = $fb->feedback_metadata;
                    // Handle both JSON string and array cases
                    if (is_string($metadata)) {
                        $metadata = json_decode($metadata, true);
                    }
                    return $metadata['rating'] ?? null;
                })
                ->filter()
                ->values();

            $averageRating = $ratings->isEmpty() ? 0 : $ratings->average();
            $positiveFeedback = $feedback->where('feedback_type', 'positive')->count();
            $negativeFeedback = $feedback->where('feedback_type', 'negative')->count();

            $result[$companyId] = [
                'total_feedback' => $totalFeedback,
                'average_rating' => round($averageRating, 2),
                'positive_feedback' => $positiveFeedback,
                'negative_feedback' => $negativeFeedback
            ];
        }

        return $result;
    }
}
