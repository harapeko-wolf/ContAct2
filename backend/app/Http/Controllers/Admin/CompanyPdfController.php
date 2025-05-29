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
        $company = Company::findOrFail($companyId);
        $documents = Document::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($documents);
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
        $document = Document::where('company_id', $companyId)
            ->findOrFail($documentId);

        // 署名付きURLを生成（1時間有効）
        $url = Storage::disk('s3')->temporaryUrl(
            $document->file_path,
            now()->addHour(),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $document->file_name . '"',
            ]
        );

        return response()->json(['url' => $url]);
    }

    /**
     * PDFのプレビューURLを取得
     */
    public function preview($companyId, $documentId)
    {
        $document = Document::where('company_id', $companyId)
            ->findOrFail($documentId);

        // 署名付きURLを生成（24時間有効）
        $url = Storage::disk('s3')->temporaryUrl(
            $document->file_path,
            now()->addDay(),
            [
                'ResponseContentDisposition' => 'inline; filename="' . $document->file_name . '"',
            ]
        );

        return response()->json(['url' => $url]);
    }
}
