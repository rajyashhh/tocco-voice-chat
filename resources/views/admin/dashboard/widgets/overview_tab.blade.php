
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<!-- <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet"> -->
<style>
.floating-dots { position:absolute; inset:0; z-index:0; background: radial-gradient(circle, rgba(0,0,0,0.06) 2px, transparent 3px); background-size:40px 40px; animation: moveDots 40s linear infinite; opacity:0.6; pointer-events:none; }
@keyframes moveDots { from { background-position:0 0;} to { background-position:400px 400px; } }

.dashboard-wrap{ position:relative; z-index:1; }
.page-padding{ padding:1.25rem; }

.cards-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:20px; margin-bottom:40px; }
.ltr .card { position: relative;  background:#fff; border-radius:12px; padding:10px 12px; text-align:start; box-shadow:0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; overflow:hidden; }
.rtl .card { position: relative; background:#fff; border-radius:12px; padding:10px 12px; text-align:start; box-shadow:0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; overflow:hidden; }
.card:hover { transform: translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.15); }
.card h2 { font-size:1.3rem; color:#333; }
.card h3 { font-size:1.3rem; margin-bottom:10px; color:#333; margin-top: 0 !important;}
.card .amount { font-size:1.5rem; font-weight:bold; margin-bottom:15px; color:#1e3a8a; transition: all 0.6s ease; }
.card canvas { width:100% !important; height:120px !important; }
.rtl .card .card-icon{   left: 14px; position: absolute; top:7px}
.ltr .card .card-icon{   right: 14px; position: absolute; top:7px}
.main-chart-container { background:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.1);     overflow: hidden; }
.main-chart-container canvas {
    height: 200px !important;
    width: 100% !important;
}
.main-chart-container {
    position: relative;
    width: 100%;
    /* height: 260px;  */
}
.main-chart-container{
    background: none;
    border-radius:12px;
    padding:20px;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
     height: 102%;
}

#shipmentsChart{
    width:100% !important;
    height: 100% !important;
}
.card-trans{min-height: 370px;}
.card-trans2{min-height: 468px;  max-height:468px ;}

.card-with{margin-top:20px;}
.carPay {min-height :456px;}
#applyFilter{
    margin-top: 19px  !important;
}
.tab-content {
    min-height: 2000px  !important;
}
.CardwalletLogsTable{
    overflow: scroll !important;
}
@media (max-width: 992px) {
    .main-chart-container {
        /* min-height: 300px; */
    }
}

@media (max-width: 600px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 10px;
    }
    .chart-container {
        position: relative;
        height: 320px !important;
    }
    .chart-title {
        font-size: 1rem;
    }
    #applyFilter{
            margin-top: 12px  !important;
    }
    .carPay {min-height :auto !important;}
}
#topUsersContainer .user-card {
    text-align: center;
    flex: 0 0 calc(50% - 0.5rem);
}
#topUsersContainer .user-card:nth-child(n+3) {
    flex: 0 0 calc(33.33% - 0.5rem);
}
.user-card img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 0.25rem;
    border: 2px solid #1d4ed8;
}
.user-card .user-name {
    font-size: 0.875rem;
    font-weight: 500;
}
#filterCard {
    display: none;
}
#toggleFilter{
    margin-bottom: 10px;
}
@media (max-width:600px){ .cards-container{ grid-template-columns:1fr; } }
</style>


