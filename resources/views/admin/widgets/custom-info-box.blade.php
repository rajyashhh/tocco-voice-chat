<div class="small-box bg-{{ $color }}" {!! $attributes !!} 
     style="display:flex; align-items:center; justify-content:space-between; padding:15px; overflow: auto; scrollbar-width: none; -ms-overflow-style: none;">
    <div class="inner">
        <h3 style="margin:0;">{!! $info !!}</h3>
        <p style="margin:0;">{{ $name }}</p>
    </div>
    <div class="icon" style="font-size: {{ $iconSize }}; top: 50px; ">
        <i class="fa fa-{{ $icon }}"></i>
    </div>
</div>

<style>
/* لإخفاء scrollbar في Webkit (Chrome, Safari, Edge) */
.small-box::-webkit-scrollbar {
    display: none;
}
</style>
