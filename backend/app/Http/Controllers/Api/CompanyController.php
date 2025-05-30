<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    /**
     * 会社一覧を取得
     */
    public function index()
    {
        try {
            // テスト用: 全ステータスの会社を取得（本番前に削除）
            $companies = Company::all();

            // フロントエンドに合わせてシンプルな配列で返す
            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error('会社一覧取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_LIST_ERROR',
                    'message' => '会社一覧の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社詳細を取得
     */
    public function show($id)
    {
        try {
            $company = Company::findOrFail($id);

            // フロントエンドに合わせてdataラッパーなしで返す
            return response()->json($company);
        } catch (\Exception $e) {
            Log::error('会社詳細取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_NOT_FOUND',
                    'message' => '会社が見つかりませんでした',
                    'details' => $e->getMessage()
                ]
            ], 404);
        }
    }

    /**
     * 会社を作成
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:companies,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string|max:1000',
                'industry' => 'nullable|string|max:100',
                'employee_count' => 'nullable|integer|min:1',
            ]);

            $company = Company::create($validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('会社作成エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_CREATE_ERROR',
                    'message' => '会社の作成に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社を更新
     */
    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:companies,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string|max:1000',
                'industry' => 'nullable|string|max:100',
                'employee_count' => 'nullable|integer|min:1',
                'status' => 'sometimes|in:active,inactive',
            ]);

            $company->update($validated);

            return response()->json([
                'data' => $company,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('会社更新エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_UPDATE_ERROR',
                    'message' => '会社の更新に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社を削除
     */
    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->delete();

            return response()->json([
                'message' => '会社が正常に削除されました',
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('会社削除エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'COMPANY_DELETE_ERROR',
                    'message' => '会社の削除に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
