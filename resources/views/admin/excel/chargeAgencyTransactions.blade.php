<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>{{ @$agency->name }}</h1>

        {{-- جدول المستلمة --}}
        <h3 class="mt-4">{{ __('Received Transactions') }}</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ __('sender') }}</th>
                    <th>{{ __('sender id') }}</th>
                    <!-- <th>{{ __('receiver') }}</th> -->
                    <!-- <th>{{ __('receiver id') }}</th> -->
                    <th>{{ __('type') }}</th>
                    <th>{{ __('amount') }}</th>
                    <th>{{ __('coins') }}</th>
                    <th>{{ __('date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($received as $charge)
                    @php
                        $sender = \App\Helpers\Common::getChargerInfo($charge);
                        $receiver = \App\Helpers\Common::getReceiverInfo($charge);
                    @endphp
                    <tr>
                        <td>{{ $sender['name'] }}</td>
                        <td>{{ $sender['uuid'] }}</td>
                        <!-- <td>{{ $receiver['name'] }}</td> -->
                        <!-- <td>{{ $receiver['uuid'] }}</td> -->
                        <td>{{ $charge->user_type }}</td>
                        <td>{{ $charge->usd !== null ? '$' . number_format($charge->usd, 2) : 0 }}</td>
                        <td>{{ $charge->amount }}</td>
                        <td>{{ $charge->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- جدول المرسلة --}}
        <h3 class="mt-4">{{ __('Sent Transactions') }}</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <!-- <th>{{ __('sender') }}</th> -->
                    <!-- <th>{{ __('sender id') }}</th> -->
                    <th>{{ __('receiver') }}</th>
                    <th>{{ __('receiver id') }}</th>
                    <th>{{ __('type') }}</th>
                    <th>{{ __('amount') }}</th>
                    <th>{{ __('coins') }}</th>
                    <th>{{ __('date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sent as $charge)
                    @php
                        $sender = \App\Helpers\Common::getChargerInfo($charge);
                        $receiver = \App\Helpers\Common::getReceiverInfo($charge);
                    @endphp
                    <tr>
                        <!-- <td>{{ $sender['name'] }}</td> -->
                        <!-- <td>{{ $sender['uuid'] }}</td> -->
                        <td>{{ $receiver['name'] }}</td>
                        <td>{{ $receiver['uuid'] }}</td>
                        <td>{{ $charge->user_type }}</td>
                        <td>{{ $charge->usd !== null ? '$' . number_format($charge->usd, 2) : 0 }}</td>
                        <td>{{ $charge->amount }}</td>
                        <td>{{ $charge->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
