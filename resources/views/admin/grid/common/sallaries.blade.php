<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">{{__('Fields')}}</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding d-flex flex">
        <div>
            <ul class="nav nav-pills nav-stacked">
                <li class="{{ request()->name == 'users' || request()->name == null ? 'active' : '' }}"><a
                        href="?name=users" class="charge_action"><i
                            class="fa fa-arrow-right text-red"></i>{{ __('users sallaries') }}</a></li>
                <li class="{{ request()->name == 'agencies' ? 'active' : '' }}"><a href="?name=agencies"
                        class="charge_action"><i
                            class="fa fa-arrow-right text-red"></i>{{ __('agencies sallaries') }}</a></li>
            </ul>
        </div>
    </div>
    <!-- /.box-body -->
</div>

<div class="col">

    @php
        $users = request()->name == 'users' || request()->name == null;
        $agencies = request()->name == 'agencies';
    @endphp

    @if ($users)
        @php
            $user = \App\Models\User::where('uuid', @request('uuid') ?? '0')->first();
            $year = request('year') == null ? now()->year : request('year');
            $month = request('month') == null ? now()->month : request('month');
            $userSalaries = \App\Models\UserSallary::when(request()->has('uuid') && request('uuid') != null, function ($query) use ($user) {
                $query->where('user_id', @$user->id);
            })
                // ->when(request()->has('agency_id') && request('agency_id') != null, function ($query) {
                //     $query->where('user_agency_id', request('agency_id'));
                // })
                // ->where(function ($query) use ($year, $month) {
                //     $query->where(DB::raw('concat(year,"-", month)'), '<=', $year . '-' . $month);
                // })
                ->whereHas('user', function ($q) {
                    $q->where('agency_id', '!=', 0);
                })
                ->select(DB::raw('sum(sallary) as totalTarget2'), DB::raw('sum(sallary - cut_amount) as totalSalary2'))
                ->first();
            $userCutAmoubt = \App\Models\UserSallary::when(request()->has('uuid') && request('uuid') != null, function ($query) use ($user) {
                $query->where('user_id', @$user->id);
            })
                ->when(request()->has('agency_id') && request('agency_id') != null, function ($query) {
                    $query->where('user_agency_id', request('agency_id'));
                })
                ->where(function ($query) use ($year, $month) {
                    $query->where(DB::raw('concat(year,"-", month)'), '<=', $year . '-' . $month);
                })
                ->whereHas('user', function ($q) {
                    $q->where('agency_id', '!=', 0);
                })
                ->select(DB::raw('sum(cut_amount) as totalPayments'))
                ->first();
            $totalDiamonds = \App\Models\UserTarget::when(request()->has('uuid') && request('uuid') != null, function ($query) use ($user) {
                $query->where('user_id', @$user->id);
            })
                // ->when(request()->has('agency_id') && request('agency_id') != null, function ($query) {
                //     $query->where('agency_id', request('agency_id'));
                // })
                // ->where(function ($query) use ($year, $month) {
                //     $query->where(DB::raw('concat(add_year,"-", add_month)'), '<=', $year . '-' . $month);
                // })
                ->sum(DB::raw('user_diamonds'));
            // @dump(request('agency_id'));
            $diamons = $totalDiamonds ?? 0;
            $targe = $userSalaries->totalTarget2 ?? 0;
            $salary = $userSalaries->totalSalary2 ?? 0;
            $payments = $userCutAmoubt->totalPayments ?? 0;

        @endphp
        <div class="col my-1 form-Roles">
            <label class="form-label">{{ __('admin.diamond') }}</label>

            <input type="text" class="form-control" name="diamond" id="diamond" value= "{{ $diamons }}">
        </div>
        <br>
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.target') }}</label>

            <input type="text" class="form-control " id="target" name="target" value="{{ $targe }}">
        </div>
        <br>
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.salary') }}</label>

            <input type="text" class="form-control " id="salary" name="salary" value="{{ $salary }}">
        </div>
        <br>
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.payments') }}</label>

            <input type="text" class="form-control " id="payments" name="payments" value="{{ $payments }}">
        </div>
    @else
        @php
            $year = request('year') == null ? now()->year : request('year');
            $month = request('month') == null ? now()->month : request('month');
            $agencySallary = \App\Models\AgencySallary::where(function ($query) use ($year, $month) {
                $query->where(DB::raw('concat(year,"-", month)'), '<=', $year . '-' . $month);
            })
                ->when(request()->has('id') && request('id') != null, function ($query) {
                    $query->where('agency_id', request('id'));
                })
                ->select(DB::raw('sum(sallary) as totalTarget'), DB::raw('sum(cut_amount) as totalPayments'), DB::raw('sum(sallary - cut_amount) as totalSallary'))
                ->first();
            $targe = $agencySallary->totalTarget ?? 0;
            $salary = $agencySallary->totalSallary ?? 0;
            $payments = $agencySallary->totalPayments ?? 0;
        @endphp
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.target') }}</label>

            <input type="text" class="form-control " id="target" name="target" value="{{ $targe }}">
        </div>
        <br>
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.salary') }}</label>

            <input type="text" class="form-control " id="sender" name="sender" value="{{ $salary }}">
        </div>
        <br>
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.payments') }}</label>

            <input type="text" class="form-control " id="sender" name="sender" value="{{ $payments }}">
        </div>
    @endif






</div>
