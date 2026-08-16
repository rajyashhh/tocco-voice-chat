<meta name="csrf-token" content="{{ csrf_token() }}">

@foreach ($notifications as $notif)
    @php
        $data = is_array($notif->data) ? $notif->data : json_decode($notif->data, true);

        $title = __("{$notif->title}") ?: $notif->title;
        $message = __(
            "{$notif->message}",
            [
                'name' => $data['requested_by'] ?? 'غير معروف',
                'id' => $data['requested_by_id'] ?? 0,
                'coins' => $data['coins_deducted'] ?? 0,
            ]
        );

        $previewUrl = $data['preview_url'] ?? null;
        if ($previewUrl) {
            $enum = SuperAdminNotificationLink::tryFrom($previewUrl);
            if ($enum) {
                $previewUrl = $enum->url($data);
            }
        }
    @endphp

    <div class="notification-item {{ $notif->is_read ? 'read' : 'unread' }}"
         data-id="{{ $notif->id }}"
         style="cursor:pointer;"
         onclick="superAdminhandleNotificationClick({{ $notif->id }}, '{{ $previewUrl }}')">

        <div class="title">{{ $title }}</div>
        <div class="message text-muted small">{{ $message }}</div>
        <div class="time text-secondary small">{{ $notif->created_at->diffForHumans() }}</div>
    </div>
@endforeach


<style>
.notification-item {
    border-bottom: 1px solid #eee;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 8px;
}
.notification-item.unread {
    background: #f3f6ff;
}
.notification-item.read {
    background: #ffffff;
    opacity: 0.9;
}
.notification-item .title {
    font-weight: bold;
    font-size: 14px;
}
.notification-item .message {
    font-size: 13px;
}
.notification-item .time {
    font-size: 12px;
    color: #888;
}
</style>

<script>


document.addEventListener("DOMContentLoaded", function () {


    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            fetch(`/admin/notifications/mark-as-read/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => {
                if (res.ok) {
                    this.closest('.notification-item').classList.remove('unread');
                    this.closest('.notification-item').classList.add('read');
                    this.remove();
                }
            });
        });
    });
});



</script>



