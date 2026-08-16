<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>


:root {
        --primary-color: {{ config('themes.primaryColor') }};
        --secondary-color: {{ config('themes.secondaryColor') }};
        --text-primary-color: {{ config('themes.textPrimaryColor') }};
        --text-secondary-color: {{ config('themes.textSecondaryColor') }};
        --box-background-color: {{ config('themes.boxBackgroundColor') }};
        --table-background-color: {{ config('themes.tableBackGroundColor')}}
        --background-image: {{ config('themes.backgroundImage') }};
        --brand_background-image: url({{ getImagePath(config('themes.brandBackgroundImage')) }});
        --second-alpha: {{ adjustColor(config('themes.boxBackgroundColor'), -30, -30, -30) }}55;
        --primary-hover-alpha: {{ config('themes.primaryColor')}}33;
        --scroll-second-color: {{ config('themes.boxBackgroundColor') }}cc;
        --scroll-first-color: {{ adjustColor(config('themes.primaryColor'), 40, 40, 40) }}33;


        --inverse-color: {{getLighterColor(config('themes.primaryColor'))}};
        --inverse-box-color: {{adjustTextColor(config('themes.boxBackgroundColor'))}};
        --success-button: linear-gradient(90deg, {{adjustColor(config('themes.primaryColor'))}} 0%, {{config('themes.primaryColor')}} 100%);
        --primary-button: linear-gradient(90deg, {{adjustColor(config('themes.primaryColor'))}} 0%, {{config('themes.primaryColor')}} 100%);
    }
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 57px;
    color: white;
    font-size: 20px;
    margin-bottom: 6px;
}

.stat-icon.bg-blue {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
}

.stat-icon.bg-green {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

.stat-label {
    font-size: 13px;
    color: #7f8c8d;
    margin-top: 5px;
}

.section-box {
    background: var(--secondary-color);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}


.section-header h4 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-header .text-yellow {
    color: #f39c12;
}

.section-header .text-red {
    color: #e74c3c;
}


.number-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #ecf0f1;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
}

.number-badge.warning {
    background: #fef9e7;
    color: #f39c12;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 0;
    color: #95a5a6;
}

.empty-state i {
    font-size: 40px;
    margin-bottom: 10px;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

.empty-table {
    padding: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #95a5a6;
    background: white;
    border-radius: 8px;
    margin: 15px 0;
}

.empty-table i {
    font-size: 40px;
    margin-bottom: 10px;
}

.empty-table p {
    margin: 0;
    font-size: 14px;
}
.agency-profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
    /* background: var(--secondary-color); */
    /* filter: brightness(0.85); */

}

.agency-header {
    display: flex;
    align-items: flex-start;
    gap: 25px;
    margin-bottom: 30px;
    position: relative;
    padding: 20px;
    background: var(--secondary-color);
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    /* filter: brightness(0.5); */

}

.agency-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.agency-avatar .logo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.agency-info {
    flex: 1;
}

.agency-name {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 28px;
    font-weight: 700;
}

.agency-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
}

.meta-label {
    font-weight: 600;
    color: #7f8c8d;
}

.meta-value {
    color: #34495e;
}

.meta-uuid {
    color: #95a5a6;
    font-size: 0.9em;
}

.agency-stats {
    display: flex;
    gap: 15px;
}

.stat-card {
    background: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    text-align: center;
    min-width: 200px;

}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #3498db;
}

.stat-label {
    font-size: 12px;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ltr .btn-back {
    background: #ecf0f1;
    border: none;
    border-radius: 6px;
    color: #7f8c8d;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.rtl .btn-back {
    background: #ecf0f1;
    border: none;
    border-radius: 6px;
    color: #7f8c8d;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.fa-arrow-left{
    margin-top: 5px;
}

.btn-back:hover {
    background: #d6e0e3;
    color: #34495e;
}

.notice-section {
    background: #fff8e1;
    border-left: 4px solid #ffc107;
    padding: 15px;
    border-radius: 0 6px 6px 0;
    margin-bottom: 25px;
}

.notice-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #ff9800;
    font-weight: 600;
}

