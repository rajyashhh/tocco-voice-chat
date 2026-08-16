<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class OptimizedImageRule implements Rule
{
    private string $errorMessage = '';

    /**
     * @param string $context  profile|room|banner|gift|general
     */
    public function __construct(
        private string $context = 'general'
    ) {}

    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            $this->errorMessage = __('The file is not a valid upload.');
            return false;
        }

        if (!$value->isValid()) {
            $this->errorMessage = __('The uploaded file is corrupted.');
            return false;
        }

        $ext  = strtolower($value->getClientOriginalExtension());
        $mime = $value->getMimeType();
        $size = $value->getSize();

        // ── 1. Allowed extensions ──
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExtensions)) {
            $this->errorMessage = __('File type not allowed. Allowed: jpg, jpeg, png, webp, gif.');
            return false;
        }

        // ── 2. MIME type check ──
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ];
        if (!in_array($mime, $allowedMimes)) {
            $this->errorMessage = __('Invalid file MIME type: :mime', ['mime' => $mime]);
            return false;
        }

        // ── 3. Extension ↔ MIME consistency ──
        $mimeExtMap = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'webp' => ['image/webp'],
            'gif'  => ['image/gif'],
        ];
        if (isset($mimeExtMap[$ext]) && !in_array($mime, $mimeExtMap[$ext])) {
            $this->errorMessage = __('File extension does not match its content.');
            return false;
        }

        // ── 4. Max file size ──
        $maxImageSize = 5 * 1024 * 1024;  // 5MB
        $maxGifSize   = 8 * 1024 * 1024;  // 8MB

        if ($ext === 'gif') {
            if ($size > $maxGifSize) {
                $this->errorMessage = __('GIF file is too large. Maximum: :max MB.', ['max' => 8]);
                return false;
            }
        } else {
            if ($size > $maxImageSize) {
                $this->errorMessage = __('Image file is too large. Maximum: :max MB.', ['max' => 5]);
                return false;
            }
        }

        // ── 5. Verify file is readable as image (not corrupted) ──
        try {
            $tempPath = $value->getPathname();
            $imageInfo = @getimagesize($tempPath);

            if ($imageInfo === false) {
                $this->errorMessage = __('The file is not a valid image.');
                return false;
            }

            // ── 6. Max dimensions check (prevent absurd sizes) ──
            $maxDimension = 6000; // px
            [$width, $height] = $imageInfo;

            if ($width > $maxDimension || $height > $maxDimension) {
                $this->errorMessage = __('Image dimensions too large. Maximum: :max px.', ['max' => $maxDimension]);
                return false;
            }
        } catch (\Throwable $e) {
            $this->errorMessage = __('Could not read image file.');
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage ?: __('The image file is invalid.');
    }
}
