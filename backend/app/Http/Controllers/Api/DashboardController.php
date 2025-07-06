<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    private DashboardServiceInterface $dashboardService;

    public function __construct(DashboardServiceInterface $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * ダッシュボード統計データを取得
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $this->dashboardService->getStats($user);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ダッシュボード統計の取得に失敗しました', 'ダッシュボード統計取得エラー');
        }
    }
}
