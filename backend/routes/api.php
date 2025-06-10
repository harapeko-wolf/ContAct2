<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TimeRexWebhookController;

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

// ヘルスチェック用のエンドポイント
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});

// TimeRex Webhook（特別な認証ミドルウェア使用）
Route::prefix('timerex')->middleware(\App\Http\Middleware\TimeRexWebhookAuth::class)->group(function () {
    Route::post('/webhook', [TimeRexWebhookController::class, 'webhook']);
    Route::get('/webhook/health', [TimeRexWebhookController::class, 'health']);
});

// 公開設定（認証不要）
Route::get('/settings/public', [SettingsController::class, 'publicSettings']);

// 認証不要の公開APIルート
Route::prefix('public/companies/{companyId}')->group(function () {
    Route::get('pdfs', [App\Http\Controllers\Admin\CompanyPdfController::class, 'publicIndex']);
    Route::get('pdfs/{documentId}/preview', [App\Http\Controllers\Admin\CompanyPdfController::class, 'publicPreview']);
});

// PDF閲覧ログ記録（認証不要）
Route::prefix('companies/{companyId}/pdfs/{documentId}')->group(function () {
    Route::post('view-logs', [App\Http\Controllers\Api\DocumentController::class, 'logView']);
    Route::get('view-logs', [App\Http\Controllers\Api\DocumentController::class, 'getViewLogs']);
    Route::post('feedback', [App\Http\Controllers\Api\DocumentController::class, 'submitFeedback']);
    Route::get('feedback', [App\Http\Controllers\Api\DocumentController::class, 'getFeedback']);

    // フォローアップメールタイマー
    Route::post('followup-timer', [App\Http\Controllers\Api\FollowupTimerController::class, 'start']);
    Route::delete('followup-timer', [App\Http\Controllers\Api\FollowupTimerController::class, 'stop']);
});

// TimeRex予約チェック（認証不要）
Route::get('companies/{companyId}/timerex-bookings/recent', [App\Http\Controllers\Api\FollowupTimerController::class, 'checkTimeRexBooking']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ダッシュボード統計
    Route::get('/admin/dashboard/stats', [DashboardController::class, 'getStats']);

    // 設定管理
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // 会社管理
    Route::apiResource('companies', App\Http\Controllers\Api\CompanyController::class);

    // 会社のスコア詳細
    Route::get('companies/{companyId}/score-details', [App\Http\Controllers\Api\CompanyController::class, 'getScoreDetails']);

    // PDF管理（管理画面用）
    Route::prefix('admin/companies/{companyId}/pdfs')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CompanyPdfController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Admin\CompanyPdfController::class, 'store']);
        Route::put('/sort-order', [App\Http\Controllers\Admin\CompanyPdfController::class, 'updateSortOrder']);
        Route::get('/{documentId}', [App\Http\Controllers\Admin\CompanyPdfController::class, 'show']);
        Route::put('/{documentId}', [App\Http\Controllers\Admin\CompanyPdfController::class, 'update']);
        Route::put('/{documentId}/status', [App\Http\Controllers\Admin\CompanyPdfController::class, 'updateStatus']);
        Route::delete('/{documentId}', [App\Http\Controllers\Admin\CompanyPdfController::class, 'destroy']);
        Route::get('/{documentId}/download', [App\Http\Controllers\Admin\CompanyPdfController::class, 'download']);
        Route::get('/{documentId}/preview', [App\Http\Controllers\Admin\CompanyPdfController::class, 'preview']);
    });

    // 会社のアクセスログを取得
    Route::get('admin/companies/{companyId}/view-logs', [App\Http\Controllers\Api\DocumentController::class, 'getCompanyViewLogs']);
});
