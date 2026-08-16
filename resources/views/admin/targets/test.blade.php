<div class="container">
    <div class="row" style="background-color: white;">
        <div class="col-md-12 mb-4" style="    margin: 10px;
        text-align: end;">
        <a href="{{ url("admin/test-test/create") }}" class="btn btn-primary"> add new</a>
        </div>
        @foreach ($targets as $target)
            <div class="col-md-4 mb-4">
                <div class="card" style="background-color: #e9dbdb; border: 1px solid #dee2e6; border-radius: 0.5rem; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); margin-bottom: 20px;    padding-left: 10px;background-color: #e9dbdb;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="dropdown" style="padding-top:10px;text-align:end">
                                    <a class="btn btn-primary btn-sm d-inline-block" href="{{ url('admin/test-test/'.$target->id.'/edit') }}">{{ __('Edit') }}</a>
                                    <a class="btn btn-danger btn-sm d-inline-block" href="#" onclick="confirmDelete({{ $target->id }})">{{ __('Delete') }}</a>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div style="padding: 10px">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th>{{ __('Target No:') }}</th>
                                                <td>{{ $target->level }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Diamonds:') }}</th>
                                                <td>{{ number_format($target->diamonds) }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('USD:') }}</th>
                                                <td>{{ $target->usd }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Hours:') }}</th>
                                                <td>{{ $target->hours }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Days:') }}</th>
                                                <td>{{ $target->days }}</td>
                                            </tr>
                                            
                                            <!-- Reel Information -->
                                            @php
                                                $reel = explode(',', $target->reel);
                                                $update = isset($reel[0]) ? $reel[0] : 0;
                                                $like = isset($reel[1]) ? $reel[1] : 0;
                                                $comment = isset($reel[2]) ? $reel[2] : 0;
                                            @endphp
                                            <tr>
                                                <th>{{ __('Reel Update:') }}</th>
                                                <td>{{ $update }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Reel Likes:') }}</th>
                                                <td>{{ $like }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Reel Comments:') }}</th>
                                                <td>{{ $comment }}</td>
                                            </tr>
                                    
                                            <!-- Moment Information -->
                                            @php
                                                $moment = explode(',', $target->moment);
                                                $moment_update = isset($moment[0]) ? $moment[0] : 0;
                                                $moment_like = isset($moment[1]) ? $moment[1] : 0;
                                                $moment_comment = isset($moment[2]) ? $moment[2] : 0;
                                            @endphp
                                            <tr>
                                                <th>{{ __('Moment Update:') }}</th>
                                                <td>{{ $moment_update }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Moment Likes:') }}</th>
                                                <td>{{ $moment_like }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Moment Comments:') }}</th>
                                                <td>{{ $moment_comment }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <script>
        function confirmDelete(targetId) {
            if (confirm('Are you sure you want to delete this target?')) {
                // Replace with actual delete logic
                window.location.href = '/admin/test-test/' + targetId + '/delete';
            }
        }
    </script>
</div>