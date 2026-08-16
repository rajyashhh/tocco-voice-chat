<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates a real, stored "letter avatar" image for entities that have no
 * picture: the first letter of the entity name drawn on a deterministic
 * colored background. The image is uploaded to the same storage disk as user
 * uploads and the returned relative path is meant to be saved on the entity's
 * photo column — so reads everywhere see a real URL, exactly as if the user
 * had uploaded it.
 *
 * The background color is derived from the entity identity (id, falling back to
 * the name) so the SAME entity always gets the SAME color, and it never changes
 * when the name changes. The image itself, once stored, is persisted on the
 * column and is therefore stable forever (we never regenerate when a photo
 * already exists).
 */
class LetterAvatarService
{
    /** Image side in pixels (square). */
    private const SIZE = 512;

    /**
     * Generate a letter avatar, upload it and return the stored RELATIVE path
     * (e.g. "letter_avatars/ab12...png") to save on the entity photo column.
     * Returns null only on hard failure (caller keeps the empty value and the
     * client-side initials widget remains the last-resort safety net).
     *
     * @param string     $folder    storage folder (mirrors the entity's own
     *                              upload folder, e.g. 'profile', 'rooms')
     * @param string     $name      entity display name (first letter is drawn)
     * @param int|string $seed      stable identity for color (entity id)
     */
    public function generate(string $folder, ?string $name, int|string $seed): ?string
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {
            Log::warning('LetterAvatarService: GD/freetype unavailable, cannot generate letter avatar', [
                'folder' => $folder,
            ]);
            return null;
        }

        try {
            $letter = $this->firstLetter($name);
            [$r, $g, $b] = $this->colorFor((string) $seed);
            $font = $this->fontFor($letter);

            if (!$font) {
                Log::warning('LetterAvatarService: no usable font bundled', ['folder' => $folder]);
                return null;
            }

            $binary = $this->render($letter, $r, $g, $b, $font);
            if ($binary === null) {
                return null;
            }

            $path = $folder . '/' . substr(hash('sha256', Str::random(40) . microtime(true)), 0, 40) . '.png';

            Storage::put($path, $binary, config('filesystems.default'));

            return $path;
        } catch (\Throwable $e) {
            Log::error('LetterAvatarService: generation failed', [
                'folder' => $folder,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Convenience: generate only when $current is empty; otherwise return
     * $current unchanged. Keeps callers a one-liner and guarantees we never
     * regenerate over an existing photo.
     */
    public function ensure(?string $current, string $folder, ?string $name, int|string $seed): ?string
    {
        if (!empty($current)) {
            return $current;
        }

        return $this->generate($folder, $name, $seed);
    }

    /** mb-safe uppercase first letter; '#' when the name is empty. */
    private function firstLetter(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '#';
        }

        $first = mb_substr($name, 0, 1, 'UTF-8');

        // Uppercase Latin; Arabic/other scripts are left as-is (no case).
        return mb_strtoupper($first, 'UTF-8');
    }

    /**
     * Deterministic color from the configured palette using the entity seed.
     * crc32 over the seed keeps it stable and independent of name changes.
     *
     * @return array{0:int,1:int,2:int} rgb
     */
    private function colorFor(string $seed): array
    {
        $palette = config('letter_avatar.palette');

        if (empty($palette) || !is_array($palette)) {
            $palette = ['#F44336'];
        }

        $hex = $palette[crc32($seed) % count($palette)];

        return $this->hexToRgb($hex);
    }

    /** @return array{0:int,1:int,2:int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /** Pick the bundled font that covers the glyph (Arabic vs Latin/digits). */
    private function fontFor(string $letter): ?string
    {
        $base = public_path('fonts/letter-avatar');
        $arabic = $base . '/Cairo-Bold-Arabic.ttf';
        $latin = $base . '/Cairo-Bold-Latin.ttf';

        $code = mb_ord($letter, 'UTF-8');
        $isArabic = $code !== false && (
            ($code >= 0x0600 && $code <= 0x06FF) || // Arabic
            ($code >= 0x0750 && $code <= 0x077F) || // Arabic Supplement
            ($code >= 0x08A0 && $code <= 0x08FF) || // Arabic Extended-A
            ($code >= 0xFB50 && $code <= 0xFDFF) || // Arabic Presentation Forms-A
            ($code >= 0xFE70 && $code <= 0xFEFF)     // Arabic Presentation Forms-B
        );

        if ($isArabic && is_file($arabic)) {
            return $arabic;
        }

        if (is_file($latin)) {
            return $latin;
        }

        // Arabic font also carries digits/punctuation as a final fallback.
        return is_file($arabic) ? $arabic : null;
    }

    /** Render the PNG bytes. Returns null on failure. */
    private function render(string $letter, int $r, int $g, int $b, string $font): ?string
    {
        $img = imagecreatetruecolor(self::SIZE, self::SIZE);
        if ($img === false) {
            return null;
        }

        try {
            $bg = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, 0, 0, self::SIZE, self::SIZE, $bg);

            // White text, slightly translucent for a softer look.
            $fg = imagecolorallocate($img, 255, 255, 255);

            $fontSize = (int) (self::SIZE * 0.42);

            // Measure to center the glyph.
            $box = imagettfbbox($fontSize, 0, $font, $letter);
            if ($box === false) {
                return null;
            }

            // bbox: [0,1]=lower-left, [2,3]=lower-right, [4,5]=upper-right, [6,7]=upper-left
            $textWidth = $box[2] - $box[0];
            $textHeight = $box[1] - $box[7];

            $x = (int) ((self::SIZE - $textWidth) / 2 - $box[0]);
            $y = (int) ((self::SIZE - $textHeight) / 2 - $box[7]);

            imagettftext($img, $fontSize, 0, $x, $y, $fg, $font, $letter);

            ob_start();
            $ok = imagepng($img, null, 6);
            $binary = ob_get_clean();

            if (!$ok || $binary === false || $binary === '') {
                return null;
            }

            return $binary;
        } finally {
            imagedestroy($img);
        }
    }
}
