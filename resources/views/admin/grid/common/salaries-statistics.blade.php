<div class="col">

    @php
        $users = request()->name == 'users' || request()->name == null;
        $agencies = request()->name == 'agencies';
    @endphp

    @if ($users)
        @php
            $user = \App\Models\User::where('uuid', @request('uuid') ?? '0')->first();
            $year =  request('year');
            $month =   request('month');
            $userSalaries = \App\Models\UserSallary::when(request()->has('uuid') && request('uuid') != null, function ($query) use ($user) {
                $query->where('user_id', @$user->id);
            })
                // ->when(request()->has('agency_id') && request('agency_id') != null, function ($query) {
                //     $query->where('user_agency_id', request('agency_id'));
                // })
                ->when(isset($year)&&isset($month),function ($query) use ($year, $month) {
                    $query->where(DB::raw('concat(year,"-", month)'), '=', $year . '-' . $month);
                })
                ->whereHas('user', function ($q) {
                    $q->where('agency_id', '!=', 0);
                })
                ->select(DB::raw('sum(sallary) as totalTarget2'), DB::raw('sum(achieved_diamond) as diamonds'))
                ->first();
           
            
            // @dump(request('agency_id'));
            $diamons = $userSalaries->diamonds ?? 0;
            $targe = $userSalaries->totalTarget2 ?? 0;
           

        @endphp
        <div class="col my-1 form-Roles">
            <label class="form-label">{{ __('admin.diamond') }}</label>

            <input type="text" class="form-control" name="diamond" id="diamond" value= "{{ $diamons }}" readonly>
        </div>
        <br>
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.total') }}</label>

            <input type="text" class="form-control " id="target" name="target" value="{{truncateAndTrim($targe)  }}" readonly>
        </div>
{{--        <br>--}}
{{--        <div class=" col  my-1 form-Roles">--}}
{{--            <label class="form-label">{{ __('admin.salary') }}</label>--}}

{{--            <input type="text" class="form-control " id="salary" name="salary" value="{{ $salary }}" readonly>--}}
{{--        </div>--}}
{{--        <br>--}}
{{--        <div class=" col  my-1 form-Roles">--}}
{{--            <label class="form-label">{{ __('admin.payments') }}</label>--}}

{{--            <input type="text" class="form-control " id="payments" name="payments" value="{{ $payments }}"readonly>--}}
{{--        </div>--}}
    @else
        @php
            $year = request('year');
            $month =request('month');
            $agency = request('agency_or_uuid');
            $agencySallary =  \App\Models\AgencySallary::
           when(isset($month)&&isset($year),function ($query) use ($year, $month) {
                    $query->where(DB::raw('concat(year,"-", month)'), '=', $year . '-' . $month);
                })
                ->when(isset($agency), function ($query) use ($agency) {
                    $query->where('agency_id', $agency) // Match directly on agency_id
                        ->orWhereHas('agency.owner', function ($subQuery) use ($agency) {
                            $subQuery->where('uuid', $agency); // Match on related owner UUID
                        });
                })
                ->select([
                    DB::raw('SUM(sallary) as totalTarget'),
                    DB::raw('SUM(cut_amount) as totalPayments'),
                    DB::raw('SUM(sallary - cut_amount) as totalSallary')
                ])
                ->first();
            $targe = $agencySallary->totalTarget ?? 0;
            $salary = $agencySallary->totalSallary ?? 0;
            $payments = $agencySallary->totalPayments ?? 0;
        @endphp
        <div class=" col  my-1 form-Roles">
            <label class="form-label">{{ __('admin.total') }}</label>

            <input type="text" class="form-control " id="target" name="target" value="{{truncateAndTrim( $targe) }}" readonly>
        </div>
{{--        <br>--}}
{{--        <div class=" col  my-1 form-Roles">--}}
{{--            <label class="form-label">{{ __('admin.salary') }}</label>--}}

{{--            <input type="text" class="form-control " id="sender" name="sender" value="{{ $salary }}" readonly>--}}
{{--        </div>--}}
{{--        <br>--}}
{{--        <div class=" col  my-1 form-Roles">--}}
{{--            <label class="form-label">{{ __('admin.payments') }}</label>--}}

{{--            <input type="text" class="form-control " id="sender" name="sender" value="{{ $payments }}" readonly>--}}
{{--        </div>--}}
    @endif






</div>
