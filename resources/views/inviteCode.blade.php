
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
                    <h3 class="box-title">{{ __('invitation code settings') }}</h3>
                </div>
                <form action="{{ route('admin.app.settings.update') }}" method="POST" class="form-horizontal">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ __('earn from invitation') }}</label>
                            <div class="col-sm-8">
                                <input type="number" name="earn_from_invitation" class="form-control"
                                         value="{{ $config['earn_from_invitation'] ?? 0 }}"
                                    required>
                                <span
                                    class="help-block">{{ __('The percentage that the inviter takes from each recharge of the invited person.') }}</span>
                            </div>
                            <label class="col-sm-3 control-label">{{ __('invitation host reward') }}</label>
                            <div class="col-sm-8">
                                <input type="number" name="invitation_host_reward" class="form-control"
                                         value="{{ $config['invitation_host_reward'] ?? 0 }}"
                                    required>
                                <span
                                    class="help-block">{{ __('Instant reward (coins) for the inviter at the moment the invitation is accepted.') }}</span>
                            </div>
                            <label class="col-sm-3 control-label">{{ __('invitation invitee reward') }}</label>
                            <div class="col-sm-8">
                                <input type="number" name="invitation_invitee_reward" class="form-control"
                                         value="{{ $config['invitation_invitee_reward'] ?? 0 }}"
                                    required>
                                <span
                                    class="help-block">{{ __('Instant reward (coins) for the invited person at the moment of acceptance.') }}</span>
                            </div>

                            <label class="col-sm-3 control-label">{{ __('invitation code date') }}</label>
                            <div class="col-sm-8">
                                <input type="number" name="invitation_code_date" class="form-control"
                                         value="{{ $config['invitation_code_date'] ?? 0 }}"
                                    required>
                                <span
                                    class="help-block">{{ __('invitation code date help') }}</span>
                            </div>
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

