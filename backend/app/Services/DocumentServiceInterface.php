<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface DocumentServiceInterface
{
    /**
     * ドキュメント一覧を取得
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getDocumentsPaginated(array $filters, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    /**
     * ドキュメントをアップロード
     *
     * @param UploadedFile $file
     * @param string $title
     * @param string $companyId
     * @param string $userId
     * @return Document
     */
    public function uploadDocument(UploadedFile $file, string $title, string $companyId, string $userId): Document;

    /**
     * ドキュメントを更新
     *
     * @param string $documentId
     * @param array $data
     * @return Document
     */
    public function updateDocument(string $documentId, array $data): Document;

    /**
     * ドキュメントを削除
     *
     * @param string $documentId
     * @return bool
     */
    public function deleteDocument(string $documentId): bool;

    /**
     * ドキュメントの詳細を取得
     *
     * @param string $documentId
     * @return Document|null
     */
    public function getDocumentById(string $documentId): ?Document;

    /**
     * ドキュメントのダウンロードURLを取得
     *
     * @param string $documentId
     * @return string
     */
    public function getDownloadUrl(string $documentId): string;

    /**
     * ドキュメントのプレビューURLを取得
     *
     * @param string $documentId
     * @return string
     */
    public function getPreviewUrl(string $documentId): string;

    /**
     * ドキュメントのソート順を更新
     *
     * @param string $companyId
     * @param array $sortData
     * @return bool
     */
    public function updateSortOrder(string $companyId, array $sortData): bool;

    /**
     * ドキュメントのステータスを更新
     *
     * @param string $documentId
     * @param string $status
     * @return Document
     */
    public function updateDocumentStatus(string $documentId, string $status): Document;

    /**
     * 閲覧ログを記録
     *
     * @param string $companyId
     * @param string $documentId
     * @param array $viewData
     * @return bool
     */
    public function logView(string $companyId, string $documentId, array $viewData): bool;

    /**
     * フィードバックを送信
     *
     * @param string $companyId
     * @param string $documentId
     * @param array $feedbackData
     * @return bool
     */
    public function submitFeedback(string $companyId, string $documentId, array $feedbackData): bool;

    /**
     * 会社のアクティブなドキュメントのみを取得
     *
     * @param string $companyId
     * @return Collection
     */
    public function getActiveDocuments(string $companyId): Collection;

    /**
     * ユーザーがドキュメントにアクセス可能かチェック
     *
     * @param string $documentId
     * @param string $companyId
     * @return bool
     */
    public function canUserAccessDocument(string $documentId, string $companyId): bool;

    /**
     * ドキュメント閲覧ログを記録
     *
     * @param string $documentId
     * @param int $pageNumber
     * @param int $viewDuration
     * @param string $viewerIp
     * @param string|null $userAgent
     * @return array
     */
    public function logDocumentView(string $documentId, int $pageNumber, int $viewDuration, string $viewerIp, ?string $userAgent = null): array;

    /**
     * ドキュメントの閲覧ログを取得
     *
     * @param string $documentId
     * @return array
     */
    public function getDocumentViewLogs(string $documentId): array;

    /**
     * 会社全体の閲覧ログを取得
     *
     * @param string $companyId
     * @return array
     */
    public function getCompanyViewLogs(string $companyId): array;

    /**
     * ドキュメントフィードバックを送信
     *
     * @param string $documentId
     * @param string $feedbackType
     * @param string|null $content
     * @param string $feedbackerIp
     * @param string|null $userAgent
     * @param array|null $feedbackMetadata
     * @return array
     */
    public function submitDocumentFeedback(string $documentId, string $feedbackType, ?string $content, string $feedbackerIp, ?string $userAgent = null, ?array $feedbackMetadata = null): array;

    /**
     * ドキュメントのフィードバックを取得
     *
     * @param string $documentId
     * @return array
     */
    public function getDocumentFeedback(string $documentId): array;
}
