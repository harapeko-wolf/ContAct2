<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/admin')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('companies/{companyId}/pdfs')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CompanyPdfController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Admin\CompanyPdfController::class, 'store']);
        Route::get('/{documentId}', [App\Http\Controllers\Admin\CompanyPdfController::class, 'show']);
        Route::put('/{documentId}', [App\Http\Controllers\Admin\CompanyPdfController::class, 'update']);
        Route::delete('/{documentId}', [App\Http\Controllers\Admin\CompanyPdfController::class, 'destroy']);
        Route::get('/{documentId}/download', [App\Http\Controllers\Admin\CompanyPdfController::class, 'download']);
        Route::get('/{documentId}/preview', [App\Http\Controllers\Admin\CompanyPdfController::class, 'preview']);
    });

    // 会社のアクセスログを取得
    Route::get('companies/{companyId}/view-logs', [App\Http\Controllers\Api\DocumentController::class, 'getCompanyViewLogs']);
});
