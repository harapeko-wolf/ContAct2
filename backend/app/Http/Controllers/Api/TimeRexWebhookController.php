<?php

namespace App\Http\Controllers\Api;

use App\Services\TimeRexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TimeRexWebhookController extends BaseApiController
{
    protected TimeRexService $timeRexService;

    public function __construct(TimeRexService $timeRexService)
    {
        $this->timeRexService = $timeRexService;
    }

    /**
     * TimeRex Webhookを受信・処理する
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // リクエストボディを取得
            $payload = $request->all();

            Log::info('TimeRex Webhook受信', [
                'webhook_type' => $payload['webhook_type'] ?? 'unknown',
                'event_id' => $payload['event']['id'] ?? 'unknown',
                'ip' => $request->ip()
            ]);

            // Webhookを処理
            $result = $this->timeRexService->processWebhook($payload);

            if ($result['success']) {
                return $this->successResponse([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return $this->badRequestResponse($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('TimeRex Webhook処理で予期しないエラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return $this->serverErrorResponse($e, 'Webhook処理中に予期しないエラーが発生しました', 'TimeRex Webhookエラー');
        }
    }

    /**
     * TimeRex Webhookのヘルスチェック
     */
    public function health(Request $request): JsonResponse
    {
        return $this->successResponse([
            'status' => 'ok',
            'message' => 'TimeRex Webhook endpoint is healthy'
        ]);
    }
}
