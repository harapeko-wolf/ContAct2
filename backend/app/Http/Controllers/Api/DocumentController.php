<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentServiceInterface;
use App\Services\FileManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    private DocumentServiceInterface $documentService;
    private FileManagementServiceInterface $fileManagementService;

    public function __construct(
        DocumentServiceInterface $documentService,
        FileManagementServiceInterface $fileManagementService
    ) {
        $this->documentService = $documentService;
        $this->fileManagementService = $fileManagementService;
    }

    /**
     * 資料一覧を取得
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'company_id' => $request->user()->company_id
            ];

            $documents = $this->documentService->getDocumentsPaginated(
                $filters,
                10,
                $request->get('page', 1)
            );

            return response()->json($documents);
        } catch (\Exception $e) {
            Log::error('ドキュメント一覧取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_LIST_ERROR',
                    'message' => 'ドキュメント一覧の取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 資料をアップロード
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf|max:51200', // 最大50MB
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        }

        try {
            $document = $this->documentService->uploadDocument(
                $request->file('file'),
                $validated['title'],
                $request->user()->company_id,
                $request->user()->id
            );

            return response()->json([
                'data' => $document,
                'meta' => [
                    'timestamp' => now()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('ドキュメントアップロードエラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_UPLOAD_ERROR',
                    'message' => 'ドキュメントのアップロードに失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 資料の詳細を取得
     */
    public function show(Document $document)
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return response()->json([
                    'error' => [
                        'code' => 'DOCUMENT_NOT_FOUND',
                        'message' => 'ドキュメントが見つかりませんでした',
                    ]
                ], 404);
            }

            return response()->json([
                'data' => $document,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ドキュメント詳細取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_NOT_FOUND',
                    'message' => 'ドキュメントが見つかりませんでした',
                    'details' => $e->getMessage()
                ]
            ], 404);
        }
    }

    /**
     * 資料を更新
     */
    public function update(Request $request, Document $document)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'status' => 'sometimes|in:active,inactive',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        }

        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, $request->user()->company_id)) {
                return response()->json([
                    'error' => [
                        'code' => 'DOCUMENT_NOT_FOUND',
                        'message' => 'ドキュメントが見つかりませんでした',
                    ]
                ], 404);
            }

            $updatedDocument = $this->documentService->updateDocument($document->id, $validated);

            return response()->json([
                'data' => $updatedDocument,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ドキュメント更新エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_UPDATE_ERROR',
                    'message' => 'ドキュメントの更新に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 資料を削除
     */
    public function destroy(Document $document)
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return response()->json([
                    'error' => [
                        'code' => 'DOCUMENT_NOT_FOUND',
                        'message' => 'ドキュメントが見つかりませんでした',
                    ]
                ], 404);
            }

            $this->documentService->deleteDocument($document->id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('ドキュメント削除エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_DELETE_ERROR',
                    'message' => 'ドキュメントの削除に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 資料をダウンロード
     */
    public function download(Document $document)
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return response()->json([
                    'error' => [
                        'code' => 'DOCUMENT_NOT_FOUND',
                        'message' => 'ドキュメントが見つかりませんでした',
                    ]
                ], 404);
            }

            $url = $this->fileManagementService->getDownloadUrlWithFileName($document->file_path, $document->file_name);

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            Log::error('ドキュメントダウンロードエラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_DOWNLOAD_ERROR',
                    'message' => 'ドキュメントのダウンロードに失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 資料のプレビューURLを取得
     */
    public function preview(Document $document)
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return response()->json([
                    'error' => [
                        'code' => 'DOCUMENT_NOT_FOUND',
                        'message' => 'ドキュメントが見つかりませんでした',
                    ]
                ], 404);
            }

            $url = $this->fileManagementService->getPreviewUrlWithFileName($document->file_path, $document->file_name);

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            Log::error('ドキュメントプレビューエラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_PREVIEW_ERROR',
                    'message' => 'ドキュメントのプレビューに失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * PDF閲覧ログを記録
     */
    public function logView(Request $request, $companyId, $documentId)
    {
        try {
            $validated = $request->validate([
                'page_number' => 'required|integer|min:1',
                'view_duration' => 'required|integer|min:0',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        }

        try {
            $viewLog = $this->documentService->logDocumentView(
                $documentId,
                $validated['page_number'],
                $validated['view_duration'],
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'data' => $viewLog,
                'meta' => [
                    'timestamp' => now()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('閲覧ログ記録エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'VIEW_LOG_ERROR',
                    'message' => '閲覧ログの記録に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * PDF閲覧ログを取得
     */
    public function getViewLogs(Request $request, Document $document)
    {
        try {
            $viewLogs = $this->documentService->getDocumentViewLogs($document->id);

            return response()->json([
                'data' => $viewLogs,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('閲覧ログ取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'VIEW_LOG_ERROR',
                    'message' => '閲覧ログの取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * 会社全体の閲覧ログを取得
     */
    public function getCompanyViewLogs(Request $request, $companyId)
    {
        try {
            // 権限チェック：管理者は全ての会社のアクセスログを閲覧可能、一般ユーザーは自分の会社のみ
            $user = $request->user();
            if (!$user->isAdmin() && $companyId !== $user->company_id) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'アクセス権限がありません',
                    ]
                ], 403);
            }

            $viewLogs = $this->documentService->getCompanyViewLogs($companyId);

            return response()->json([
                'data' => $viewLogs,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('会社閲覧ログ取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'VIEW_LOG_ERROR',
                    'message' => '会社閲覧ログの取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * フィードバックを送信
     */
    public function submitFeedback(Request $request, $companyId, $documentId)
    {
        try {
            // 興味度アンケート用のバリデーションルールに変更（フロントエンドのデータ構造に合わせる）
            $validated = $request->validate([
                'selected_option' => 'required|array',
                'selected_option.id' => 'required|integer',
                'selected_option.label' => 'required|string',
                'selected_option.score' => 'required|integer',
                'feedback_type' => 'sometimes|in:survey,rating,comment,survey_response',
                'content' => 'nullable|string|max:1000',
                'interest_level' => 'sometimes|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラーが発生しました',
                    'details' => $e->errors()
                ]
            ], 422);
        }

        try {
            // 興味度アンケートデータを適切なフォーマットに変換
            $feedbackType = $validated['feedback_type'] ?? 'survey';
            $feedbackMetadata = [
                'selected_option' => $validated['selected_option'],
                'survey_response' => true,
                'interest_level' => $validated['interest_level'] ?? null
            ];

            $feedback = $this->documentService->submitDocumentFeedback(
                $documentId,
                $feedbackType,
                $validated['content'] ?? null,
                $request->ip(),
                $request->userAgent(),
                $feedbackMetadata
            );

            return response()->json([
                'data' => $feedback,
                'meta' => [
                    'timestamp' => now()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('フィードバック送信エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'FEEDBACK_ERROR',
                    'message' => 'フィードバックの送信に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * フィードバックを取得
     */
    public function getFeedback(Request $request, $companyId, $documentId)
    {
        try {
            $feedback = $this->documentService->getDocumentFeedback($documentId);

            return response()->json([
                'data' => $feedback,
                'meta' => [
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('フィードバック取得エラー: ' . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'FEEDBACK_ERROR',
                    'message' => 'フィードバックの取得に失敗しました',
                    'details' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
