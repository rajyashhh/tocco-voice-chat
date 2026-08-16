<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

 use Encore\Admin\Form;
 use App\Admin\Extensions\Form\Field\DynamicFields;
 use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Navbar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use KevinSoft\MultiLanguage\MultiLanguage;


//Encore\Admin\Form::forget( ['map', 'editor']);
//Admin::js('/packages/customization/js/main.js');

Admin::favicon(getFavIcon());

Admin::navbar(function ($navbar) {

    $languages = MultiLanguage::config('languages');
    $cookieName = MultiLanguage::config('cookie-name', 'locale');
    $current = request()->cookie($cookieName, config('app.locale'));

    $navbar->right(
        view('vendor.multi-language.language-menu', compact('languages', 'current'))
    );
});

Admin::css ('css/admin.css');
Admin::js(asset('js/laravel_admin.js'));

app('view')->prependNamespace('admin', resource_path('views/admin'));
view()->composer('admin::partials.menu', function (Illuminate\View\View $view) {
    $view->setPath(resource_path('views/admin/views/partials/menu.blade.php'));
});
view()->composer('admin::partials.footer', function (Illuminate\View\View $view) {
    $view->setPath(resource_path('views/admin/views/partials/footer.blade.php'));
});

view()->composer('admin::partials.js', function (Illuminate\View\View $view) {
    $view->setPath(resource_path('views/admin/views/partials/js.blade.php'));
});
view()->composer('admin::partials.cdn', function (Illuminate\View\View $view) {
    $view->setPath(resource_path('views/admin/views/partials/cdn.blade.php'));
});

view()->composer('admin::partials.css', function (Illuminate\View\View $view) {
    $view->setPath(resource_path('views/admin/views/partials/css.blade.php'));
});

Form::extend('dynamicFields', DynamicFields::class);


Encore\Admin\Admin::script(<<<'JS'
    $(document).on('pjax:start', function () {
        $('.select2-container--open').each(function () {
            $(this).remove();
        });
    });
JS);

// Root fix for "save returns me to the edit page" across the whole panel:
// DisablePjaxForOctane strips the X-PJAX request header under Swoole, so the
// server never applies the Pjax middleware (no #pjax-container filtering, no
// X-PJAX-URL header). jquery-pjax still submits every Encore form via XHR,
// follows the 302 to the resource index invisibly, receives a full HTML
// document it cannot extract the container from, and falls back to
// locationReplace(options.requestUrl) — the form's OWN action URL — landing
// the admin back on the edit page after every successful save.
//
// A bare off('submit') is NOT enough though: laravel-admin's cascade shim
// (Form::addCascadeScript) binds a DIRECT submit handler on every model form
// that always calls e.preventDefault() — it only wanted to disable hidden
// cascade-group inputs before the pjax XHR that no longer exists. With the
// pjax handler gone, that preventDefault cancels native submission too and
// the Submit button goes completely dead (proven via CDP: submit event fires,
// defaultPrevented=true, zero network activity).
//
// So: replace the pjax submit handler with a delegated one. Direct form
// handlers always run before document-level delegated ones, so by the time
// this runs the cascade shim has already disabled its hidden inputs and
// (possibly) cancelled the native submission — in that case re-issue it
// natively so the browser follows the real 302 (resource index, or the
// after-save checkbox targets). GET filter/search forms are never prevented
// and keep their native full reload. A per-form flag guards double submits.
if (function_exists('swoole_version')) {
    Encore\Admin\Admin::script(<<<'JS'
$(document).off('submit', 'form[pjax-container]').on('submit', 'form[pjax-container]', function (e) {
    if (this.__nativeSubmitted) { e.preventDefault(); return; }
    this.__nativeSubmitted = true;
    $(this).find(':submit').prop('disabled', true);
    if (e.isDefaultPrevented() || (e.originalEvent && e.originalEvent.defaultPrevented)) {
        HTMLFormElement.prototype.submit.call(this);
    }
});
JS);
}
