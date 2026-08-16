{{-- إشعارات السوبر أدمن --}}
<li class="nav-item dropdown" id="notificationsDropdown">
    <a href="#" class="nav-link" onclick="openModal(event)" style="position: relative;">
        <i class="fa fa-bell" style="font-size: 20px;"></i>
        <span id="notificationsCount"
              style="position:absolute; top:5px; right:5px; background:red; color:white; border-radius:50%; padding:2px 6px; font-size:11px; display:none;">
        </span>
    </a>
</li>

<!-- Modal -->
<div class="modal-overlay" id="myModal">
    <div class="modal-no">
        <div class="modal-header">
            <h5>{{ __('Notifications') }}</h5>
            <button type="button" class="close-btn" id="closeModalBtn">×</button>
        </div>

        <div class="modal-body2" id="notificationsContent">
            <div class="text-center text-muted p-3">{{ __('dashboard.login.loading.prepare') }}</div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-footer btn-mark-all" id="markAllReadBtn">
                {{ __('Mark all as read') }}
            </button>
            <a href="{{ route('superadmin.notifications.grid') }}" class="btn btn-footer btn-show-more" onclick=" closeModal();" id="loadMoreBtn">
                {{ __('Show more') }}
            </a>
        </div>
    </div>
</div>

<audio id="notificationSound" src="{{ asset('sounds/notification.mp3') }}" preload="auto" style="display:none;"></audio>

<script>
    window.NOTIFICATIONS_API = {
        countUrl: "{{ superadmin_url('notifications/count') }}",
        listUrl: "{{ superadmin_url('notifications/list') }}",
        markReadUrl: "{{ superadmin_url('notifications/mark-all-read') }}"
    };

    const modal = document.getElementById('myModal');

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
</script>

<link rel="stylesheet" href="{{ asset('css/superadmin/modal.css') }}">
<script src="{{ asset('js/superadmin/modal.js') }}"></script>
<script type="module" src="{{ asset('js/superadmin-firebase-notification.js') }}"></script>
