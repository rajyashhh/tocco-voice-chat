<style>
    .inner-settings-menu {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .inner-settings-menu button {
        padding: 10px 20px;
        background: var(--secondary-color);
        color: black;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }


.settings-section {
    display: none; /* Hide all sections by default */
}

.settings-section.active {
    display: block; /* Only show the active section */
}
.settings-menu button {
    display: inline-flex;   /* important: keep inline alignment */
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 12px;
    border: none;
    background-color: #f0f0f0;
    cursor: pointer;
    white-space: nowrap;    /* prevent text from wrapping */
}

.settings-menu button i {
    flex-shrink: 0;
}

.sb-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.sb-brand-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
}
.sb-brand-text {
    font-size: 15px;
    font-weight: 700;
    color: #e2e8f0;
}

.sb-soon {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    background: #ef4444;
    color: #fff;
    padding: 2px 7px;
    border-radius: 4px;
    margin-left: 8px;
}
.rtl .sb-soon {
    margin-left: 0;
    margin-right: 8px;
}

.settings-menu button.active {
    background-color: var(--primary-color, gray) !important;
    color: var(--panel-text-color, #fff) !important;
    border-left: 3px solid var(--panel-text-color, #fff);
    box-shadow:  10px 10px 8px rgb(148 2 2 / 15%);
}

.dark-mode .settings-menu button.active {
    background-color: var(--accent) !important;
    color: var(--accent-contrast, #fff) !important;
    border-left: 3px solid var(--accent-contrast, #fff);
    box-shadow: 0 2px 10px var(--accent-soft);
}

.rtl .settings-menu button.active {
    border-left: none;
    border-right: 3px solid var(--panel-text-color, #fff);
}

    .inner-settings-menu button.active {
        background: var(--primary-color);
        color: var(--panel-text-color, #fff);
    }

    .settings-section {
        display: none;
    }

    .settings-section.active {
        display: block;
    }

    .colorpicker {
        min-width: 220px;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        font-family: Arial, sans-serif;
    }

    .colorpicker-saturation {
        border-radius: 5px !important;
    }

    .colorpicker-hue {
        border-radius: 5px !important;
    }

    .colorpicker-alpha {
        border-radius: 5px !important;
    }

    .colorpicker-color div {
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    .colorpicker.colorpicker-right {
        left: auto !important;
        right: 0 !important;
    }

    .colorpicker.colorpicker-left {
        left: 0 !important;
        right: auto !important;
    }

    .radio-options-container {
        display: flex;
        gap: 20px;
        align-items: center;
        margin: 15px 0;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .radio-input {
        margin: 0;
    }

    .radio-label {
        margin: 0;
        cursor: pointer;
        user-select: none;
    }

    .radio-input {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border: 2px solid var(--accent);
        border-radius: 50%;
        outline: none;
        cursor: pointer;
        position: relative;
    }

    .radio-input:checked {
        background-color: var(--accent);
    }

    .radio-input:checked::after {
        content: '';
        position: absolute;
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    /* Scoped to the settings page wrapper. This used to be a bare `body {}`
       rule that leaked its flex layout onto the real admin panel body and
       broke its scroll containers. */
    .settings-page {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: var(--secondary-color);
        display: flex;
        align-items: flex-start;
    }

    .settings-sidebar {
        width: 250px;
        background: #222;
        min-height: 400px;
        padding: 20px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
    }

    .settings-sidebar h2 {
        text-align: center;
        color: var(--accent);
    }

    .settings-content {
        flex-grow: 1;
        padding: 20px !important;
    }

    .settings-section {
        display: none;
    }

    .settings-section.active {
        display: block;
    }

    .settings-page label {
        display: block;
        margin: 10px 0 5px;
    }

    .settings-page button {
        padding: 10px;
        border: none;
        cursor: pointer;
        font-weight: bold;
    }

    .all-page {
        display: inline-flex;
    }

    .wrapper {
        width: 100%;
    }

    .settings-content {
        width: 869px;
    }

    .settings-page button {
        width: 171px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        padding-top: 50px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: var(--secondary-color);;
    }

   
    select.form-control {
        height: 45px;
        line-height: 45px;
    }


    .modal-content {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: var(--panel-text-color, #fff);
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }

    .settings-page img {
        width: 201px;
        display: inline;
        height: 99px;
    }

    .settings-sidebar {
        background-color: var(--table-background-color);
        justify-content: center;
        align-items: center;
        padding: 10px 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        color: var(--text-secondary-color);
        white-space: nowrap;
        scrollbar-width: thin;
        /* Independent scroll: the menu sticks and scrolls on its own without
           dragging the page, and the page scrolls without moving the menu. */
        position: sticky;
        top: 0;
        align-self: flex-start;
        max-height: 100vh;
        overflow-y: auto;
    }

    /* Vertical stack: each tab is its own full-width row in the sidebar column.
       (The buttons default to inline-flex for icon alignment; without an explicit
       column here they flow horizontally and overlap inside the 250px rail.) */
    .settings-menu {
        display: flex;
        flex-direction: column;
        gap: 4px;
        color: var(--text-secondary-color);
        scrollbar-width: thin;
    }

    .settings-menu button {
        width: 100%;
        justify-content: flex-start;
        background-color: var(--secondary-color);
        border: none;
        padding: 10px 15px;
        font-size: 16px;
        cursor: pointer;
        transition: color 0.3s ease-in-out;
    }

    .card {
        border-radius: 10px;
        border: 1px solid #ddd;
        background: "{{ $settings['primary_color'] ?? '#000000' }}";
        box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        transition: transform 0.2s ease-in-out;
        margin-top: 40px;
        position: relative;
    }

    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-save:active {
        transform: translateY(0);
    }

    .btn-save i {
        font-size: 14px;
    }

    .settings-form {
        position: relative;
    }

    .card:hover {
        transform: scale(1.02);
    }

    .card-header {
        background: {{ $settings['primary_color'] ?? '#000000' }};
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        border-radius: 8px 8px 0 0;
        text-align: center;
        font-weight: bold;
        font-size: 1.2rem;
        color: #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h4 {
        margin: 0;
    }

    .d-flex.align-items-center {
        gap: 10px;
    }

    .custom-radio {
        display: none;
    }

    .custom-payment-radio {
        display: none;
    }

    .switch {
        display: inline-block;
        width: 50px;
        height: 25px;
        background-color: #ccc;
        border-radius: 25px;
        position: relative;
        cursor: pointer;
        transition: background 0.3s;
    }

    .switch::after {
        content: "";
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        left: 5px;
        transform: translateY(-50%);
        transition: left 0.3s;
    }

    .switch.active {
        background: #4caf50;
    }

    .switch.active::after {
        left: 25px;
    }

    .border-success {
        border: 5px solid #4caf50;
    }

    .position-relative {
        position: relative;
        overflow: visible;
    }

    .ribbon-banner {
        position: absolute;
        top: 6px;
        right: -10px;
        background-color: #ff0000;
        padding: 2px 7px;
        transform: rotate(90deg);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .ribbon-banner-card {
        position: absolute;
        top: 6px;
        right: -9px;
        background-color: #ff0000;
        padding: 2px 7px;
        transform: rotate(90deg);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .ribbon-banner-card span {
        color: white;
        font-size: 15px;
        font-weight: normal;
        text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);

    }

    .rtl .ribbon-banner {
        right: auto !important;
        left: -11px !important;
        padding: 2px 13px !important;
    }

    .ribbon-banner span {
        color: white;
        font-size: 15px;
        font-weight: normal;
        text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
    }

    .text-center.my-3 img.img-fluid {
        max-height: 80px;
        display: unset !important;
        margin-top: 20px;
    }

    .card-top {
        margin-top: 33px;
    }

    .no-background-form {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .exp-card {
        margin-bottom: 32px;
    }

    .exp-card-cont {
        height: 400px;
    }

    .copy-container {
        position: relative;
    }

    .copy-button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none !important;
        cursor: pointer;
        padding: 0;
        font-size: 16px;
    }

    .ltr .copy-button {
        left: 95px;
    }

    .rtl .copy-button {
        right: 95px;
    }

    #landPageSettings {
        max-width: 1020px;
        margin: 18px auto;
        border-radius: 10px;
        padding: 22px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        color: #222;
        background: white;
    }

    .settings-container {
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }

    .tab-btn {
        background-color: var(--secondary-color);
        color: var(--text-secondary-color);
        border: none;
        padding: 10px 15px;
        border-radius: 10px;
        text-align: right;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tab-btn:hover {
        transform: translateY(-1px);
    }

    .tab-btn.active {
        background: var(--primary-color);
        color: var(--accent-contrast);
        border-color: var(--accent);
    }

    .tab-content {
        flex: 1;
        min-width: 0;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.show {
        display: block;
    }

    #landPageSettings h5 {
        font-weight: 700;
        margin-bottom: 8px;
    }

    #landPageSettings hr {
        margin-top: 8px;
        margin-bottom: 14px;
        border: none;
        height: 1px;
    }

    .form-control {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #dcdcdc;
        border-radius: 6px;
        box-sizing: border-box;
    }

    .col-md-6 {
        flex: 0 0 calc(50% - 12px);
        min-width: 240px;
    }

    .col-md-4 {
        flex: 0 0 calc(33.333% - 12px);
        min-width: 160px;
    }

    /* Tab Navigation */
    .inner-settings-menu {
        display: flex;
        gap: 10px;
        padding: 10px;
        border-radius: 12px;
        margin-bottom: 20px;
    }

    .inner-settings-menu .tab-btn {
        padding: 12px 24px;
        border: none;
        color: var(--text-secondary-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .inner-settings-menu .tab-btn:hover {
        color: var(--text-secondary-color);
    }

    .inner-settings-menu .tab-btn.active {
        color: var(--text-secondary-color);
    }

    /* Section Header */
    .section-header h4 {
        margin: 0 0 5px 0;
        color: #fff;
    }

    .section-header p {
        margin: 0;
        font-size: 14px;
    }

    /* Cards Grid */
    .exp-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }

    .exp-cards-grid.single-card {
        grid-template-columns: minmax(320px, 400px);
    }

    /* Individual Card */
    .exp-card {
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .exp-card:hover {
        border-color: rgba(255,255,255,0.15);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    /* Card Header */
    .exp-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .exp-card-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    /* Card Icons */
    .exp-card-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .exp-card-icon.wealth {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: #fff;
    }

    .exp-card-icon.attraction {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: #fff;
    }

    .exp-card-icon.charge {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: #fff;
    }

    .exp-card-icon.rooms {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
        color: #fff;
    }

    .exp-card-icon.cp {
        background: linear-gradient(135deg, #1abc9c, #16a085);
        color: #fff;
    }

    .exp-card-icon.diamond {
        background: linear-gradient(135deg, #00d2d3, #01a3a4);
        color: #fff;
    }

    /* Card Body */
    .exp-card-body {
        padding: 20px;
    }

    .exp-card-body .form-group {
        margin-bottom: 16px;
    }

    .exp-card-body .form-group:last-child {
        margin-bottom: 0;
    }

    .dark-mode .exp-card-body label {
        color: var(--white);
    }

    .exp-card-body .form-control {
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 10px 14px;
        width: 100%;
    }

    .dark-mode .exp-card-body .form-control {
        color: #fff;
    }

    .exp-card-body .form-control:focus {
        border-color: var(--primary-color, #e74c3c);
        outline: none;
        box-shadow: 0 0 0 3px rgba(231,76,60,0.1);
    }

    .exp-card-body small.text-muted {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #666;
    }

    /* Input with Result */
    .input-with-result {
        position: relative;
    }

    .input-with-result .result-badge {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: var(--primary-color, #e74c3c);
        color: #fff;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Alert Link */
    .alert-link {
        margin-top: 10px;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
    }

    .alert-link a {
        color: #e74c3c;
        text-decoration: none;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .alert-link a:hover {
        text-decoration: underline;
    }

    /* Card Footer */
    .exp-card-footer {
        padding: 15px 20px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }

    .exp-card-footer .btn-block {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    /* Calculator Box */
    .calculator-box {
        padding: 15px;
        border-radius: 10px;
        border: 1px dashed rgba(255,255,255,0.1);
    }

    /* RTL Support */
    [dir="rtl"] .exp-card-header {
        flex-direction: row-reverse;
    }

    .rtl .input-with-result .result-badge {
        right: auto;
        left: 10px;
    }

    /* Mobile Links Grid */
    .mobile-links-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    /* Link Card - Compact Style */
    .link-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--white);
        border: 1px solid #eaeaea;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);

    }

    .dark-mode .link-card {
        background: var(--dark-secondry-color);
    }

    .link-card:hover {
        border-color: rgba(255,255,255,0.15);
    }

    /* Card Icon */
    .link-card-icon {
        width: 45px;
        height: 45px;
        min-width: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .link-card-icon.android {
        background: linear-gradient(135deg, #3DDC84, #2DA65A);
        color: #fff;
    }

    .link-card-icon.ios {
        background: linear-gradient(135deg, #555, #333);
        color: #fff;
    }

    .link-card-icon.huawei {
        background: linear-gradient(135deg, #C10C23, #8B0A1A);
        color: #fff;
    }

    /* Card Content */
    .link-card-content {
        flex: 1;
        min-width: 0;
    }

    .link-card-info {
        margin-bottom: 8px;
    }

    .link-card-info h5 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
    }

    /* Card Input */
    .link-card-input .form-control {
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 8px 12px;
        color: #fff;
        font-size: 12px;
        width: 100%;
    }

    .link-card-input .form-control:focus {
        border-color: var(--primary-color, #32e5ac);
        outline: none;
    }

    .link-card-input .form-control::placeholder {
        color: #555;
        font-size: 11px;
    }

    /* Section Subtitle */
    .section-subtitle {
        color: #888;
        font-size: 13px;
        margin-bottom: 15px;
    }

    /* Action Buttons */
    .action-buttons .btn {
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    /* Desktop View - Side by Side */
    @media (min-width: 768px) {
        .mobile-links-grid {
            flex-direction: row;
            flex-wrap: wrap;
        }

        .link-card {
            flex: 1;
            min-width: 280px;
        }

        .action-buttons .btn {
            width: auto;
        }
    }

    /* RTL Support */
    [dir="rtl"] .link-card {
        flex-direction: row-reverse;
    }

    [dir="rtl"] .link-card-info {
        text-align: right;
    }

    .real-time-card-height {
       /* height: 255px;*/
       min-height: 332px !important;
    }

    @media (max-width: 576px) {
    }

    @media (max-width: 768px) {
        .row {
            display: flex;
            flex-wrap: wrap;
        }

        form {
            background: #222;
            padding: 20px;
            border-radius: 5px;
            width: 100%;
            position: relative;
            margin: auto;
        }

        .real-time-card-height {
          /*  height: auto;*/
        }

        .rtl .theme-settings form,
        .rtl .app-settings form {
            padding-right: 40px;
        }

        .p-3 {
            padding: 3rem !important;
        }
        .settings-menu {
            display: flex;
            flex-wrap: nowrap;
        }

        .settings-menu button {
            display: inline-block;
            min-width: 150px;
            margin-right: 0.5rem;
            margin-bottom: 0;
            white-space: normal;
        }

        .settings-container {
            flex-direction: column;
        }

        .tab-btn {
            white-space: nowrap;
            padding: 8px 10px;
            font-size: 14px;
        }

        .tab-content {
            margin-top: 12px;
        }

        .col-md-6, .col-md-4 {
            flex: 1 1 100%;
            min-width: 0;
        }

        .settings-content {
            width: 100% !important;
        }

        .row {
            gap: 12px;
        }
    }

    /* Modern White Form Card */
    form {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid #eaeaea;
    }

    form .section-title {
        color: #1a1a2e;
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
    }

    form .form-control {
        background: #f8f9fb;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 10px 14px;
        color: #333;
        transition: all 0.2s ease;
    }

    form .form-control:focus {
        background: #fff;
        border-color: var(--primary-color, #711e1e);
        box-shadow: 0 0 0 3px rgba(113, 30, 30, 0.08);
        outline: none;
    }

    form .form-control::placeholder {
        color: #aaa;
    }

    form .btn-primary {
        background: #1a1a2e;
        border: none;
        border-radius: 8px;
        padding: 10px 24px;
        font-weight: 500;
        color: #fff;
        transition: all 0.2s ease;
    }

    form .btn-primary:hover {
        background: #2d2d44;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    form .form-group {
        margin-bottom: 16px;
    }

    .preset-card {
        padding: 15px;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 5px;
    }
    @media (max-width: 992px) {
    }

    @media (max-width: 1200px) {
    }

    @media (max-width: 1400px) {
        .form-control {
            width: 170px !important;
        }

        .rtl .copy-button {
            right: 75px;
        }
    }

    .color-white{
        color: white !important;
    }
    .p-9-px {
        padding: 9px !important;
    }
    .btn0bottom{
            bottom: 22px;
            position: absolute;
            margin: auto;
    }

    .pusher-settings-form{
        height: 560px;
    }
    .pusher-btn0bottom {
        position: absolute;
        margin: 17px;
        bottom: 0;
    }

    /* ═══════════════════════════════════════════════════════
       MODERN UI COMPONENTS — Section Headers, Cards, etc.
       ═══════════════════════════════════════════════════════ */

    /* ── Section Header Bar ── */
    .section-header-bar {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 18px;
        border-bottom: 2px solid var(--accent);
    }

    .section-header-bar h3 {
        margin: 0;
        font-size: 19px;
        font-weight: 700;
        color: var(--text-secondary-color, #1e293b);
    }

    .dark-mode .section-header-bar h3 { color: #f1f5f9; }

    .section-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: var(--accent-contrast);
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .section-header-text p {
        margin: 4px 0 0;
        font-size: 14px;
        color: #94a3b8;
    }

    /* ── Form Section Title ── */
    .form-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 600;
        color: #1e293b !important;
        margin-bottom: 16px;
        padding: 10px 14px;
        background: var(--accent-soft);
        border-radius: 10px;
        border-left: 3px solid var(--accent);
    }

    .rtl .form-section-title { border-left: none; border-right: 3px solid var(--accent); }
    .dark-mode .form-section-title { background: var(--accent-soft); color: #e2e8f0 !important; }
    .form-section-title i { color: var(--accent); font-size: 14px; }

    /* ── Form Divider ── */
    .form-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        margin: 24px 0;
    }

    .dark-mode .form-divider { background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent); }

    /* ── Image Preview Card ── */
    .img-preview-card {
        margin-top: 12px;
        padding: 14px;
        background: var(--accent-soft);
        border: 1px dashed var(--border-strong);
        border-radius: 12px;
        text-align: center;
        transition: all 0.2s;
    }

    .img-preview-card:hover { border-color: var(--accent); background: var(--accent-soft); }
    .dark-mode .img-preview-card { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.1); }
    .img-preview-card.hidden { display: none; }

    .img-preview-card img {
        width: auto !important;
        max-width: 140px !important;
        height: auto !important;
        max-height: 80px !important;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s;
        display: block !important;
        margin: 0 auto !important;
    }

    .img-preview-card img:hover { transform: scale(1.05); }

    .img-label {
        display: block;
        margin-top: 8px;
        font-size: 10px;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ── Modern Form ── */
    .modern-form {
        background: var(--white, #fff) !important;
        border-radius: 16px !important;
        padding: 28px !important;
        box-shadow: 0 1px 8px rgba(0,0,0,0.04) !important;
        border: 1px solid #e2e8f0 !important;
    }

    .dark-mode .modern-form {
        background: var(--dark-secondry-color, #1e293b) !important;
        border-color: rgba(255,255,255,0.06) !important;
    }

    .floating-group label {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .floating-group label i { font-size: 12px; }

    /* ── File Input ── */
    .file-input {
        padding: 8px 12px !important;
        cursor: pointer;
        border: 1.5px dashed var(--border-strong) !important;
        background: var(--accent-soft) !important;
        transition: all 0.2s;
    }

    .file-input:hover { border-color: var(--accent) !important; background: var(--accent-soft) !important; }

    .text-end { text-align: end; }

    /* ── Enhanced Save Button ── */
    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 26px;
        font-weight: 600;
        font-size: 14px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--accent), var(--accent-strong)) !important;
        color: var(--accent-contrast) !important;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 2px 8px var(--accent-soft);
        width: auto !important;
        border: none !important;
    }

    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--accent-soft); }
    .btn-save:active { transform: translateY(0); }

</style>


