<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Room Support</title>
     <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #90EE90 0%, #228B22 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            margin-bottom: 20px;
        }

        .back-btn {
            font-size: 24px;
            color: #333;
            text-decoration: none;
        }

        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .refresh-btn {
            font-size: 24px;
            color: #333;
        }

        .banner {
            position: relative;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-radius: 20px;
            padding: 30px 20px;
            margin-bottom: 20px;
            text-align: center;
            overflow: hidden;
        }

        .banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(10%, 10%); }
        }

        .user-info {
            background: rgba(139, 0, 255, 0.9);
            display: inline-flex;
            align-items: center;
            padding: 8px 20px;
            border-radius: 20px;
            color: white;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .user-id {
            margin-left: 10px;
            font-weight: bold;
        }

        .treasure-btn {
            background: linear-gradient(135deg, #FF6B6B, #FF4757);
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }

        .title-badge {
            position: relative;
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            color: white;
            padding: 15px 60px;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            clip-path: polygon(5% 0%, 95% 0%, 100% 50%, 95% 100%, 5% 100%, 0% 50%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .title-badge::before {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            right: 3px;
            bottom: 3px;
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            clip-path: polygon(5% 0%, 95% 0%, 100% 50%, 95% 100%, 5% 100%, 0% 50%);
            z-index: -1;
        }

        .title-badge::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: transparent;
            border: 3px solid #FFD700;
            clip-path: polygon(5% 0%, 95% 0%, 100% 50%, 95% 100%, 5% 100%, 0% 50%);
            z-index: -2;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #FFD700, #FFA500);
        }

        th {
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            border-right: 1px solid rgba(255,255,255,0.3);
        }

        th:last-child {
            border-right: none;
        }

        tbody tr {
            background: #2ECC71;
            border-bottom: 1px solid #27AE60;
        }

        tbody tr:nth-child(even) {
            background: #27AE60;
        }

        td {
            padding: 20px 10px;
            text-align: center;
            color: white;
            font-weight: 500;
            border-right: 1px solid rgba(255,255,255,0.2);
            font-size: 13px;
        }

        td:first-child {
            font-weight: bold;
            font-size: 16px;
        }

        td:last-child {
            border-right: none;
        }

        .condition-text {
            line-height: 1.6;
        }

        .reward-text {
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="title-badge">{{__('Weekly Room Support')}}</div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{__('Level')}}</th>
                        <th>{{__('Weekly Condition')}}</th>
                        <th>{{__('Total Support')}}</th>
                        <th>{{__('Support Reward')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cupTargets as $index =>$cupTarget)
                       @php
                       $totalAdminProfit = ($cupTarget->admin_profit / $cupTarget->number_of_admins);
                       $total =  $cupTarget->admin_profit + $cupTarget->owner_profit;
                        @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="condition-text">
                             {{__('Trophy'). ': ' .numToStringNew($cupTarget->total) }}<br>
                             {{__('Visitor'). ': ' .$cupTarget->number_of_visitors }}
                        </td>
                        <td>{{ numToStringNew($total)  }}</td>
                        <td class="reward-text">
                             {{__('Owner') . ': ' .numToStringNew($cupTarget->owner_profit) }}<br>
                             {{__('Admin') .'*'. ': ' .$cupTarget->number_of_admins}}: {{ numToStringNew($totalAdminProfit) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>