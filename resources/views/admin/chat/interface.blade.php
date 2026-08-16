<style>@include('admin.chat.style')</style>

<div class="chat-wrapper">
    <div class="chat-container">
        <!-- New Message Notification (shown at top when scrolled up) -->
        <div class="new-message-notification" id="newMessageNotification" onclick="scrollToLatestMessage()">
            <i class="fa fa-comment-dots"></i>
            <span>{{ __('New message received') }}</span>
            <span class="message-count" id="notificationCount">1</span>
        </div>

        <!-- Scroll to Latest Button (shown when not at bottom) -->
        <button class="scroll-to-latest" id="scrollToLatestBtn" onclick="scrollToLatestMessage()">
            <i class="fa fa-chevron-down"></i>
            <span class="unread-badge" id="unreadBadge" style="display: none;">0</span>
        </button>

        <!-- Header -->
        <div class="chat-header">
            <a href="{{ route('admin.group-chat.index') }}" class="back-btn">
                <i class="fa fa-arrow-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}"></i>
            </a>
            <div class="profile-pic">
                <i class="fa fa-comments"></i>
            </div>
            <div class="chat-info">
                <h2>{{ __('Group Chat') }}</h2>
                <p class="status">
                    <span class="status-dot"></span>
                    {{ __('Admin View') }}
                </p>
            </div>
            <button class="refresh-btn" onclick="refreshMessages()">
                <span>{{ __('Refresh') }}</span>
                <i class="fa fa-sync-alt"></i>
            </button>
        </div>

        <div class="chat-filter">
            <div class="filter-toggle" onclick="toggleFilterPanel()">
                <i class="fa fa-filter"></i>
                <span>{{ __('Filters') }}</span>
                <i class="fa fa-chevron-down toggle-icon" id="filterToggleIcon"></i>
            </div>

            <div class="filter-panel" id="filterPanel" style="display: none;">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="userIdFilter">{{ __('User ID') }}:</label>
                        <input type="number" id="userIdFilter" class="filter-input" placeholder="{{ __('Enter User ID') }}" min="1">
                    </div>

                    <div class="filter-group">
                        <label for="userNameFilter">{{ __('User Name') }}:</label>
                        <input type="text" id="userNameFilter" class="filter-input" placeholder="{{ __('Enter User Name') }}">
                    </div>

                    <div class="filter-group">
                        <label for="uuidFilter">{{ __('UUID') }}:</label>
                        <input type="text" id="uuidFilter" class="filter-input" placeholder="{{ __('Enter UUID') }}">
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label for="dateFromFilter">{{ __('Date From') }}:</label>
                        <input type="date" id="dateFromFilter" class="filter-input">
                    </div>

                    <div class="filter-group">
                        <label for="dateToFilter">{{ __('Date To') }}:</label>
                        <input type="date" id="dateToFilter" class="filter-input">
                    </div>

                    <div class="filter-actions">
                        <button class="btn-info apply-filter" onclick="applyFilters()">
                            <i class="fa fa-search"></i> {{ __('Apply') }}
                        </button>
                        <button class="btn-danger" onclick="clearFilters()" id="clearFilterBtn" style="display: none;">
                            <i class="fa fa-times"></i> {{ __('Clear') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Filters Badge -->
            <div class="active-filters" id="activeFilters" style="display: none;">
                <span class="active-filters-label">{{ __('Active Filters') }}:</span>
                <div class="filter-badges" id="filterBadges"></div>
            </div>
        </div>

        <!-- Reply Preview (shown when replying to a message) -->
        <div class="reply-preview" id="replyPreview" style="display: none;">
            <div class="reply-content">
                <div class="reply-indicator"></div>
                <div class="reply-info">
                    <span class="reply-to-name" id="replyToName">{{ __('Replying to User') }}</span>
                    <span class="reply-to-text" id="replyToText">{{ __('Message text here...') }}</span>
                </div>
            </div>
            <button class="cancel-reply-btn" onclick="cancelReply()">
                <i class="fa fa-times"></i>
            </button>
            <input type="hidden" id="replyToId" value="">
        </div>

        <!-- Chat Messages -->
        <div class="chat-messages" id="chatMessages">
        </div>

        <!-- Message Input -->
        <div class="chat-input {{ !$canSendMessages ? 'disabled' : '' }}">
            <input type="text" id="messageInput"
                   placeholder="{{ $canSendMessages ? __('Type your message here') : __('You cannot send messages') }}"
                   class="message-input"
                {{ !$canSendMessages ? 'disabled' : '' }}>
            <button class="send-btn" onclick="sendMessage()" {{ !$canSendMessages ? 'disabled' : '' }}>
                <i class="fa fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fa fa-edit"></i>
                {{ __('Edit Message') }}
            </h3>
            <button class="close-btn" onclick="closeEditModal()">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <textarea id="editMessageText" rows="4" placeholder="{{ __('Type your message...') }}"></textarea>
        <input type="hidden" id="editMessageId">
        <div class="modal-footer">
            <button class="btn-danger" onclick="closeEditModal()">
                <i class="fa fa-times"></i> {{ __('Cancel') }}
            </button>
            <button class="btn-info" onclick="saveEdit()">
                <i class="fa fa-check"></i> {{ __('Save') }}
            </button>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    // ==================== TRANSLATIONS ====================
    const translations = {
        loadingMessages: "{{ __('Loading messages...') }}",
        failedToLoadMessages: "{{ __('Failed to load messages') }}",
        failedToLoadMoreMessages: "{{ __('Failed to load more messages') }}",
        refreshingMessages: "{{ __('Refreshing messages...') }}",
        pleaseEnterMessage: "{{ __('Please enter a message') }}",
        messageSentSuccessfully: "{{ __('Message sent successfully') }}",
        failedToSendMessage: "{{ __('Failed to send message') }}",
        messageCannotBeEmpty: "{{ __('Message cannot be empty') }}",
        messageUpdatedSuccessfully: "{{ __('Message updated successfully') }}",
        failedToUpdateMessage: "{{ __('Failed to update message') }}",
        confirmDeleteMessage: "{{ __('Are you sure you want to delete this message?') }}",
        messageDeletedSuccessfully: "{{ __('Message deleted successfully') }}",
        failedToDeleteMessage: "{{ __('Failed to delete message') }}",
        messageNotLoaded: "{{ __('Message not loaded. Loading older messages...') }}",
        scrollUpForOlderMessages: "{{ __('Scroll up for older messages') }}",
        replyingTo: "{{ __('Replying to') }}",
        user: "{{ __('User') }}",
        filtersApplied: "{{ __('Filters applied') }}",
        filtersCleared: "{{ __('Filters cleared') }}",
        noFiltersApplied: "{{ __('No filters to clear') }}",
        userId: "{{ __('User ID') }}",
        userName: "{{ __('User Name') }}",
        uuid: "{{ __('UUID') }}",
        dateFrom: "{{ __('Date From') }}",
        dateTo: "{{ __('Date To') }}",
        newMessage: "{{ __('new message') }}"
    };

    // ==================== CONFIGURATION ====================
    const csrfToken = '{{ csrf_token() }}';
    const adminAppId = {{ $adminAppId ?? 0 }};
    const canSendMessages = {{ $canSendMessages ? 'true' : 'false' }};
    const messagesRoute = '{{ route("admin.chat.messages") }}';
    const storeRoute = '{{ route("admin.chat.store") }}';
    const updateRoute = '{{ route("admin.chat.update") }}';
    const deleteRouteBase = '{{ route("admin.chat.delete", "") }}';
    const pusherKey = '{{ config("broadcasting.connections.pusher.key") }}';
    const pusherCluster = '{{ config("broadcasting.connections.pusher.options.cluster") }}';

    // ==================== STATE VARIABLES ====================
    let currentPage = 1;
    let lastPage = 1;
    let isLoading = false;
    let allMessages = [];
    let initialLoad = true;
    let hasMoreMessages = true;
    let loadedMessageIds = new Set();
    let replyingTo = null;
    let newMessageCount = 0;
    let unreadCount = 0;
    let isUserScrolledUp = false;
    let pusher = null;
    let channel = null;
    let newMessageIds = [];

    // Filter state
    let activeFilters = {
        user_id: null,
        user_name: null,
        uuid: null,
        date_from: null,
        date_to: null
    };

    const SCROLL_THRESHOLD = 100;
    const SCROLL_BOTTOM_THRESHOLD = 150;
    let scrollDebounceTimer = null;

    // ==================== DOM ELEMENTS ====================
    const chatContainer = document.getElementById('chatMessages');
    const filterPanel = document.getElementById('filterPanel');
    const filterToggleIcon = document.getElementById('filterToggleIcon');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const activeFiltersContainer = document.getElementById('activeFilters');
    const filterBadgesContainer = document.getElementById('filterBadges');

    // ==================== FILTER FUNCTIONS ====================
    function toggleFilterPanel() {
        const toggle = document.querySelector('.filter-toggle');
        if (filterPanel.style.display === 'none') {
            filterPanel.style.display = 'block';
            toggle.classList.add('active');
        } else {
            filterPanel.style.display = 'none';
            toggle.classList.remove('active');
        }
    }

    function applyFilters() {
        const userId = document.getElementById('userIdFilter').value.trim();
        const userName = document.getElementById('userNameFilter').value.trim();
        const uuid = document.getElementById('uuidFilter').value.trim();
        const dateFrom = document.getElementById('dateFromFilter').value;
        const dateTo = document.getElementById('dateToFilter').value;

        activeFilters.user_id = userId || null;
        activeFilters.user_name = userName || null;
        activeFilters.uuid = uuid || null;
        activeFilters.date_from = dateFrom || null;
        activeFilters.date_to = dateTo || null;

        const hasFilters = Object.values(activeFilters).some(v => v !== null);
        updateFilterUI(hasFilters);
        resetAndReload();

        if (hasFilters) {
            toastr.info(translations.filtersApplied);
        }
    }

    function clearFilters() {
        const hasFilters = Object.values(activeFilters).some(v => v !== null);
        if (!hasFilters) {
            toastr.info(translations.noFiltersApplied);
            return;
        }

        document.getElementById('userIdFilter').value = '';
        document.getElementById('userNameFilter').value = '';
        document.getElementById('uuidFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';

        activeFilters = { user_id: null, user_name: null, uuid: null, date_from: null, date_to: null };
        updateFilterUI(false);
        resetAndReload();
        toastr.info(translations.filtersCleared);
    }

    function removeFilter(filterKey) {
        activeFilters[filterKey] = null;
        const inputMap = {
            user_id: 'userIdFilter',
            user_name: 'userNameFilter',
            uuid: 'uuidFilter',
            date_from: 'dateFromFilter',
            date_to: 'dateToFilter'
        };
        const inputId = inputMap[filterKey];
        if (inputId) document.getElementById(inputId).value = '';

        const hasFilters = Object.values(activeFilters).some(v => v !== null);
        updateFilterUI(hasFilters);
        resetAndReload();
    }

    function updateFilterUI(hasFilters) {
        clearFilterBtn.style.display = hasFilters ? 'inline-flex' : 'none';
        activeFiltersContainer.style.display = hasFilters ? 'flex' : 'none';
        if (hasFilters) renderFilterBadges();
    }

    function renderFilterBadges() {
        const labelMap = {
            user_id: translations.userId,
            user_name: translations.userName,
            uuid: translations.uuid,
            date_from: translations.dateFrom,
            date_to: translations.dateTo
        };
        let badgesHtml = '';
        Object.entries(activeFilters).forEach(([key, value]) => {
            if (value !== null) {
                badgesHtml += `<span class="filter-badge">${labelMap[key]}: ${value}<i class="fa fa-times badge-remove" onclick="removeFilter('${key}')"></i></span>`;
            }
        });
        filterBadgesContainer.innerHTML = badgesHtml;
    }

    function buildFilterQueryString() {
        const params = new URLSearchParams();
        if (activeFilters.user_id) params.append('user_id', activeFilters.user_id);
        if (activeFilters.user_name) params.append('user_name', activeFilters.user_name);
        if (activeFilters.uuid) params.append('uuid', activeFilters.uuid);
        if (activeFilters.date_from) params.append('date_from', activeFilters.date_from);
        if (activeFilters.date_to) params.append('date_to', activeFilters.date_to);
        return params.toString();
    }

    function hasActiveFilters() {
        return Object.values(activeFilters).some(v => v !== null);
    }

    function messageMatchesFilters(message) {
        if (activeFilters.user_id && message.user_id != activeFilters.user_id) return false;
        if (activeFilters.user_name && !message.user_name?.toLowerCase().includes(activeFilters.user_name.toLowerCase())) return false;
        if (activeFilters.uuid && message.user_uuid && !message.user_uuid.toLowerCase().includes(activeFilters.uuid.toLowerCase())) return false;
        return true;
    }

    // ==================== SCROLL & NOTIFICATION SYSTEM ====================
    function isUserNearBottom() {
        const threshold = SCROLL_BOTTOM_THRESHOLD;
        return (chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight) < threshold;
    }

    function updateScrollState() {
        const nearBottom = isUserNearBottom();
        isUserScrolledUp = !nearBottom;

        const scrollBtn = document.getElementById('scrollToLatestBtn');
        const unreadBadge = document.getElementById('unreadBadge');

        if (isUserScrolledUp) {
            scrollBtn.style.display = 'flex';
            if (unreadCount > 0) {
                unreadBadge.style.display = 'flex';
                unreadBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            }
        } else {
            scrollBtn.style.display = 'none';
            resetNotifications();
        }
    }

    function showNewMessageNotification(message) {
        newMessageCount++;
        unreadCount++;

        const notification = document.getElementById('newMessageNotification');
        const countBadge = document.getElementById('notificationCount');
        const unreadBadge = document.getElementById('unreadBadge');

        countBadge.textContent = newMessageCount > 99 ? '99+' : newMessageCount;
        notification.style.display = 'flex';

        if (document.getElementById('scrollToLatestBtn').style.display === 'flex') {
            unreadBadge.style.display = 'flex';
            unreadBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        }

        clearTimeout(window.notificationTimeout);
        window.notificationTimeout = setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }

    function resetNotifications() {
        newMessageCount = 0;
        unreadCount = 0;

        const notification = document.getElementById('newMessageNotification');
        const unreadBadge = document.getElementById('unreadBadge');

        if (notification) notification.style.display = 'none';
        if (unreadBadge) unreadBadge.style.display = 'none';

        clearTimeout(window.notificationTimeout);

        // Highlight all new messages when user scrolls to bottom naturally
        if (newMessageIds.length > 0) {
            highlightAllNewMessages();
        }
    }

    function highlightMessage(messageId) {
        const msgElement = chatContainer.querySelector(`[data-id="${messageId}"]`);
        if (msgElement) {
            msgElement.classList.add('highlighted');
            setTimeout(() => msgElement.classList.remove('highlighted'), 2000);
        }
    }

    function highlightAllNewMessages() {
        if (newMessageIds.length === 0) return;

        // Highlight each message with a slight delay for visual effect
        newMessageIds.forEach((messageId, index) => {
            setTimeout(() => {
                highlightMessage(messageId);
            }, index * 150); // 150ms delay between each highlight
        });

        // Clear the array after highlighting
        newMessageIds = [];
    }

    function scrollToLatestMessage() {
        chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });

        // Hide notification and scroll button
        const notification = document.getElementById('newMessageNotification');
        if (notification) notification.style.display = 'none';
        document.getElementById('scrollToLatestBtn').style.display = 'none';

        // Reset counts
        newMessageCount = 0;
        unreadCount = 0;
        const unreadBadge = document.getElementById('unreadBadge');
        if (unreadBadge) unreadBadge.style.display = 'none';

        clearTimeout(window.notificationTimeout);

        // Highlight ALL new messages after scroll completes
        setTimeout(() => {
            highlightAllNewMessages();
        }, 500);
    }

    function smoothScrollToBottom() {
        chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });
    }

    // ==================== SCROLL HANDLER ====================
    function handleScroll() {
        if (scrollDebounceTimer) clearTimeout(scrollDebounceTimer);
        scrollDebounceTimer = setTimeout(() => {
            if (chatContainer.scrollTop <= SCROLL_THRESHOLD && !isLoading && hasMoreMessages) {
                loadMoreMessages();
            }
            updateScrollState();
        }, 100);
    }

    chatContainer.addEventListener('scroll', handleScroll);

    // ==================== MESSAGE LOADING ====================
    function loadMessages() {
        if (isLoading) return;
        isLoading = true;
        showTopLoader();

        let url = `${messagesRoute}?page=1`;
        const filterQuery = buildFilterQueryString();
        if (filterQuery) url += `&${filterQuery}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    allMessages = [];
                    loadedMessageIds.clear();

                    (data.messages || []).forEach(msg => {
                        if (!loadedMessageIds.has(msg.id)) {
                            loadedMessageIds.add(msg.id);
                            allMessages.push(msg);
                        }
                    });

                    lastPage = data.last_page || 1;
                    currentPage = 1;
                    hasMoreMessages = currentPage < lastPage;

                    renderMessages();
                    hideTopLoader();

                    if (initialLoad) {
                        setTimeout(() => {
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                            initialLoad = false;
                            updateScrollState();
                        }, 100);
                    }
                }
                isLoading = false;
            })
            .catch(err => {
                console.error(err);
                toastr.error(translations.failedToLoadMessages);
                hideTopLoader();
                isLoading = false;
            });
    }

    function loadMoreMessages() {
        if (isLoading || !hasMoreMessages || currentPage >= lastPage) return;

        const nextPage = currentPage + 1;
        isLoading = true;
        showTopLoader();

        const anchorElement = chatContainer.querySelector('.message-wrapper');

        let url = `${messagesRoute}?page=${nextPage}`;
        const filterQuery = buildFilterQueryString();
        if (filterQuery) url += `&${filterQuery}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const olderMessages = data.messages || [];
                    const sortedOlderMessages = [...olderMessages].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

                    let htmlToInsert = '';
                    sortedOlderMessages.forEach(msg => {
                        if (!loadedMessageIds.has(msg.id)) {
                            loadedMessageIds.add(msg.id);
                            allMessages.push(msg);
                            htmlToInsert += createMessageElement(msg);
                        }
                    });

                    if (anchorElement && htmlToInsert) {
                        anchorElement.insertAdjacentHTML('beforebegin', htmlToInsert);
                        anchorElement.scrollIntoView({ block: 'start', behavior: 'instant' });
                        chatContainer.scrollTop -= 220;
                    }

                    currentPage = nextPage;
                    hasMoreMessages = currentPage < lastPage;

                    const scrollUpIndicator = document.getElementById('scrollUpIndicator');
                    if (!hasMoreMessages && scrollUpIndicator) scrollUpIndicator.remove();

                    hideTopLoader();
                }
                isLoading = false;
            })
            .catch(err => {
                console.error(err);
                toastr.error(translations.failedToLoadMoreMessages);
                hideTopLoader();
                isLoading = false;
            });
    }

    // ==================== LOADER FUNCTIONS ====================
    function showTopLoader() {
        let loader = document.getElementById('topLoader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'topLoader';
            loader.className = 'top-loading-indicator';
            loader.innerHTML = `<div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i> <span>${translations.loadingMessages}</span></div>`;
            chatContainer.prepend(loader);
        }
        loader.style.display = 'flex';
    }

    function hideTopLoader() {
        const loader = document.getElementById('topLoader');
        if (loader) loader.style.display = 'none';
    }

    // ==================== RESET & REFRESH ====================
    function resetAndReload() {
        currentPage = 1;
        lastPage = 1;
        allMessages = [];
        loadedMessageIds.clear();
        initialLoad = true;
        hasMoreMessages = true;
        isLoading = false;
        chatContainer.innerHTML = '';
        resetNotifications();
        loadMessages();
    }

    function refreshMessages() {
        resetAndReload();
        toastr.info(translations.refreshingMessages);
    }

    // ==================== RENDER MESSAGES ====================
    function renderMessages() {
        chatContainer.querySelectorAll('.message-wrapper, .scroll-up-indicator').forEach(el => el.remove());

        const sorted = [...allMessages].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

        if (hasMoreMessages) {
            const indicator = document.createElement('div');
            indicator.className = 'scroll-up-indicator';
            indicator.id = 'scrollUpIndicator';
            indicator.innerHTML = `<i class="fa fa-arrow-up"></i> <span>${translations.scrollUpForOlderMessages}</span>`;
            const loader = document.getElementById('topLoader');
            if (loader) loader.after(indicator);
            else chatContainer.prepend(indicator);
        }

        sorted.forEach(msg => {
            chatContainer.insertAdjacentHTML('beforeend', createMessageElement(msg));
        });
    }

    function createMessageElement(message) {
        const isAdmin = message.user_id == adminAppId;
        const messageClass = isAdmin ? 'sent-message' : 'received-message';
        const time = new Date(message.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const avatarUrl = message.user_avatar || '{{ asset("images/businessman-icon.jpg") }}';
        const userName = message.user_name || translations.user;
        const userInitial = userName.charAt(0).toUpperCase();

        let messageContent = '';
        let isShareRoom = false;
        let roomImage = '';
        let shareRoomText = '';

        if (message.text && message.text.startsWith('share_room:')) {
            isShareRoom = true;
            const parts = message.text.split(':');
            const roomId = parts[3] || '';

            const locale = '{{ app()->getLocale() }}';
            const translations_share = {
                'ar': 'مرحبا تعال وانضم الي هذه الغرفه معي انها ممتعه حقا',
                'en': 'Hello, come and join me in this room, it\'s really fun',
                'tr': 'Merhaba, gel ve benimle bu odaya katıl, gerçekten çok eğlenceli',
                'hi': 'नमस्ते, आओ और मेरे साथ इस कमरे में शामिल हो जाओ, यह वाकई बहुत मज़ेदार है'
            };
            shareRoomText = translations_share[locale] || translations_share['en'];

            if (roomId) {
                roomImage = 'loading';
                fetch(`/admin/rooms/${roomId}/image`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.image) {
                            const imgElement = document.querySelector(`[data-id="${message.id}"] .room-share-image`);
                            if (imgElement) {
                                imgElement.src = data.image;
                                imgElement.style.display = 'block';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching room image:', error);
                    });
            }
        }

        let replyHtml = '';
        if (message.parent) {
            replyHtml = `
                <div class="replied-message" onclick="scrollToMessage(${message.parent.id})">
                    <div class="replied-user">${escapeHtml(message.parent.user_name)}</div>
                    <div class="replied-text">${escapeHtml(message.parent.text)}</div>
                </div>`;
        }

        const actionMenuHtml = `
            <div class="message-actions">
                <button class="more-btn" onclick="toggleActionMenu(event, ${message.id})">
                    <i class="fa fa-ellipsis-v"></i>
                </button>
                <div class="action-menu" id="action-menu-${message.id}">
                    <button class="action-menu-item reply-item" onclick="setReply(${message.id}, '${escapeHtml(userName)}', '${escapeHtml(message.text)}')">
                        <i class="fa fa-reply"></i> {{ __('Reply') }}
        </button>
        <button class="action-menu-item edit-item" onclick="editMessage(${message.id}, '${escapeHtml(message.text)}')">
                        <i class="fa fa-edit"></i> {{ __('Edit') }}
        </button>
        <button class="action-menu-item delete-item" onclick="deleteMessage(${message.id})">
                        <i class="fa fa-trash"></i> {{ __('Delete') }}
        </button>
    </div>
</div>`;

        if (isShareRoom) {
            messageContent = `
                <div class="share-room-content">
                    <img src="{{ asset('images/loading.gif') }}" alt="Room" class="room-share-image" style="max-width: 50%; border-radius: 8px; margin-bottom: 8px; display: ${roomImage === 'loading' ? 'block' : 'none'};">
                    <p class="share-room-text">${shareRoomText}</p>
                </div>`;
        } else {
            messageContent = `<p>${escapeHtml(message.text)}</p>`;
        }

        if (isAdmin) {
            return `
                <div class="message-wrapper ${messageClass}" data-id="${message.id}">
                    ${actionMenuHtml}
                    <div class="message-content">
                        <div class="message-bubble">
                            ${replyHtml}
                            ${messageContent}
                            <div class="message-footer">
                                <span class="message-time">${time}</span>
                                <i class="fa fa-check-double"></i>
                            </div>
                        </div>
                        <div class="user-avatar sender">
                            <img src="${avatarUrl}" alt="${userName}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="avatar-fallback" style="display:none;">${userInitial}</span>
                        </div>
                    </div>
                </div>`;
        } else {
            return `
                <div class="message-wrapper ${messageClass}" data-id="${message.id}">
                    <div class="message-content">
                        <div class="user-avatar">
                            <img src="${avatarUrl}" alt="${userName}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="avatar-fallback" style="display:none;">${userInitial}</span>
                        </div>
                        <div class="message-bubble">
                            ${replyHtml}
                            <p class="user-name-label">${userName}</p>
                            ${messageContent}
                            <span class="message-time">${time}</span>
                        </div>
                    </div>
                    ${actionMenuHtml}
                </div>`;
        }
    }

    // ==================== ACTION MENU ====================
    function toggleActionMenu(event, messageId) {
        event.stopPropagation();
        document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show', 'open-up', 'open-down'));

        const menu = document.getElementById(`action-menu-${messageId}`);
        if (!menu) return;

        const wrapper = menu.closest('.message-wrapper');
        const containerRect = chatContainer.getBoundingClientRect();
        const msgRect = wrapper.getBoundingClientRect();

        if (containerRect.bottom - msgRect.bottom < 150) {
            menu.classList.add('open-up');
        } else {
            menu.classList.add('open-down');
        }
        menu.classList.add('show');
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('.message-actions')) {
            document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show', 'open-up', 'open-down'));
        }
    });

    // ==================== REPLY FUNCTIONS ====================
    function setReply(messageId, userName, messageText) {
        replyingTo = messageId;
        document.getElementById('replyToId').value = messageId;
        document.getElementById('replyToName').textContent = `${translations.replyingTo} ${userName}`;
        document.getElementById('replyToText').textContent = messageText;
        document.getElementById('replyPreview').style.display = 'flex';
        document.getElementById('messageInput').focus();
        document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show', 'open-up', 'open-down'));
    }

    function cancelReply() {
        replyingTo = null;
        document.getElementById('replyToId').value = '';
        document.getElementById('replyPreview').style.display = 'none';
    }

    function scrollToMessage(messageId) {
        const el = document.querySelector(`.message-wrapper[data-id="${messageId}"]`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('highlighted');
            setTimeout(() => el.classList.remove('highlighted'), 2000);
        } else {
            toastr.info(translations.messageNotLoaded);
        }
    }

    // ==================== UTILITY FUNCTIONS ====================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    // ==================== SEND MESSAGE ====================
    function sendMessage() {
        if (!canSendMessages) {
            toastr.error("{{ __('You cannot send messages. Your account is not linked to an app user.') }}");
            return;
        }

        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        if (!text) {
            toastr.warning(translations.pleaseEnterMessage);
            return;
        }

        const payload = { text };
        if (replyingTo) payload.parent_id = replyingTo;

        fetch(storeRoute, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    cancelReply();

                    if (!loadedMessageIds.has(data.message.id)) {
                        allMessages.push(data.message);
                        loadedMessageIds.add(data.message.id);
                        const messageHtml = createMessageElement(data.message);
                        chatContainer.insertAdjacentHTML('beforeend', messageHtml);
                    }

                    smoothScrollToBottom();
                    resetNotifications();
                    toastr.success(translations.messageSentSuccessfully);
                } else {
                    toastr.error(data.error || translations.failedToSendMessage);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error(translations.failedToSendMessage);
            });
    }

    // ==================== EDIT MESSAGE ====================
    function editMessage(id, text) {
        document.getElementById('editMessageId').value = id;
        document.getElementById('editMessageText').value = text.replace(/\\'/g, "'").replace(/\\"/g, '"');
        document.getElementById('editModal').classList.add('active');
        document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show', 'open-up', 'open-down'));
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    function saveEdit() {
        const id = document.getElementById('editMessageId').value;
        const text = document.getElementById('editMessageText').value.trim();

        if (!text) {
            toastr.warning(translations.messageCannotBeEmpty);
            return;
        }

        fetch(updateRoute, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ id, text })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    const idx = allMessages.findIndex(m => m.id == id);
                    if (idx !== -1) {
                        allMessages[idx].text = text;
                        renderMessages();
                    }
                    toastr.success(translations.messageUpdatedSuccessfully);
                } else {
                    toastr.error(data.error || translations.failedToUpdateMessage);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error(translations.failedToUpdateMessage);
            });
    }

    // ==================== DELETE MESSAGE ====================
    function deleteMessage(id) {
        document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show', 'open-up', 'open-down'));
        if (!confirm(translations.confirmDeleteMessage)) return;

        fetch(`${deleteRouteBase}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    allMessages = allMessages.filter(m => m.id != id);
                    loadedMessageIds.delete(parseInt(id));
                    renderMessages();
                    toastr.success(translations.messageDeletedSuccessfully);
                } else {
                    toastr.error(data.error || translations.failedToDeleteMessage);
                }
            })
            .catch(err => {
                console.error(err);
                toastr.error(translations.failedToDeleteMessage);
            });
    }

    // ==================== PUSHER REAL-TIME MESSAGING ====================
    function initializePusher() {
        if (!pusherKey) {
            console.warn('Pusher key not configured');
            return;
        }

        pusher = new Pusher(pusherKey, { cluster: pusherCluster, encrypted: true });
        channel = pusher.subscribe('group-chat');

        channel.bind('getGroupChatBloc', function(data) {
            handleIncomingPusherMessage(data);
        });

        pusher.connection.bind('connected', function() {
            console.log('Pusher connected');
            updateConnectionStatus(true);
        });

        pusher.connection.bind('disconnected', function() {
            console.log('Pusher disconnected');
            updateConnectionStatus(false);
        });
    }

    function handleIncomingPusherMessage(pusherData) {
        console.log('Received Pusher message:', pusherData);

        let avatarUrl = '{{ asset("images/businessman-icon.jpg") }}';
        if (pusherData.profile?.image) {
            if (pusherData.profile.image.startsWith('http')) {
                avatarUrl = pusherData.profile.image;
            } else {
                avatarUrl = '{{ url("storage") }}/' + pusherData.profile.image;
            }
        }

        const message = {
            id: pusherData.message_id,
            text: pusherData.group_message || '',
            user_id: pusherData.id,
            user_name: pusherData.name || 'User',
            user_uuid: pusherData.uuid,
            user_avatar: avatarUrl,
            image: pusherData.group_image || null,
            parent_id: pusherData.replay?.id || null,
            parent: pusherData.replay ? {
                id: pusherData.replay.id,
                text: pusherData.replay.text || pusherData.replay.group_message || '',
                user_name: pusherData.replay.name || pusherData.replay.user_name || 'User'
            } : null,
            created_at: pusherData.created_at,
            updated_at: pusherData.created_at
        };

        if (loadedMessageIds.has(message.id)) {
            console.log('Message already exists, skipping:', message.id);
            return;
        }

        if (hasActiveFilters() && !messageMatchesFilters(message)) {
            console.log('Message does not match active filters');
            return;
        }

        loadedMessageIds.add(message.id);
        allMessages.push(message);
        appendNewMessage(message);
    }

    function appendNewMessage(message) {
        const messageHtml = createMessageElement(message);
        const wasNearBottom = isUserNearBottom();

        chatContainer.insertAdjacentHTML('beforeend', messageHtml);

        const isOwnMessage = message.user_id == adminAppId;

        if (wasNearBottom) {
            smoothScrollToBottom();
            // Highlight just this message since user is at bottom
            highlightMessage(message.id);
        } else if (!isOwnMessage) {
            // User is scrolled up, track this message ID for later highlighting
            newMessageIds.push(message.id);
            showNewMessageNotification(message);
        }

        updateScrollState();
    }

    function updateConnectionStatus(connected) {
        const statusElement = document.querySelector('.status');
        if (statusElement) {
            statusElement.innerHTML = `
                <span class="status-dot" style="background-color: ${connected ? '#4CAF50' : '#f44336'};"></span>
                ${connected ? '{{ __("Connected") }}' : '{{ __("Disconnected") }}'}
            `;
        }
    }

    // ==================== EVENT LISTENERS ====================
    document.getElementById('messageInput').addEventListener('keypress', e => {
        if (e.key === 'Enter') sendMessage();
    });

    document.getElementById('editModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeEditModal();
    });

    document.getElementById('userIdFilter').addEventListener('keypress', e => {
        if (e.key === 'Enter') applyFilters();
    });

    document.getElementById('userNameFilter').addEventListener('keypress', e => {
        if (e.key === 'Enter') applyFilters();
    });

    document.getElementById('uuidFilter').addEventListener('keypress', e => {
        if (e.key === 'Enter') applyFilters();
    });

    window.addEventListener('beforeunload', function() {
        if (pusher) pusher.disconnect();
    });

    // ==================== INITIALIZE ====================
    loadMessages();
    initializePusher();

    setTimeout(() => {
        updateScrollState();
    }, 1000);
</script>
