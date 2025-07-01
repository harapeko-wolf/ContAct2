<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\DocumentFeedback;
use App\Models\DocumentView;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyRepository implements CompanyRepositoryInterface
{
    /**
     * 会社一覧をスコア付きで取得
     */
    public function getCompaniesWithScore(int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Company::with(['documents' => function($query) {
                    $query->where('status', 'active')
                        ->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('会社一覧取得エラー: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 会社のフィードバックデータを一括取得（N+1問題回避）
     */
    public function getFeedbacksDataBatch(array $companyIds): array
    {
        try {
            $feedbacks = DocumentFeedback::select([
                    'document_feedback.feedback_metadata',
                    'documents.company_id'
                ])
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->whereIn('documents.company_id', $companyIds)
                ->get();

            // 会社IDをキーとしてグループ化
            $groupedFeedbacks = [];
            foreach ($feedbacks as $feedback) {
                $companyId = $feedback->company_id;
                if (!isset($groupedFeedbacks[$companyId])) {
                    $groupedFeedbacks[$companyId] = [];
                }
                $groupedFeedbacks[$companyId][] = [
                    'feedback_metadata' => $feedback->feedback_metadata
                ];
            }

            return $groupedFeedbacks;

        } catch (\Exception $e) {
            Log::error('フィードバックデータ一括取得エラー: ' . $e->getMessage(), ['company_ids' => $companyIds]);
            return [];
        }
    }

    /**
     * 会社の閲覧データを一括取得（N+1問題回避）
     */
    public function getViewsDataBatch(array $companyIds): array
    {
        try {
            $views = DocumentView::select([
                    'document_views.view_duration',
                    'document_views.document_id',
                    'documents.company_id'
                ])
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->whereIn('documents.company_id', $companyIds)
                ->where('document_views.view_duration', '>=', 3000) // 設定値は後でサービス層から取得
                ->get();

            // 会社IDをキーとしてグループ化
            $groupedViews = [];
            foreach ($views as $view) {
                $companyId = $view->company_id;
                if (!isset($groupedViews[$companyId])) {
                    $groupedViews[$companyId] = [];
                }
                $groupedViews[$companyId][] = [
                    'view_duration' => $view->view_duration,
                    'document_id' => $view->document_id
                ];
            }

            return $groupedViews;

        } catch (\Exception $e) {
            Log::error('閲覧データ一括取得エラー: ' . $e->getMessage(), ['company_ids' => $companyIds]);
            return [];
        }
    }

    /**
     * IDで会社を取得
     */
    public function findById(string $id): ?Company
    {
        try {
            return Company::find($id);
        } catch (\Exception $e) {
            Log::error('会社取得エラー: ' . $e->getMessage(), ['id' => $id]);
            return null;
        }
    }

    /**
     * ユーザーの会社一覧を取得
     */
    public function getByUserId(string $userId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Company::where('user_id', $userId)
                ->with(['documents' => function($query) {
                    $query->where('status', 'active')
                        ->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('ユーザー会社一覧取得エラー: ' . $e->getMessage(), ['user_id' => $userId]);
            throw $e;
        }
    }

    /**
     * 会社を作成
     */
    public function create(array $data): Company
    {
        try {
            $data['id'] = Str::uuid();
            return Company::create($data);

        } catch (\Exception $e) {
            Log::error('会社作成エラー: ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * 会社を更新
     */
    public function update(Company $company, array $data): bool
    {
        try {
            return $company->update($data);

        } catch (\Exception $e) {
            Log::error('会社更新エラー: ' . $e->getMessage(), [
                'company_id' => $company->id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * 会社を削除
     */
    public function delete(Company $company): bool
    {
        try {
            return $company->delete();

        } catch (\Exception $e) {
            Log::error('会社削除エラー: ' . $e->getMessage(), ['company_id' => $company->id]);
            throw $e;
        }
    }

    /**
     * 公開会社一覧を取得
     */
    public function getPublicCompanies(): Collection
    {
        try {
            return Company::where('status', 'active')
                ->with(['documents' => function($query) {
                    $query->where('status', 'active')
                        ->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

        } catch (\Exception $e) {
            Log::error('公開会社一覧取得エラー: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * 会社の存在確認（ユーザー所有）
     */
    public function existsForUser(string $companyId, string $userId): bool
    {
        try {
            return Company::where('id', $companyId)
                ->where('user_id', $userId)
                ->exists();

        } catch (\Exception $e) {
            Log::error('会社存在確認エラー: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * 会社の統計データを取得
     */
    public function getCompanyStats(string $companyId): array
    {
        try {
            // ドキュメント数
            $documentCount = DB::table('documents')
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->count();

            // 総閲覧数
            $totalViews = DB::table('document_views')
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->count();

            // ユニーク閲覧者数
            $uniqueViewers = DB::table('document_views')
                ->join('documents', 'document_views.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->distinct('viewer_ip')
                ->count('viewer_ip');

            // フィードバック数
            $feedbackCount = DB::table('document_feedback')
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->count();

            return [
                'document_count' => $documentCount,
                'total_views' => $totalViews,
                'unique_viewers' => $uniqueViewers,
                'feedback_count' => $feedbackCount,
            ];

        } catch (\Exception $e) {
            Log::error('会社統計取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [
                'document_count' => 0,
                'total_views' => 0,
                'unique_viewers' => 0,
                'feedback_count' => 0,
            ];
        }
    }

    /**
     * 会社の詳細情報をスコア付きで取得
     */
    public function getCompanyWithScoreDetails(string $companyId): ?Company
    {
        try {
            return Company::with([
                'documents' => function($query) {
                    $query->where('status', 'active')
                        ->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc');
                },
                'documents.documentViews' => function($query) {
                    $query->orderBy('viewed_at', 'desc')->limit(10);
                },
                'documents.documentFeedback' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                }
            ])->find($companyId);

        } catch (\Exception $e) {
            Log::error('会社詳細取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return null;
        }
    }

    /**
     * 検索による会社一覧取得
     */
    public function searchCompanies(string $searchTerm, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Company::where(function($query) use ($searchTerm) {
                    $query->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('industry', 'LIKE', "%{$searchTerm}%");
                })
                ->with(['documents' => function($query) {
                    $query->where('status', 'active')
                        ->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('会社検索エラー: ' . $e->getMessage(), ['search_term' => $searchTerm]);
            throw $e;
        }
    }
}
