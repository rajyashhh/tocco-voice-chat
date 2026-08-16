<div class="box-body table-responsive">
    <div>
        <div class="row mb-3" style="margin-bottom: 10px;
        margin-left: 23px;">
            <div class="col-md-4">
                <label for="">سعر الشحن من وكاله الشحن الي المستخدم</label>
                <input type="text" class="form-control" name="level" >
            </div>
            <div class="col-md-4">
                <label for="">سعر الشحن من وكاله المضفين الى المستخدمين</label>

                <input type="text" class="form-control" name="diamonds" >
            </div>
            <div class="col-md-4">
                <label for="">عدد المشرفين في الوكاله</label>

                <input type="text" class="form-control" name="usd" >
            </div>
        </div>
    </div>
    <div class="container">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('target no') }}</th>
                    <th>{{ __('diamonds') }}</th>
                    <th>{{ __('usd') }}</th>
                    <th>{{ __('hours') }}</th>
                    <th>{{ __('days') }}</th>
                    <th>{{ __('reel') }}</th>
                    <th>{{ __('Moment') }}</th>
                    <th>{{ __('agency share') }} (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($targets as $target)
                    <tr>
                        <td>{{ $target->id }}</td>
                        <td>{{ $target->level }}</td>
                        <td>{{ number_format($target->diamonds) }}</td>
                        <td>{{ $target->usd }}</td>
                        <td>{{ $target->hours }}</td>
                        <td>{{ $target->days }}</td>
                        <td>
                            @php
                                $reel = explode(',', $target->reel);
                                $update = $reel[0] != '' && $reel[0] != null ? $reel[0] : 0;
                                $like = $reel[1] ?? 0;
                                $comment = $reel[2] ?? 0;
                            @endphp
                            <span style="color: #000f;">{{ __('admin.update') }} {{ $update }}</span><br>
                            <span style="color: #000f;">{{ __('admin.like') }} {{ $like }}</span><br>
                            <span style="color: #000f;">{{ __('admin.comment') }} {{ $comment }}</span>
                        </td>
                        <td>
                            @php
                                $moment = explode(',', $target->moment);
                                $update = $moment[0] != '' && $moment[0] != null ? $moment[0] : 0;
                                $like = $moment[1] ?? 0;
                                $comment = $moment[2] ?? 0;
                            @endphp
                            <span style="color: #000f;">{{ __('admin.update') }} {{ $update }}</span><br>
                            <span style="color: #000f;">{{ __('admin.like') }} {{ $like }}</span><br>
                            <span style="color: #000f;">{{ __('admin.comment') }} {{ $comment }}</span>
                        </td>
                        <td>{{ number_format($target->agency_share, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>