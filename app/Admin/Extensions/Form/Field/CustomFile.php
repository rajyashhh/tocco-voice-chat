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
     * Inject SVGA into bootstrap-fileinput's fileTypeSettings BEFORE the
     * .fileinput() call.  This is the ONLY reliable hook: the field's inline
     * script runs during Admin::script() output, so prepending here ensures
     * the extension is live before the plugin initialises each instance.
     *
     * The vendor partials.js view is NOT rendered by the admin layout, so
     * any SVGA extension placed there has no effect.
     */
    protected function setupScripts($options)
    {
        parent::setupScripts($options);

        $svgaInit = <<<'SVGA_INIT'
if(typeof jQuery!=='undefined'&&jQuery.fn.fileinput){
 var d=jQuery.fn.fileinput.defaults;
 if(d&&d.fileTypeSettings&&!d.fileTypeSettings.svga){
  d.fileTypeSettings.svga=function(t,n){return/\.svga$/i.test(n)};
 }
}
SVGA_INIT;

        $this->script = $svgaInit . "\n" . $this->script;
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
