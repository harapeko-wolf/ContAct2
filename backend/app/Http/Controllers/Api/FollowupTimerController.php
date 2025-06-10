<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FollowupEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FollowupTimerController extends Controller
{
    private FollowupEmailService $followupService;

    public function __construct(FollowupEmailService $followupService)
    {
        $this->followupService = $followupService;
    }

    /**
     * フォローアップタイマーを開始
     */
    public function start(Request $request, string $companyId, string $documentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'viewer_ip' => 'required|ip',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'バリデーションエラー',
                        'details' => $validator->errors(),
                    ]
                ], 422);
            }

            $viewerIp = $request->input('viewer_ip');

            $result = $this->followupService->startFollowupTimer($companyId, $documentId, $viewerIp);

            if ($result['success']) {
                return response()->json([
                    'data' => [
                        'message' => $result['message'],
                        'followup_timer' => $result['data'],
                    ],
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                    ]
                ]);
            } else {
                return response()->json([
                    'error' => [
                        'code' => 'FOLLOWUP_TIMER_START_ERROR',
                        'message' => $result['message'],
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'フォローアップタイマーの開始に失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * フォローアップタイマーを停止
     */
    public function stop(Request $request, string $companyId, string $documentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'viewer_ip' => 'required|ip',
                'reason' => 'required|string|in:user_dismissed,timerex_booked,user_cancelled,timer_reset',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'バリデーションエラー',
                        'details' => $validator->errors(),
                    ]
                ], 422);
            }

            $viewerIp = $request->input('viewer_ip');
            $reason = $request->input('reason');

            $result = $this->followupService->stopFollowupTimer($companyId, $documentId, $viewerIp, $reason);

            if ($result['success']) {
                return response()->json([
                    'data' => [
                        'message' => $result['message'],
                        'cancelled_count' => $result['data']['cancelled_count'],
                    ],
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                    ]
                ]);
            } else {
                return response()->json([
                    'error' => [
                        'code' => 'FOLLOWUP_TIMER_STOP_ERROR',
                        'message' => $result['message'],
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'フォローアップタイマーの停止に失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * TimeRex予約チェック
     */
    public function checkTimeRexBooking(Request $request, string $companyId)
    {
        try {
            $result = $this->followupService->checkAndCancelForTimeRexBooking($companyId);

            if ($result['success']) {
                return response()->json([
                    'data' => [
                        'message' => $result['message'],
                        'has_recent_booking' => $result['data']['has_recent_booking'] ?? false,
                        'cancelled_count' => $result['data']['cancelled_count'] ?? 0,
                    ],
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                    ]
                ]);
            } else {
                return response()->json([
                    'error' => [
                        'code' => 'TIMEREX_CHECK_ERROR',
                        'message' => $result['message'],
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'TimeRex予約チェックに失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
        }
    }
}
