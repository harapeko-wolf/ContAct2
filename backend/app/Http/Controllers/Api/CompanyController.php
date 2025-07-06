<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CompanyServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    private CompanyServiceInterface $companyService;

    public function __construct(CompanyServiceInterface $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * 会社一覧を取得
     */
    public function index(Request $request)
    {
        try {
            // ページネーションパラメータ
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $sortBy = $request->get('sort_by', 'created_at'); // created_at, name, score
            $sortOrder = $request->get('sort_order', 'desc'); // asc, desc

            $result = $this->companyService->getCompaniesPaginatedWithScore(
                $perPage,
                $page,
                $sortBy,
                $sortOrder,
                $request->user()->id
            );

            return response()->json($result);

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

    public function getScoreDetails($id)
    {
        try {
            $result = $this->companyService->getCompanyScoreDetails($id);

            return response()->json([
                'data' => $result,
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
    public function show($id)
    {
        try {
            $company = $this->companyService->getCompanyById($id);
            $user = request()->user();

            // 権限チェック：管理者は全ての会社を閲覧可能、一般ユーザーは自分の会社のみ
            if (!$company) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした',
                    ]
                ], 404);
            }

            if (!$user->isAdmin() && $company->user_id !== $user->id) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした',
                    ]
                ], 404);
            }

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
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        }

        try {
            // 認証済みユーザーのIDを追加
            $validated['user_id'] = $request->user()->id;

            $company = $this->companyService->createCompany($validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ], 201);
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
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        }

        try {
            // 権限チェック：管理者は全ての会社を更新可能、一般ユーザーは自分の会社のみ
            $company = $this->companyService->getCompanyById($id);
            $user = $request->user();

            if (!$company) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした',
                    ]
                ], 404);
            }

            if (!$user->isAdmin() && $company->user_id !== $user->id) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした',
                    ]
                ], 404);
            }

            $company = $this->companyService->updateCompany($id, $validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
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
    public function destroy($id)
    {
        try {
            // 権限チェック：管理者は全ての会社を削除可能、一般ユーザーは自分の会社のみ
            $company = $this->companyService->getCompanyById($id);
            $user = request()->user();

            if (!$company) {
                return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした',
                    ]
                ], 404);
            }

            if (!$user->isAdmin() && $company->user_id !== $user->id) {
            return response()->json([
                    'error' => [
                        'code' => 'COMPANY_NOT_FOUND',
                        'message' => '会社が見つかりませんでした',
                    ]
                ], 404);
            }

            $this->companyService->deleteCompany($id);

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
