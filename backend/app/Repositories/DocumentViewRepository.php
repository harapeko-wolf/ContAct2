<?php

namespace App\Repositories;

use App\Models\DocumentView;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class DocumentViewRepository implements DocumentViewRepositoryInterface
{
    public function create(array $data): DocumentView
    {
        return DocumentView::create($data);
    }

    public function getViewsByDocument(string $documentId): Collection
    {
        return DocumentView::where('document_id', $documentId)
            ->orderBy('viewed_at', 'desc')
            ->get();
    }

    public function getViewsByCompany(string $companyId): Collection
    {
        return DocumentView::whereHas('document', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->orderBy('viewed_at', 'desc')
        ->get();
    }

    public function getBatchViewsByCompanies(array $companyIds, int $minDuration = 0): array
    {
        $query = DocumentView::with('document')
            ->whereHas('document', function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            });

        if ($minDuration > 0) {
            $query->where('view_duration', '>=', $minDuration);
        }

        $views = $query->orderBy('viewed_at', 'desc')->get();

        // Group by company_id
        $result = [];
        foreach ($views as $view) {
            $companyId = $view->document->company_id;
            if (!isset($result[$companyId])) {
                $result[$companyId] = [];
            }
            $result[$companyId][] = $view;
        }

        return $result;
    }

    public function countViewsBetween(Carbon $startDate, Carbon $endDate): int
    {
        return DocumentView::whereBetween('viewed_at', [$startDate, $endDate])->count();
    }

    public function countTotalViews(): int
    {
        return DocumentView::count();
    }

    public function getRecentViews(int $limit = 10): Collection
    {
        return DocumentView::with('document.company')
            ->orderBy('viewed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getViewsByCompanyWithMinDuration(string $companyId, int $minDuration): Collection
    {
        return DocumentView::whereHas('document', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->where('view_duration', '>=', $minDuration)
        ->orderBy('viewed_at', 'desc')
        ->get();
    }

    public function getViewStatsByCompany(string $companyId): array
    {
        $views = $this->getViewsByCompany($companyId);

        if ($views->isEmpty()) {
            return [
                'total_views' => 0,
                'unique_viewers' => 0,
                'average_duration' => 0,
                'total_duration' => 0
            ];
        }

        $totalViews = $views->count();
        $uniqueViewers = $views->unique('viewer_ip')->count();
        $totalDuration = $views->sum('view_duration');
        $averageDuration = $totalDuration > 0 ? round($totalDuration / $totalViews) : 0;

        return [
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'average_duration' => $averageDuration,
            'total_duration' => $totalDuration
        ];
    }

    public function getBatchViewStats(array $companyIds): array
    {
        $batchViews = $this->getBatchViewsByCompanies($companyIds);
        $result = [];

        foreach ($companyIds as $companyId) {
            $views = collect($batchViews[$companyId] ?? []);

            if ($views->isEmpty()) {
                $result[$companyId] = [
                    'total_views' => 0,
                    'unique_viewers' => 0,
                    'average_duration' => 0,
                    'total_duration' => 0
                ];
                continue;
            }

            $totalViews = $views->count();
            $uniqueViewers = $views->unique('viewer_ip')->count();
            $totalDuration = $views->sum('view_duration');
            $averageDuration = $totalDuration > 0 ? round($totalDuration / $totalViews) : 0;

            $result[$companyId] = [
                'total_views' => $totalViews,
                'unique_viewers' => $uniqueViewers,
                'average_duration' => $averageDuration,
                'total_duration' => $totalDuration
            ];
        }

        return $result;
    }
}
