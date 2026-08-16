<?php

namespace App\Listeners;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laravel\Octane\Events\RequestReceived;

class SetupMultiLanguageListener
{
    /**
     * Handle the event.
     * يتم تنفيذه مع كل request في Octane لضبط اللغات
     */
    public function handle(RequestReceived $event): void
    {
        $this->setupLanguages();
        $this->setupAppTitle();
    }

    /**
     * ضبط اللغات المتاحة للـ multi-language extension
     */
    protected function setupLanguages(): void
    {
        $enabledLanguages = Cache::rememberForever('languages', function () {
            return Language::where('is_enabled', true)
                ->pluck('name', 'code')
                ->toArray();
        });

        Config::set('admin.extensions.multi-language.languages', $enabledLanguages);
    }

    /**
     * ضبط عنوان التطبيق حسب اللغة الحالية
     */
    protected function setupAppTitle(): void
    {
        $locale = app()->getLocale();
        $key = $locale === 'ar' ? 'app_title_ar' : 'app_title_en';

        $appName = Cache::rememberForever("settings.{$key}", function () use ($key) {
            return \App\Models\Setting::where('key', $key)->value('value') ?? 'Default';
        });

        Config::set('app.name', $appName);
        Config::set('admin.logo', $appName);
    }
}
