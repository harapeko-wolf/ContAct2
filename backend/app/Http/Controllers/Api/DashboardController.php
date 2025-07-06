<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardServiceInterface;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private DashboardServiceInterface $dashboardService;

    public function __construct(DashboardServiceInterface $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * ダッシュボード統計データを取得
     */
    public function getStats(Request $request)
    {
        try {
            $user = $request->user();
            $data = $this->dashboardService->getStats($user);

            return response()->json([
                'data' => $data,
                'meta' => [
                    'timestamp' => now(),
                ]
            ]);

                } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DASHBOARD_STATS_ERROR',
                    'message' => 'ダッシュボード統計の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
