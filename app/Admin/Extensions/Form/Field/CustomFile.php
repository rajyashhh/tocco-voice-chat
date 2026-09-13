<?php
namespace App\Admin\Extensions\Form\Field;

use Encore\Admin\Form\Field\File;

class CustomFile extends File
{
    protected $view = 'admin::form.file';

    /**
     * Override fileTypes so guessPreviewType() recognises .svga on existing
     * saved values (server-side initial preview).  This does NOT affect the
     * client-side new-file preview — that is handled by setupScripts().
     */
    protected $fileTypes = [
        'image'  => '/^(gif|png|jpe?g|svg|webp)$/i',
        'html'   => '/^(htm|html)$/i',
        'office' => '/^(docx?|xlsx?|pptx?|pps|potx?)$/i',
        'gdocs'  => '/^(docx?|xlsx?|pptx?|pps|potx?|rtf|ods|odt|pages|ai|dxf|ttf|tiff?|wmf|e?ps)$/i',
        'text'   => '/^(txt|md|csv|nfo|ini|json|php|js|css|ts|sql)$/i',
        'video'  => '/^(og?|mp4|webm|mp?g|mov|3gp)$/i',
        'audio'  => '/^(og?|mp3|mp?g|wav)$/i',
        'pdf'    => '/^(pdf)$/i',
        'flash'  => '/^(swf)$/i',
        'svga'   => '/^(svga)$/i',
    ];

    /**
     * Tell bootstrap-fileinput to treat SVGA as binary on existing values.
     * Maps to 'other' with a binary MIME so the plugin never wraps the
     * content in a <textarea>.
     */
    protected function guessPreviewType($file)
    {
        $extra = parent::guessPreviewType($file);

        if (($extra['type'] ?? '') === 'svga') {
            $extra['type']     = 'other';
            $extra['filetype'] = 'application/octet-stream';
            $extra['svga']     = true;
        }

        return $extra;
    }

    /**
     * Inject SVGA support into the per-instance fileinput() options.
     *
     * ROOT CAUSE (verified against fileinput.min.js v4.5.2 source):
     *
     * $.fn.fileinput.defaults does NOT contain fileTypeSettings in v4.5.2.
     * That object only holds simple scalar UI options (language, showCaption…).
     * fileTypeSettings lives on the plugin's internal defaults literal (the
     * 'Z' / 'v.defaults' object inside the IIFE closure) and is deep-merged
     * with per-instance options via $.extend(true, {}, internalDefaults, opts).
     *
     * The previous setupScripts() wrote:
     *   $.fn.fileinput.defaults.fileTypeSettings.svga = ...
     * This targeted a property that does not exist → TypeError / silent no-op.
     * The plugin's internal merge never saw the svga entry.
     *
     * _parseFileType(mime, filename) then looped through allowedPreviewTypes
     * (default: ["image","html","text","video","audio","flash","pdf","object"])
     * and called fileTypeSettings.text(mime, filename).  For a .svga file the
     * browser reports MIME="" or "text/plain" (unknown extension).
     * text:function matches t.compare(e,"text.*") → true for "text/plain" →
     * FileReader.readAsText() fires → binary content dumped into a <textarea>.
     *
     * THE FIX:
     *   1. Inject fileTypeSettings.svga (extension-only matcher) into the
     *      per-instance options. Since JSON cannot carry JS functions, we
     *      build the .fileinput() call manually in setupScripts().
     *   2. In allowedPreviewTypes, insert 'svga' BEFORE 'text'.  'text' is
     *      kept intact so .txt/.json/.js/.css etc. continue to render in the
     *      text preview as before.  Because _parseFileType() stops at the
     *      FIRST match, 'svga' is tried before 'text': for a .svga file the
     *      custom matcher returns true immediately and 'text' is never reached.
     *   3. The 'svga' type has no built-in plugin template, so the plugin
     *      falls through to the generic 'other' template (file icon + download
     *      link) — verified from the v4.5.2 source: _previewFile() calls
     *      _generatePreviewTemplate("svga", blobUrl, ...) and since there is
     *      no previewTemplates["svga"], it uses the 'other' fallback.
     *   4. FileReader.readAsArrayBuffer() (not readAsText) is invoked for the
     *      .svga file because D(text)=false, z(html)=false, $(image)=false;
     *      readAsArrayBuffer is only used for magic-byte sniffing and does NOT
     *      corrupt the file. The original File object is pushed to filestack
     *      unconditionally via r.addToStack(B) AFTER the FileReader path,
     *      so FormData always receives the raw binary blob.
     */
    protected function setupScripts($options)
    {
        $selector = $this->getElementClassSelector();

        $this->script = <<<SCRIPT
(function() {
    var _base = {$options};
    var _svgaTypes = ["image", "html", "svga", "text", "video", "audio", "flash", "pdf", "object"];
    var _merged = $.extend(true, {}, _base);
    _merged.allowedPreviewTypes = _svgaTypes;
    _merged.fileTypeSettings = $.extend({}, (_base.fileTypeSettings || {}), {
        svga: function(mime, filename) {
            return /\.svga$/i.test(filename || '');
        }
    });
    $("input{$selector}").fileinput(_merged);
})();
SCRIPT;

        if ($this->fileActionSettings['showRemove']) {
            $text = [
                'title'   => trans('admin.delete_confirm'),
                'confirm' => trans('admin.confirm'),
                'cancel'  => trans('admin.cancel'),
            ];

            $this->script .= <<<EOT

$("input{$selector}").on('filebeforedelete', function() {
    return new Promise(function(resolve, reject) {
        var remove = resolve;
        swal({
            title: "{$text['title']}",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "{$text['confirm']}",
            showLoaderOnConfirm: true,
            cancelButtonText: "{$text['cancel']}",
            preConfirm: function() {
                return new Promise(function(resolve) {
                    resolve(remove());
                });
            }
        });
    });
});
EOT;
        }
    }

    protected function preview()
    {
        if (!$this->value) return '';

        $url = $this->objectUrl($this->value);
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $uniqueId = 'file_' . uniqid();

        if (in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
            return "<img src='{$url}' class='file-preview-image img-responsive' style='max-height:150px'>";
        }

        return handleShowImageWithTypes($uniqueId, $url, 100, 100, 10);
    }
}
