<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\AuthorizationTrait;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Requests\LogDocumentViewRequest;
use App\Http\Requests\SubmitDocumentFeedbackRequest;
use App\Models\Document;
use App\Services\DocumentServiceInterface;
use App\Services\FileManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DocumentController extends BaseApiController
{
    use AuthorizationTrait;

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
    public function index(Request $request): JsonResponse
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

            return $this->successResponse($documents);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ドキュメント一覧の取得に失敗しました', 'ドキュメント一覧取得エラー');
        }
    }

    /**
     * 資料をアップロード
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        try {
            $document = $this->documentService->uploadDocument(
                $request->file('file'),
                $request->validated()['title'],
                $request->user()->company_id,
                $request->user()->id
            );

            return $this->createdResponse($document);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ドキュメントのアップロードに失敗しました', 'ドキュメントアップロードエラー');
        }
    }

    /**
     * 資料の詳細を取得
     */
    public function show(Document $document): JsonResponse
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return $this->notFoundResponse('ドキュメントが見つかりませんでした');
            }

            return $this->successResponse($document);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ドキュメントが見つかりませんでした', 'ドキュメント詳細取得エラー');
        }
    }

    /**
     * 資料を更新
     */
    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, $request->user()->company_id)) {
                return $this->notFoundResponse('ドキュメントが見つかりませんでした');
            }

            $updatedDocument = $this->documentService->updateDocument($document->id, $request->validated());

            return $this->successResponse($updatedDocument);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ドキュメントの更新に失敗しました', 'ドキュメント更新エラー');
        }
    }

    /**
     * 資料を削除
     */
    public function destroy(Document $document): JsonResponse
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return $this->notFoundResponse('ドキュメントが見つかりませんでした');
            }

            $this->documentService->deleteDocument($document->id);

            return $this->deletedResponse();
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ドキュメントの削除に失敗しました', 'ドキュメント削除エラー');
        }
    }

    /**
     * 資料をダウンロード
     */
    public function download(Document $document): JsonResponse
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return $this->notFoundResponse('ドキュメントが見つかりませんでした');
            }

            $url = $this->fileManagementService->getDownloadUrlWithFileName($document->file_path, $document->file_name);

            return $this->successResponse(['url' => $url]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ドキュメントのダウンロードに失敗しました', 'ドキュメントダウンロードエラー');
        }
    }

    /**
     * 資料のプレビューURLを取得
     */
    public function preview(Document $document): JsonResponse
    {
        try {
            // 権限チェック
            if (!$this->documentService->canUserAccessDocument($document->id, request()->user()->company_id)) {
                return $this->notFoundResponse('ドキュメントが見つかりませんでした');
            }

            $url = $this->fileManagementService->getPreviewUrlWithFileName($document->file_path, $document->file_name);

            return $this->successResponse(['url' => $url]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'ドキュメントのプレビューに失敗しました', 'ドキュメントプレビューエラー');
        }
    }

    /**
     * PDF閲覧ログを記録
     */
    public function logView(LogDocumentViewRequest $request, $companyId, $documentId): JsonResponse
    {
        try {
            $viewLog = $this->documentService->logDocumentView(
                $documentId,
                $request->validated()['page_number'],
                $request->validated()['view_duration'],
                $request->ip(),
                $request->userAgent()
            );

            return $this->createdResponse($viewLog);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '閲覧ログの記録に失敗しました', '閲覧ログ記録エラー');
        }
    }

    /**
     * PDF閲覧ログを取得
     */
    public function getViewLogs(Request $request, Document $document): JsonResponse
    {
        try {
            $viewLogs = $this->documentService->getDocumentViewLogs($document->id);

            return $this->successResponse($viewLogs);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '閲覧ログの取得に失敗しました', '閲覧ログ取得エラー');
        }
    }

    /**
     * 会社全体の閲覧ログを取得
     */
    public function getCompanyViewLogs(Request $request, $companyId): JsonResponse
    {
        try {
            // 権限チェック：管理者は全ての会社のアクセスログを閲覧可能、一般ユーザーは自分の会社のみ
            if ($authError = $this->ensureCompanyDocumentAccess($companyId)) {
                return $authError;
            }

            $viewLogs = $this->documentService->getCompanyViewLogs($companyId);

            return $this->successResponse($viewLogs);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, '会社閲覧ログの取得に失敗しました', '会社閲覧ログ取得エラー');
        }
    }

    /**
     * フィードバックを送信
     */
    public function submitFeedback(SubmitDocumentFeedbackRequest $request, $companyId, $documentId): JsonResponse
    {
        try {
            $validated = $request->validated();

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

            return $this->createdResponse($feedback);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'フィードバックの送信に失敗しました', 'フィードバック送信エラー');
        }
    }

    /**
     * フィードバックを取得
     */
    public function getFeedback(Request $request, $companyId, $documentId): JsonResponse
    {
        try {
            $feedback = $this->documentService->getDocumentFeedback($documentId);

            return $this->successResponse($feedback);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e, 'フィードバックの取得に失敗しました', 'フィードバック取得エラー');
        }
    }
}
