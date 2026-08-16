<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">أجراءات</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding">
        <ul class="nav nav-pills nav-stacked">
            {!! (new \App\Admin\Actions\BanUserId())->render () !!}
            {!! (new \App\Admin\Actions\RemoveBanUserId())->render () !!}
        </ul>
    </div>
    <!-- /.box-body -->
</div>
