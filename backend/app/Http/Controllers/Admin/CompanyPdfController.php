<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CompanyPdfController extends Controller
{
    /**
     * 会社のPDF一覧を取得
     */
    public function index($companyId)
    {
        try {
            $company = Company::findOrFail($companyId);
            $documents = Document::where('company_id', $companyId)
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json($documents);
        } catch (\Exception $e) {
            Log::error('PDF一覧取得エラー: ' . $e->getMessage());
            return response()->json([
                'message' => 'PDF一覧の取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PDFをアップロード
     */
    public function store(Request $request, string $companyId)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf|max:10240', // 10MBまで
            ]);

            $file = $request->file('file');
            $fileName = Str::uuid() . '.pdf';
            $env = app()->environment();
            $filePath = sprintf('%s/companies/%s/pdfs/%s', $env, $companyId, $fileName);

            // S3にアップロード
            if (!Storage::disk('s3')->put($filePath, file_get_contents($file->getPathname()))) {
                throw new \Exception('ファイルのアップロードに失敗しました');
            }

            // データベースに保存
            $document = Document::create([
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'title' => $request->title,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'active',
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_by' => $request->user()->id,
                    'environment' => $env,
                ],
            ]);

            return response()->json([
                'message' => 'PDFが正常にアップロードされました',
                'document' => $document
            ], 201);

        } catch (\Exception $e) {
            Log::error('PDFアップロードエラー: ' . $e->getMessage());
            return response()->json([
                'message' => 'PDFのアップロードに失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PDFの詳細を取得
     */
    public function show($companyId, $documentId)
    {
        $document = Document::where('company_id', $companyId)
            ->findOrFail($documentId);

        return response()->json($document);
    }

    /**
     * PDFの情報を更新
     */
    public function update(Request $request, $companyId, $documentId)
    {
        $document = Document::where('company_id', $companyId)
            ->findOrFail($documentId);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $document->update($request->only(['title', 'status']));

        return response()->json($document);
    }

    /**
     * PDFを削除
     */
    public function destroy($companyId, $documentId)
    {
        $document = Document::where('company_id', $companyId)
            ->findOrFail($documentId);

        // S3からファイルを削除
        Storage::disk('s3')->delete($document->file_path);

        // データベースから削除
        $document->delete();

        return response()->json(null, 204);
    }

    /**
     * PDFのダウンロードURLを取得
     */
    public function download($companyId, $documentId)
    {
        try {
            $document = Document::where('company_id', $companyId)
                ->findOrFail($documentId);

            // ファイルが存在するかチェック
            if (!Storage::disk('s3')->exists($document->file_path)) {
                return response()->json(['error' => 'ファイルが見つかりません'], 404);
            }

            // ファイルの内容を取得
            $fileContent = Storage::disk('s3')->get($document->file_path);

            return response($fileContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $document->file_name . '"',
                'Content-Length' => strlen($fileContent),
            ]);
        } catch (\Exception $e) {
            Log::error('PDF ダウンロードエラー: ' . $e->getMessage());
            return response()->json(['error' => 'ダウンロードに失敗しました'], 500);
        }
    }

    /**
     * PDFのプレビューURLを取得
     */
    public function preview($companyId, $documentId)
    {
        try {
            $document = Document::where('company_id', $companyId)
                ->findOrFail($documentId);

            // ファイルが存在するかチェック
            if (!Storage::disk('s3')->exists($document->file_path)) {
                return response()->json(['error' => 'ファイルが見つかりません'], 404);
            }

            // ファイルの内容を取得
            $fileContent = Storage::disk('s3')->get($document->file_path);

            return response($fileContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
                'Content-Length' => strlen($fileContent),
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF プレビューエラー: ' . $e->getMessage());
            return response()->json(['error' => 'プレビューに失敗しました'], 500);
        }
    }

    /**
     * PDFの並び順を更新
     */
    public function updateSortOrder(Request $request, string $companyId)
    {
        try {
            $request->validate([
                'documents' => 'required|array',
                'documents.*.id' => 'required|string',
                'documents.*.sort_order' => 'required|integer',
            ]);

            $documents = $request->input('documents');

            foreach ($documents as $docData) {
                Document::where('company_id', $companyId)
                    ->where('id', $docData['id'])
                    ->update(['sort_order' => $docData['sort_order']]);
            }

            return response()->json([
                'message' => 'PDF並び順が正常に更新されました',
            ]);

        } catch (\Exception $e) {
            Log::error('PDF並び順更新エラー: ' . $e->getMessage());
            return response()->json([
                'message' => 'PDF並び順の更新に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 公開PDFプレビュー（認証なし）
     */
    public function publicPreview($companyId, $documentId)
    {
        try {
            $document = Document::where('company_id', $companyId)
                ->where('status', 'active')
                ->findOrFail($documentId);

            // ファイルが存在するかチェック
            if (!Storage::disk('s3')->exists($document->file_path)) {
                abort(404, 'ファイルが見つかりません');
            }

            // ファイルの内容を取得
            $fileContent = Storage::disk('s3')->get($document->file_path);

            return response($fileContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
                'Content-Length' => strlen($fileContent),
                'Cache-Control' => 'public, max-age=3600',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            ]);
        } catch (\Exception $e) {
            Log::error('公開PDF プレビューエラー: ' . $e->getMessage());
            abort(500, 'プレビューに失敗しました');
        }
    }

    /**
     * 公開PDF一覧取得（認証なし）
     */
    public function publicIndex($companyId)
    {
        try {
            $company = Company::findOrFail($companyId);
            $documents = Document::where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $documents,
                'company' => $company
            ]);
        } catch (\Exception $e) {
            Log::error('公開PDF一覧取得エラー: ' . $e->getMessage());
            return response()->json([
                'message' => 'PDF一覧の取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PDFのステータスを更新
     */
    public function updateStatus(Request $request, $companyId, $documentId)
    {
        try {
            $request->validate([
                'status' => 'required|in:active,inactive',
            ]);

            $document = Document::where('company_id', $companyId)
                ->findOrFail($documentId);

            $document->update(['status' => $request->status]);

            return response()->json([
                'message' => 'ステータスが正常に更新されました',
                'document' => $document
            ]);

        } catch (\Exception $e) {
            Log::error('PDFステータス更新エラー: ' . $e->getMessage());
            return response()->json([
                'message' => 'ステータスの更新に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
