<?php
namespace App\Traits\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

trait DashBoardTrait {

    function store_img(UploadedFile $file, $folder = null ){
        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Security: Validate file size (10MB max)
        $maxSize = 10485760; // 10MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of 10MB');
        }

        // Security: Validate MIME type (images only)
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, $allowedMimes)) {
            throw new \Exception('Invalid file type. Only images (JPG, PNG, GIF, WebP) are allowed');
        }

        // Security: Map MIME type to safe extension (don't trust client extension)
        $safeExtension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new \Exception('Unsupported image format'),
        };

        // Log suspicious activity if client extension doesn't match
        $clientExtension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if ($clientExtension && !in_array($clientExtension, $allowedExtensions)) {
            Log::warning('Suspicious file extension detected in dashboard upload', [
                'client_extension' => $clientExtension,
                'mime_type' => $mimeType,
                'safe_extension' => $safeExtension,
                'ip' => request()->ip()
            ]);
        }

        // Generate secure filename using hash
        $hash = hash('sha256', Str::random(40) . microtime(true));
        $name = substr($hash, 0, 32);

        return $file->storeAs(
            $folder,
            $name . "." . $safeExtension,
            'gcs'
        );
    }

    public function delete_img($path = null)
    {
        Storage::disk('gcs')->delete($path);
    }

    public function user_type($type)
    {
        $types = [
            0 => 'User',
            1 => 'Host',
            2 => 'Host Agent',
            3 => 'Shipping Agent',
            4 => 'Host and Shipping Agent',
            5 => 'Administrator',
        ];
        return $types[$type] ?? null;
    }

    function ware_types($type){
        $types = [
            1 => 'Gemstone',
            3 => 'Card Scroll',
            4 => 'Avatar Frame',
            5 => 'Bubble Frame',
            6 => 'Entering Special Effects',
            7 => 'Microphone Aperture',
            8 => 'Badge',
            9 => 'NoKick',
            10 => 'Icon',
            11 => 'intro animation',
            12 => 'wapel',
            13 => 'hide country',
            14 => 'vip gifts',
            15 => 'no pan',
            16 => 'hidden room',
            17 => 'anonymous man',
            18 => 'colored name',
            19 => 'profile visitors hide in',
            20 => 'hide last active'
        ];

        return $types[$type] ?? null;
    }

    function wares_main_type($type){
        $types = [
            4 => 'purchase',
            6 => 'vip wares',
        ];
        return $types[$type] ?? null;
    }

    function vip_previlage_type($type){
        $types = [
            1   =>'Gemstone',
            3   =>'Card Scroll',
            4   =>'Avatar Frame',
            5   =>'Bubble Frame',
            6   =>'Entering Special Effects',
            7   =>'Microphone Aperture',
            8   =>'Badge',
            9   =>'NoKick',
            10  =>'Icon',
            11  =>'intro animation',
            12  =>'wapel',
            13  =>'hide country',
            14  =>'vip gifts',
            15  =>'no pan',
            16  =>'hidden room',
            17  =>'anonymous man',
            18  =>'colored name',
            19  =>'profile visitors hide in',
            20  =>'last login',
        ];
        return $types[$type] ?? null;
    }

    function cuarsel_type($type){
        $types = [
            0=> 'normal',
            1=> 'Room',
            2=> 'url'
        ];
        return $types[$type] ?? null;
    }

    function cuarsel_from_type($type){
        $types = [
            0 => '',
            1 => 'hours',
            2 => 'days',
            3 => 'month'
        ];
        return $types[$type] ?? null;
    }

    protected function store_music($file, $folder)
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Security: Validate file size (50MB max for audio)
        $maxSize = 52428800; // 50MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of 50MB');
        }

        // Security: Validate MIME type (audio only)
        $allowedMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a'];
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, $allowedMimes)) {
            throw new \Exception('Invalid file type. Only audio files (MP3, WAV, OGG, M4A) are allowed');
        }

        // Security: Map MIME type to safe extension
        $safeExtension = match ($mimeType) {
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            default => throw new \Exception('Unsupported audio format'),
        };

        // Log suspicious activity if client extension doesn't match
        $clientExtension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
        if ($clientExtension && !in_array($clientExtension, $allowedExtensions)) {
            Log::warning('Suspicious file extension detected in music upload', [
                'client_extension' => $clientExtension,
                'mime_type' => $mimeType,
                'safe_extension' => $safeExtension,
                'ip' => request()->ip()
            ]);
        }

        // Generate secure filename using hash
        $hash = hash('sha256', Str::random(40) . microtime(true));
        $name = substr($hash, 0, 32);

        return $file->storeAs(
            $folder,
            $name . "." . $safeExtension
        );
    }
}
