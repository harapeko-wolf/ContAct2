<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\AuthorizationTrait;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Services\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends BaseApiController
{
    use AuthorizationTrait;

    private CompanyServiceInterface $companyService;

    public function __construct(CompanyServiceInterface $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * 会社一覧を取得
     */
    public function index(Request $request): JsonResponse
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

            return $this->paginatedResponse($result);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '会社一覧の取得に失敗しました', '会社一覧取得エラー');
        }
    }

    /**
     * 会社スコア詳細を取得
     */
    public function getScoreDetails(string $id): JsonResponse
    {
        try {
            $result = $this->companyService->getCompanyScoreDetails($id);

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '会社スコア詳細の取得に失敗しました', '会社スコア詳細取得エラー');
        }
    }

    /**
     * 会社詳細を取得
     */
    public function show(string $id): JsonResponse
    {
        try {
            $company = $this->companyService->getCompanyById($id);

            if (!$company) {
                return $this->notFoundResponse('会社が見つかりませんでした');
            }

            // 権限チェック：管理者は全ての会社を閲覧可能、一般ユーザーは自分の会社のみ
            if ($authError = $this->ensureCompanyAccess($company)) {
                return $authError;
            }

            return $this->successResponse($company);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '会社が見つかりませんでした', '会社詳細取得エラー');
        }
    }

    /**
     * 会社を作成
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        try {
            // 認証済みユーザーのIDを追加
            $validated = $request->validated();
            $validated['user_id'] = $request->user()->id;

            $company = $this->companyService->createCompany($validated);

            return $this->createdResponse($company);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '会社の作成に失敗しました', '会社作成エラー');
        }
    }

    /**
     * 会社を更新
     */
    public function update(UpdateCompanyRequest $request, string $id): JsonResponse
    {
        try {
            $company = $this->companyService->getCompanyById($id);

            if (!$company) {
                return $this->notFoundResponse('会社が見つかりませんでした');
            }

            // 権限チェック：管理者は全ての会社を更新可能、一般ユーザーは自分の会社のみ
            if ($authError = $this->ensureCompanyAccess($company)) {
                return $authError;
            }

            $updatedCompany = $this->companyService->updateCompany($id, $request->validated());

            return $this->successResponse($updatedCompany);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '会社の更新に失敗しました', '会社更新エラー');
        }
    }

    /**
     * 会社を削除
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $company = $this->companyService->getCompanyById($id);

            if (!$company) {
                return $this->notFoundResponse('会社が見つかりませんでした');
            }

            // 権限チェック：管理者は全ての会社を削除可能、一般ユーザーは自分の会社のみ
            if ($authError = $this->ensureCompanyAccess($company)) {
                return $authError;
            }

            $this->companyService->deleteCompany($id);

            return $this->deletedResponse();
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '会社の削除に失敗しました', '会社削除エラー');
        }
    }
}
