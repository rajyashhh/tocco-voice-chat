


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Cup Target Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
        --primary-color: {{ config('themes.primaryColor') }};
        --secondary-color: {{ config('themes.secondaryColor') }};
        --text-primary-color: {{ config('themes.textPrimaryColor') }};
        --text-secondary-color: {{ config('themes.textSecondaryColor') }};
        --box-background-color: {{ config('themes.boxBackgroundColor') }};
        --table-background-color: {{ config('themes.tableBackGroundColor')}}
             --background-image:{{ config('themes.backgroundImage') }};
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .all-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .settings-content {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: var(--primary-color);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .input-group {
            display: flex;
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .btn {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            background-color: white;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-copy {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: none;
        }

        .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 30px 0;
            position: relative;
        }

        .divider-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 0 15px;
            color: #6c757d;
            font-size: 14px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(75, 181, 67, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .all-page {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="all-page">
        <div class="settings-content">
            <div class="modal-body p-0">
                <div class="p-4">
                    <!-- Room Cup Target Section -->
                    <div class="section-header">

                        <h2 class="section-title">{{__('Room Cup Target Settings')}}</h2>
                    </div>

                    <div class="settings-grid">
                        <div class="card">
                            <br>
                            <div class="form-group">
                                <label class="form-label" for="lang_id">{{__('Language')}}</label>
                                <select id="lang_id" name="lang_id" class="form-control" style="width: 100%;" required></select>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-title">{{__('Room Cup Target Details')}}</div>
                            <div class="form-group">
                                <label class="form-label">{{__('Link')}}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="zego_client_id" readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-copy" id="copy-zego-id">{{__('Copy')}}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="divider">
                        <span class="divider-text">{{__('Super Admin Configuration')}}</span>
                    </div>

                    <!-- Super Admin Section -->
                    <div class="section-header">
                        <h2 class="section-title">{{__('Super Admin Details')}}</h2>
                    </div>

                    <div class="settings-grid">
                        <div class="card">
                            <br>
                            <div class="form-group">
                                <label for="target_id" class="form-label">{{__('Countries')}}</label>
                                <select id="target_id" name="country_id" class="form-control" style="width: 100%;" required></select>
                            </div>

                        </div>

                        <div class="card">
                            <div class="card-title">{{__('Super Admin Details')}}</div>
                            <div class="form-group">
                                <label class="form-label">{{__('Link')}}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="super_admin_link" readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-copy" id="copy-admin-link">{{__('Copy')}}</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for country dropdown
            $('#target_id').select2({
                placeholder: 'Select country',
                allowClear: true,
                language: {
                    noResults: function() {
                        return "No countries found";
                    }
                },
                ajax: {
                    url: '/api/search/countries',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        const items = data.data || data || [];
                        return {
                            results: items.map(item => ({
                                id: item.id,
                                text: item.name
                            })),
                            pagination: {
                                more: (params.page * 10) < (data.total || 0)
                            }
                        };
                    },
                    cache: true
                }
            });

            // Initialize Select2 for language dropdown
            $('#lang_id').select2({
                placeholder: 'Select language',
                allowClear: true,
                language: {
                    noResults: function() {
                        return "No languages found";
                    }
                },
                ajax: {
                    url: '/api/search/language',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        const items = data.data || data || [];
                        return {
                            results: items.map(item => ({
                                id: item.code,
                                text: item.name
                            })),
                            pagination: {
                                more: (params.page * 10) < (data.total || 0)
                            }
                        };
                    },
                    cache: true
                }
            });


            function updateLink() {
                const selectedLang = $('#lang_id').val() || 'ar'; // default to Arabic
                const fullLink = `${window.location.origin}/cup-targets-view?lang=${selectedLang}`;
                $('#zego_client_id').val(fullLink);
            }

    // Update when language changes
    $('#lang_id').on('change', updateLink);

    // Initialize link on page load (with default)
    updateLink();

    // Copy button
    $('#copy-zego-id').on('click', function() {
        const input = document.getElementById('zego_client_id');
        input.select();
        document.execCommand('copy');
        alert('Link copied: ' + input.value);
    });


    function updateCountryLink() {
        const selectedCountry = $('#target_id').val();
        const baseUrl = `${window.location.origin}/countries`;
        const fullLink = selectedCountry ? `${baseUrl}/${selectedCountry}` : '';
        $('#super_admin_link').val(fullLink);
    }

    // When user selects or clears a country
    $('#target_id').on('change', updateCountryLink);

    // Copy link button
    $('#copy-admin-link').on('click', function() {
        const input = document.getElementById('super_admin_link');
        if (input.value.trim() === '') {
            alert('Please select a country first!');
            return;
        }
        input.select();
        document.execCommand('copy');
        alert('Link copied: ' + input.value);
    });



});
    </script>
</body>
</html>
