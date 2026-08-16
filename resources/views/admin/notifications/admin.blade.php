<li class="nav-item dropdown" id="notificationsDropdown">
    <a href="#" class="nav-link" onclick="openModal(event)" style="position: relative;">
        <i class="fa fa-bell" style="font-size: 20px;"></i>
        <span id="notificationsCount"
            style="position:absolute; top:5px; right:5px; background:red; color:white; border-radius:50%; padding:2px 6px; font-size:11px; display:none;">
        </span>
    </a>
</li>

<!-- المودال -->
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
            <a href="{{ route('admin.notifications.grid') }}" class="btn btn-footer btn-show-more" id="loadMoreBtn" >
                    {{ __('Show more') }}
            </a>
        </div>
    </div>
</div>
<audio id="notificationSound" src="{{ asset('sounds/notification.mp3') }}" preload="auto" style="display:none;"></audio>

<script>
    window.NOTIFICATIONS_API = {
        countUrl: "{{ admin_url('notifications/count') }}",
        listUrl: "{{ admin_url('notifications/list') }}",
        markReadUrl: "{{ admin_url('notifications/mark-all-read') }}"
    };

    const modal = document.getElementById('myModal');

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    $('.btn-show-more').click(function (e) {
        closeModal();
    });
</script>

<link rel="stylesheet" href="{{ asset('css/modal.css') }}">
<script src="{{ asset('js/modal.js') }}"></script>
<script type="module" src="{{ asset('js/firebase-notification.js') }}"></script>

