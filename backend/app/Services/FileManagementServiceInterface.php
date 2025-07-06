<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

interface FileManagementServiceInterface
{
    /**
     * ファイルをS3にアップロード
     *
     * @param UploadedFile $file
     * @param string $path
     * @return array
     */
    public function uploadFile(UploadedFile $file, string $path): array;

    /**
     * ファイルをS3から削除
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool;

    /**
     * ファイルの存在確認
     *
     * @param string $path
     * @return bool
     */
    public function fileExists(string $path): bool;

    /**
     * ファイルの内容を取得
     *
     * @param string $path
     * @return string|null
     */
    public function getFileContent(string $path): ?string;

    /**
     * 署名付きURLを生成（ダウンロード用）
     *
     * @param string $path
     * @param string $fileName
     * @param int $expiration
     * @return string
     */
    public function generateDownloadUrl(string $path, string $fileName, int $expiration = 3600): string;

    /**
     * 署名付きURLを生成（プレビュー用）
     *
     * @param string $path
     * @param string $fileName
     * @param int $expiration
     * @return string
     */
    public function generatePreviewUrl(string $path, string $fileName, int $expiration = 86400): string;

    /**
     * ファイルパスを生成
     *
     * @param string $companyId
     * @param string $fileName
     * @param string $environment
     * @return string
     */
    public function generateFilePath(string $companyId, string $fileName, string $environment): string;

    /**
     * 一意のファイル名を生成
     *
     * @param string $originalExtension
     * @return string
     */
    public function generateUniqueFileName(string $originalExtension): string;

    /**
     * ファイルのMIMEタイプを検証
     *
     * @param UploadedFile $file
     * @param array $allowedTypes
     * @return bool
     */
    public function validateFileType(UploadedFile $file, array $allowedTypes): bool;

    /**
     * ファイルサイズを検証
     *
     * @param UploadedFile $file
     * @param int $maxSize
     * @return bool
     */
    public function validateFileSize(UploadedFile $file, int $maxSize): bool;

    /**
     * ファイルのURLを取得（プレビュー用）
     *
     * @param string $path
     * @return string
     */
    public function getFileUrl(string $path): string;

    /**
     * ダウンロード用URLを取得
     *
     * @param string $path
     * @return string
     */
    public function getDownloadUrl(string $path): string;

    /**
     * ダウンロード用URLを取得（ファイル名指定）
     *
     * @param string $filePath
     * @param string $fileName
     * @return string
     */
    public function getDownloadUrlWithFileName(string $filePath, string $fileName): string;

    /**
     * プレビュー用URLを取得（ファイル名指定）
     *
     * @param string $filePath
     * @param string $fileName
     * @return string
     */
    public function getPreviewUrlWithFileName(string $filePath, string $fileName): string;
}
