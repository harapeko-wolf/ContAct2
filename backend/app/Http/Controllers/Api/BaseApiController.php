<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseApiController extends Controller
{
    /**
     * 成功レスポンスを返す
     */
    protected function successResponse($data = null, $meta = [], int $statusCode = 200): JsonResponse
    {
        $response = [
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => now()->toISOString(),
            ], $meta)
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * エラーレスポンスを返す
     */
    protected function errorResponse(string $code, string $message, $details = null, int $statusCode = 500): JsonResponse
    {
        $response = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ];

        if ($details) {
            $response['error']['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * バリデーションエラーレスポンスを返す
     */
    protected function validationErrorResponse($errors, string $message = 'バリデーションエラーが発生しました'): JsonResponse
    {
        return $this->errorResponse('VALIDATION_ERROR', $message, $errors, 422);
    }

    /**
     * 認証エラーレスポンスを返す
     */
    protected function unauthorizedResponse(string $message = '認証が必要です'): JsonResponse
    {
        return $this->errorResponse('UNAUTHENTICATED', $message, null, 401);
    }

    /**
     * 権限エラーレスポンスを返す
     */
    protected function forbiddenResponse(string $message = 'アクセス権限がありません'): JsonResponse
    {
        return $this->errorResponse('FORBIDDEN', $message, null, 403);
    }

    /**
     * リソースが見つからないエラーレスポンスを返す
     */
    protected function notFoundResponse(string $message = 'リソースが見つかりませんでした'): JsonResponse
    {
        return $this->errorResponse('NOT_FOUND', $message, null, 404);
    }

    /**
     * サーバーエラーレスポンスを返す
     */
    protected function serverErrorResponse(\Exception $e, string $message = 'サーバーエラーが発生しました', string $logContext = ''): JsonResponse
    {
        Log::error($logContext . ': ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);

        $details = config('app.debug') ? $e->getMessage() : null;

        return $this->errorResponse('INTERNAL_ERROR', $message, $details, 500);
    }

    /**
     * ページネーション情報を含むレスポンスを返す
     */
    protected function paginatedResponse($paginatedData): JsonResponse
    {
        return response()->json([
            'data' => $paginatedData->items(),
            'meta' => [
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'last_page' => $paginatedData->lastPage(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
                'timestamp' => now()->toISOString(),
            ],
            'links' => [
                'first' => $paginatedData->url(1),
                'last' => $paginatedData->url($paginatedData->lastPage()),
                'prev' => $paginatedData->previousPageUrl(),
                'next' => $paginatedData->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 作成成功レスポンスを返す
     */
    protected function createdResponse($data = null): JsonResponse
    {
        return $this->successResponse($data, [], 201);
    }

    /**
     * 削除成功レスポンスを返す
     */
    protected function deletedResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * カスタムメッセージ付きの成功レスポンスを返す
     */
    protected function messageResponse(string $message, $data = null): JsonResponse
    {
        return $this->successResponse([
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * 不正リクエストエラーレスポンスを返す
     */
    protected function badRequestResponse(string $message = '不正なリクエストです'): JsonResponse
    {
        return $this->errorResponse('BAD_REQUEST', $message, null, 400);
    }
}
