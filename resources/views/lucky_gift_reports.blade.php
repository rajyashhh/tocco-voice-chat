@php
    /** @var array $summary */
    $fmt = function ($v) {
        $f = (float) $v;
        return number_format($f, fmod($f, 1.0) == 0.0 ? 0 : 2);
    };
    // صافي اللاعبين = صافي المرسلين فقط (هل اللاعبون اللي بيلعبوا كسبوا أم خسروا).
    // نصيب المستقبلين بند منفصل تماماً (المستقبل بياخد نسبته ثابتة، مش "لعِب").
    $playersNet = (float) $summary['senders_net'];
    $vault = (int) ($liveVault ?? 0);
@endphp

<style>
    .lucky-reports { direction: rtl; text-align: right; }
    .lucky-reports table th { white-space: nowrap; }
    .lucky-reports .table > tbody > tr > td { vertical-align: middle; }
    .lucky-reports .filter-form .form-group { margin-left: 10px; margin-bottom: 10px; }
    .lucky-reports .filter-form label { margin-left: 5px; font-weight: 600; }
    .lucky-reports .net-pos { color: #28a745; font-weight: 700; }
    .lucky-reports .net-neg { color: #dc3545; font-weight: 700; }
    .lucky-reports .pagination { margin: 0; }

    /* الكروت الرئيسية (الأهم) */
    .lucky-reports .hero { border-radius: 8px; padding: 16px 18px; color: #fff; margin-bottom: 15px; box-shadow: 0 2px 6px rgba(0,0,0,.12); }
    .lucky-reports .hero .h-val { font-size: 28px; font-weight: 800; line-height: 1.1; white-space: nowrap; }
    .lucky-reports .hero .h-lbl { font-size: 14px; opacity: .95; margin-top: 6px; }
    .lucky-reports .hero .h-sub { font-size: 12px; opacity: .85; margin-top: 3px; }

    /* الشريط المصغّر للإحصائيات الثانوية */
    .lucky-reports .ministat { background: #fff; border: 1px solid #e6e9ed; border-radius: 6px; padding: 9px 10px; text-align: center; margin-bottom: 10px; }
    .lucky-reports .ministat .m-val { font-size: 17px; font-weight: 700; color: #2c3e50; white-space: nowrap; }
    .lucky-reports .ministat .m-lbl { font-size: 11px; color: #8a939b; margin-top: 2px; }
</style>

<div class="lucky-reports">

    {{-- ===================== الفلاتر ===================== --}}
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> فلاتر الفترة (الافتراضي: اليوم — الحد الأقصى {{ $maxRangeDays }} يوم)</h3>
        </div>
        <div class="box-body">
            @php
                // أزرار سريعة لاختيار الفترة — روابط GET تحافظ على باقي الفلاتر
                // (المستخدم/النتيجة/المضاعف/الغرفة) وتغيّر التواريخ فقط.
                $qToday = \Carbon\Carbon::today();
                $quickRanges = [
                    'اليوم'            => [$qToday->toDateString(), $qToday->toDateString()],
                    'أمس'              => [$qToday->copy()->subDay()->toDateString(), $qToday->copy()->subDay()->toDateString()],
                    'هذا الأسبوع'      => [$qToday->copy()->startOfWeek()->toDateString(), $qToday->toDateString()],
                    'الأسبوع الماضي'   => [$qToday->copy()->subWeek()->startOfWeek()->toDateString(), $qToday->copy()->subWeek()->endOfWeek()->toDateString()],
                    'آخر 7 أيام'       => [$qToday->copy()->subDays(6)->toDateString(), $qToday->toDateString()],
                    'آخر 30 يوم'       => [$qToday->copy()->subDays(29)->toDateString(), $qToday->toDateString()],
                ];
                $quickBase = request()->except(['from', 'to', 'page']);
                $curFrom = $from->format('Y-m-d');
                $curTo = $to->format('Y-m-d');
            @endphp
            <div class="form-group" style="display:block; margin-bottom:12px;">
                @foreach($quickRanges as $label => $range)
                    @php $active = ($curFrom === $range[0] && $curTo === $range[1]); @endphp
                    <a href="{{ admin_url('lucky-gift-reports') }}?{{ http_build_query($quickBase + ['from' => $range[0], 'to' => $range[1]]) }}"
                       class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-default' }}"
                       style="margin-left:6px; margin-bottom:6px;">{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ admin_url('lucky-gift-reports') }}" class="form-inline filter-form">
                <div class="form-group">
                    <label>من</label>
                    <input type="date" name="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>إلى</label>
                    <input type="date" name="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>المستخدم</label>
                    <input type="text" name="user" class="form-control" value="{{ $userTerm }}" placeholder="ID أو UUID أو السبيشيال">
                </div>
                <div class="form-group">
                    <label>النتيجة</label>
                    <select name="result" class="form-control">
                        <option value="">الكل</option>
                        <option value="win" {{ $result === 'win' ? 'selected' : '' }}>فوز فقط</option>
                        <option value="lose" {{ $result === 'lose' ? 'selected' : '' }}>خسارة فقط</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>المضاعف</label>
                    <select name="multiplier" class="form-control">
                        <option value="">كل المضاعفات</option>
                        @foreach($multiplierOptions as $m)
                            <option value="{{ $m }}" {{ (int) $multiplier === (int) $m ? 'selected' : '' }}>x{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>رقم الغرفة</label>
                    <input type="number" min="1" name="room_id" class="form-control" value="{{ $roomInput }}" placeholder="ID الغرفة أو UID صاحبها">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> عرض</button>
                <a href="{{ admin_url('lucky-gift-reports') }}" class="btn btn-default">إعادة تعيين</a>
            </form>

            @if($userNotFound)
                <div class="alert alert-warning" style="margin-bottom: 0;">
                    <i class="fa fa-warning"></i> لا يوجد مستخدم بالمعرف "{{ $userTerm }}" — النتائج فارغة.
                </div>
            @elseif($roomNotFound)
                <div class="alert alert-warning" style="margin-bottom: 0;">
                    <i class="fa fa-warning"></i> لا توجد غرفة بالرقم "{{ $roomInput }}" (لا كـ ID غرفة ولا كـ UID صاحبها) — النتائج فارغة.
                </div>
            @elseif($filterUser)
                <div class="alert alert-info" style="margin-bottom: 0;">
                    <i class="fa fa-user"></i> تصفية على المستخدم:
                    <a href="{{ admin_url('users/' . $filterUser->id) }}" target="_blank">
                        {{ $filterUser->name }} ({{ $filterUser->uuid }})
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== الكروت الرئيسية ===================== --}}
    <div class="row">
        {{-- 1) صافي اللاعبين: هل اللاعبون كسبوا أم خسروا اليوم --}}
        <div class="col-md-4 col-sm-6">
            <div class="hero" style="background: {{ $playersNet >= 0 ? 'linear-gradient(135deg,#27ae60,#1e8449)' : 'linear-gradient(135deg,#e74c3c,#c0392b)' }};">
                <div class="h-val">{{ ($playersNet > 0 ? '+' : '') . $fmt($playersNet) }}</div>
                <div class="h-lbl">
                    <i class="fa {{ $playersNet >= 0 ? 'fa-smile-o' : 'fa-frown-o' }}"></i>
                    صافي اللاعبين للفترة
                </div>
                <div class="h-sub">{{ $playersNet >= 0 ? 'اللاعبون كسبوا إجمالاً ✓' : 'اللاعبون خسروا إجمالاً' }}</div>
            </div>
        </div>

        {{-- 2) رصيد خزنة الحظ الآن (لحظي، عام) --}}
        <div class="col-md-4 col-sm-6">
            <div class="hero" style="background: linear-gradient(135deg,#2c3e50,#1a252f);">
                <div class="h-val">{{ number_format($vault) }}</div>
                <div class="h-lbl"><i class="fa fa-database"></i> رصيد خزنة الحظ الآن</div>
                <div class="h-sub">بنك الجوائز الحالي (لحظي — غير مرتبط بالفلتر)</div>
            </div>
        </div>

        {{-- 3) ربح التطبيق للفترة --}}
        <div class="col-md-4 col-sm-6">
            <div class="hero" style="background: linear-gradient(135deg,#2980b9,#1f618d);">
                <div class="h-val">{{ $fmt($summary['app_total']) }}</div>
                <div class="h-lbl"><i class="fa fa-building"></i> ربح التطبيق للفترة</div>
                <div class="h-sub">العمولة المضمونة من كل هدية</div>
            </div>
        </div>
    </div>

    {{-- ===================== إحصائيات مصغّرة ===================== --}}
    <div class="row">
        <div class="col-md-2 col-xs-4">
            <div class="ministat">
                <div class="m-val net-pos">{{ $fmt($summary['receivers_total']) }}</div>
                <div class="m-lbl">نصيب المستقبلين</div>
            </div>
        </div>
        <div class="col-md-2 col-xs-4">
            <div class="ministat">
                <div class="m-val">{{ $fmt($summary['total_bets']) }}</div>
                <div class="m-lbl">إجمالي الرهانات</div>
            </div>
        </div>
        <div class="col-md-2 col-xs-4">
            <div class="ministat">
                <div class="m-val">{{ number_format($summary['rounds']) }}</div>
                <div class="m-lbl">عدد الجولات</div>
            </div>
        </div>
        <div class="col-md-2 col-xs-4">
            <div class="ministat">
                <div class="m-val">{{ number_format($summary['players']) }}</div>
                <div class="m-lbl">عدد اللاعبين</div>
            </div>
        </div>
        <div class="col-md-2 col-xs-4">
            <div class="ministat">
                <div class="m-val">{{ $summary['win_rate'] }}%</div>
                <div class="m-lbl">نسبة الفوز ({{ number_format($summary['wins']) }})</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 col-xs-6">
            <div class="ministat">
                <div class="m-val">{{ $summary['rtp'] }}%</div>
                <div class="m-lbl">الـRTP الفعلي للفترة</div>
            </div>
        </div>
    </div>

    {{-- ===================== أعلى اللاعبين للفترة ===================== --}}
    <div class="row">
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-arrow-down"></i> أكبر 10 خاسرين صافي</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اللاعب</th>
                                <th>الجولات</th>
                                <th>إجمالي الرهان</th>
                                <th>الصافي</th>
                            </tr>
                        </thead>
                        <tbody id="lucky-top-losers">
                            <tr><td colspan="5" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> جارٍ التحميل…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-arrow-up"></i> أكبر 10 رابحين صافي</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اللاعب</th>
                                <th>الجولات</th>
                                <th>إجمالي الرهان</th>
                                <th>الصافي</th>
                            </tr>
                        </thead>
                        <tbody id="lucky-top-winners">
                            <tr><td colspan="5" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> جارٍ التحميل…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== جدول الجولات ===================== --}}
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-list"></i> سجل الجولات (الأحدث أولاً — 50 لكل صفحة)</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>الوقت</th>
                        <th>اللاعب</th>
                        <th>الغرفة</th>
                        <th>الهدية</th>
                        <th>الرهان</th>
                        <th>النتيجة</th>
                        <th>المضاعف</th>
                        <th>صافي الجولة</th>
                        <th>رصيده بعدها</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rounds as $t)
                        @php
                            $u = $usersById->get($t->user_id);
                            $g = $giftsById->get($t->gift_id);
                            $net = (float) $t->profit_amount;
                        @endphp
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($t->created_at)->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <a href="{{ admin_url('users/' . $t->user_id) }}" target="_blank">
                                    {{ $u->name ?? ('#' . $t->user_id) }}
                                    <small>({{ $u->uuid ?? '-' }})</small>
                                </a>
                            </td>
                            <td>
                                @if($t->room_id)
                                    <a href="{{ admin_url('rooms/' . $t->room_id) }}" target="_blank">
                                        {{ $roomNamesById->get($t->room_id) ?? ('#' . $t->room_id) }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $g->name ?? $g->e_name ?? ('#' . $t->gift_id) }}</td>
                            <td>{{ $fmt($t->bet_amount) }}</td>
                            <td>
                                @if($t->is_winner)
                                    <span class="label label-success">فاز</span>
                                @else
                                    <span class="label label-danger">خسر</span>
                                @endif
                            </td>
                            <td>{{ $t->multiplier ? 'x' . $t->multiplier : '-' }}</td>
                            <td class="{{ $net >= 0 ? 'net-pos' : 'net-neg' }}">{{ ($net > 0 ? '+' : '') . $fmt($net) }}</td>
                            <td>{{ $t->sender_balance_after !== null ? $fmt($t->sender_balance_after) : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">لا توجد جولات في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="box-footer clearfix" style="direction: ltr;">
            {{ $rounds->links('vendor.pagination.simple-bootstrap-4') }}
        </div>
    </div>

</div>

{{-- ===================== تحميل كسول لأعلى اللاعبين ===================== --}}
{{-- The heavy top winners/losers GROUP BY is pulled off the page-paint path and --}}
{{-- streamed in after first byte from the short-cached /top endpoint. --}}
<script>
(function () {
    var endpoint = '{{ admin_url('lucky-gift-reports/top') }}' + (window.location.search || '');

    function nf(n) {
        n = parseInt(n || 0, 10);
        return n.toLocaleString('en-US');
    }
    function fmt(n) {
        // mirror the blade $fmt: integers without decimals.
        return nf(n);
    }
    function userUrl(id) {
        return '{{ admin_url('users') }}/' + id;
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function renderRows(rows, netClass, sign) {
        if (!rows || rows.length === 0) {
            return '<tr><td colspan="5" class="text-center text-muted">لا توجد بيانات للفترة</td></tr>';
        }
        var html = '';
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            html += '<tr>'
                + '<td>' + (i + 1) + '</td>'
                + '<td><a href="' + userUrl(r.user_id) + '" target="_blank">'
                + escapeHtml(r.name) + ' <small>(' + escapeHtml(r.uuid) + ')</small></a></td>'
                + '<td>' + nf(r.rounds) + '</td>'
                + '<td>' + fmt(r.total_bets) + '</td>'
                + '<td class="' + netClass + '">' + sign + fmt(r.net) + '</td>'
                + '</tr>';
        }
        return html;
    }

    function fail(id) {
        var el = document.getElementById(id);
        if (el) {
            el.innerHTML = '<tr><td colspan="5" class="text-center text-muted">تعذّر التحميل</td></tr>';
        }
    }

    fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
        .then(function (data) {
            var w = document.getElementById('lucky-top-winners');
            var l = document.getElementById('lucky-top-losers');
            if (w) { w.innerHTML = renderRows(data.winners, 'net-pos', '+'); }
            if (l) { l.innerHTML = renderRows(data.losers, 'net-neg', ''); }
        })
        .catch(function () { fail('lucky-top-winners'); fail('lucky-top-losers'); });
})();
</script>
