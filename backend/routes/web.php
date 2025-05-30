<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 公開PDF配信（認証なし）
Route::get('api/public/companies/{companyId}/pdfs', [App\Http\Controllers\Admin\CompanyPdfController::class, 'publicIndex']);
Route::get('api/public/companies/{companyId}/pdfs/{documentId}/preview', [App\Http\Controllers\Admin\CompanyPdfController::class, 'publicPreview']);
