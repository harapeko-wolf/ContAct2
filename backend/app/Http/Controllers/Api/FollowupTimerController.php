<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StartFollowupTimerRequest;
use App\Http\Requests\StopFollowupTimerRequest;
use App\Services\FollowupEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowupTimerController extends BaseApiController
{
    private FollowupEmailService $followupService;

    public function __construct(FollowupEmailService $followupService)
    {
        $this->followupService = $followupService;
    }

    /**
     * フォローアップタイマーを開始
     */
    public function start(StartFollowupTimerRequest $request, string $companyId, string $documentId): JsonResponse
    {
        try {
            $viewerIp = $request->validated()['viewer_ip'];

            $result = $this->followupService->startFollowupTimer($companyId, $documentId, $viewerIp);

            if ($result['success']) {
                return $this->successResponse([
                    'message' => $result['message'],
                    'followup_timer' => $result['data'],
                ]);
            } else {
                return $this->badRequestResponse($result['message']);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'フォローアップタイマーの開始に失敗しました', 'フォローアップタイマー開始エラー');
        }
    }

    /**
     * フォローアップタイマーを停止
     */
    public function stop(StopFollowupTimerRequest $request, string $companyId, string $documentId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $viewerIp = $validated['viewer_ip'];
            $reason = $validated['reason'];

            $result = $this->followupService->stopFollowupTimer($companyId, $documentId, $viewerIp, $reason);

            if ($result['success']) {
                return $this->successResponse([
                    'message' => $result['message'],
                    'cancelled_count' => $result['data']['cancelled_count'],
                ]);
            } else {
                return $this->badRequestResponse($result['message']);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'フォローアップタイマーの停止に失敗しました', 'フォローアップタイマー停止エラー');
        }
    }

    /**
     * TimeRex予約チェック
     */
    public function checkTimeRexBooking(Request $request, string $companyId): JsonResponse
    {
        try {
            $result = $this->followupService->checkAndCancelForTimeRexBooking($companyId);

            if ($result['success']) {
                return $this->successResponse([
                    'message' => $result['message'],
                    'has_recent_booking' => $result['data']['has_recent_booking'] ?? false,
                    'cancelled_count' => $result['data']['cancelled_count'] ?? 0,
                ]);
            } else {
                return $this->badRequestResponse($result['message']);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'TimeRex予約チェックに失敗しました', 'TimeRex予約チェックエラー');
        }
    }
}
