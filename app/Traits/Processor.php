<?php

namespace App\Traits;

use Exception;
use App\Models\Setting;
use App\Models\PaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;

trait  Processor
{
    public function response_formatter($constant, $content = null, $errors = []): array
    {
        $constant = (array)$constant;
        $constant['content'] = $content;
        $constant['errors'] = $errors;
        return $constant;
    }

    public function error_processor($validator): array
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['error_code' => $index, 'message' => self::translate($error[0])];
        }
        return $errors;
    }

    public function translate($key)
    {
        try {
            App::setLocale('en');
            $lang_array = include(base_path('resources/lang/' . 'en' . '/lang.php'));
            $processed_key = ucfirst(str_replace('_', ' ', str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', $key)));
            if (!array_key_exists($key, $lang_array)) {
                $lang_array[$key] = $processed_key;
                $str = "<?php return " . var_export($lang_array, true) . ";";
                file_put_contents(base_path('resources/lang/' . 'en' . '/lang.php'), $str);
                $result = $processed_key;
            } else {
                $result = __('lang.' . $key);
            }
            return $result;
        } catch (\Exception $exception) {
            return $key;
        }
    }

    public function payment_config($key, $settings_type): object|null
    {
        try {
            $config = DB::table('addon_settings')->where('key_name', $key)
                ->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return new Setting();
        }

        return (isset($config)) ? $config : null;
    }
    public static function getDisk()
    {
        $config=\App\CentralLogics\Helpers::get_business_settings('local_storage');

        return isset($config)?($config==0?'s3':'public'):'public';
    }
    public function file_uploader(string $dir, string $format, $image = null, $old_image = null)
    {
        if ($image == null) return $old_image ?? 'def.png';

        // Security: If $image is a URL string, validate it against SSRF
        if (is_string($image) && (str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))) {
            $validation = \App\Helpers\UrlValidator::validateUrl($image, true);

            if (!$validation['valid']) {
                \Log::warning('SSRF attempt blocked in file_uploader', [
                    'url' => $image,
                    'error' => $validation['error'],
                    'ip' => request()->ip()
                ]);
                throw new \Exception('Invalid image URL: ' . $validation['error']);
            }

            // Only allow HTTPS
            if (!str_starts_with($image, 'https://')) {
                throw new \Exception('Only HTTPS URLs are allowed');
            }

            // Use HTTP client instead of file_get_contents to prevent stream wrapper attacks
            try {
                $response = \Http::timeout(10)
                    ->withOptions([
                        'verify' => true,
                        'allow_redirects' => ['max' => 2, 'strict' => true]
                    ])
                    ->get($image);

                if (!$response->successful()) {
                    throw new \Exception('Failed to download image');
                }

                $content = $response->body();
                if (strlen($content) > 10485760) { // 10MB limit
                    throw new \Exception('Image exceeds size limit');
                }
            } catch (\Exception $e) {
                \Log::error('file_uploader download failed', [
                    'url' => $image,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        } elseif (is_string($image)) {
            // Security: Block dangerous PHP stream wrappers
            $dangerousWrappers = ['php://', 'file://', 'phar://', 'data://', 'glob://', 'expect://'];
            foreach ($dangerousWrappers as $wrapper) {
                if (str_starts_with(strtolower($image), $wrapper)) {
                    \Log::warning('Blocked dangerous stream wrapper in file_uploader', [
                        'wrapper' => $wrapper,
                        'input' => $image,
                        'ip' => request()->ip()
                    ]);
                    throw new \Exception('Invalid file path');
                }
            }

            // If it's a local file path, read it safely
            if (file_exists($image) && is_file($image)) {
                $content = file_get_contents($image);
            } else {
                throw new \Exception('File not found');
            }
        } else {
            // Assume it's file content already
            $content = $image;
        }

        if (isset($old_image)) Storage::disk(self::getDisk())->delete($dir . $old_image);

        $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
        if (!Storage::disk(self::getDisk())->exists($dir)) {
            Storage::disk(self::getDisk())->makeDirectory($dir);
        }
        Storage::disk(self::getDisk())->put($dir . $imageName, $content, [
            'CacheControl' => 'public, max-age=31536000',
            'visibility' => 'public'
        ]);

        return $imageName;
    }

    public function payment_response($payment_info, $payment_flag): Application|JsonResponse|Redirector|RedirectResponse|\Illuminate\Contracts\Foundation\Application
    {
        // $payment_info = PaymentRequest::find($payment_info->id);
        // $token_string = 'payment_method=' . $payment_info->payment_method . '&&attribute_id=' . $payment_info->attribute_id . '&&transaction_reference=' . $payment_info->transaction_id;
        // if (in_array($payment_info->payment_platform, ['web', 'app']) && $payment_info['external_redirect_link'] != null) {
        //     return redirect($payment_info['external_redirect_link'] . '?flag=' . $payment_flag . '&&token=' . base64_encode($token_string));
        // }
        // return redirect()->route('payment-' . $payment_flag, ['token' => base64_encode($token_string)]);
    }
}
