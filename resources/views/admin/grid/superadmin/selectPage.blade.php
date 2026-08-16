@php
    $superAdmins = \Modules\Country\Entities\SuperAdmin::all();
    $defaultSuperAdmin = \Modules\Country\Entities\SuperAdmin::where('default', 1)->first();
@endphp

<style>

    .rtl .bck-bt {
        width: 15%;
        margin: 15px auto;
        text-align: center;
        border-radius: 12px;
        border: 2px;
        background-color: var(--primary-color) !important;
        top: -73px;
        position: relative;
        left: -43%;
    }

    .ltr .bck-bt {
        width: 15%;
        margin: 15px auto;
        text-align: center;
        border-radius: 12px;
        border: 2px;
        background-color: var(--primary-color) !important;
        top: -73px;
        position: relative;
        right: -43%;
    }
</style>
<!-- *** ***************************************************************************************************** -->

<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 80px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: attr(data-label-off);
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        line-height: 26px;
        text-align: center;
        font-size: 12px;
        font-weight: bold;
        color: #000;
    }

    input:checked + .slider {
        background-color: #28a745;
    }

    input:checked + .slider:before {
        transform: translateX(46px);
        content: attr(data-label-on);
        color: #28a745;
    }

    .superadmin-form {
        padding: 20px 86px;
        border: 2px;
        border-radius: 30px;
        width: 50%;
        margin: 10px auto 88px;
    }

    .superadmin-info-box {
        width: 50%;
        margin: 20px auto;
        padding: 10px;
        border-radius: 10px;
    }

    @media (max-width: 576px) {
    }
    @media (max-width: 768px) {
        .superadmin-form {
            width: 100%;
        }
        .superadmin-info-box {
            width: 100%;
        }
        .rtl .bck-bt {
            width: 20%;
        }
    }
    @media (max-width: 992px) {
    }
    @media (max-width: 1200px) {
    }
    @media (max-width: 1400px) {
    }
</style>

<!-- *** ***************************************************************************************************** -->
<div style="" class="bck-bt">
    <a href="{{ route('admin.usersBd.index') }}" class="btn btn-secondary mt-3">{{ __('back') }}</a>
</div>

<div class="superadmin-info-box">
    <p>
        &#9432;
        {{ __('default_current_superadmin') }}:
        <span title="{{ __('default_current_info') }}" style="cursor: help; color: #007bff;">

        </span>
    </p>
</div>

<form action="{{ route('admin.make-superadmin-default') }}" method="POST" class="superadmin-form">
    @csrf
    <div class="form-group">
        <label for="superadmin_id">{{ __('select_default_superadmin') }}:</label>
        <select name="superadmin_id" id="superadmin_id" class="form-control" required>
            @foreach($superAdmins as $superAdmin)
                <option value="{{ $superAdmin->id }}" {{ ($defaultSuperAdmin && $superAdmin->id == $defaultSuperAdmin->id) ? 'selected' : '' }}>
                    {{ $superAdmin->name }} - {{ $superAdmin->username }}
                </option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary mt-2">{{ __('set_superadmin_as_default') }}</button>
</form>

