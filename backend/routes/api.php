<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Api\DocumentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// 認証不要のルート
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// 公開PDF閲覧用のルート
Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);
Route::post('documents/{document}/view-logs', [DocumentController::class, 'logView']);
Route::get('documents/{document}/view-logs', [DocumentController::class, 'getViewLogs']);

// ヘルスチェック用のエンドポイント
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // 会社関連のルート
    Route::apiResource('companies', CompanyController::class);

    // PDF関連のルート
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);

    // 会社ごとのPDF関連のルート
    Route::prefix('companies/{companyId}/pdfs')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/{documentId}', [DocumentController::class, 'show']);
        Route::put('/{documentId}', [DocumentController::class, 'update']);
        Route::delete('/{documentId}', [DocumentController::class, 'destroy']);
        Route::get('/{documentId}/download', [DocumentController::class, 'download']);
        Route::get('/{documentId}/preview', [DocumentController::class, 'preview']);
        Route::post('/{documentId}/view-logs', [DocumentController::class, 'logView']);
        Route::get('/{documentId}/view-logs', [DocumentController::class, 'getViewLogs']);
    });

    // 会社ごとの閲覧ログを取得するルート
    Route::get('/companies/{companyId}/view-logs', [DocumentController::class, 'getCompanyViewLogs']);
});
