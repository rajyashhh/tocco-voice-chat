<div {!! $attributes !!}>
    <div class="inner">
        <h4>{{ $info }}</h4>

        <h4>{{ $name }}</h4>
    </div>
    <div class="icon">
        <i class="fa fa-{{ $icon }}"></i>
    </div>
    @if (!empty($link))
        <a href="{{ $link }}" class="small-box-footer">
            {{ trans('admin.more') }}&nbsp;
            <i class="fa fa-arrow-circle-right"></i>
        </a>
    @endif
</div>
