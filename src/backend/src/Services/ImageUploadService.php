<?php

namespace App\Services;

use Exception;

/**
 * Service for handling image uploads from base64 encoded data.
 */
class ImageUploadService
{
    private string $uploadDir;
    private array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    private int $maxSize = 5242880; // 5MB in bytes

    /**
     * @param string $uploadDir Directory to store uploaded images
     */
    public function __construct(string $uploadDir = 'uploads/')
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
    }

    /**
     * Upload an image from base64 encoded data.
     *
     * @param string $base64Data Base64 encoded image data (with or without data URI prefix)
     * @return array Result array with 'success', 'path' or 'error' keys
     */
    public function uploadBase64(string $base64Data): array
    {
        // Parse the base64 data
        $parsed = $this->parseBase64($base64Data);
        if ($parsed === null) {
            return ['success' => false, 'error' => 'Invalid base64 data'];
        }

        $mimeType = $parsed['mimeType'];
        $decodedData = $parsed['data'];

        // Validate MIME type
        if (!$this->validateMimeType($mimeType)) {
            return ['success' => false, 'error' => 'Invalid file format'];
        }

        // Validate size
        if (!$this->validateSize($decodedData)) {
            return ['success' => false, 'error' => 'File too large'];
        }

        // Generate unique filename
        $filename = $this->generateFilename($mimeType);
        $fullPath = $this->uploadDir . $filename;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Save the file
        $result = file_put_contents($fullPath, $decodedData);
        if ($result === false) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }

        return ['success' => true, 'path' => $this->uploadDir . $filename];
    }

    /**
     * Delete an uploaded image.
     *
     * @param string $path Path to the image file
     * @return bool True if deletion successful, false otherwise
     */
    public function delete(string $path): bool
    {
        if (empty($path) || !file_exists($path)) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Parse base64 data URI and extract MIME type and decoded data.
     *
     * @param string $base64Data Base64 string (with or without data URI prefix)
     * @return array|null Array with 'mimeType' and 'data' keys, or null if invalid
     */
    private function parseBase64(string $base64Data): ?array
    {
        // Handle data URI format: data:image/jpeg;base64,/9j/4AAQ...
        if (preg_match('/^data:([a-zA-Z0-9\/+]+);base64,(.+)$/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $data = base64_decode($matches[2], true);
            
            if ($data === false) {
                return null;
            }

            return ['mimeType' => $mimeType, 'data' => $data];
        }

        // Handle raw base64 (try to detect MIME type from decoded data)
        $data = base64_decode($base64Data, true);
        if ($data === false) {
            return null;
        }

        $mimeType = $this->detectMimeType($data);
        if ($mimeType === null) {
            return null;
        }

        return ['mimeType' => $mimeType, 'data' => $data];
    }

    /**
     * Detect MIME type from binary data using magic bytes.
     *
     * @param string $data Binary data
     * @return string|null Detected MIME type or null
     */
    private function detectMimeType(string $data): ?string
    {
        // Check magic bytes
        $bytes = substr($data, 0, 12);
        
        // JPEG: FF D8 FF
        if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
            return 'image/jpeg';
        }
        
        // PNG: 89 50 4E 47 0D 0A 1A 0A
        if (substr($bytes, 0, 8) === "\x89PNG\r\n\x1A\n") {
            return 'image/png';
        }
        
        // WebP: RIFF....WEBP
        if (substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }

    /**
     * Validate if the MIME type is allowed.
     *
     * @param string $mimeType MIME type to validate
     * @return bool True if allowed, false otherwise
     */
    private function validateMimeType(string $mimeType): bool
    {
        return in_array($mimeType, $this->allowedMimeTypes, true);
    }

    /**
     * Validate if the data size is within limits.
     *
     * @param string $data Decoded binary data
     * @return bool True if within limits, false otherwise
     */
    private function validateSize(string $data): bool
    {
        return strlen($data) <= $this->maxSize;
    }

    /**
     * Generate a unique filename based on MIME type.
     *
     * @param string $mimeType MIME type of the image
     * @return string Generated filename
     */
    private function generateFilename(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $ext = $extensions[$mimeType] ?? 'bin';
        return uniqid('img_', true) . '.' . $ext;
    }

    /**
     * Get the upload directory path.
     *
     * @return string Upload directory
     */
    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Get the maximum allowed file size.
     *
     * @return int Max size in bytes
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Get allowed MIME types.
     *
     * @return array Allowed MIME types
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }
}
