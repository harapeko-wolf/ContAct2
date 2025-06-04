<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentView;
use App\Models\DocumentFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Spatie\PdfToText\Pdf;

class DocumentController extends Controller
{
    /**
     * 資料一覧を取得
     */
    public function index(Request $request)
    {
        $query = Document::query()
            ->where('company_id', $request->user()->company_id)
            ->orderBy('created_at', 'desc');

        // 検索条件
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        // ステータスフィルター
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $documents = $query->paginate(10);

        return response()->json($documents);
    }

    /**
     * 資料をアップロード
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:51200', // 最大50MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $fileName = Str::uuid() . '.pdf';
        $env = config('app.env', 'local');
        $filePath = sprintf('%s/companies/%s/pdfs/%s', $env, $request->user()->company_id, $fileName);

        // S3にアップロード
        Storage::disk('s3')->put($filePath, file_get_contents($file->getPathname()));

        $document = Document::create([
            'company_id' => $request->user()->company_id,
            'title' => $request->input('title'),
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

        return response()->json($document, 201);
    }

    /**
     * 資料の詳細を取得
     */
    public function show(Document $document)
    {
        // 権限チェック
        if ($document->company_id !== request()->user()->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($document);
    }

    /**
     * 資料を更新
     */
    public function update(Request $request, Document $document)
    {
        // 権限チェック
        if ($document->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
     * 資料を削除
     */
    public function destroy(Document $document)
    {
        // 権限チェック
        if ($document->company_id !== request()->user()->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // S3からファイルを削除
        Storage::disk('s3')->delete($document->file_path);

        // データベースから削除
        $document->delete();

        return response()->json(null, 204);
    }

    /**
     * 資料をダウンロード
     */
    public function download(Document $document)
    {
        // 権限チェック
        if ($document->company_id !== request()->user()->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
     * 資料のプレビューURLを取得
     */
    public function preview(Document $document)
    {
        // 権限チェック
        if ($document->company_id !== request()->user()->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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

    /**
     * PDF閲覧ログを記録
     */
    public function logView(Request $request, $companyId, $documentId)
    {
        $validator = Validator::make($request->all(), [
            'page_number' => 'required|integer|min:1',
            'view_duration' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $document = Document::where('company_id', $companyId)
            ->where('id', $documentId)
            ->firstOrFail();

        $view = DocumentView::create([
            'id' => Str::uuid(),
            'document_id' => $document->id,
            'viewer_ip' => $request->ip(),
            'viewer_user_agent' => $request->userAgent(),
            'page_number' => $request->input('page_number'),
            'view_duration' => $request->input('view_duration'),
            'viewed_at' => now(),
            'viewer_metadata' => [
                'user_id' => $request->user() ? $request->user()->id : null,
                'company_id' => $document->company_id,
            ],
        ]);

        return response()->json($view, 201);
    }

    /**
     * PDF閲覧ログを取得
     */
    public function getViewLogs(Request $request, Document $document)
    {
        $query = DocumentView::where('document_id', $document->id)
            ->orderBy('viewed_at', 'desc');

        // ページごとの集計
        if ($request->has('group_by_page')) {
            $logs = $query->selectRaw('
                page_number,
                COUNT(*) as view_count,
                AVG(view_duration) as avg_duration,
                MAX(viewed_at) as last_viewed
            ')
            ->groupBy('page_number')
            ->get();

            return response()->json($logs);
        }

        // 通常のログ一覧
        $logs = $query->paginate(20);
        return response()->json($logs);
    }

    /**
     * 会社の全PDF閲覧ログを取得
     */
    public function getCompanyViewLogs(Request $request, $companyId)
    {
        $query = DocumentView::whereHas('document', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->orderBy('viewed_at', 'desc');

        // ページごとの集計
        if ($request->has('group_by_page')) {
            $logs = $query->selectRaw('
                page_number,
                COUNT(*) as view_count,
                AVG(view_duration) as avg_duration,
                MAX(viewed_at) as last_viewed
            ')
            ->groupBy('page_number')
            ->get();

            return response()->json($logs);
        }

        // 通常のログ一覧
        $logs = $query->paginate(20);
        return response()->json($logs);
    }

    /**
     * フィードバックを送信（認証不要）
     */
    public function submitFeedback(Request $request, $companyId, $documentId)
    {
        $validator = Validator::make($request->all(), [
            'feedback_type' => 'required|string|max:100',
            'content' => 'sometimes|string|max:1000',
            'interest_level' => 'sometimes|integer|min:0|max:100',
            'selected_option' => 'sometimes|array',
            'selected_option.id' => 'sometimes|integer',
            'selected_option.label' => 'sometimes|string|max:255',
            'selected_option.score' => 'sometimes|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => '入力内容に誤りがあります',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        try {
            $document = Document::where('company_id', $companyId)
                ->where('id', $documentId)
                ->firstOrFail();

            $feedback = DocumentFeedback::create([
                'id' => Str::uuid(),
                'document_id' => $document->id,
                'feedback_type' => $request->input('feedback_type'),
                'content' => $request->input('content'),
                'feedbacker_ip' => $request->ip(),
                'feedbacker_user_agent' => $request->userAgent(),
                'feedback_metadata' => [
                    'interest_level' => $request->input('interest_level'),
                    'selected_option' => $request->input('selected_option'),
                    'company_id' => $document->company_id,
                    'submitted_at' => now()->toISOString(),
                ],
            ]);

            return response()->json([
                'data' => [
                    'id' => $feedback->id,
                    'message' => 'フィードバックが送信されました',
                    'feedback_summary' => [
                        'option_label' => $request->input('selected_option.label'),
                        'score' => $request->input('selected_option.score', $request->input('interest_level')),
                        'content' => $request->input('content'),
                    ]
                ],
                'meta' => [
                    'timestamp' => now()->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'FEEDBACK_SUBMISSION_ERROR',
                    'message' => 'フィードバックの送信に失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * ドキュメントのフィードバック一覧を取得
     */
    public function getFeedback(Request $request, $companyId, $documentId)
    {
        try {
            $document = Document::where('company_id', $companyId)
                ->where('id', $documentId)
                ->firstOrFail();

            $query = DocumentFeedback::where('document_id', $document->id)
                ->orderBy('created_at', 'desc');

            // フィードバック種別でフィルタ
            if ($request->has('feedback_type')) {
                $query->where('feedback_type', $request->input('feedback_type'));
            }

            $feedback = $query->paginate(20);

            return response()->json([
                'data' => $feedback,
                'meta' => [
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'FEEDBACK_FETCH_ERROR',
                    'message' => 'フィードバックの取得に失敗しました',
                    'details' => ['error' => $e->getMessage()]
                ]
            ], 500);
        }
    }
}
