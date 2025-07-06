<?php

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface
{
    /**
     * 会社のドキュメント一覧を取得
     *
     * @param string $companyId
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginatedDocumentsByCompany(string $companyId, int $perPage = 10, array $filters = []): LengthAwarePaginator;

    /**
     * ドキュメントを作成
     *
     * @param array $data
     * @return Document
     */
    public function create(array $data): Document;

    /**
     * ドキュメントを更新
     *
     * @param string $id
     * @param array $data
     * @return Document
     */
    public function update(string $id, array $data): Document;

    /**
     * ドキュメントを削除
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * ドキュメントを検索
     *
     * @param string $id
     * @return Document|null
     */
    public function findById(string $id): ?Document;

    /**
     * 会社とドキュメントIDでドキュメントを検索
     *
     * @param string $companyId
     * @param string $documentId
     * @return Document|null
     */
    public function findByCompanyAndId(string $companyId, string $documentId): ?Document;

    /**
     * 会社のドキュメント一覧を取得（ソート順付き）
     *
     * @param string $companyId
     * @return Collection
     */
    public function getDocumentsByCompanyWithSort(string $companyId): Collection;

    /**
     * ドキュメントのソート順を更新
     *
     * @param array $sortData
     * @return bool
     */
    public function updateSortOrder(array $sortData): bool;

    /**
     * ドキュメントのステータスを更新
     *
     * @param string $id
     * @param string $status
     * @return Document
     */
    public function updateStatus(string $id, string $status): Document;

    /**
     * アクティブなドキュメントのみを取得
     *
     * @param string $companyId
     * @return Collection
     */
    public function getActiveDocumentsByCompany(string $companyId): Collection;

    /**
     * 総ドキュメント数を取得
     *
     * @return int
     */
    public function count(): int;

    /**
     * 会社のドキュメント一覧をページネーション付きで取得（ソート対応）
     *
     * @param string $companyId
     * @param int $perPage
     * @param int $page
     * @param string $sortBy
     * @param string $sortDirection
     * @return LengthAwarePaginator
     */
    public function getDocumentsByCompany(
        string $companyId,
        int $perPage = 10,
        int $page = 1,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator;

    /**
     * ドキュメントを会社情報付きで取得
     *
     * @param string $id
     * @return Document|null
     */
    public function findWithCompany(string $id): ?Document;

    /**
     * ステータス別ドキュメント一覧を取得
     *
     * @param string $status
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getDocumentsByStatus(string $status, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    /**
     * ドキュメント検索
     *
     * @param string $companyId
     * @param string $query
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function searchDocuments(string $companyId, string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    /**
     * ドキュメントのメトリクスを取得
     *
     * @param string $documentId
     * @return array
     */
    public function getDocumentMetrics(string $documentId): array;
}
