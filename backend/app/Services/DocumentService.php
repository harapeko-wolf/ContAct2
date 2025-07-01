<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DocumentService
{
    /**
     * ドキュメントをアップロード
     */
    public function uploadDocument(array $data, UploadedFile $file, string $companyId, ?string $userId = null): Document
    {
        try {
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
                'title' => $data['title'],
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'active',
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_by' => $userId,
                    'environment' => $env,
                ],
            ]);

            return $document;

        } catch (\Exception $e) {
            Log::error('ドキュメントアップロードエラー: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ドキュメントを更新
     */
    public function updateDocument(Document $document, array $data): Document
    {
        try {
            $document->update($data);
            return $document->fresh();

        } catch (\Exception $e) {
            Log::error('ドキュメント更新エラー: ' . $e->getMessage(), ['document_id' => $document->id]);
            throw $e;
        }
    }

    /**
     * ドキュメントを削除
     */
    public function deleteDocument(Document $document): bool
    {
        try {
            // S3からファイルを削除
            Storage::disk('s3')->delete($document->file_path);

            // データベースから削除
            $document->delete();

            return true;

        } catch (\Exception $e) {
            Log::error('ドキュメント削除エラー: ' . $e->getMessage(), ['document_id' => $document->id]);
            throw $e;
        }
    }

    /**
     * プレビューURLを生成
     */
    public function generatePreviewUrl(Document $document, int $hoursValid = 24): string
    {
        try {
            // ファイルが存在するかチェック
            if (!Storage::disk('s3')->exists($document->file_path)) {
                throw new \Exception('ファイルが見つかりません');
            }

            // 署名付きURLを生成
            return Storage::disk('s3')->temporaryUrl(
                $document->file_path,
                now()->addHours($hoursValid),
                [
                    'ResponseContentDisposition' => 'inline; filename="' . $document->file_name . '"',
                ]
            );

        } catch (\Exception $e) {
            Log::error('プレビューURL生成エラー: ' . $e->getMessage(), ['document_id' => $document->id]);
            throw $e;
        }
    }

    /**
     * ダウンロードURLを生成
     */
    public function generateDownloadUrl(Document $document, int $hoursValid = 1): string
    {
        try {
            // ファイルが存在するかチェック
            if (!Storage::disk('s3')->exists($document->file_path)) {
                throw new \Exception('ファイルが見つかりません');
            }

            // 署名付きURLを生成
            return Storage::disk('s3')->temporaryUrl(
                $document->file_path,
                now()->addHours($hoursValid),
                [
                    'ResponseContentDisposition' => 'attachment; filename="' . $document->file_name . '"',
                ]
            );

        } catch (\Exception $e) {
            Log::error('ダウンロードURL生成エラー: ' . $e->getMessage(), ['document_id' => $document->id]);
            throw $e;
        }
    }

    /**
     * ドキュメントの内容を直接取得（プレビュー用）
     */
    public function getDocumentContent(Document $document): string
    {
        try {
            // ファイルが存在するかチェック
            if (!Storage::disk('s3')->exists($document->file_path)) {
                throw new \Exception('ファイルが見つかりません');
            }

            return Storage::disk('s3')->get($document->file_path);

        } catch (\Exception $e) {
            Log::error('ドキュメント内容取得エラー: ' . $e->getMessage(), ['document_id' => $document->id]);
            throw $e;
        }
    }

    /**
     * ドキュメントの並び順を更新
     */
    public function updateSortOrder(string $companyId, array $documentOrders): bool
    {
        try {
            foreach ($documentOrders as $order) {
                Document::where('company_id', $companyId)
                    ->where('id', $order['id'])
                    ->update(['sort_order' => $order['sort_order']]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('ドキュメント並び順更新エラー: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'orders' => $documentOrders
            ]);
            throw $e;
        }
    }

    /**
     * ドキュメントのステータスを更新
     */
    public function updateStatus(Document $document, string $status): Document
    {
        try {
            $document->update(['status' => $status]);
            return $document->fresh();

        } catch (\Exception $e) {
            Log::error('ドキュメントステータス更新エラー: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'status' => $status
            ]);
            throw $e;
        }
    }

    /**
     * 会社のドキュメント一覧を取得
     */
    public function getDocumentsByCompany(string $companyId, int $perPage = 10): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            return Document::where('company_id', $companyId)
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('会社ドキュメント一覧取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            throw $e;
        }
    }

    /**
     * 公開ドキュメント一覧を取得
     */
    public function getPublicDocumentsByCompany(string $companyId): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Document::where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

        } catch (\Exception $e) {
            Log::error('公開ドキュメント一覧取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            throw $e;
        }
    }

    /**
     * ドキュメントの存在確認
     */
    public function documentExists(Document $document): bool
    {
        return Storage::disk('s3')->exists($document->file_path);
    }

    /**
     * ドキュメントファイルサイズを取得
     */
    public function getDocumentFileSize(Document $document): int
    {
        try {
            if (!$this->documentExists($document)) {
                throw new \Exception('ファイルが見つかりません');
            }

            return Storage::disk('s3')->size($document->file_path);

        } catch (\Exception $e) {
            Log::error('ドキュメントファイルサイズ取得エラー: ' . $e->getMessage(), ['document_id' => $document->id]);
            return 0;
        }
    }
}