.notice-content {
    color: #5d4037;
    line-height: 1.5;
}

.top-performers-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .top-performers-section {
        grid-template-columns: 1fr;
    }
}

.performers-card {
    background: var(--secondary-color);
    filter: brightness(0.85);
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.ltr .performers-card{
    margin-left: 20px;
}

.rtl .performers-card{
    margin-right: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.stats-row {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
}

.section-title {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #2c3e50;
}

.section-badge {
    background: #3498db;
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.avatar-grid {
    /* display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 15px; */
    align-content: center;
    width: 12%;
    margin: auto;
    border: 2px solid;
    height: 60px;
    padding: 10px;
    border-radius: 31px;
}

.avatar-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s;
}

.avatar-item:hover {
    transform: translateY(-3px);
}

.avatar-img-container {
    position: relative;
    width: 60px;
    height: 60px;
    margin-bottom: 8px;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.avatar-badge {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    border: 2px solid white;
}

.avatar-badge.admin {
    background: #27ae60;
}

.avatar-name {
    font-size: 12px;
    text-align: center;
    max-width: 80px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 0;
    color: #95a5a6;
}

.empty-state i {
    font-size: 40px;
    margin-bottom: 10px;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

.agency-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
    overflow-x: auto;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-weight: 600;

    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}

.tab-btn.active {
    color:var(--primary-color) ;
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.card {
    background: var(--secondary-color);
    filter: brightness(0.85);
    padding-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--secondary-color);
}

.card-header h3 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
}

.count-badge {
    background: #ecf0f1;
    color: #7f8c8d;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--secondary-color);
}


.table-section {
    width: 100%;
    border-collapse: collapse;
    background: var(--secondary-color);
}

.data-table th {
    /* text-align: left; */
    padding: 12px 15px;
    background: var(--secondary-color);

    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-info strong {
    font-size: 14px;
}

.user-info small {
    font-size: 11px;
    color: #95a5a6;
}

.role-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.role-badge.owner {
    background: #9b59b6;
}

.role-badge.admin {
    background: #27ae60;
}

.btn-action {
    padding: 5px 10px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-action:hover {
    background: #2980b9;
}

.empty-table {
    padding: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #95a5a6;
}

.empty-table i {
    font-size: 40px;
    margin-bottom: 10px;
}

.empty-table p {
    margin: 0;
    font-size: 14px;
}

.user-avatar,
.supporter-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
}
.supporters-avatars {
    display: flex;
    gap: 5px;
    align-items: center;
}
.user-info-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}


.pagination-wrapper {
    padding: 15px 20px;
    display: flex;
    justify-content: center;
    border-top: 1px solid #eee;
     background: var(--secondary-color);
}



    .target-card-section-1{
        /* display: inline-flex; */
        width: 100%;
        padding-top: 26px;
        margin-bottom: 35px;

    }
    .card-target-filter{
        display: inline;
        width: 34%;
        left: 33px;
        position: absolute;
    }
    .card-target-filter .form-group {
        margin-bottom: 16px;
        right: 20px;
        position: relative;
        top: 10px;
    }
    .card-target-filter button {
        position: relative;
        left: -49px;
        bottom: -29px;
    }
    .card-target-filter-phone{
        width: 49%;
        position: relative;
    }
    .card-target-filter-phone .form-group {
        margin-bottom: 16px;
        position: relative;
        top: 58px;
    }

    .rtl .card-target-filter-phone .form-group{
        right: 20px;
    }

    .ltr .card-target-filter-phone .form-group{
        left: 20px;
    }

.card-target-filter-phone button {
        position: relative;
        bottom: -58px;
    }

    .target-card-stat{
        width: 50%;
    }
    .filter-form{
        border-radius: 13px;
        height: 165px;
        margin-top: 20px;
    }

    .ltr .filter-form{
        margin-right: 20px;
    }

    .rtl .filter-form{
        margin-left: 20px;
    }

    .card-target-filter-phone{
        /* display: none; */
    }
    .card-target-filter{
        display: none;
    }
    @media (max-width: 768px) {
    .stats-row {
        flex-direction: column;
        width: 108%;

    }

    .avatar-grid {
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    }

    .target-card-section-1 {
            display: grid;
            width: 100%;
            padding-top: 26px;
            margin-bottom: 35px;
        }


    .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 118px;
            color: white;
            font-size: 20px;
            margin-bottom: 6px;

    }


    .target-card-stat {
         width: 92%;

    }
    .card-target-filter{
        display: none;
    }
    .card-target-filter-phone {
        display: block;
        width: 49%;
        left: 0px;
        position: relative;

    }
    .card-target-filter-phone  .col-md-7{
       float: none;
    }

    .card-target-filter-phone .form-control {
            display: block;
            width: 89%;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            color: var(--text-secondary-color) !important;
            background-color: #fff;
            background-image: none;
            border: 1px solid var(--primary-hover-alpha) !important;
            border-radius: 4px;
        }

        .card-target-filter-phone .filter-form {
            border-radius: 13px;
            height: 238px;
        }

        .card-target-filter-phone .align-items-end{
            display: grid;
        }

        .card-target-filter-phone button {
            left: -224px
        }
}
.table-responsive {
    margin-right: 20px;
    margin-left: 20px;
}

.agency-tabs {
    margin: 20px 20px 20px 20px;
}

.filter-section {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
}

.select2-container--default .select2-selection--single {
    height: 38px;
    line-height: 38px;
    border: 1px solid #ced4da;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

</style>


</head>
<body>
    <div class="agency-profile-container">
        <!-- Header Section -->
        <div class="agency-header">
                    <div class="agency-avatar" style="
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-left: 10px;
        ">
                   @php
                    $image =getImagePath($agency->img);
                    $defaultImage =asset("images/icon-agency.jpg");


                    if (!isImageExists($image)) {
                        $image = $defaultImage;
                    }


                @endphp
            <img src="{{ $image }}" alt="Agency Logo" class="logo-img" style="
                width: 100%;
                height: 100%;
                object-fit: contain;
                padding: 5px;
            ">
        </div>
            <div class="agency-info">
                <h1 class="agency-name">{{ @$agency?->name ?? ''}}</h1>
                <div class="agency-meta">
                    <div class="meta-item">
                        <span class="meta-label">{{__("ID")}}:</span>
                        <span class="meta-value">{{ @$agency->id }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">{{__("Phone")}}:</span>
                        <span class="meta-value">{{ @$agency->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">{{__("Owner")}}:</span>
                        <div style="
                            width: 30px;
                            height: 30px;
                            border-radius: 50%;
                            overflow: hidden;
                            background: #f0f0f0;
                            border: 2px solid rgba(255,255,255,0.3);
                        ">
                              @php
                                $image =getImagePath($agency?->owner?->profile?->avatar);
                                $defaultImage =asset("images/businessman-icon.jpg");


                                if (!isImageExists($image)) {
                                    $image = $defaultImage;
                                }


                          @endphp
                            <img src="{{ $image }}"
                                alt="Owner"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>

                        <span class="meta-value">
                            @if($agency?->owner)
                                <a href="{{ url('bd/users/profile/' . $agency?->owner->id) }}">
                                    {{ $agency->owner->name ?? '' }}
                                </a>
                                 <br>
                            <span class="meta-uuid">(UUID: {{ $agency?->owner->uuid ?? 'N/A' }})</span>
                            @else

                            @endif
                        </span>

                    </div>
                </div>
                <div class="agency-stats">
                    <!-- <div class="stat-card">
                        <div class="stat-value">{{ number_format(@$agency->coins) ?? 0 }}</div>
                        <div class="stat-label">{{__("coins")}}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format(@$agency->salary) ?? 0 }}</div>
                        <div class="stat-label">{{__("salary")}}</div>
                    </div> -->
                </div>
            </div>
            <a class="btn btn-success btn-back"
                href="{{ url('download-charge-agency/' . $agency?->id) . '?' . http_build_query(request()->all()) }}">
                    {{ __('Export to Excel') }}
                </a>
           <button class="btn-back" onclick="window.location.href='{{ bd_url('/charges') }}'">
                {{ __('Go Back') }} <i class="fas fa-arrow-left"></i>
            </button>



        </div>

        <div class="card">
            <div class="card-body">
        <!-- Notice Section -->
        @if(@$agency->notice)
        <div class="notice-section">
            <div class="notice-header">
                <i class="fas fa-info-circle"></i>
                <span>{{__("Notice")}}</span>
            </div>
            <div class="notice-content">
                {{ @$agency->notice }}
            </div>
        </div>
        @endif


                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; padding: 15px;">
                    <!-- Card 1: Balance -->
                    <div class="performers-card" style="font-family: Arial, sans-serif;">
                        <div class="card-content" style="
                            padding: 20px;
                            display: flex;
                            justify-content: space-between;
                            background: linear-gradient(135deg, #3a4f6a 0%, #265329 100%);
                            border-radius: 12px;
                            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                            color: white;
                            position: relative;
                            overflow: hidden;
                            min-height: 180px;
                            height: 100%;
                        ">
                            <!-- Decorative elements -->
                            <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                            <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>

                            <!-- Left Content -->
                            <div style="width: 50%; display: flex; flex-direction: column; justify-content: space-between; z-index: 2;">
                                <div>
                                    <div style="color: rgba(255,255,255,0.7); margin-bottom: 5px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                        {{ __('coin shipping agency wallet') }}
                                    </div>
                                </div>
                                <div>
                                    <div style="color: rgba(255,255,255,0.7); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                        {{ __('Balance') }}
                                    </div>
                                    <div style="font-size: 28px; font-weight: bold; letter-spacing: 1px; font-family: 'Courier New', monospace; margin-top: 5px;">
                                        {{ numToString(@$agency->coins) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Right Content -->
                            <div style="width: 45%; display: flex; flex-direction: column; justify-content: center; gap: 12px; z-index: 2;">
                                <div style="color: white;font-size: 18px; font-weight: bold; letter-spacing: 1px;">
                                    {{ \App\Helpers\Common::getSettingsValue(app()->getLocale() == 'ar' ? 'app_title_ar' : 'app_title_en') }}
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <img src="{{ asset('images/coin.jpg') }}" alt="Coin" style="width: 40px; height: 40px;">
                                </div>
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <span style="color: white; font-size: 14px; text-transform: uppercase;">
                                        {{__('id')}} {{ @$agency->owner->uuid }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <span style="color: white; font-size: 14px; text-transform: uppercase;">
                                        {{ @$agency->owner->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Sent Balance -->
                    {{-- <div class="performers-card" style="font-family: Arial, sans-serif;">
                        <div class="card-content" style="
                            padding: 20px;
                            display: flex;
                            justify-content: space-between;
                            background: linear-gradient(135deg, #3a4f6a 0%, #53264d 100%);
                            border-radius: 12px;
                            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                            color: white;
                            position: relative;
                            overflow: hidden;
                            min-height: 180px;
                            height: 100%;
                        ">
                            <!-- Decorative elements -->
                            <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                            <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>

                            <!-- Left Content -->
                            <div style="width: 50%; display: flex; flex-direction: column; justify-content: space-between; z-index: 2;">
                                <div>
                                    <div style="color: rgba(255,255,255,0.7); margin-bottom: 5px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                        {{ __('coin shipping agency wallet') }}
                                    </div>
                                </div>
                                <div>
                                    <div style="color: rgba(255,255,255,0.7); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                        {{ __('Sent Balance') }}
                                    </div>
                                    <div style="font-size: 28px; font-weight: bold; letter-spacing: 1px; font-family: 'Courier New', monospace; margin-top: 5px;">
                                        {{ numToString(@$totalSend) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Right Content -->
                            <div style="width: 45%; display: flex; flex-direction: column; justify-content: center; gap: 12px; z-index: 2;">
                                <div style="color: white;font-size: 18px; font-weight: bold; letter-spacing: 1px;">
                                    {{ \App\Helpers\Common::getSettingsValue(app()->getLocale() == 'ar' ? 'app_title_ar' : 'app_title_en') }}
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <img src="{{ asset('images/dollar.jpg') }}" alt="Coin" style="width: 40px; height: 40px;">
                                </div>
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <span style="color: white; font-size: 14px; text-transform: uppercase;">
                                        {{__('id')}} {{ @$agency->owner->uuid }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <span style="color: white; font-size: 14px; text-transform: uppercase;">
                                        {{ @$agency->owner->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Received Balance -->
                    <div class="performers-card" style="font-family: Arial, sans-serif;">
                        <div class="card-content" style="
                            padding: 20px;
                            display: flex;
                            justify-content: space-between;
                            background: linear-gradient(135deg, #3a4f6a 0%, #532626 100%);
                            border-radius: 12px;
                            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                            color: white;
                            position: relative;
                            overflow: hidden;
                            min-height: 180px;
                            height: 100%;
                        ">
                            <!-- Decorative elements -->
                            <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                            <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>

                            <!-- Left Content -->
                            <div style="width: 50%; display: flex; flex-direction: column; justify-content: space-between; z-index: 2;">
                                <div>
                                    <div style="color: rgba(255,255,255,0.7); margin-bottom: 5px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                        {{ __('coin shipping agency wallet') }}
                                    </div>
                                </div>
                                <div>
                                    <div style="color: rgba(255,255,255,0.7); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                        {{ __('Received Balance') }}
                                    </div>
                                    <div style="font-size: 28px; font-weight: bold; letter-spacing: 1px; font-family: 'Courier New', monospace; margin-top: 5px;">
                                        {{ numToString(@$totalReceive) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Right Content -->
                            <div style="width: 45%; display: flex; flex-direction: column; justify-content: center; gap: 12px; z-index: 2;">
                                <div style="color: white;font-size: 18px; font-weight: bold; letter-spacing: 1px;">
                                    {{ \App\Helpers\Common::getSettingsValue(app()->getLocale() == 'ar' ? 'app_title_ar' : 'app_title_en') }}
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <img src="{{ asset('images/dollar.jpg') }}" alt="Coin" style="width: 40px; height: 40px;">
                                </div>
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <span style="color: white; font-size: 14px; text-transform: uppercase;">
                                        {{__('id')}} {{ @$agency->owner->uuid }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <span style="color: white; font-size: 14px; text-transform: uppercase;">
                                        {{ @$agency->owner->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                </div>
                 <div style="display: flex; flex-direction: column; align-items: center; gap: 20px; padding: 15px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; width: 100%; max-width: 800px;">
                        <!-- Card 1 -->
                        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; display: flex; flex-direction: column; align-items: center;">
                        <div style="font-size: 24px; font-weight: bold; margin-bottom: 10px;">{{ __('Sent Balance') }}</div>

                        <div style="margin-top: 10px; font-size: 16px;">{{numToString(@$totalSend)}}
                            <img src="{{ asset('images/coin.jpg') }}" alt="Coin" style="width: 40px; height: 40px;">
                        </div>
                        </div>

                        <!-- Card 2 -->
                        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; display: flex; flex-direction: column; align-items: center;">
                        <div style="font-size: 24px; font-weight: bold; margin-bottom: 10px;">{{__("Received Balance")}}</div>

                        <div style="margin-top: 10px; font-size: 16px;">{{ numToString(@$totalReceive) }}
                            <img src="{{ asset('images/coin.jpg') }}" alt="Coin" style="width: 40px; height: 40px;">
                        </div>
                        </div>
                    </div>
                    </div>
                </div>

@php
    $activeTab = request('tab', 'charges');
@endphp

<!-- Navigation Tabs -->
<div class="agency-tabs">
    <a href="?tab=charges" class="tab-btn {{ ($activeTab == 'charges') ? 'active' : '' }}" data-target="charges-tab">{{ __('Sent Transactions') }}</a>
    <a href="?tab=resived" class="tab-btn {{ ($activeTab == 'resived') ? 'active' : '' }}" data-target="resived-tab">{{ __('Received Transactions') }}</a>
</div>

<!-- Loading Indicator -->
<div id="tab-loading" style="
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    background: var(--primary-color);
    color: var(--text-primary-color);
    z-index: 9999;
    padding: 30px 40px;
    border-radius: 10px;
    font-size: 20px;
    font-weight: bold;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
">
    {{ __('Loading...') }}
</div>
<!-- Charges Tab Content -->
<div id="charges-tab" style="display: {{ ($activeTab == 'charges') ? 'block' : 'none' }}">
    <div class="performers-card">
        <div class="filter-section mb-3">
            <select id="receiver-type" class="form-control" style="width: 200px;">
                <option value="">{{ __('Select type') }}</option>
                <option value="user" {{ request('filter_by') == 'user' ? 'selected' : '' }}>{{ __('Users') }}</option>
                <option value="agency" {{ request('filter_by') == 'agency' ? 'selected' : '' }}>{{ __('Agencies') }}</option>
            </select>

            <select id="receiver-id" class="form-control select2" style="width: 300px;">
                <option value="">{{ __('Search') }}</option>
            </select>

            <button class="btn btn-primary search-btn" data-tab="charges">
                <i class="fas fa-search"></i> {{ __('Search') }}
            </button>

            <button class="btn btn-secondary reset-filters" data-tab="charges">
                <i class="fas fa-redo"></i> {{ __('Reset') }}
            </button>
        </div>
        @if($charges && $charges->count())
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                        <th>{{__("id")}}</th>
                            <th>{{ __('receiver') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('coins') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($charges as $index => $charge)
                                 @php
                                    $sender = \App\Helpers\Common::getChargerInfo($charge);
                                    $receiver = \App\Helpers\Common::getReceiverInfo($charge);
                                    $url = $receiver['url'];
                                    $image = getImagePath($receiver['image']);
                                    $defaultImage = $charge->user_type == 'agency'?  asset("images/icon-agency.jpg") :asset('images/businessman-icon.jpg');
                                     if (!isImageExists($image)) {
                                            $image = $defaultImage;
                                        }
                                @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{ $charge?->id}}</td>
                                <td>

                                        <div style="display: flex; align-items: center; gap: 10px;">
                                           <img src="{{ $image }}" alt="user"
                                                width="{{ $charge?->user_type == 'agency' ? '50' : '40' }}"
                                                height="40"
                                                style="border-radius: {{ $charge->user_type == 'agency' ? '0' : '50%' }};">
                                            <div>
                                                <strong>
                                                     <a href="{{ $url }}" target="_blank" style="text-decoration: none; color: inherit;">
                                                    {{ $receiver['name'] }}
                                                </a></strong><br>
                                                <small>uuid: {{ $receiver['uuid'] }}</small>
                                            </div>
                                        </div>
                                </td>
                                <td>{{ $charge?->usd !== null ? '$' . number_format($charge->usd, 2) : 0 }}</td>
                                <td>{{ $charge?->amount ?? '-' }}</td>
                                <td>{{ $charge?->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $charges->withQueryString()->links() }}
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <p>{{ __('No  data available') }}</p>
            </div>
        @endif
    </div>
</div>

<!-- Resived Tab Content -->
<div id="resived-tab" style="display: {{ ($activeTab == 'resived') ? 'block' : 'none' }}">
    <div class="performers-card">
        <div class="filter-section mb-3">
            <select id="sender-type" class="form-control" style="width: 200px;">
                <option value="">{{ __('Select type') }}</option>
                <option value="user">{{ __('Users') }}</option>
                <option value="host_agency">{{ __('Agencies') }}</option>
                <option value="agency">{{ __('Shipping Agencies') }}</option>
                <option value="bd">{{ __('BD') }}</option>
                <option value="dash">{{ __('admins') }}</option>
            </select>

            <select id="sender-id" class="form-control select2" style="width: 300px;">
                <option value="">{{ __('Search') }}</option>
            </select>

            <button class="btn btn-primary search-btn" data-tab="resived">
                <i class="fas fa-search"></i> {{ __('Search') }}
            </button>

            <button class="btn btn-secondary reset-filters" data-tab="resived">
                <i class="fas fa-redo"></i> {{ __('Reset') }}
            </button>
        </div>
        @if($resiveds && $resiveds->count())
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{__("id")}}</th>
                            <th>{{ __('Sender') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('coins') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resiveds as $index => $res)
                                @php
                                    $sender = \App\Helpers\Common::getChargerInfo($res);
                                    $receiver = \App\Helpers\Common::getReceiverInfo($res);
                                    $image = getImagePath($sender['image']);
                                   // $url = $sender['url'];
                                    $url = '';
                                    $defaultImage = $res->charger_type == 'agency'?  asset("images/icon-agency.jpg") :asset('images/businessman-icon.jpg');
                                     if (!isImageExists($image)) {
                                            $image = $defaultImage;
                                        }
                                @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{ $res?->id }}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="{{ $image }}" alt="user"
                                            width="{{ $res->charger_type == 'agency' ? '50' : '40' }}"
                                            height="40"
                                            style="border-radius: {{ $res->charger_type == 'agency' ? '0' : '50%' }};">
                                             <div>
                                             <!-- target="_blank" -->

                                            <strong> <a href="{{ $url }}"
                                              style="text-decoration: none; color: inherit;">
                                                {{ $sender['name'] }}
                                            </a></strong><br>
                                            <small>uuid: {{$sender['uuid'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $res->usd !== null ? '$' . number_format($res->usd, 2) : 0 }}</td>
                                <td>{{ $res->amount ?? '-' }}</td>
                                <td>{{ $res->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $resiveds->withQueryString()->links() }}
            </div>
        @else
            <p class="text-center text-muted">{{ __('No received charges found.') }}</p>
        @endif
    </div>
</div>

        </div>
    </div>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
        $('.reset-filters').on('click', function() {
            const tab = $(this).data('tab');
            const currentUrl = new URL(window.location.href);

            Array.from(currentUrl.searchParams.keys()).forEach(key => {
                if (key !== 'tab') {
                    currentUrl.searchParams.delete(key);
                }
            });

            currentUrl.searchParams.set('tab', tab);

            window.location.href = currentUrl.toString();
        });

    });
</script>

<script>
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'charges';

        $('.select2').select2({
            placeholder: "Search",
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                delay: 250,
                url: "{{ route('search.charges') }}",
                dataType: 'json',
                data: function(params) {
                    return {
                        q: params.term,
                        type: $(this).parent().find('.form-control:first').val(),
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.data.map(item => ({
                            id: item.id,
                            text: item.name
                        })),
                        pagination: {
                            more: data.current_page < data.last_page
                        }
                    };
                },
                cache: true
            }
        });

        if (currentTab === 'charges') {
            const filterBy = urlParams.get('filter_by');
            const filterId = urlParams.get('filter_id');

            if (filterBy) {
                $('#receiver-type').val(filterBy);
            }

            if (filterId) {
                $.ajax({
                    url: "{{ route('search.charges') }}",
                    data: {
                        id: filterId,
                        type: filterBy
                    },
                    success: function(response) {
                        if (response.data && response.data.length > 0) {
                            const option = new Option(response.data[0].name, filterId, true, true);
                            $('#receiver-id').append(option).trigger('change');
                        }
                    }
                });
            }
        } else if (currentTab === 'resived') {
            const senderType = urlParams.get('sender_type');
            const senderId = urlParams.get('sender_id');

            if (senderType) {
                $('#sender-type').val(senderType);
            }

            if (senderId) {
                $.ajax({
                    url: "{{ route('search.charges') }}",
                    data: {
                        id: senderId,
                        type: senderType
                    },
                    success: function(response) {
                        if (response.data && response.data.length > 0) {
                            const option = new Option(response.data[0].name, senderId, true, true);
                            $('#sender-id').append(option).trigger('change');
                        }
                    }
                });
            }
        }

        $('#receiver-type, #sender-type').on('change', function() {
            $(this).siblings('.select2').val(null).trigger('change');
        });

        $('.search-btn').on('click', function() {
            const tab = $(this).data('tab');
            const currentUrl = new URL(window.location.href);

            if (tab === 'charges') {
                const type = $('#receiver-type').val();
                const id = $('#receiver-id').val();
                if(type) currentUrl.searchParams.set('filter_by', type);
                else currentUrl.searchParams.delete('filter_by');
                if(id) currentUrl.searchParams.set('filter_id', id);
                else currentUrl.searchParams.delete('filter_id');
                currentUrl.searchParams.set('tab', 'charges');
            } else {
                const type = $('#sender-type').val();
                const id = $('#sender-id').val();
                if(type) currentUrl.searchParams.set('sender_type', type);
                else currentUrl.searchParams.delete('sender_type');
                if(id) currentUrl.searchParams.set('sender_id', id);
                else currentUrl.searchParams.delete('sender_id');
                currentUrl.searchParams.set('tab', 'resived');
            }
            window.location.href = currentUrl.toString();
        });

        $('.reset-filters').on('click', function() {
            const tab = $(this).data('tab');
            const currentUrl = new URL(window.location.href);

            Array.from(currentUrl.searchParams.keys()).forEach(key => {
                if (key !== 'tab') {
                    currentUrl.searchParams.delete(key);
                }
            });

            currentUrl.searchParams.set('tab', tab);
            window.location.href = currentUrl.toString();
        });
    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const selectedTab = (urlParams.get('tab') || 'charges') + '-tab';

    const allTabs = document.querySelectorAll('.tab-btn');
    const allTabContents = document.querySelectorAll('[id$="-tab"]');

    let targetElement = null;

    allTabs.forEach(tab => {
        const target = tab.getAttribute('data-target');
        const content = document.getElementById(target);

        if (target === selectedTab) {
            tab.classList.add('active');
            if (content) { content.style.display = 'block'; content.classList.add('active'); }
        } else {
            tab.classList.remove('active');
            if (content) { content.style.display = 'none'; content.classList.remove('active'); }
        }

        tab.addEventListener('click', function (e) {
            e.preventDefault();
            allTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            allTabs.forEach(t => {
                const panelId = t.getAttribute('data-target');
                const panel = document.getElementById(panelId);
                if (!panel) return;
                if (panelId === target) { panel.style.display = 'block'; panel.classList.add('active'); }
                else { panel.style.display = 'none'; panel.classList.remove('active'); }
            });
            const url = new URL(window.location.href);
            const tabParam = new URLSearchParams(this.getAttribute('href').replace('?', ''));
            url.searchParams.set('tab', tabParam.get('tab'));
            window.history.replaceState({}, '', url.toString());
        });
    });
});
</script>