<div class="position-relative">
    <div class="floating-dots"></div>

    <div class="dashboard-wrap page-padding">

        <div class="dashboard-wrap page-padding" style=" position: relative;     margin-bottom: 53px;">
            <button id="toggleFilter" class="btn btn-dropbox 694015f1a711d-filter-btn mb-3" style=" left: 0;position: absolute;">
                    <i class="fas fa-filter"></i>
            </button>
        </div>

        <div id="filterCard" class="card p-3 mb-4" data-aos="fade-down">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small text-muted">{{ __('From') }}</label>
                    <input type="date" id="from_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="small text-muted">{{ __('To') }}</label>
                    <input type="date" id="to_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <button id="applyFilter" class="btn btn-primary w-100">
                        {{ __('Filter') }}
                    </button>
                </div>
            </div>
        </div>



        <div class="cards-container mb-4" data-aos="fade-up">
            @foreach ([
                'total_balance'     => 'fa-wallet',
                'pending_balance'   => 'fa-clock',
                'available_balance' => 'fa-money-bill-wave',
                'today_balance'     => 'fa-coins'
            ] as $id => $icon)
                <div class="card finance-card" id="{{ $id }}">
                    <div class="card-icon"><i class="fa-solid {{ $icon }}"></i></div>
                    <h2 style="margin-top:0 !important;">{{ __("{$id}") }}</h2>
                    <p class="amount">0 $</p>
                    <h3>{{ __("{$id}") }}</h3>
                </div>
            @endforeach
        </div>

        <div class="row g-3">

            <div class="col-lg-7">

                <div class="col-lg-12">
                    <div class="main-chart-container card" data-aos="fade-up">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="chart-title">{{ __("shipments_revenue") }}</h5>
                            <select id="filterChart" class="form-select form-select-sm" style="width:auto;">
                                <option value="7" selected>{{ __("filter_week") }}</option>
                                <option value="30">{{ __("filter_month") }}</option>
                                <option value="365">{{ __("filter_year") }}</option>
                            </select>
                        </div>
                            <div class="chart-container" style="height: 73%;">
                                <canvas id="shipmentsChart"></canvas>
                            </div>
                    </div>
                </div>

                <div class="col-lg-12 mt-4">
                    <div class="card card-with p-3" data-aos="fade-up">
                        <h5 class="mb-3">{{ __("latest_withdrawals") }}</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0">
                                <thead class="text-muted small">
                                    <tr>
                                        <th>{{ __("table_header_index") }}</th>
                                        <th>{{ __("table_header_account") }}</th>
                                        <th>{{ __("table_header_amount") }}</th>
                                        <th>{{ __("table_header_date") }}</th>
                                    </tr>
                                </thead>
                                <tbody id="withdrawalsTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-5">
                <div class="card card-trans card-trans2 p-3" data-aos="fade-up">
                    <h5 class="mb-3">{{ __("latest_payments") }}</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle">
                            <thead class="text-muted small">
                                <tr>
                                    <th>{{ __("table_header_index") }}</th>
                                    <th>{{ __("table_header_gateway") }}</th>
                                    <th>{{ __("table_header_amount") }}</th>
                                    <th>{{ __("table_header_status") }}</th>
                                    <th>{{ __("table_header_date") }}</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsTable"></tbody>
                        </table>
                    </div>
                </div>

                 <div class="card card-trans p-3 mt-4" data-aos="fade-up">
                    <h5 class="mb-3">{{ __("top_users_shipping") }}</h5>
                    <div class="row g-2 justify-content-center" id="topUsersContainer"></div>
                </div>
            </div>

        </div>


    </div>
</div>



<div class="card  CardwalletLogsTable">
    <div class="card-header">
        <h4>{{ __('Core Wallet Transactions') }}</h4>
    </div>
    <div class="card-body">
        <table id="walletLogsTable" class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('User') }}</th>
                    <th> {{ __('Amount') }}</th>
                    <th> {{ __('balance before') }}</th>
                    <th> {{ __('amount after') }}</th>
                    <th> {{ __('created at') }}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

@php
    if (request()->is('superadmin*')) {
        $prefix = 'superadmin';
    } elseif (request()->is('areaManager*')) {
        $prefix = 'areaManager';
    } else {
        $prefix = 'admin';
    }
@endphp

<script>
    const routePrefix = "{{ $prefix }}";
</script>

