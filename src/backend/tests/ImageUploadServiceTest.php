<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\ImageUploadService;

/**
 * Unit tests for ImageUploadService.
 */
class ImageUploadServiceTest extends TestCase
{
    private string $testUploadDir;
    private ImageUploadService $service;

    protected function setUp(): void
    {
        $this->testUploadDir = sys_get_temp_dir() . '/test_uploads_' . uniqid() . '/';
        $this->service = new ImageUploadService($this->testUploadDir);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testUploadDir)) {
            $files = glob($this->testUploadDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testUploadDir);
        }
    }

    /**
     * Create a minimal valid JPEG image in base64.
     */
    private function createValidJpegBase64(): string
    {
        // Minimal 1x1 pixel JPEG
        $jpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";
        $jpeg .= "\xFF\xDB\x00C\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\x09\x09";
        $jpeg .= "\x08\x0A\x0C\x14\x0D\x0C\x0B\x0B\x0C\x19\x12\x13\x0F\x14\x1D\x1A";
        $jpeg .= "\x1F\x1E\x1D\x1A\x1C\x1C $.\x27),01444\x271444444";
        $jpeg .= "44444444444444444444\xFF\xC0\x00\x0B\x08\x00\x01\x00\x01\x01\x01\x11\x00";
        $jpeg .= "\xFF\xC4\x00\x1F\x00\x00\x01\x05\x01\x01\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x00";
        $jpeg .= "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B";
        $jpeg .= "\xFF\xC4\x00\xB5\x10\x00\x02\x01\x03\x03\x02\x04\x03\x05\x05\x04\x04\x00\x00\x01\x7D";
        $jpeg .= "\x01\x02\x03\x00\x04\x11\x05\x12!1A\x06\x13Qa\x07\"q\x142\x81\x91\xA1\x08#B\xB1";
        $jpeg .= "\xC1\x15R\xD1\xF0$3br\x82\x09\x0A\x16\x17\x18\x19\x1A%&'()*456789:CDEFGHIJ";
        $jpeg .= "STUVWXYZ\xFF\xDA\x00\x08\x01\x01\x00\x00?\x00\xFB\xD5n\xA0\x00\x00\x00";
        $jpeg .= "\xFF\xD9";
        
        return base64_encode($jpeg);
    }

    /**
     * Create a minimal valid PNG image in base64.
     */
    private function createValidPngBase64(): string
    {
        // Minimal 1x1 pixel PNG (red pixel)
        $png = "\x89PNG\r\n\x1A\n";  // PNG signature
        $png .= "\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xDE";  // IHDR chunk
        $png .= "\x00\x00\x00\x0CIDAT\x08\xD7c\xF8\x0F\x00\x00\x01\x01\x00\x05\x18\xD8N";  // IDAT chunk
        $png .= "\x00\x00\x00\x00IEND\xAEB`\x82";  // IEND chunk
        
        return base64_encode($png);
    }

    // ========================================================================
    // Upload Tests
    // ========================================================================

    public function testUploadValidJpegWithDataUri(): void
    {
        $base64 = 'data:image/jpeg;base64,' . $this->createValidJpegBase64();

        $result = $this->service->uploadBase64($base64);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringEndsWith('.jpg', $result['path']);
        $this->assertTrue(file_exists($result['path']));
    }

    public function testUploadValidPngWithDataUri(): void
    {
        $base64 = 'data:image/png;base64,' . $this->createValidPngBase64();

        $result = $this->service->uploadBase64($base64);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringEndsWith('.png', $result['path']);
    }

    public function testUploadValidJpegWithoutDataUri(): void
    {
        $base64 = $this->createValidJpegBase64();

        $result = $this->service->uploadBase64($base64);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('path', $result);
    }

    public function testUploadFailsWithInvalidMimeType(): void
    {
        // GIF is not allowed
        $base64 = 'data:image/gif;base64,' . base64_encode('GIF89a...');

        $result = $this->service->uploadBase64($base64);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid file format', $result['error']);
    }

    public function testUploadFailsWithInvalidBase64(): void
    {
        $result = $this->service->uploadBase64('not-valid-base64!!!');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testUploadFailsWithFileTooLarge(): void
    {
        // Create a base64 string that decodes to > 5MB
        $largeData = str_repeat('A', 6 * 1024 * 1024); // 6MB of data
        $base64 = 'data:image/jpeg;base64,' . base64_encode("\xFF\xD8\xFF" . $largeData);

        $result = $this->service->uploadBase64($base64);

        $this->assertFalse($result['success']);
        $this->assertEquals('File too large', $result['error']);
    }

    public function testUploadCreatesDirectory(): void
    {
        $newDir = sys_get_temp_dir() . '/new_upload_dir_' . uniqid() . '/';
        $service = new ImageUploadService($newDir);
        $base64 = 'data:image/jpeg;base64,' . $this->createValidJpegBase64();

        $result = $service->uploadBase64($base64);

        $this->assertTrue($result['success']);
        $this->assertTrue(is_dir($newDir));

        // Cleanup
        if (file_exists($result['path'])) {
            unlink($result['path']);
        }
        rmdir($newDir);
    }

    public function testUploadGeneratesUniqueFilenames(): void
    {
        $base64 = 'data:image/jpeg;base64,' . $this->createValidJpegBase64();

        $result1 = $this->service->uploadBase64($base64);
        $result2 = $this->service->uploadBase64($base64);

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertNotEquals($result1['path'], $result2['path']);
    }

    // ========================================================================
    // Delete Tests
    // ========================================================================

    public function testDeleteExistingFile(): void
    {
        $base64 = 'data:image/jpeg;base64,' . $this->createValidJpegBase64();
        $uploadResult = $this->service->uploadBase64($base64);
        
        $this->assertTrue($uploadResult['success']);
        $this->assertTrue(file_exists($uploadResult['path']));

        $result = $this->service->delete($uploadResult['path']);

        $this->assertTrue($result);
        $this->assertFalse(file_exists($uploadResult['path']));
    }

    public function testDeleteNonExistentFile(): void
    {
        $result = $this->service->delete('/path/to/nonexistent/file.jpg');

        $this->assertFalse($result);
    }

    public function testDeleteEmptyPath(): void
    {
        $result = $this->service->delete('');

        $this->assertFalse($result);
    }

    // ========================================================================
    // Configuration Tests
    // ========================================================================

    public function testGetUploadDir(): void
    {
        $this->assertEquals($this->testUploadDir, $this->service->getUploadDir());
    }

    public function testGetMaxSize(): void
    {
        $maxSize = $this->service->getMaxSize();

        $this->assertEquals(5242880, $maxSize); // 5MB
    }

    public function testGetAllowedMimeTypes(): void
    {
        $allowedTypes = $this->service->getAllowedMimeTypes();

        $this->assertContains('image/jpeg', $allowedTypes);
        $this->assertContains('image/png', $allowedTypes);
        $this->assertContains('image/webp', $allowedTypes);
        $this->assertNotContains('image/gif', $allowedTypes);
    }

    // ========================================================================
    // MIME Type Detection Tests
    // ========================================================================

    public function testDetectJpegFromMagicBytes(): void
    {
        // JPEG starts with FF D8 FF
        $jpegData = "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100);
        $base64 = base64_encode($jpegData);

        $result = $this->service->uploadBase64($base64);

        // Should be detected as JPEG and succeed
        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.jpg', $result['path']);
    }

    public function testDetectPngFromMagicBytes(): void
    {
        // PNG starts with 89 50 4E 47 0D 0A 1A 0A
        $pngData = "\x89PNG\r\n\x1A\n" . str_repeat("\x00", 100);
        $base64 = base64_encode($pngData);

        $result = $this->service->uploadBase64($base64);

        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.png', $result['path']);
    }

    public function testRejectUnknownFormat(): void
    {
        // Random bytes that don't match any known format
        $randomData = "\x00\x01\x02\x03\x04\x05";
        $base64 = base64_encode($randomData);

        $result = $this->service->uploadBase64($base64);

        $this->assertFalse($result['success']);
    }
}
