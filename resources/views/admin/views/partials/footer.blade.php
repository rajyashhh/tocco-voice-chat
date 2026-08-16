<!-- Main Footer -->
<footer class="main-footer">
    <!-- To the right -->
    <div class="pull-right hidden-xs">
        @if(config('admin.show_environment'))
            <strong>Env</strong>&nbsp;&nbsp; {!! config('app.env') !!}
        @endif

        &nbsp;&nbsp;&nbsp;&nbsp;

        @if(config('admin.show_version'))
        <strong>Version</strong>&nbsp;&nbsp; {!! \Encore\Admin\Admin::VERSION !!}
        @endif

    </div>
    <!-- 🔹 مودال عرض الوصف -->
<style>
    .modal-title{
        color: var(--text-secondary-color);
    }
</style>
        <div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="max-width: 800px; min-height: 400px;">
            <div class="modal-content" style="background-color: var(--box-background-color); color: var(--text-secondary-color); height:100%;">
                <div class="modal-header" style="background-color: var(--primary-color); color: var(--text-secondary-color);">
                    <h5 class="modal-title" id="modalDescriptionTitle"></h5>
                    <button type="button" class="close" data-dismiss="modal" style="color: var(--text-secondary-color);">&times;</button>
                </div>
                <div class="modal-body" style="height:80%; background-color: var(--table-background-color); color: var(--text-secondary-color);">
                    <p id="modalDescriptionContent"></p>
                </div>
                <div class="modal-footer" style="background-color: var(--secondary-color);">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background-color: var(--primary-color); border: none; color: white;">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- 🔹 مودال عرض الصورة -->

    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="max-width: 600px;">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <img id="modalImageContent" src="" style="max-width: 100%; height: auto;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Default to the left -->
    <!-- <div class="modal fade" id="langModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="max-width: 900px;    min-height: 500px;">
        <div class="modal-content" style="background-color: var(--box-background-color); color: var(--text-secondary-color); height:100%;">
            <div class="modal-header" style="background-color: var(--primary-color); color: var(--text-secondary-color);">
                <h5 class="modal-title" id="modalLangTitle"></h5>
                <button type="button" class="close" data-dismiss="modal" style="color: var(--text-secondary-color);">&times;</button>
            </div>
            <div class="modal-body" style="height:80%; background-color: var(--table-background-color); color: var(--text-secondary-color);">
                <p id="modalLangContent"></p>
            </div>
            <div class="modal-footer" style="background-color: var(--secondary-color);">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background-color: var(--primary-color); border: none; color: white;">Close</button>
            </div>
        </div>
    </div>
</div> -->
<style>
    .modal-title{
        color: var(--text-secondary-color);
    }
</style>
<div class="modal fade" id="langModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="max-width: 900px; min-height: 500px;">
        <div class="modal-content" style="background-color: var(--box-background-color); color: var(--text-secondary-color); height:100%;">
            <div class="modal-header" style="background-color: var(--primary-color); color: var(--text-secondary-color);">
                <h5 class="modal-title" id="modalLangTitle"></h5>
                <button type="button" class="close" data-dismiss="modal" style="color: var(--text-secondary-color);">&times;</button>
            </div>
            <div class="modal-body" style="height:80%; background-color: var(--table-background-color); color: var(--text-secondary-color);">
                <p><strong>📌{{ __('title') }}:</strong> <span id="modalNotifTitle"></span></p>
                <p><strong>📩 {{ __('Message') }}:</strong> <span id="modalNotifMessage"></span></p>
            </div>
            <div class="modal-footer" style="background-color: var(--secondary-color);">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background-color: var(--primary-color); border: none; color: white;">إغلاق</button>
            </div>
        </div>
    </div>
</div>
</footer>

