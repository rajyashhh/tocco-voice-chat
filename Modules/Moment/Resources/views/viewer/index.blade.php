<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print"
      onload="this.media='all'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print"
      onload="this.media='all'">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" media="print"
      onload="this.media='all'">
<link rel="stylesheet" href="{{ route(config('admin.route.prefix') . '.moment-viewer.viewer-css') }}?v={{ time() }}">

<div class="viewer-layout">
    <div class="viewer-main-content">
        <div class="viewer-container" style="min-height: 100vh; height: auto; overflow: visible;">
            <div class="viewer-header">
                <h1 class="viewer-title">
                    <i class="fas fa-photo-video"></i>
                    {{ __('moment_viewer.momentsBrowser') }}
                </h1>
                <div class="viewer-controls">
                    <input type="text" id="userIdFilter" class="filter-input"
                           placeholder="{{ __('moment_viewer.filterByUserId') }}">
                    <input type="text" id="userSearch" class="search-input"
                           placeholder="{{ __('moment_viewer.searchByName') }}">
                    <select id="sortSelect" class="sort-select">
                        <option value="random" selected>{{ __('moment_viewer.randomOrder') }} 🔀</option>
                        <option value="newest">{{ __('moment_viewer.newestFirst') }} 🆕</option>
                        <option value="oldest">{{ __('moment_viewer.oldestFirst') }} 🕰</option>
                    </select>
                    <button id="refreshBtn" class="refresh-btn">
                        <i class="fas fa-sync-alt"></i>
                        {{ __('moment_viewer.refresh') }}
                    </button>
                </div>
            </div>

            <div id="momentsFeed" class="moments-feed">
                <div class="loading-container">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="users-sidebar">
        <div class="users-sidebar-header">
            <h3><i class="fas fa-users"></i> {{ __('moment_viewer.usersWithMoments') }} <span id="usersTotalCount"></span></h3>
        </div>
        <div class="users-search-box">
            <input type="text" id="usersListSearch" class="users-list-search"
                   placeholder="{{ __('moment_viewer.searchUsers') }}">
        </div>
        <div class="users-filter-info" id="usersFilterInfo">
            <button id="clearFilterBtn" class="clear-filter-btn">
                <i class="fas fa-times"></i> {{ __('moment_viewer.clearFilter') }}
            </button>
        </div>
        <div id="usersListContainer" class="users-list-container">
            <div class="loading-container">
                <div class="spinner"></div>
            </div>
        </div>
        <div id="usersLoadingMore" class="users-loading-more">
            <div class="spinner-small"></div>
        </div>
        <div id="usersLoadMore" class="users-load-more">
            <button id="loadMoreUsersBtn" class="load-more-users-btn">{{ __('moment_viewer.loadMoreUsers') }}</button>
        </div>
    </div>
</div>

<button class="scroll-top" id="scrollTopBtn">
    <i class="fas fa-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.MomentViewerConfig = {
        routes: {
            moments: '{{ route(config('admin.route.prefix') . '.moment-viewer.api.moments') }}',
            resetRandom: '{{ route(config('admin.route.prefix') . '.moment-viewer.api.reset-random') }}',
            comments: '{{ route(config('admin.route.prefix') . '.moment-viewer.api.comments', ['id' => ':id']) }}',
            likes: '{{ route(config('admin.route.prefix') . '.moment-viewer.api.likes', ['id' => ':id']) }}',
            gifts: '{{ route(config('admin.route.prefix') . '.moment-viewer.api.gifts', ['id' => ':id']) }}',
            updateDescription: "{{ url(config('admin.route.prefix') . '/moment-viewer/api/moment') }}/:id/description",
            deleteComment: "{{ url(config('admin.route.prefix') . '/moment-viewer/api/comment') }}/:id",
            deleteMoment: '{{ route(config('admin.route.prefix') . '.moment-viewer.api.delete-moment', ['id' => ':id']) }}',
            usersWithMoments: '{{ route(config('admin.route.prefix') . '.moment-viewer.api.users-with-moments') }}'
        },
        adminUserUrl: "{{ admin_url('users') }}/",
        defaultAvatar: "{{ asset('images/businessman-icon.jpg') }}",
        storageUrl: "{{ config('filesystems.disks.' . config('filesystems.default') . '.url') }}",
        csrf: '{{ csrf_token() }}',
        texts: {
            error: '{{ __('moment_viewer.error') }}',
            tryAgain: '{{ __('moment_viewer.tryAgain') }}',
            noData: '{{ __('moment_viewer.noData') }}',
            failedLoad: '{{ __('moment_viewer.failedLoad') }}',
            noMoments: '{{ __('moment_viewer.noMoments') }}',
            noMomentsMsg: '{{ __('moment_viewer.noMomentsMsg') }}',
            clearSearch: '{{ __('moment_viewer.clearSearch') }}',
            comments: '{{ __('moment_viewer.comments') }}',
            comment: '{{ __('moment_viewer.comment') }}',
            delete: '{{ __('moment_viewer.delete') }}',
            noComments: '{{ __('moment_viewer.noComments') }}',
            failComments: '{{ __('moment_viewer.failComments') }}',
            noLikes: '{{ __('moment_viewer.noLikes') }}',
            failLikes: '{{ __('moment_viewer.failLikes') }}',
            gifts: '{{ __('moment_viewer.gifts') }}',
            noGifts: '{{ __('moment_viewer.noGifts') }}',
            failGifts: '{{ __('moment_viewer.failGifts') }}',
            editDesc: '{{ __('moment_viewer.editDesc') }}',
            deleteMoment: '{{ __('moment_viewer.deleteMoment') }}',
            save: '{{ __('moment_viewer.save') }}',
            cancel: '{{ __('moment_viewer.cancel') }}',
            descTooLong: '{{ __('moment_viewer.descTooLong') }}',
            updated: '{{ __('moment_viewer.updated') }}',
            descUpdated: '{{ __('moment_viewer.descUpdated') }}',
            failUpdate: '{{ __('moment_viewer.failUpdate') }}',
            sure: '{{ __('moment_viewer.sure') }}',
            noRevert: '{{ __('moment_viewer.noRevert') }}',
            yesDelete: '{{ __('moment_viewer.yesDelete') }}',
            deleted: '{{ __('moment_viewer.deleted') }}',
            momentDeleted: '{{ __('moment_viewer.momentDeleted') }}',
            failDeleteMoment: '{{ __('moment_viewer.failDeleteMoment') }}',
            commentDeleted: '{{ __('moment_viewer.commentDeleted') }}',
            failDeleteComment: '{{ __('moment_viewer.failDeleteComment') }}',
            years: '{{ __('moment_viewer.years') }}',
            months: '{{ __('moment_viewer.months') }}',
            days: '{{ __('moment_viewer.days') }}',
            hours: '{{ __('moment_viewer.hours') }}',
            minutes: '{{ __('moment_viewer.minutes') }}',
            now: '{{ __('moment_viewer.now') }}',
            likes: '{{ __('moment_viewer.likes') }}',
            usersWithMoments: '{{ __('moment_viewer.usersWithMoments') }}',
            searchUsers: '{{ __('moment_viewer.searchUsers') }}',
            clearFilter: '{{ __('moment_viewer.clearFilter') }}',
            loadMoreUsers: '{{ __('moment_viewer.loadMoreUsers') }}'
        }
    };
</script>
<script src="{{ asset('modules/moment/viewer.js') }}?v={{ time() }}"></script>
