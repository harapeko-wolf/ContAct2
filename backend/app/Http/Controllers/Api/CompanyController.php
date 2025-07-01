<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyScoreService;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    protected $companyRepository;
    protected $companyScoreService;

    public function __construct(
        CompanyRepositoryInterface $companyRepository,
        CompanyScoreService $companyScoreService
    ) {
        $this->companyRepository = $companyRepository;
        $this->companyScoreService = $companyScoreService;
    }

    /**
     * 会社一覧を取得
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Repository層を使用して会社一覧を取得
            $companies = $this->companyRepository->getCompaniesWithScore($perPage);
            $companyIds = $companies->getCollection()->pluck('id')->toArray();

            // Service層を使用してスコア計算
            $companiesWithScores = $this->companyScoreService->calculateCompanyScoreBatch($companyIds);

            // レスポンス形式に変換
            $data = $companies->getCollection()->map(function ($company) use ($companiesWithScores) {
                $scores = $companiesWithScores[$company->id] ?? $this->companyScoreService->getDefaultScores();

                return array_merge($company->toArray(), [
                    'average_score' => $scores['total_score'],
                    'feedback_count' => $scores['feedback_count'],
                    'engagement_score' => $scores['engagement_score'],
                    'survey_score' => $scores['survey_score'],
                    'booking_status' => $this->getBookingStatus($company),
                    'timerex_stats' => $company->timeRexStats,
                ]);
            });

            // ソート処理
            if (in_array($sortBy, ['score', 'feedback_count'])) {
                $sortKey = $sortBy === 'score' ? 'average_score' : 'feedback_count';
                $data = $data->sortBy($sortKey, SORT_REGULAR, $sortOrder === 'desc');
            }

            return response()->json([
                'current_page' => $companies->currentPage(),
                'data' => $data->values()->toArray(),
                'first_page_url' => $companies->url(1),
                'from' => $companies->firstItem(),
                'last_page' => $companies->lastPage(),
                'last_page_url' => $companies->url($companies->lastPage()),
                'next_page_url' => $companies->nextPageUrl(),
                'path' => $companies->path(),
                'per_page' => $companies->perPage(),
                'prev_page_url' => $companies->previousPageUrl(),
                'to' => $companies->lastItem(),
                'total' => $companies->total(),
            ]);

        } catch (\Exception $e) {
            Log::error('会社一覧取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_LIST_ERROR',
                    'message' => '会社一覧の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }



    /**
     * TimeRex予約ステータスを判定
     */
    private function getBookingStatus($company)
    {
        $bookings = $company->timerex_bookings;

        if (!$bookings || empty($bookings['bookings'])) {
            return 'considering'; // 予約検討中（予約なし）
        }

        // 最新の予約を取得
        $latestBooking = collect($bookings['bookings'])
            ->sortByDesc('created_at')
            ->first();

        if (!$latestBooking) {
            return 'considering';
        }

        // ステータスに基づいて判定
        switch ($latestBooking['status']) {
            case 'confirmed':
                return 'confirmed'; // 予約確定
            case 'cancelled':
                return 'cancelled'; // 予約キャンセル
            default:
                return 'considering'; // 予約検討中
        }
    }





    /**
     * 会社のスコア詳細を取得
     */
    public function getScoreDetails($id)
    {
        try {
            $company = $this->companyRepository->findById($id);
            if (!$company) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした'
                    ]
                ], 404);
            }

            // Service層を使用してスコア計算
            $scores = $this->companyScoreService->calculateCompanyScore($id);

            return response()->json([
                'data' => [
                    'company' => $company,
                    'score_summary' => $scores,
                ],
                'meta' => [
                    'timestamp' => now()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('会社スコア詳細取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_SCORE_ERROR',
                    'message' => '会社スコア詳細の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社詳細を取得
     */
    public function show($id, Request $request)
    {
        try {
            // ユーザー所有の会社のみ取得
            if (!$this->companyRepository->existsForUser($id, $request->user()->id)) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした'
                    ]
                ], 404);
            }

            $company = $this->companyRepository->findById($id);

            // テストに合わせてdataラッパーで返す
            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('会社詳細取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_NOT_FOUND',
                    'message' => '会社が見つかりませんでした',
                    'details' => $e->getMessage()
                ]
            ], 404);
        }
    }

    /**
     * 会社を作成
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:companies,email,NULL,id,user_id,' . $request->user()->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string|max:1000',
                'industry' => 'nullable|string|max:100',
                'employee_count' => 'nullable|integer',
                'status' => 'nullable|in:active,considering,inactive',
                'booking_link' => 'nullable|url|max:255',
            ]);

            // 認証済みユーザーのIDを追加
            $validated['user_id'] = $request->user()->id;
            $validated['status'] = $validated['status'] ?? 'active';

            $company = $this->companyRepository->create($validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        } catch (\Exception $e) {
            Log::error('会社作成エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_CREATE_ERROR',
                    'message' => '会社の作成に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社を更新
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:companies,email,' . $id . ',id,user_id,' . $request->user()->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string|max:1000',
                'industry' => 'nullable|string|max:100',
                'employee_count' => 'nullable|integer',
                'status' => 'sometimes|in:active,considering,inactive',
                'booking_link' => 'nullable|url|max:255',
            ]);

            // ユーザー所有の会社のみ更新
            if (!$this->companyRepository->existsForUser($id, $request->user()->id)) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした'
                    ]
                ], 404);
            }

            $company = $this->companyRepository->findById($id);
            $this->companyRepository->update($company, $validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        } catch (\Exception $e) {
            Log::error('会社更新エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_UPDATE_ERROR',
                    'message' => '会社の更新に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社を削除
     */
    public function destroy($id, Request $request)
    {
        try {
            // ユーザー所有の会社のみ削除
            if (!$this->companyRepository->existsForUser($id, $request->user()->id)) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした'
                    ]
                ], 404);
            }

            $company = $this->companyRepository->findById($id);
            $this->companyRepository->delete($company);

            // テストに合わせて204レスポンス
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('会社削除エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_DELETE_ERROR',
                    'message' => '会社の削除に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
