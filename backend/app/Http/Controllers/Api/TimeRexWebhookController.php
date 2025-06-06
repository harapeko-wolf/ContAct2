<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimeRexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TimeRexWebhookController extends Controller
{
    protected TimeRexService $timeRexService;

    public function __construct(TimeRexService $timeRexService)
    {
        $this->timeRexService = $timeRexService;
    }

    /**
     * TimeRex Webhookを受信・処理する
     */
    public function webhook(Request $request)
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
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error' => [
                        'code' => 'WEBHOOK_PROCESSING_ERROR',
                        'message' => $result['message']
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('TimeRex Webhook処理で予期しないエラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => '内部サーバーエラー',
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => 'Webhook処理中に予期しないエラーが発生しました'
                ]
            ], 500);
        }
    }

    /**
     * TimeRex Webhookのヘルスチェック
     */
    public function health(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'TimeRex Webhook endpoint is healthy',
            'timestamp' => now()->toISOString()
        ], 200);
    }
}
