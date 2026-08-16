@if($errors->hasBag('exception'))
    @php
        // Encore swallows grid exceptions into a MessageBag without reporting
        // them, which made production failures undiagnosable from the logs.
        $__exceptionBag = $errors->getBag('exception');
        \Log::error('Admin panel exception (Encore grid/form)', [
            'type' => $__exceptionBag->first('type'),
            'message' => $__exceptionBag->first('message'),
            'file' => $__exceptionBag->first('file'),
            'line' => $__exceptionBag->first('line'),
            'url' => request()->fullUrl(),
            'admin_user' => optional(\Encore\Admin\Facades\Admin::user())->id,
            'trace' => \Illuminate\Support\Str::limit((string) $__exceptionBag->first('trace'), 4000),
        ]);
    @endphp
    @if(config('app.debug') == true)
        <?php $error = $errors->getBag('exception');?>
        <div class="alert alert-warning alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4>
                <i class="icon fa fa-warning"></i>
                <i style="border-bottom: 1px dotted #fff;cursor: pointer;" title="{{ $error->first('type') }}" ondblclick="var f=this.innerHTML;this.innerHTML=this.title;this.title=f;">{{ class_basename($error->first('type')) }}</i>
                In <i title="{{ $error->first('file') }} line {{ $error->first('line') }}" style="border-bottom: 1px dotted #fff;cursor: pointer;" ondblclick="var f=this.innerHTML;this.innerHTML=this.title;this.title=f;">{{ basename($error->first('file')) }} line {{ $error->first('line') }}</i> :
            </h4>
            <p><a style="cursor: pointer;" onclick="$('#laravel-admin-exception-trace').toggleClass('hidden');$('i', this).toggleClass('fa-angle-double-down fa-angle-double-up');"><i class="fa fa-angle-double-down"></i>&nbsp;&nbsp;{{ $error->first('message') }}</a></p>

            <p class="hidden" id="laravel-admin-exception-trace"><br>{!! nl2br(e($error->first('trace'))) !!}</p>
        </div>
    @else
        <div class="alert alert-warning">
            @if(app()->getLocale() === 'ar')
                <h4><i class="icon fa fa-warning"></i> حدث خطأ أثناء تحميل هذه الصفحة.</h4>
                <p>يرجى مراجعة سجلات النظام أو التواصل مع المسؤول.</p>
            @else
                <h4><i class="icon fa fa-warning"></i> An error occurred while loading this page.</h4>
                <p>Please check the application logs or contact the administrator.</p>
            @endif
        </div>
    @endif
@endif