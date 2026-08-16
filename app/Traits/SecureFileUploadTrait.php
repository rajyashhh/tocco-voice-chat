<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

trait SecureFileUploadTrait
{
    /**
     * Allowed image extensions
     */
    protected array $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Allowed image MIME types
     */
    protected array $allowedImageMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    /**
     * Maximum file size in bytes (10MB default)
     */
    protected int $maxFileSize = 10485760; // 10MB

    /**
     * Validate uploaded file for security
     *
     * @param UploadedFile $file
     * @param array|null $allowedExtensions
     * @param array|null $allowedMimes
     * @param int|null $maxSize
     * @return array
     * @throws Exception
     */
    protected function validateUploadedFile(
        UploadedFile $file,
        ?array $allowedExtensions = null,
        ?array $allowedMimes = null,
        ?int $maxSize = null
    ): array {
        $allowedExtensions = $allowedExtensions ?? $this->allowedImageExtensions;
        $allowedMimes = $allowedMimes ?? $this->allowedImageMimes;
        $maxSize = $maxSize ?? $this->maxFileSize;

        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }

        // Get and validate MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Invalid file type. Only images are allowed');
        }

        // Get client extension (not trusted)
        $clientExtension = strtolower($file->getClientOriginalExtension());

        // Map MIME to safe extension (trusted)
        $safeExtension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };

        if (!$safeExtension) {
            throw new Exception('Unsupported image format');
        }

        // Verify client extension matches MIME type (if provided)
        if ($clientExtension && !in_array($clientExtension, $allowedExtensions)) {
            Log::warning('Suspicious file extension detected', [
                'client_extension' => $clientExtension,
                'mime_type' => $mimeType,
                'safe_extension' => $safeExtension
            ]);
        }

        return [
            'mime_type' => $mimeType,
            'safe_extension' => $safeExtension,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName()
        ];
    }

    /**
     * Generate secure filename using hash
     *
     * @param string $extension
     * @param string|null $prefix
     * @return string
     */
    protected function generateSecureFilename(string $extension, ?string $prefix = null): string
    {
        $hash = hash('sha256', Str::random(40) . microtime(true));
        $shortHash = substr($hash, 0, 32);

        if ($prefix) {
            return $prefix . '_' . $shortHash . '.' . $extension;
        }

        return $shortHash . '.' . $extension;
    }

    /**
     * Secure file upload with validation
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string|null $disk
     * @param array|null $allowedExtensions
     * @param array|null $allowedMimes
     * @param int|null $maxSize
     * @return string
     * @throws Exception
     */
    protected function secureUpload(
        UploadedFile $file,
        string $folder,
        ?string $disk = null,
        ?array $allowedExtensions = null,
        ?array $allowedMimes = null,
        ?int $maxSize = null
    ): string {
        // Validate file
        $validation = $this->validateUploadedFile($file, $allowedExtensions, $allowedMimes, $maxSize);

        // Generate secure filename
        $fileName = $this->generateSecureFilename($validation['safe_extension']);

        // Store file
        $config = $disk ?: config('filesystems.default');
        $file->storeAs($folder, $fileName, $config);

        return $folder . DIRECTORY_SEPARATOR . $fileName;
    }
}
