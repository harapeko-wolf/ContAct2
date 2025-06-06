<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Services\TimeRexService;

class TimeRexWebhookAuth
{
    protected TimeRexService $timeRexService;

    public function __construct(TimeRexService $timeRexService)
    {
        $this->timeRexService = $timeRexService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 開発環境では認証をスキップ
        if (app()->environment('local', 'testing')) {
            Log::info('TimeRex Webhook認証スキップ（開発環境）');
            return $next($request);
        }

        // 認証ヘッダーを取得
        $authToken = $request->header('x-timerex-authorization');

        if (empty($authToken)) {
            Log::warning('TimeRex Webhook認証失敗: トークンなし', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => '認証トークンが必要です'
                ]
            ], 401);
        }

        // トークン検証
        if (!$this->timeRexService->validateAuthToken($authToken)) {
            Log::warning('TimeRex Webhook認証失敗: 無効なトークン', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_prefix' => substr($authToken, 0, 8) . '...'
            ]);

            return response()->json([
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => '無効な認証トークンです'
                ]
            ], 401);
        }

        Log::info('TimeRex Webhook認証成功', [
            'ip' => $request->ip()
        ]);

        return $next($request);
    }
}
