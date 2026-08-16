
<style>
    .nav-tabs-custom>.nav-tabs>li.active {
        border-top-color: #3c8dbc;
    }

    /* RTL Support for Horizontal Form */
    .rtl .form-horizontal .control-label,
    [dir="rtl"] .form-horizontal .control-label {
        text-align: right !important;
    }

    .rtl .form-horizontal .col-sm-3,
    .rtl .form-horizontal .col-sm-8,
    [dir="rtl"] .form-horizontal .col-sm-3,
    [dir="rtl"] .form-horizontal .col-sm-8 {
        float: right !important;
    }

    .ltr .form-horizontal .control-label {
        text-align: right !important;
    }

    /* Ensure vertical alignment is consistent */
    .form-horizontal .control-label {
        padding-top: 7px;
        margin-bottom: 0;
    }
</style>

<div class="nav-tabs-custom">
    <div class="tab-content">
       
        
        <div class="tab-pane active" id="tab_2">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('family settings') }}</h3>
                </div>
                <form action="{{ route('admin.update-configs-group-chat') }}" method="POST" class="form-horizontal">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ __('price family') }}</label>
                            <div class="col-sm-8">
                                <input type="number" name="family_price" class="form-control"
                                         value="{{ $config['family_price'] ?? 0 }}"
                                    required>
                                <span
                                    class="help-block">{{ __('The price for creating a family.') }}</span>
                            </div>
                            
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-warning pull-right">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

