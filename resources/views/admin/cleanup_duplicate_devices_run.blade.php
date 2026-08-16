<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-check-circle"></i>
            تم تنفيذ الحذف بنجاح!
        </h3>
    </div>
    <div class="box-body">
        <div class="alert alert-success" style="font-size:16px;">
            <h3><i class="fa fa-check-circle"></i> تمت العملية بنجاح!</h3>
            <p>تم حذف الحسابات المكررة بنجاح.</p>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-mobile"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">الأجهزة المعالجة</span>
                        <span class="info-box-number">{{ $devices_processed }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-red">
                    <span class="info-box-icon"><i class="fa fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">الحسابات المحذوفة</span>
                        <span class="info-box-number">{{ $total_users_deleted }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-link"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">user_accounts</span>
                        <span class="info-box-number">{{ $total_user_accounts_deleted }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-aqua">
                    <span class="info-box-icon"><i class="fa fa-key"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tokens</span>
                        <span class="info-box-number">{{ $total_tokens_deleted }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if(count($deleted_users) > 0)
        <hr>
        <h4><i class="fa fa-list"></i> قائمة الحسابات المحذوفة:</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead style="background:#5cb85c; color:white;">
                    <tr>
                        <th>ID</th>
                        <th>الاسم</th>
                        <th>UUID</th>
                        <th>Device Token</th>
                        <th>تاريخ الإنشاء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deleted_users as $user)
                    <tr>
                        <td>{{ $user['id'] }}</td>
                        <td>{{ $user['name'] ?? 'بدون اسم' }}</td>
                        <td><code>{{ $user['uuid'] }}</code></td>
                        <td><code style="font-size:10px;">{{ substr($user['device_token'], 0, 20) }}...</code></td>
                        <td>{{ $user['created_at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <hr>
        <a href="/admin/cleanup-duplicate-devices/preview" class="btn btn-info">
            <i class="fa fa-eye"></i> عرض المعاينة مرة أخرى
        </a>
        <a href="/admin/users" class="btn btn-primary">
            <i class="fa fa-arrow-left"></i> العودة للمستخدمين
        </a>
    </div>
</div>
