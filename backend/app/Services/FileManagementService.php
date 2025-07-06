<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManagementService implements FileManagementServiceInterface
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 's3');
    }

    public function uploadFile(UploadedFile $file, string $path): array
    {
        $fileName = $this->generateUniqueFileName($file->getClientOriginalExtension());
        $fullPath = trim($path, '/') . '/' . $fileName;

        $uploaded = Storage::disk($this->disk)->putFileAs(
            dirname($fullPath),
            $file,
            basename($fullPath)
        );

        return [
            'path' => $uploaded,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType()
        ];
    }

    public function deleteFile(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function getFileContent(string $path): ?string
    {
        if (!$this->fileExists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->get($path);
    }

    public function generateDownloadUrl(string $path, string $fileName, int $expiration = 3600): string
    {
        // For S3 implementation, use Laravel's URL generation
        if ($this->disk === 's3') {
            // Implementation will be added when S3 is properly configured
            return asset('storage/' . $path);
        }

        return asset('storage/' . $path);
    }

    public function generatePreviewUrl(string $path, string $fileName, int $expiration = 86400): string
    {
        // For S3 implementation, use Laravel's URL generation
        if ($this->disk === 's3') {
            // Implementation will be added when S3 is properly configured
            return asset('storage/' . $path);
        }

        return asset('storage/' . $path);
    }

    public function generateFilePath(string $companyId, string $fileName, string $environment): string
    {
        $sanitizedFileName = Str::slug(pathinfo($fileName, PATHINFO_FILENAME)) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
        return "{$environment}/companies/{$companyId}/documents/{$sanitizedFileName}";
    }

    public function generateUniqueFileName(string $originalExtension): string
    {
        return Str::uuid() . '.' . ltrim($originalExtension, '.');
    }

    public function validateFileType(UploadedFile $file, array $allowedTypes): bool
    {
        return in_array($file->getMimeType(), $allowedTypes);
    }

    public function validateFileSize(UploadedFile $file, int $maxSize): bool
    {
        return $file->getSize() <= $maxSize;
    }

    public function getFileUrl(string $path): string
    {
        return asset('storage/' . $path);
    }

    public function getDownloadUrl(string $path): string
    {
        $fileName = basename($path);
        return $this->generateDownloadUrl($path, $fileName);
    }

    public function getDownloadUrlWithFileName(string $filePath, string $fileName): string
    {
        return $this->generateDownloadUrl($filePath, $fileName);
    }

    public function getPreviewUrlWithFileName(string $filePath, string $fileName): string
    {
        return $this->generatePreviewUrl($filePath, $fileName);
    }
}
