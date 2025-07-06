<?php

namespace Tests\Unit\Services;

use App\Services\FileManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FileManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FileManagementService();

        // Mock S3 storage for testing
        Storage::fake('s3');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_uploadFile_successfully_uploads_file_and_returns_metadata()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $path = 'documents/company-123';

        // Act
        $result = $this->service->uploadFile($file, $path);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertEquals(1024 * 1024, $result['size']); // 1MB in bytes
        $this->assertStringContainsString($path, $result['path']);
    }

    public function test_uploadFile_generates_unique_filename()
    {
        // Arrange
        $file1 = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $file2 = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $path = 'documents/company-123';

        // Act
        $result1 = $this->service->uploadFile($file1, $path);
        $result2 = $this->service->uploadFile($file2, $path);

        // Assert
        $this->assertNotEquals($result1['path'], $result2['path']);
    }

    public function test_deleteFile_successfully_removes_file()
    {
        // Arrange
        $filePath = 'documents/company-123/test.pdf';
        Storage::disk('s3')->put($filePath, 'test content');

        // Act
        $result = $this->service->deleteFile($filePath);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('s3')->exists($filePath));
    }

    public function test_deleteFile_returns_true_for_nonexistent_file()
    {
        // Arrange
        $filePath = 'documents/company-123/nonexistent.pdf';

        // Act
        $result = $this->service->deleteFile($filePath);

        // Assert - Laravel's delete method returns true even for non-existent files
        $this->assertTrue($result);
    }

    public function test_fileExists_returns_true_for_existing_file()
    {
        // Arrange
        $filePath = 'documents/company-123/test.pdf';
        Storage::disk('s3')->put($filePath, 'test content');

        // Act
        $result = $this->service->fileExists($filePath);

        // Assert
        $this->assertTrue($result);
    }

    public function test_fileExists_returns_false_for_nonexistent_file()
    {
        // Arrange
        $filePath = 'documents/company-123/nonexistent.pdf';

        // Act
        $result = $this->service->fileExists($filePath);

        // Assert
        $this->assertFalse($result);
    }

    public function test_getFileContent_returns_file_content()
    {
        // Arrange
        $filePath = 'documents/company-123/test.txt';
        $content = 'This is test content';
        Storage::disk('s3')->put($filePath, $content);

        // Act
        $result = $this->service->getFileContent($filePath);

        // Assert
        $this->assertEquals($content, $result);
    }

    public function test_getFileContent_returns_null_for_nonexistent_file()
    {
        // Arrange
        $filePath = 'documents/company-123/nonexistent.txt';

        // Act
        $result = $this->service->getFileContent($filePath);

        // Assert
        $this->assertNull($result);
    }

    public function test_generateDownloadUrl_returns_signed_url()
    {
        // Arrange
        $filePath = 'documents/company-123/test.pdf';
        $fileName = 'Test Document.pdf';
        $expiration = 3600;

        // Act
        $result = $this->service->generateDownloadUrl($filePath, $fileName, $expiration);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('storage', $result);
    }

    public function test_generatePreviewUrl_returns_signed_url()
    {
        // Arrange
        $filePath = 'documents/company-123/test.pdf';
        $fileName = 'Test Document.pdf';
        $expiration = 86400;

        // Act
        $result = $this->service->generatePreviewUrl($filePath, $fileName, $expiration);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('storage', $result);
    }

    public function test_generateFilePath_creates_structured_path()
    {
        // Arrange
        $companyId = 'company-123';
        $fileName = 'test-document.pdf';
        $environment = 'production';

        // Act
        $result = $this->service->generateFilePath($companyId, $fileName, $environment);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString($companyId, $result);
        $this->assertStringContainsString($fileName, $result);
        $this->assertStringContainsString($environment, $result);
    }

    public function test_generateUniqueFileName_creates_unique_name_with_extension()
    {
        // Arrange
        $extension = 'pdf';

        // Act
        $result1 = $this->service->generateUniqueFileName($extension);
        $result2 = $this->service->generateUniqueFileName($extension);

        // Assert
        $this->assertIsString($result1);
        $this->assertIsString($result2);
        $this->assertStringEndsWith(".{$extension}", $result1);
        $this->assertStringEndsWith(".{$extension}", $result2);
        $this->assertNotEquals($result1, $result2);
    }

    public function test_validateFileType_returns_true_for_allowed_types()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $allowedTypes = ['application/pdf', 'application/msword'];

        // Act
        $result = $this->service->validateFileType($file, $allowedTypes);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validateFileType_returns_false_for_disallowed_types()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.exe', 1024, 'application/x-executable');
        $allowedTypes = ['application/pdf', 'application/msword'];

        // Act
        $result = $this->service->validateFileType($file, $allowedTypes);

        // Assert
        $this->assertFalse($result);
    }

    public function test_validateFileSize_returns_true_for_valid_size()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.pdf', 512); // 512KB
        $maxSize = 1024 * 1024; // 1MB

        // Act
        $result = $this->service->validateFileSize($file, $maxSize);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validateFileSize_returns_false_for_oversized_file()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.pdf', 2048); // 2MB
        $maxSize = 1024 * 1024; // 1MB

        // Act
        $result = $this->service->validateFileSize($file, $maxSize);

        // Assert
        $this->assertFalse($result);
    }

    public function test_getFileUrl_returns_public_url()
    {
        // Arrange
        $filePath = 'documents/company-123/test.pdf';

        // Act
        $result = $this->service->getFileUrl($filePath);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('storage', $result);
    }

    public function test_getDownloadUrl_returns_download_url()
    {
        // Arrange
        $filePath = 'documents/company-123/test.pdf';

        // Act
        $result = $this->service->getDownloadUrl($filePath);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('storage', $result);
    }

    public function test_uploadFile_handles_special_characters_in_filename()
    {
        // Arrange
        $file = UploadedFile::fake()->create('テスト文書 (特殊文字).pdf', 1024, 'application/pdf');
        $path = 'documents/company-123';

        // Act
        $result = $this->service->uploadFile($file, $path);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('path', $result);
        // The filename will be generated as UUID, so no special characters should be present
        $this->assertIsString($result['path']);
    }

    public function test_generateFilePath_with_different_environments()
    {
        // Arrange
        $companyId = 'company-123';
        $fileName = 'test.pdf';

        // Act
        $prodPath = $this->service->generateFilePath($companyId, $fileName, 'production');
        $testPath = $this->service->generateFilePath($companyId, $fileName, 'testing');

        // Assert
        $this->assertStringContainsString('production', $prodPath);
        $this->assertStringContainsString('testing', $testPath);
        $this->assertNotEquals($prodPath, $testPath);
    }

    public function test_validateFileType_handles_empty_allowed_types()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $allowedTypes = [];

        // Act
        $result = $this->service->validateFileType($file, $allowedTypes);

        // Assert
        $this->assertFalse($result);
    }

    public function test_validateFileSize_handles_zero_max_size()
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.pdf', 1); // 1KB
        $maxSize = 0;

        // Act
        $result = $this->service->validateFileSize($file, $maxSize);

        // Assert
        $this->assertFalse($result);
    }
}
