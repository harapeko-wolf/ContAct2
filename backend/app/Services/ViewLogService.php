<?php

namespace App\Services;

use App\Models\DocumentView;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ViewLogService
{
    /**
     * 閲覧ログを記録
     */
    public function recordViewLog(string $companyId, string $documentId, array $data, string $viewerIp, ?string $userAgent = null): DocumentView
    {
        try {
            // ドキュメントの存在確認
            $document = Document::where('company_id', $companyId)
                ->where('id', $documentId)
                ->firstOrFail();

            // 閲覧ログを作成
            $viewLog = DocumentView::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'document_id' => $documentId,
                'page_number' => $data['page_number'],
                'view_duration' => $data['view_duration'],
                'viewer_ip' => $viewerIp,
                'viewer_user_agent' => $userAgent,
                'viewed_at' => now(),
                'viewer_metadata' => $data['metadata'] ?? null,
            ]);

            return $viewLog;

        } catch (\Exception $e) {
            Log::error('閲覧ログ記録エラー: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'document_id' => $documentId,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * ドキュメントの閲覧ログ一覧を取得
     */
    public function getDocumentViewLogs(string $documentId, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            return DocumentView::where('document_id', $documentId)
                ->orderBy('viewed_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('ドキュメント閲覧ログ取得エラー: ' . $e->getMessage(), ['document_id' => $documentId]);
            throw $e;
        }
    }

    /**
     * 会社全体の閲覧ログを取得
     */
    public function getCompanyViewLogs(string $companyId, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            return DocumentView::select('document_views.*', 'documents.title', 'documents.file_name')
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->orderBy('document_views.viewed_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('会社閲覧ログ取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            throw $e;
        }
    }

    /**
     * 閲覧統計情報を取得
     */
    public function getViewStatistics(string $companyId): array
    {
        try {
            $stats = DocumentView::select([
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_views'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT viewer_ip) as unique_viewers'),
                    \Illuminate\Support\Facades\DB::raw('AVG(view_duration) as avg_duration'),
                    \Illuminate\Support\Facades\DB::raw('MAX(view_duration) as max_duration'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT document_id) as viewed_documents')
                ])
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->first();

            // 日別の閲覧数統計（過去30日）
            $dailyStats = DocumentView::select([
                    \Illuminate\Support\Facades\DB::raw('DATE(viewed_at) as date'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as views'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT viewer_ip) as unique_viewers')
                ])
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->where('document_views.viewed_at', '>=', now()->subDays(30))
                ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(viewed_at)'))
                ->orderBy('date', 'desc')
                ->get();

            // ページ別閲覧統計
            $pageStats = DocumentView::select([
                    'page_number',
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as views'),
                    \Illuminate\Support\Facades\DB::raw('AVG(view_duration) as avg_duration')
                ])
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->groupBy('page_number')
                ->orderBy('page_number')
                ->get();

            return [
                'total_views' => $stats->total_views ?? 0,
                'unique_viewers' => $stats->unique_viewers ?? 0,
                'avg_duration' => round($stats->avg_duration ?? 0, 2),
                'max_duration' => $stats->max_duration ?? 0,
                'viewed_documents' => $stats->viewed_documents ?? 0,
                'daily_stats' => $dailyStats->toArray(),
                'page_stats' => $pageStats->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error('閲覧統計取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [
                'total_views' => 0,
                'unique_viewers' => 0,
                'avg_duration' => 0,
                'max_duration' => 0,
                'viewed_documents' => 0,
                'daily_stats' => [],
                'page_stats' => [],
            ];
        }
    }

    /**
     * ドキュメント別の閲覧統計を取得
     */
    public function getDocumentViewStatistics(string $documentId): array
    {
        try {
            $stats = DocumentView::select([
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_views'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT viewer_ip) as unique_viewers'),
                    \Illuminate\Support\Facades\DB::raw('AVG(view_duration) as avg_duration'),
                    \Illuminate\Support\Facades\DB::raw('MAX(view_duration) as max_duration'),
                    \Illuminate\Support\Facades\DB::raw('MIN(view_duration) as min_duration')
                ])
                ->where('document_id', $documentId)
                ->first();

            // 時間帯別閲覧統計
            $hourlyStats = DocumentView::select([
                    \Illuminate\Support\Facades\DB::raw('HOUR(viewed_at) as hour'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as views')
                ])
                ->where('document_id', $documentId)
                ->groupBy(\Illuminate\Support\Facades\DB::raw('HOUR(viewed_at)'))
                ->orderBy('hour')
                ->get();

            return [
                'total_views' => $stats->total_views ?? 0,
                'unique_viewers' => $stats->unique_viewers ?? 0,
                'avg_duration' => round($stats->avg_duration ?? 0, 2),
                'max_duration' => $stats->max_duration ?? 0,
                'min_duration' => $stats->min_duration ?? 0,
                'hourly_stats' => $hourlyStats->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error('ドキュメント閲覧統計取得エラー: ' . $e->getMessage(), ['document_id' => $documentId]);
            return [
                'total_views' => 0,
                'unique_viewers' => 0,
                'avg_duration' => 0,
                'max_duration' => 0,
                'min_duration' => 0,
                'hourly_stats' => [],
            ];
        }
    }

    /**
     * 最近の閲覧ログを取得
     */
    public function getRecentViewLogs(string $companyId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return DocumentView::select('document_views.*', 'documents.title', 'documents.file_name')
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->orderBy('document_views.viewed_at', 'desc')
                ->limit($limit)
                ->get();

        } catch (\Exception $e) {
            Log::error('最近の閲覧ログ取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return collect([]);
        }
    }

    /**
     * IPアドレス別の閲覧統計を取得
     */
    public function getViewerStatistics(string $companyId): array
    {
        try {
            $viewerStats = DocumentView::select([
                    'viewer_ip',
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as views'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT document_id) as documents_viewed'),
                    \Illuminate\Support\Facades\DB::raw('AVG(view_duration) as avg_duration'),
                    \Illuminate\Support\Facades\DB::raw('MAX(viewed_at) as last_viewed')
                ])
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->groupBy('viewer_ip')
                ->orderBy('views', 'desc')
                ->limit(20)
                ->get();

            return $viewerStats->toArray();

        } catch (\Exception $e) {
            Log::error('閲覧者統計取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [];
        }
    }

    /**
     * 長時間閲覧ログを取得（エンゲージメント高）
     */
    public function getHighEngagementViews(string $companyId, int $minDuration = 30000): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return DocumentView::select('document_views.*', 'documents.title', 'documents.file_name')
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->where('document_views.view_duration', '>=', $minDuration)
                ->orderBy('document_views.view_duration', 'desc')
                ->get();

        } catch (\Exception $e) {
            Log::error('高エンゲージメント閲覧ログ取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return collect([]);
        }
    }
}
