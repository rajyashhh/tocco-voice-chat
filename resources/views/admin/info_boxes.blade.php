<div class="row">
    <div class="col-md-3">
        {!! new \App\Admin\Widgets\CustomInfoBox(__('Total Played'), 'gamepad', 'blue', number_format($totals->total_played ?? 0, 2), '50px') !!}
    </div>
    <div class="col-md-3">
        {!! new \App\Admin\Widgets\CustomInfoBox(__('Total Loss'), 'times-circle', 'red', number_format($totals->total_loss ?? 0, 2), '50px') !!}
    </div>
    <div class="col-md-3">
        {!! new \App\Admin\Widgets\CustomInfoBox(__('Total Win'), 'trophy', 'orange', number_format($totals->total_win ?? 0, 2), '50px') !!}
    </div>
    <div class="col-md-3">
        {!! new \App\Admin\Widgets\CustomInfoBox(__('App Profit'), 'dollar', 'green', number_format($totals->app_profit ?? 0, 2), '50px') !!}
    </div>
</div>