<script>
function loadWalletLogs() {
    $.ajax({
        url: `/${routePrefix}/dashboard/wallet-logs/ajax`,
        type: "GET",
        dataType: "json",
        success: function(response) {
            const tbody = $('#walletLogsTable tbody');
            tbody.empty();

            if(response.data && response.data.length > 0) {
                response.data.forEach(log => {
                    const operationBadge = log.operation === 'add'
                        ? '<span class="badge bg-success">ADD</span>'
                        : '<span class="badge bg-danger">CUT</span>';
                    let bgColor = '';

                    const amount = parseFloat(log.amount);

                    if (amount > 0) {
                        bgColor = '#10b981';
                    } else if (amount < 0) {
                        bgColor = '#ef4444';
                    } else {
                        bgColor = '#6b7280';
                    }

                    const row = `
                        <tr>
                            <td>${log.id}</td>

                            <td>
                                <a href="/${routePrefix}/users/${log.user_id}"
                                style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
                                    <img src="${log.img || '/vendor/laravel-admin/AdminLTE/dist/img/default-avatar.svg'}" alt="${log.user_name}"
                                        style="width:40px; height:40px; border-radius:50%; object-fit:cover;"
                                        onerror="this.onerror=null; this.src='/vendor/laravel-admin/AdminLTE/dist/img/default-avatar.svg'">
                                    <div>
                                        <div style="font-weight:600;">${log.user_name}</div>
                                        <div style="font-size:12px; color:#666;">${log.user_uuid}</div>
                                    </div>
                                </a>
                            </td>

                            <td style="background-color:${bgColor}; font-weight:600;">${amount.toLocaleString()} $</td>
                            <td>${log.before_amount}</td>
                            <td>${log.after_amount}</td>
                            <td>${log.created_at}</td>
                        </tr>
                        `;

                    tbody.append(row);

                });
            } else {
                tbody.append('<tr><td colspan="8" class="text-center">لا توجد سجلات</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
        }
    });
}

$(document).ready(function() {
    loadWalletLogs();
});

// Listen for PJAX completion to reload data
$(document).on('pjax:complete', function() {
    loadWalletLogs();
});

</script>




<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

<script>
let completed = "{{ __('completed') }}";
let cancelled = "{{ __('cancelled') }}";

AOS.init({ once: true, duration: 600 });

let shipmentsChart = null;

function getFilters() {
    return {
        from: document.getElementById('from_date')?.value || '',
        to: document.getElementById('to_date')?.value || '',
        days: document.getElementById('filterChart')?.value || 7
    };
}

function animateAmount(el, value) {
    let start = 0;
    const duration = 100;
    const startTime = performance.now();
    function step(now) {
        const p = Math.min((now - startTime)/duration, 1);
        el.innerText = Math.floor(start + p*value).toLocaleString() + ' $';
        if (p<1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

function renderStatusBadge(status) {
    return status == 1
        ? `<span class="badge bg-success">${completed}</span>`
        : `<span class="badge bg-danger">${cancelled}</span>`;
}

/* =======================
   Cards Loader
======================= */
async function loadFinanceCards() {
    try {
        const { from, to } = getFilters();
        const res = await fetch(`/${routePrefix}/dashboard/finance/cards?from=${from}&to=${to}`);
        if (!res.ok) throw new Error(res.statusText);
        const data = await res.json();
        Object.keys(data).forEach(key => {
            const card = document.getElementById(key);
            if(card) animateAmount(card.querySelector('.amount'), data[key]);
        });
    } catch(err) {
        console.error('Cards load error:', err);
    }
}

/* =======================
   Tables Loader
======================= */
async function loadFinanceTables() {
    const paymentsTbody = document.getElementById('paymentsTable');
    const withdrawalsTbody = document.getElementById('withdrawalsTable');
    const topUsersContainer = document.getElementById('topUsersContainer');

    try {
        const res = await fetch(`/${routePrefix}/dashboard/finance/tables`);
        console.log('Finance tables API status:', res.status);
       const defaultAvatar = "{{ asset('images/businessman-icon.jpg') }}";

        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);

        const data = await res.json();
        console.log('Finance tables data:', data);

        paymentsTbody.innerHTML = '';
        (data.payments || []).forEach(p => {
            paymentsTbody.innerHTML += `
                <tr>
                    <td>#${p.id}</td>
                    <td>${p.gateway}</td>
                    <td>${p.amount.toLocaleString()} $</td>
                    <td>${renderStatusBadge(p.status)}</td>
                    <td class="text-muted small">${p.date}</td>
                </tr>
            `;
        });

        withdrawalsTbody.innerHTML = '';
        (data.withdrawals || []).forEach(w => {
            withdrawalsTbody.innerHTML += `
                <tr>
                    <td>#${w.id}</td>
                    <td>
                        <a href="/${routePrefix}/users/${w.user_id}" target="_blank"
                        style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
                            <img src="${w.img || '/vendor/laravel-admin/AdminLTE/dist/img/default-avatar.svg'}" alt="${w.user_name}"
                                style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                            <div>
                                <div style="font-weight:600;">${w.user_name}</div>
                                <div style="font-size:12px; color:#666;">${w.uuid || ''}</div>
                            </div>
                        </a>
                    </td>
                    <td>${w.amount.toLocaleString()} $</td>
                    <td class="text-muted small">${w.date}</td>
                </tr>
            `;
        });

        // عرض المستخدمين الأعلى
        let topUsers = data.topUsers ?? [];
        if (!Array.isArray(topUsers) || topUsers.length === 0) {
            console.warn('No top users found, displaying defaults.');
            topUsers = [
                { name: "User 1", avatar: defaultAvatar },
                { name: "User 2", avatar: defaultAvatar },
                { name: "User 3", avatar: defaultAvatar },
                { name: "User 4", avatar: defaultAvatar },
                { name: "User 5", avatar: defaultAvatar }
            ];
        }

        const topRow = topUsers.slice(0, Math.min(3, topUsers.length));
        const bottomRow = topUsers.slice(3, topUsers.length);

        topUsersContainer.innerHTML = `
            <div class="top-row" style="display: flex; justify-content: center; gap: 10px; margin-top: 10px; margin-bottom: 10px;">
                ${topRow.map(u => `
                    <a href="/${routePrefix}/users/${u.id}">
                        <div class="user-card">
                            <img src="${u.avatar}" alt="${u.name}" onerror="this.onerror=null; this.src='/vendor/laravel-admin/AdminLTE/dist/img/default-avatar.svg'">
                            <div class="user-name">${u.name}</div>
                            <div class="user-name">${u.uuid}</div>
                        </div>
                    </a>
                `).join('')}
            </div>
            ${bottomRow.length > 0 ? `<div class="bottom-row" style="display: flex; justify-content: center; gap: 10px;">
                ${bottomRow.map(u => `
                    <a href="/admin/users/${u.id}" style="text-decoration: none; color: inherit; flex: 0 0 calc(${100 / bottomRow.length}% - 10px);">
                        <div class="user-card">
                            <img src="${u.avatar}" alt="${u.name}" onerror="this.onerror=null; this.src='/vendor/laravel-admin/AdminLTE/dist/img/default-avatar.svg'">
                            <div class="user-name">${u.name}</div>
                            <div class="user-name">${u.uuid}</div>
                        </div>
                    </a>
                `).join('')}
            </div>` : ''}
        `;


    } catch(err) {
        console.error('Finance tables load error:', err);

        paymentsTbody.innerHTML = `<tr><td colspan="5" class="text-center">لا توجد بيانات</td></tr>`;
        withdrawalsTbody.innerHTML = `<tr><td colspan="5" class="text-center">لا توجد بيانات</td></tr>`;
        topUsersContainer.innerHTML = [
            { name: "User 1", avatar: defaultAvatar },
            { name: "User 2", avatar: defaultAvatar },
            { name: "User 3", avatar: defaultAvatar },
            { name: "User 4", avatar: defaultAvatar },
            { name: "User 5", avatar: defaultAvatar },
        ].map(u => `
            <div class="user-card">
                <img src="${u.avatar}" alt="${u.name}">
                <div class="user-name">${u.name}</div>
                <div class="user-name">${u.name}</div>
            </div>
        `).join('');
    }


}

/* =======================
   Chart Loader
======================= */
function renderShipmentsChart(canvasId, labels=[], values=[]) {
    if(!Array.isArray(labels) || !Array.isArray(values)) return;
    const canvas = document.getElementById(canvasId);
    if(!canvas) return;
    const ctx = canvas.getContext('2d');
    if(shipmentsChart) shipmentsChart.destroy();
    shipmentsChart = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets:[{label:'إيرادات الشحنات', data: values, backgroundColor:'#1d4ed8', borderRadius:8, maxBarThickness:50}] },
        options: {
            responsive:true,
            maintainAspectRatio:false,
            animation:{duration:900, easing:'easeOutQuart'},
            plugins:{legend:{display:true, position:'bottom'},
                     tooltip:{callbacks:{label: ctx => `${ctx.dataset.label}: ${ctx.raw.toLocaleString()} $`}}},
            scales:{x:{grid:{display:false}}, y:{beginAtZero:true,ticks:{callback:v=>v.toLocaleString()}}}
        }
    });
}

async function loadFinanceChart() {
    try {
        const { from, to, days } = getFilters();
        const res = await fetch(`/${routePrefix}/dashboard/finance/chart?from=${from}&to=${to}&days=${days}`);
        if(!res.ok) throw new Error(res.statusText);
        const data = await res.json();
        const labels = Array.from(data.labels ?? []);
        const values = Array.from(data.values ?? []).map(v=>Number(v)||0);
        renderShipmentsChart('shipmentsChart', labels, values);
    } catch(err) {
        console.error('Chart load error:', err);
    }
}


/* =======================
   Events
======================= */


function initOverviewTab() {
    loadFinanceCards();
    loadFinanceTables();
    loadFinanceChart();
}

// Load immediately - no DOM waiting, no PJAX reloading
initOverviewTab();

document.getElementById('applyFilter')?.addEventListener('click', () => {
    loadFinanceCards();
    loadFinanceTables();
    loadFinanceChart();
});

document.getElementById('filterChart')?.addEventListener('change', () => {
    loadFinanceCards();
    loadFinanceChart();
});

document.getElementById('toggleFilter').addEventListener('click', function() {
    const card = document.getElementById('filterCard');
    if (card.style.display === 'none' || card.style.display === '') {
        card.style.display = 'block';
    } else {
        card.style.display = 'none';
    }
});
</script>
