<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-eye"></i>
            معاينة الحسابات المكررة
        </h3>
    </div>
    <div class="box-body">
        @if(count($devices) > 0)
            <div class="alert alert-warning" style="font-size:15px;">
                <h4><i class="fa fa-exclamation-triangle"></i> ملخص المعاينة</h4>
                <ul style="font-size:16px; margin-top:10px;">
                    <li><strong>عدد الأجهزة المتأثرة:</strong> <span class="label label-warning" style="font-size:14px;">{{ $total_devices }}</span></li>
                    <li><strong>إجمالي الحسابات التي سيتم حذفها:</strong> <span class="label label-danger" style="font-size:14px;">{{ $total_to_delete }}</span></li>
                    <li><strong>الحد المسموح:</strong> <span class="label label-info" style="font-size:14px;">{{ $allowed_accounts }}</span> حساب لكل جهاز</li>
                </ul>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead style="background:#5bc0de; color:white;">
                        <tr>
                            <th style="width:15%;">Device Token</th>
                            <th style="width:10%; text-align:center;">إجمالي الحسابات</th>
                            <th style="width:10%; text-align:center;">سيتم الحذف</th>
                            <th style="width:32%;">الحسابات المحذوفة ❌</th>
                            <th style="width:33%;">الحسابات المتبقية ✅</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($devices as $index => $device)
                        <tr style="{{ $index % 2 === 0 ? 'background:#f9f9f9;' : '' }}">
                            <td><code style="font-size:11px;">{{ substr($device['device_token'], 0, 25) }}...</code></td>
                            <td style="text-align:center;"><span class="badge bg-blue" style="font-size:13px;">{{ $device['total_accounts'] }}</span></td>
                            <td style="text-align:center;"><span class="badge bg-red" style="font-size:13px;">{{ $device['to_delete_count'] }}</span></td>
                            <td>
                                @foreach($device['users_to_delete'] as $user)
                                <div style="margin:8px 0; padding:10px; background:#ffe6e6; border-left:4px solid #d9534f; border-radius:3px;">
                                    <strong style="font-size:14px;">{{ $user->name ?? 'بدون اسم' }}</strong>
                                    @if($user->is_logout)
                                        <span class="label label-warning" style="margin-left:5px;">Logout</span>
                                    @else
                                        <span class="label label-success" style="margin-left:5px;">Active</span>
                                    @endif
                                    <br>
                                    <small style="color:#555;">
                                        <i class="fa fa-user"></i> ID: {{ $user->id }} |
                                        <i class="fa fa-hashtag"></i> UUID: {{ $user->uuid }}<br>
                                        <i class="fa fa-phone"></i> {{ $user->phone ?? $user->email ?? 'بدون بيانات' }}<br>
                                        <i class="fa fa-calendar"></i> {{ $user->created_at }}
                                    </small>
                                </div>
                                @endforeach
                            </td>
                            <td>
                                @foreach($device['users_to_keep'] as $user)
                                <div style="margin:8px 0; padding:10px; background:#e6ffe6; border-left:4px solid #5cb85c; border-radius:3px;">
                                    <strong style="font-size:14px;">{{ $user->name ?? 'بدون اسم' }}</strong><br>
                                    <small style="color:#555;">
                                        <i class="fa fa-user"></i> ID: {{ $user->id }} |
                                        <i class="fa fa-hashtag"></i> UUID: {{ $user->uuid }}<br>
                                        <i class="fa fa-phone"></i> {{ $user->phone ?? $user->email ?? 'بدون بيانات' }}<br>
                                        <i class="fa fa-calendar"></i> {{ $user->created_at }}
                                    </small>
                                </div>
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <hr>
            <div class="alert alert-danger" style="font-size:15px;">
                <h4><i class="fa fa-warning"></i> تحذير!</h4>
                <p>إذا قمت بتغيير الرابط من <code>/preview</code> إلى <code>/run</code>، سيتم حذف الحسابات المعروضة أعلاه بشكل نهائي!</p>
            </div>

            <a href="/admin/cleanup-duplicate-devices/run" class="btn btn-danger btn-lg"
               onclick="return confirm('⚠️ هل أنت متأكد من حذف {{ $total_to_delete }} حساب؟\n\nسيتم حذف:\n- الحسابات من قاعدة البيانات (soft delete)\n- جميع الـ tokens المرتبطة\n- بيانات user_accounts\n\nهذا الإجراء لا يمكن التراجع عنه بسهولة!');">
                <i class="fa fa-trash"></i> تنفيذ الحذف (غير الرابط إلى /run)
            </a>
            <a href="/admin/users" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> العودة للمستخدمين
            </a>

        @else
            <div class="alert alert-success" style="font-size:16px;">
                <i class="fa fa-check-circle fa-2x"></i>
                <strong style="font-size:18px;">رائع!</strong>
                <p>لا توجد حسابات زيادة تحتاج للحذف. جميع الأجهزة ضمن الحد المسموح ({{ $allowed_accounts }} حساب).</p>
            </div>

            <a href="/admin/users" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i> العودة للمستخدمين
            </a>
        @endif
    </div>
</div>
