(function($) {
    'use strict';

    const cfg = window.MomentViewerConfig || {};
    const routes = cfg.routes || {};
    const texts = cfg.texts || {};
    const adminUserUrl = cfg.adminUserUrl || '';
    const defaultAvatar = cfg.defaultAvatar || '';
    const storageUrl = cfg.storageUrl || '';
    const csrf = $('meta[name="csrf-token"]').attr('content') || cfg.csrf || '';

    let currentPage = 1;
    let currentSort = 'random'; // الترتيب الافتراضي عشوائي
    let totalPages = 1;
    let searchQuery = '';
    let userIdFilter = '';
    let isLoading = false;
    let allMomentsLoaded = []; // تخزين جميع الـ Moments المحملة
    let currentlyVisibleCount = 0; // عدد العناصر المرئية حالياً
    const INITIAL_LOAD = 10; // تحميل أول 10 عناصر
    const LOAD_MORE_COUNT = 10; // تحميل 10 عناصر في كل دفعة
    const TRIGGER_THRESHOLD = 3; // التحميل عند الوصول لآخر 3 عناصر
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    // ترجمات See More لجميع اللغات المدعومة
    const seeMoreTexts = {
        ar: { more: 'عرض المزيد', less: 'عرض أقل' },
        en: { more: 'See More', less: 'See Less' },
        fr: { more: 'Voir Plus', less: 'Voir Moins' },
        es: { more: 'Ver Más', less: 'Ver Menos' },
        hi: { more: 'और देखें', less: 'कम देखें' },
        tr: { more: 'Devamını Gör', less: 'Daha Az Gör' },
        id: { more: 'Lihat Selengkapnya', less: 'Lihat Lebih Sedikit' }
    };

    $(document).ready(function() {
        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrf
            }
        });

        // اكتشاف اللغة وتطبيق الاتجاه
        detectAndApplyDirection();

        loadMoments();
        setupEventHandlers();
        setupScrollHandling();
        initUsersSidebar();
    });

    function detectAndApplyDirection() {
        // تحقق من عدة مصادر لتحديد اللغة
        const htmlLang = document.documentElement.lang || '';
        const htmlDir = document.documentElement.getAttribute('dir') || '';
        const bodyDir = document.body.getAttribute('dir') || '';
        const browserLang = navigator.language || navigator.userLanguage || 'en';

        // إذا كان dir محدد بالفعل، استخدمه
        if (htmlDir === 'rtl' || bodyDir === 'rtl') {
            document.documentElement.setAttribute('dir', 'rtl');
            document.body.setAttribute('dir', 'rtl');
            $('html').addClass('rtl');
            return;
        }

        // تحقق من اللغة
        const isRTL = htmlLang.startsWith('ar') ||
            htmlLang.startsWith('he') ||
            htmlLang.startsWith('fa') ||
            browserLang.startsWith('ar') ||
            browserLang.startsWith('he') ||
            browserLang.startsWith('fa') ||
            // تحقق من محتوى النصوص في الصفحة
            checkPageTextDirection();

        if (isRTL) {
            document.documentElement.setAttribute('dir', 'rtl');
            document.body.setAttribute('dir', 'rtl');
            $('html').addClass('rtl');
            $('.viewer-container').attr('dir', 'rtl');
        } else {
            document.documentElement.setAttribute('dir', 'ltr');
            document.body.setAttribute('dir', 'ltr');
            $('html').removeClass('rtl');
            $('.viewer-container').attr('dir', 'ltr');
        }
    }

    function checkPageTextDirection() {
        // تحقق من نصوص الأزرار والعناوين
        const sampleTexts = [
            cfg.texts?.comments || '',
            cfg.texts?.likes || '',
            cfg.texts?.editDesc || '',
            cfg.texts?.deleteMoment || ''
        ].join(' ');

        const rtlChars = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF\u0590-\u05FF]/;
        return rtlChars.test(sampleTexts);
    }

    function setupEventHandlers() {
        // فلتر بالمعرف
        let filterTimeout;
        $('#userIdFilter').on('input', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() {
                userIdFilter = $('#userIdFilter').val().trim();
                currentPage = 1;
                loadMoments();
            }, 500);
        });

        // بحث بالاسم أو UUID
        let searchTimeout;
        $('#userSearch').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchQuery = $('#userSearch').val().trim();
                currentPage = 1;
                loadMoments();
            }, 500);
        });

        // الترتيب
        $('#sortSelect').on('change', function() {
            currentSort = $(this).val();
            currentPage = 1;
            loadMoments();
        });

        // تحديث
        $('#refreshBtn').on('click', function() {
            const icon = $(this).find('i');
            icon.addClass('fa-spin');
            currentPage = 1;

            // مسح الكاش وإعادة التحميل
            allMomentsLoaded = [];
            currentlyVisibleCount = 0;

            loadMoments().always(() => {
                setTimeout(() => icon.removeClass('fa-spin'), 400);
            });
        });

        // زر العودة للأعلى
        $('#scrollTopBtn').on('click', scrollToTop);

        // Event delegation for post actions (works with dynamically created elements)
        $(document).on('click', '[data-action="toggle-menu"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).data('id');
            var menu = $('#menu-' + id);
            $('.post-dropdown').not(menu).hide();
            menu.toggle();
        });

        $(document).on('click', '[data-action="delete-moment"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).data('id');
            $('.post-dropdown').hide();
            deleteMoment(id, e);
        });

        $(document).on('click', '[data-action="edit-moment"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).data('id');
            $('.post-dropdown').hide();
            editMoment(id, e);
        });

        // إغلاق القوائم المنسدلة عند النقر خارجها
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.post-menu').length) {
                $('.post-dropdown').hide();
                $('.dropdown-menu').removeClass('show');
            }
        });
    }

    function setupScrollHandling() {
        let ticking = false;
        let lastScrollTime = 0;
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const scrollDelay = isMobile ? 150 : 100;

        const scrollElements = [
            $('.content-wrapper'),
            $(window),
            $(document)
        ];

        scrollElements.forEach(el => {
            if (el.length) {
                el.on('scroll', function() {
                    const now = Date.now();
                    if (now - lastScrollTime < scrollDelay) return;

                    if (!ticking) {
                        window.requestAnimationFrame(function() {
                            handleInfiniteScroll();
                            ticking = false;
                            lastScrollTime = now;
                        });
                        ticking = true;
                    }
                });
            }
        });

        // Intersection Observer لمراقبة العناصر المرئية
        setupIntersectionObserver();
    }

    function setupIntersectionObserver() {
        const options = {
            root: null,
            rootMargin: '300px', // بدء التحميل قبل 300px من النهاية
            threshold: 0.1
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const momentCard = $(entry.target);
                    const index = momentCard.index();
                    const totalVisible = $('.moment-post:visible').length;

                    // إذا وصل المستخدم لآخر 3-4 عناصر، حمل المزيد
                    if (totalVisible - index <= TRIGGER_THRESHOLD && !isLoading) {
                        loadMoreMomentsFromCache();
                    }
                }
            });
        }, options);

        // مراقبة العناصر المرئية
        window.momentObserver = observer;
    }

    function observeVisibleMoments() {
        if (window.momentObserver) {
            // مراقبة آخر 5 عناصر فقط
            const moments = $('.moment-post:visible').slice(-5);
            moments.each(function() {
                window.momentObserver.observe(this);
            });
        }
    }

    function handleInfiniteScroll() {
        const scrollEl = $('.content-wrapper').length ? $('.content-wrapper') : $(window);
        const scrollTop = scrollEl.scrollTop();
        const scrollHeight = scrollEl[0]?.scrollHeight || $(document).height();
        const clientHeight = scrollEl.height();

        // إظهار/إخفاء زر العودة للأعلى
        if (scrollTop > 300) {
            $('#scrollTopBtn').addClass('visible');
        } else {
            $('#scrollTopBtn').removeClass('visible');
        }

        // التحقق من القرب من النهاية
        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

        // إذا كان المستخدم قريباً من النهاية (أقل من 500px)
        if (distanceFromBottom < 500 && !isLoading) {
            loadMoreMomentsFromCache();
        }
    }

    function scrollToTop() {
        const scrollEl = $('.content-wrapper').length ? $('.content-wrapper') : $('html, body');
        scrollEl.animate({ scrollTop: 0 }, 300);
    }

    function loadMoments(append = false) {
        if (isLoading) return $.Deferred().resolve();

        const feed = $('#momentsFeed');
        if (!append) {
            // إعادة تعيين كل شيء
            allMomentsLoaded = [];
            currentlyVisibleCount = 0;
            currentPage = 1;
            feed.html('<div class="loading-container"><div class="spinner"></div></div>');
        }

        isLoading = true;
        const loadMoreContainer = $('#loadMoreContainer');
        loadMoreContainer.hide();

        return $.ajax({
            url: routes.moments,
            type: 'GET',
            data: {
                sort: currentSort,
                page: currentPage,
                per_page: INITIAL_LOAD,
                search: searchQuery,
                user_id: userIdFilter
            },
            success: function(response) {
                if (response.success && response.data) {
                    // تخزين البيانات المحملة بدون تكرار
                    if (append) {
                        // الحصول على IDs الموجودة لتجنب التكرار
                        const existingIds = new Set(allMomentsLoaded.map(m => m.id));
                        const newMoments = response.data.filter(m => !existingIds.has(m.id));
                        allMomentsLoaded = allMomentsLoaded.concat(newMoments);
                    } else {
                        allMomentsLoaded = response.data;
                    }

                    // عرض العناصر
                    renderInitialMoments(append);

                    if (response.pagination) {
                        totalPages = response.pagination.last_page;
                    }
                } else {
                    showError(texts.noData || 'No data returned');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || xhr.statusText || 'Unknown error';
                showError((texts.failedLoad || 'Failed to load moments') + ': ' + message);
            },
            complete: function() {
                isLoading = false;
            }
        });
    }

    function renderInitialMoments(append = false) {
        const feed = $('#momentsFeed');

        if (!allMomentsLoaded || allMomentsLoaded.length === 0) {
            if (!append) {
                feed.html(`
                    <div class="empty-state">
                        <i class="fas fa-photo-video"></i>
                        <h3>${texts.noMoments || 'No Moments Found'}</h3>
                        <p>${texts.noMomentsMsg || 'There are no moments to display'}</p>
                        ${(searchQuery || userIdFilter) ? `
                            <button class="refresh-btn" onclick="clearFilters()" style="margin-top: 20px;">
                                <i class="fas fa-times"></i> ${texts.clearSearch || 'Clear Filters'}
                            </button>
                        ` : ''}
                    </div>
                `);
            }
            return;
        }

        let momentsToShow;
        if (append) {
            // عند الإضافة، أضف فقط العناصر الجديدة
            momentsToShow = allMomentsLoaded.slice(currentlyVisibleCount);
        } else {
            // عند التحميل الأول، اعرض أول INITIAL_LOAD عناصر
            momentsToShow = allMomentsLoaded.slice(0, INITIAL_LOAD);
        }

        if (momentsToShow.length === 0) return;

        let html = '';
        momentsToShow.forEach(moment => {
            html += renderMomentCard(moment);
        });

        if (append) {
            feed.append(html);
        } else {
            feed.html(html);
        }

        currentlyVisibleCount += momentsToShow.length;

        // مراقبة العناصر المرئية
        setTimeout(() => {
            observeVisibleMoments();
        }, 100);
    }

    function loadMoreMomentsFromCache() {
        if (isLoading) return;

        // إذا كانت جميع العناصر المحملة معروضة بالفعل
        if (currentlyVisibleCount >= allMomentsLoaded.length) {
            // جلب صفحة جديدة من السيرفر
            if (currentPage < totalPages) {
                currentPage++;
                loadMoments(true);
            }
            return;
        }

        isLoading = true;

        // عرض LOAD_MORE_COUNT عنصر إضافي من الكاش
        const nextBatch = allMomentsLoaded.slice(
            currentlyVisibleCount,
            currentlyVisibleCount + LOAD_MORE_COUNT
        );

        if (nextBatch.length > 0) {
            const feed = $('#momentsFeed');
            let html = '';

            nextBatch.forEach(moment => {
                html += renderMomentCard(moment);
            });

            feed.append(html);
            currentlyVisibleCount += nextBatch.length;

            // مراقبة العناصر الجديدة
            setTimeout(() => {
                observeVisibleMoments();
            }, 100);
        }

        setTimeout(() => {
            isLoading = false;
        }, 300);
    }

    function renderMomentCard(moment) {
        if ($(`.moment-post[data-moment-id="${moment.id}"]`).length > 0) {
            console.log(`Moment ${moment.id} already exists in DOM, skipping...`);
            return '';
        }

        const user = moment.user || {};
        const avatar = getUserAvatar(user);
        const userName = user.name || 'Unknown User';
        const userUuid = user.uuid || 'N/A';
        const userId = moment.user_id;
        const userUrl = adminUserUrl + userId;

        const images = moment.images || [];
        const allMedia = images.length > 0 ? images : (moment.img ? [{image: moment.img}] : []);
        const validMedia = allMedia.filter(media => media && media.image && media.image.trim() !== '');

        const createdAt = new Date(moment.created_at);
        const timeAgo = getTimeAgo(createdAt);
        const fullDateTime = formatDateTime(createdAt);

        return `
            <div class="moment-post" data-moment-id="${moment.id}">
                <div class="post-header">
                    <img src="${avatar}" alt="${userName}" class="user-avatar" loading="lazy"
                         onclick="window.open('${userUrl}', '_blank')">
                    <div class="user-info">
                        <div class="user-name" onclick="window.open('${userUrl}', '_blank')">
                            ${escapeHtml(userName)}
                        </div>
                        <div class="user-meta">
                            <span class="user-uuid">ID: ${userId} • UUID: ${userUuid}</span>
                            <span class="post-time" title="${fullDateTime}"> • ${timeAgo}</span>
                        </div>
                    </div>
                    <div class="post-menu">
                        <a href="javascript:void(0)" class="menu-btn" data-action="toggle-menu" data-id="${moment.id}">
                            <i class="fas fa-ellipsis-h"></i>
                        </a>
                        <div class="post-dropdown" id="menu-${moment.id}" style="display:none;">
                            <a href="javascript:void(0)" class="post-dropdown-item" data-action="edit-moment" data-id="${moment.id}">
                                <i class="fas fa-edit"></i>
                                <span>${texts.editDesc || 'Edit Description'}</span>
                            </a>
                            <a href="javascript:void(0)" class="post-dropdown-item delete-item" data-action="delete-moment" data-id="${moment.id}">
                                <i class="fas fa-trash"></i>
                                <span>${texts.deleteMoment || 'Delete Moment'}</span>
                            </a>
                        </div>
                    </div>
                </div>

                ${moment.description ? renderDescription(moment.id, moment.description) : ''}

                ${validMedia && validMedia.length > 0 ? renderMedia(moment.id, validMedia) : ''}

                <div class="post-stats">
                    <div class="stats-left">
                        <div class="stat-item" onclick="toggleLikes(${moment.id}, event)">
                            <i class="fas fa-heart"></i>
                            <span>${moment.likes_count || 0}</span>
                        </div>
                        <div class="stat-item" onclick="toggleGifts(${moment.id}, event)">
                            <i class="fas fa-gift"></i>
                            <span>${moment.gifts_count || 0}</span>
                        </div>
                    </div>
                    <div class="stats-right">
                        <div class="stat-item" onclick="toggleComments(${moment.id}, event)">
                            <i class="far fa-comment"></i>
                            <span>${moment.comments_count || 0}</span>
                        </div>
                    </div>
                </div>



                <div class="likes-section" id="likes-${moment.id}"></div>
                <div class="gifts-section" id="gifts-${moment.id}"></div>
                <div class="comments-section" id="comments-${moment.id}"></div>
            </div>
        `;
    }

    function renderDescription(momentId, description) {
        if (!description) return '';

        const textDir = detectTextDirection(description);
        const textAlign = textDir === 'rtl' ? 'right' : 'left';
        const escapedDesc = escapeHtml(description);

        const estimatedLines = Math.ceil(escapedDesc.length / 60);
        const needsSeeMore = estimatedLines > 2;

        let lang = 'en';
        if (textDir === 'rtl') {
            lang = 'ar';
        } else {
            const htmlLang = document.documentElement.lang || '';
            if (htmlLang.startsWith('fr')) lang = 'fr';
            else if (htmlLang.startsWith('es')) lang = 'es';
        }

        const seeMoreText = seeMoreTexts[lang] || seeMoreTexts.en;

        return `<div class="post-content">
                <div class="post-description ${needsSeeMore ? 'collapsible' : ''}"
                     id="desc-${momentId}"
                     dir="${textDir}"
                     style="text-align: ${textAlign};"
                     data-full-text="${escapedDesc}"
                     data-collapsed="true"><span class="description-text">${escapedDesc}</span></div>${needsSeeMore ? `
                <button class="see-more-btn"
                        onclick="toggleDescription(${momentId}, event)"
                        data-lang="${lang}">${seeMoreText.more}</button>` : ''}
            </div>`;
    }

    function renderMedia(momentId, allMedia) {
        if (!allMedia || allMedia.length === 0) return '';

        // فلترة الوسائط لإزالة العناصر الفارغة
        const validMedia = allMedia.filter(media => media && media.image && media.image.trim() !== '');

        if (validMedia.length === 0) return '';

        const count = validMedia.length;
        let gridClass = 'media-grid';
        if (count === 1) gridClass += ' grid-1';
        else if (count === 2) gridClass += ' grid-2';
        else if (count === 3) gridClass += ' grid-3';
        else if (count === 4) gridClass += ' grid-4';
        else gridClass += ' grid-5-plus';

        let mediaHtml = `<div class="post-media ${gridClass}" data-moment-id="${momentId}" data-media='${JSON.stringify(validMedia).replace(/'/g, "&apos;")}'>`;

        const maxDisplay = count > 5 ? 5 : count;
        validMedia.slice(0, maxDisplay).forEach((media, index) => {
            const mediaPath = getImagePath(media.image);
            const isVideo = mediaPath && (mediaPath.includes('.mp4') || mediaPath.includes('.mov') || mediaPath.includes('.webm'));

            mediaHtml += `
                <div class="media-item" data-index="${index}" onclick="openMediaLightbox(${momentId}, ${index}, event)">
                    ${isVideo ?
                `<video src="${mediaPath}" preload="metadata"></video>` :
                `<img src="${mediaPath}" alt="Moment" loading="lazy"
                             onerror="this.style.display='none'">`
            }
                    ${index === 4 && count > 5 ? `<div class="media-overlay">+${count - 5}</div>` : ''}
                </div>
            `;
        });

        mediaHtml += '</div>';
        return mediaHtml;
    }

    function showError(message) {
        $('#momentsFeed').html(`
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle" style="color: #e4405f;"></i>
                <h3>${texts.error || 'Error'}</h3>
                <p>${message}</p>
                <button class="refresh-btn" onclick="retryLoad()" style="margin-top: 20px;">
                    <i class="fas fa-sync-alt"></i> ${texts.tryAgain || 'Try Again'}
                </button>
            </div>
        `);
    }

    // Global Functions
    window.toggleDescription = function(momentId, event) {
        if (event) event.stopPropagation();

        const descElement = $(`#desc-${momentId}`);
        const btn = $(event.target);
        const lang = btn.data('lang') || 'en';
        const seeMoreText = seeMoreTexts[lang] || seeMoreTexts.en;
        const isCollapsed = descElement.data('collapsed');

        if (isCollapsed) {
            // عرض النص الكامل
            descElement.removeClass('collapsible').data('collapsed', false);
            btn.text(seeMoreText.less);
        } else {
            // إخفاء النص
            descElement.addClass('collapsible').data('collapsed', true);
            btn.text(seeMoreText.more);
        }
    };

    window.toggleMenu = function(momentId, event) {
        if (event) event.stopPropagation();
        const menu = $(`#menu-${momentId}`);
        $('.dropdown-menu').not(menu).removeClass('show');
        menu.toggleClass('show');
    };

    window.openMediaLightbox = function(momentId, startIndex, event) {
        if (event) event.stopPropagation();

        // الحصول على البيانات من DOM
        const postMedia = $(`.post-media[data-moment-id="${momentId}"]`);
        if (postMedia.length === 0) return;

        const mediaData = postMedia.attr('data-media');
        if (!mediaData) return;

        let validMedia;
        try {
            validMedia = JSON.parse(mediaData);
        } catch (e) {
            console.error('Error parsing media data:', e);
            return;
        }

        if (!validMedia || validMedia.length === 0) return;

        // إنشاء lightbox modal
        let lightbox = $('#mediaLightbox');
        if (lightbox.length === 0) {
            $('body').append(`
                <div id="mediaLightbox" class="media-lightbox">
                    <div class="lightbox-overlay" onclick="closeMediaLightbox()"></div>
                    <div class="lightbox-content">
                        <button class="lightbox-close" onclick="closeMediaLightbox()">
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="lightbox-nav lightbox-prev" onclick="navigateLightbox(-1, event)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="lightbox-nav lightbox-next" onclick="navigateLightbox(1, event)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="lightbox-media"></div>
                    </div>
                </div>
            `);
            lightbox = $('#mediaLightbox');
        }

        // تخزين البيانات في lightbox
        lightbox.data('media', validMedia);
        lightbox.data('currentIndex', startIndex);

        // عرض الصورة
        updateLightboxMedia(startIndex);

        // إظهار lightbox
        lightbox.addClass('active');
        $('body').addClass('modal-open');
    };

    window.closeMediaLightbox = function() {
        const lightbox = $('#mediaLightbox');
        lightbox.removeClass('active');
        $('body').removeClass('modal-open');
    };

    window.navigateLightbox = function(direction, event) {
        if (event) event.stopPropagation();

        const lightbox = $('#mediaLightbox');
        const media = lightbox.data('media');
        const currentIndex = lightbox.data('currentIndex');

        let newIndex = currentIndex + direction;
        if (newIndex < 0) newIndex = media.length - 1;
        if (newIndex >= media.length) newIndex = 0;

        lightbox.data('currentIndex', newIndex);
        updateLightboxMedia(newIndex);
    };

    function updateLightboxMedia(index) {
        const lightbox = $('#mediaLightbox');
        const media = lightbox.data('media');
        const currentMedia = media[index];
        const mediaPath = getImagePath(currentMedia.image);
        const isVideo = mediaPath && (mediaPath.includes('.mp4') || mediaPath.includes('.mov') || mediaPath.includes('.webm'));

        const mediaHtml = isVideo ?
            `<video src="${mediaPath}" controls autoplay></video>` :
            `<img src="${mediaPath}" alt="Moment">`;

        lightbox.find('.lightbox-media').html(mediaHtml);
        // lightbox.find('.lightbox-counter').text(`${index + 1} / ${media.length}`);

        // إخفاء أزرار التنقل إذا كانت صورة واحدة فقط
        if (media.length === 1) {
            lightbox.find('.lightbox-nav').hide();
        } else {
            lightbox.find('.lightbox-nav').show();
        }
    }

    window.toggleComments = function(momentId, event) {
        if (event) event.stopPropagation();
        openSideModal('comments', momentId);
    };

    window.toggleLikes = function(momentId, event) {
        if (event) event.stopPropagation();
        openSideModal('likes', momentId);
    };

    window.toggleGifts = function(momentId, event) {
        if (event) event.stopPropagation();
        openSideModal('gifts', momentId);
    };

    function openSideModal(type, momentId) {
        const modalId = 'sideModal';
        let modal = $(`#${modalId}`);

        // إنشاء الموديل إذا لم يكن موجوداً
        if (modal.length === 0) {
            $('body').append(`
                <div id="${modalId}" class="side-modal">
                    <div class="side-modal-overlay"></div>
                    <div class="side-modal-content">
                        <div class="side-modal-header">
                            <h3 class="side-modal-title"></h3>
                            <button class="side-modal-close" onclick="closeSideModal()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="side-modal-body"></div>
                    </div>
                </div>
            `);
            modal = $(`#${modalId}`);

            // إغلاق عند النقر على الخلفية
            modal.find('.side-modal-overlay').on('click', closeSideModal);
        }

        // تحديث العنوان والمحتوى
        const title = type === 'comments'
            ? (texts.comments || 'Comments')
            : type === 'gifts'
                ? (texts.gifts || 'Gifts')
                : (texts.likes || 'Likes');

        modal.find('.side-modal-title').text(title);
        modal.find('.side-modal-body').html('<div class="loading-container"><div class="spinner"></div></div>');

        // فتح الموديل
        modal.addClass('active');
        $('body').addClass('modal-open');

        // تحميل البيانات
        if (type === 'comments') {
            loadCommentsInModal(momentId);
        } else if (type === 'gifts') {
            loadGiftsInModal(momentId);
        } else {
            loadLikesInModal(momentId);
        }
    }

    window.closeSideModal = function() {
        $('#sideModal').removeClass('active');
        $('body').removeClass('modal-open');
    };

    let modalCommentsPage = 1;
    let modalCommentsTotalPages = 1;
    let modalCommentsLoading = false;
    let currentModalMomentId = null;
    let currentModalType = null;

    function loadCommentsInModal(momentId, page = 1, append = false) {
        if (modalCommentsLoading) return;

        const url = routes.comments.replace(':id', momentId);
        const container = $('#sideModal .side-modal-body');

        if (!append) {
            currentModalMomentId = momentId;
            currentModalType = 'comments';
            modalCommentsPage = 1;
            container.html('<div class="loading-container"><div class="spinner"></div></div>');
        } else {
            container.find('.modal-list').append('<div class="modal-loading"><div class="spinner"></div></div>');
        }

        modalCommentsLoading = true;

        $.ajax({
            url: url,
            type: 'GET',
            data: { page: page, per_page: 20 },
            success: function(response) {
                if (response.success) {
                    if (response.pagination) {
                        modalCommentsPage = response.pagination.current_page;
                        modalCommentsTotalPages = response.pagination.last_page;
                    }

                    if (response.data.length > 0) {
                        let html = '';
                        response.data.forEach(comment => {
                            const user = comment.user || {};
                            const avatar = user.profile?.avatar ? getImagePath(user.profile.avatar) : defaultAvatar;
                            const userName = user.name || 'Unknown';
                            const userUuid = user.uuid || '';
                            const userId = user.id || '';
                            const userUrl = adminUserUrl + userId;
                            const commentDate = new Date(comment.created_at);
                            const timeAgo = getTimeAgo(commentDate);
                            const fullDateTime = formatDateTime(commentDate);
                            const commentDir = detectTextDirection(comment.comment);

                            html += `
                                <div class="modal-user-item">
                                    <div class="modal-user-header" onclick="window.open('${userUrl}', '_blank')">
                                      <button class="modal-delete-btn" onclick="event.stopPropagation(); deleteCommentFromModal(${comment.id}, ${momentId}, event)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <img src="${avatar}" alt="${escapeHtml(userName)}" class="modal-user-avatar" loading="lazy">
                                        <div class="modal-user-info">
                                            <div class="modal-user-name">${escapeHtml(userName)}</div>
                                            <div class="modal-user-meta">ID: ${userId}${userUuid ? ' • ' + userUuid : ''}</div>
                                        </div>
                                    </div>
                                    <div class="modal-comment-text" dir="${commentDir}" style="text-align: ${commentDir === 'rtl' ? 'right' : 'left'};">${escapeHtml(comment.comment)}</div>
                                    <div class="modal-comment-time" title="${fullDateTime}">${timeAgo}</div>
                                </div>
                            `;
                        });

                        if (append) {
                            container.find('.modal-loading').remove();
                            container.find('.modal-list').append(html);
                        } else {
                            container.html('<div class="modal-list">' + html + '</div>');
                            setupModalScrolling();
                        }
                    } else if (!append) {
                        container.html(`
                            <div class="modal-empty">
                                <i class="fas fa-comment-slash"></i>
                                <p>${texts.noComments || 'No comments yet'}</p>
                            </div>
                        `);
                    }
                }
            },
            error: function() {
                if (append) {
                    container.find('.modal-loading').remove();
                } else {
                    container.html(`
                        <div class="modal-empty">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p style="color: #e4405f;">${texts.failComments || 'Failed to load comments'}</p>
                        </div>
                    `);
                }
            },
            complete: function() {
                modalCommentsLoading = false;
            }
        });
    }

    let modalLikesPage = 1;
    let modalLikesTotalPages = 1;
    let modalLikesLoading = false;

    function loadLikesInModal(momentId, page = 1, append = false) {
        if (modalLikesLoading) return;

        const url = routes.likes.replace(':id', momentId);
        const container = $('#sideModal .side-modal-body');

        if (!append) {
            currentModalMomentId = momentId;
            currentModalType = 'likes';
            modalLikesPage = 1;
            container.html('<div class="loading-container"><div class="spinner"></div></div>');
        } else {
            container.find('.modal-list').append('<div class="modal-loading"><div class="spinner"></div></div>');
        }

        modalLikesLoading = true;

        $.ajax({
            url: url,
            type: 'GET',
            data: { page: page, per_page: 20 },
            success: function(response) {
                if (response.success) {
                    if (response.pagination) {
                        modalLikesPage = response.pagination.current_page;
                        modalLikesTotalPages = response.pagination.last_page;
                    }

                    if (response.data.length > 0) {
                        let html = '';
                        response.data.forEach(like => {
                            const user = like.user || {};
                            const avatar = user.profile?.avatar ? getImagePath(user.profile.avatar) : defaultAvatar;
                            const userName = user.name || 'Unknown';
                            const userUuid = user.uuid || '';
                            const userId = user.id || '';
                            const userUrl = adminUserUrl + userId;
                            const likeDate = new Date(like.created_at);
                            const timeAgo = getTimeAgo(likeDate);
                            const fullDateTime = formatDateTime(likeDate);

                            html += `
                                <div class="modal-user-item" onclick="window.open('${userUrl}', '_blank')">
                                    <img src="${avatar}" alt="${escapeHtml(userName)}" class="modal-user-avatar" loading="lazy">
                                    <div class="modal-user-info">
                                        <div class="modal-user-name">${escapeHtml(userName)}</div>
                                        <div class="modal-user-meta">ID: ${userId}${userUuid ? ' • ' + userUuid : ''}</div>
                                    </div>
                                    <div class="modal-like-info">
                                        <i class="fas fa-heart modal-like-icon"></i>
                                        <span class="modal-like-time" title="${fullDateTime}">${timeAgo}</span>
                                    </div>
                                </div>
                            `;
                        });

                        if (append) {
                            container.find('.modal-loading').remove();
                            container.find('.modal-list').append(html);
                        } else {
                            container.html('<div class="modal-list">' + html + '</div>');
                            setupModalScrolling();
                        }
                    } else if (!append) {
                        container.html(`
                            <div class="modal-empty">
                                <i class="fas fa-heart-broken"></i>
                                <p>${texts.noLikes || 'No likes yet'}</p>
                            </div>
                        `);
                    }
                }
            },
            error: function() {
                if (append) {
                    container.find('.modal-loading').remove();
                } else {
                    container.html(`
                        <div class="modal-empty">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p style="color: #e4405f;">${texts.failLikes || 'Failed to load likes'}</p>
                        </div>
                    `);
                }
            },
            complete: function() {
                modalLikesLoading = false;
            }
        });
    }

    let modalGiftsPage = 1;
    let modalGiftsTotalPages = 1;
    let modalGiftsLoading = false;

    function loadGiftsInModal(momentId, page = 1, append = false) {
        if (modalGiftsLoading) return;

        const url = routes.gifts.replace(':id', momentId);
        const container = $('#sideModal .side-modal-body');

        if (!append) {
            currentModalMomentId = momentId;
            currentModalType = 'gifts';
            modalGiftsPage = 1;
            container.html('<div class="loading-container"><div class="spinner"></div></div>');
        } else {
            container.find('.modal-list').append('<div class="modal-loading"><div class="spinner"></div></div>');
        }

        modalGiftsLoading = true;

        $.ajax({
            url: url,
            method: 'GET',
            data: { page: page, per_page: 20 },
            success: function(response) {
                if (response.success && response.data) {
                    modalGiftsTotalPages = response.pagination?.last_page || 1;
                    modalGiftsPage = page;

                    if (response.data.length > 0) {
                        let html = '';
                        response.data.forEach(gift => {
                            const userName = gift.user_name || 'Unknown';
                            const userId = gift.user_id;
                            const userUuid = gift.user_uuid || '';
                            const userUrl = adminUserUrl + userId;
                            const avatar = gift.user_avatar
                                ? getImagePath(gift.user_avatar)
                                : defaultAvatar;

                            const giftName = gift.gift_name || 'Gift';
                            const giftValue = gift.gift_value || 0;
                            const giftImg = gift.gift_img
                                ? getImagePath(gift.gift_img)
                                : '';

                            // التحقق من وجود التاريخ وصحته
                            let timeAgo = texts.unknown || 'Unknown';
                            let fullDateTime = '';
                            if (gift.created_at) {
                                const giftDate = new Date(gift.created_at);
                                // التحقق من أن التاريخ صحيح (بعد 2020)
                                if (!isNaN(giftDate.getTime()) && giftDate.getFullYear() > 2020) {
                                    timeAgo = getTimeAgo(giftDate);
                                    fullDateTime = formatDateTime(giftDate);
                                }
                            }

                            html += `
                                <div class="modal-gift-item">
                                    <div class="modal-user-item" onclick="window.open('${userUrl}', '_blank')">
                                        <img src="${avatar}" alt="${escapeHtml(userName)}" class="modal-user-avatar" loading="lazy">
                                        <div class="modal-user-info">
                                            <div class="modal-user-name">${escapeHtml(userName)}</div>
                                            <div class="modal-user-meta">ID: ${userId}${userUuid ? ' • ' + userUuid : ''}</div>
                                        </div>
                                    </div>
                                    <div class="gift-info">
                                        ${giftImg ? `<img src="${giftImg}" alt="${escapeHtml(giftName)}" class="gift-img" loading="lazy">` : '<i class="fas fa-gift gift-icon"></i>'}
                                        <div class="gift-details">
                                            <div class="gift-name">${escapeHtml(giftName)}</div>
                                            <div class="gift-value"><i class="fas fa-coins"></i> ${giftValue}</div>
                                            <div class="gift-time" title="${fullDateTime}">${timeAgo}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        if (append) {
                            container.find('.modal-loading').remove();
                            container.find('.modal-list').append(html);
                        } else {
                            container.html('<div class="modal-list">' + html + '</div>');
                            setupModalScrolling();
                        }
                    } else if (!append) {
                        container.html(`
                            <div class="modal-empty">
                                <i class="fas fa-gift"></i>
                                <p>${texts.noGifts || 'No gifts yet'}</p>
                            </div>
                        `);
                    }
                }
            },
            error: function() {
                if (append) {
                    container.find('.modal-loading').remove();
                } else {
                    container.html(`
                        <div class="modal-empty">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p style="color: #e4405f;">${texts.failGifts || 'Failed to load gifts'}</p>
                        </div>
                    `);
                }
            },
            complete: function() {
                modalGiftsLoading = false;
            }
        });
    }

    function setupModalScrolling() {
        const modalBody = $('#sideModal .side-modal-body');

        modalBody.off('scroll').on('scroll', function() {
            const scrollTop = $(this).scrollTop();
            const scrollHeight = this.scrollHeight;
            const clientHeight = $(this).height();
            const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

            if (distanceFromBottom < 100) {
                if (currentModalType === 'comments' && modalCommentsPage < modalCommentsTotalPages && !modalCommentsLoading) {
                    loadCommentsInModal(currentModalMomentId, modalCommentsPage + 1, true);
                } else if (currentModalType === 'likes' && modalLikesPage < modalLikesTotalPages && !modalLikesLoading) {
                    loadLikesInModal(currentModalMomentId, modalLikesPage + 1, true);
                } else if (currentModalType === 'gifts' && modalGiftsPage < modalGiftsTotalPages && !modalGiftsLoading) {
                    loadGiftsInModal(currentModalMomentId, modalGiftsPage + 1, true);
                }
            }
        });
    }

    function loadComments(momentId) {
        const url = routes.comments.replace(':id', momentId);
        const container = $(`#comments-${momentId}`);

        container.html('<div class="loading-container"><div class="spinner"></div></div>');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(comment => {
                        const user = comment.user || {};
                        const avatar = user.profile?.avatar ? getImagePath(user.profile.avatar) : defaultAvatar;
                        const timeAgo = getTimeAgo(new Date(comment.created_at));

                        html += `
                            <div class="comment-item">
                                <img src="${avatar}" alt="${escapeHtml(user.name)}" class="comment-avatar" loading="lazy">
                                <div class="comment-content">
                                    <div class="comment-bubble">
                                        <div class="comment-author">${escapeHtml(user.name || 'Unknown')}</div>
                                        <div class="comment-text">${escapeHtml(comment.comment)}</div>
                                    </div>
                                    <div class="comment-actions">
                                        <span>${timeAgo}</span>
                                        <span class="comment-delete" onclick="deleteComment(${comment.id}, ${momentId})">
                                            ${texts.delete || 'Delete'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    container.html(html);
                } else {
                    container.html(`
                        <p style="text-align: center; color: var(--text-secondary); padding: 20px;">
                            ${texts.noComments || 'No comments yet'}
                        </p>
                    `);
                }
            },
            error: function() {
                container.html(`
                    <p style="text-align: center; color: #e4405f; padding: 20px;">
                        ${texts.failComments || 'Failed to load comments'}
                    </p>
                `);
            }
        });
    }

    function loadLikes(momentId) {
        const url = routes.likes.replace(':id', momentId);
        const container = $(`#likes-${momentId}`);

        container.html('<div class="loading-container"><div class="spinner"></div></div>');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(like => {
                        const user = like.user || {};
                        const avatar = user.profile?.avatar ? getImagePath(user.profile.avatar) : defaultAvatar;
                        const userUrl = adminUserUrl + user.id;

                        html += `
                            <div class="like-item" onclick="window.open('${userUrl}', '_blank')">
                                <img src="${avatar}" alt="${escapeHtml(user.name)}" class="like-avatar" loading="lazy">
                                <div class="like-user-info">
                                    <div class="like-name">${escapeHtml(user.name || 'Unknown')}</div>
                                    <div class="like-uuid">${user.uuid || ''}</div>
                                </div>
                            </div>
                        `;
                    });
                    container.html(html);
                } else {
                    container.html(`
                        <p style="text-align: center; color: var(--text-secondary); padding: 20px;">
                            ${texts.noLikes || 'No likes yet'}
                        </p>
                    `);
                }
            },
            error: function() {
                container.html(`
                    <p style="text-align: center; color: #e4405f; padding: 20px;">
                        ${texts.failLikes || 'Failed to load likes'}
                    </p>
                `);
            }
        });
    }

    window.editMoment = function(momentId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        $('.post-dropdown').hide();
        $('.dropdown-menu').removeClass('show');

        const descElement = $(`#desc-${momentId}`);
        const currentDesc = descElement.length ? descElement.text().trim() : '';

        Swal.fire({
            title: texts.editDesc || 'Edit Description',
            input: 'textarea',
            inputValue: currentDesc,
            inputAttributes: { rows: 5 },
            showCancelButton: true,
            confirmButtonColor: '#1877f2',
            cancelButtonColor: '#65676b',
            confirmButtonText: texts.save || 'Save',
            cancelButtonText: texts.cancel || 'Cancel',
            preConfirm: function(inputValue) {
                updateMomentDescription(momentId, inputValue || '');
            }
        });
    };

    function updateMomentDescription(momentId, description) {
        var currentCsrf = $('meta[name="csrf-token"]').attr('content') || csrf;
        $.ajax({
            url: routes.updateDescription.replace(':id', momentId),
            method: 'PUT',
            data: { description: description, _token: currentCsrf },
            headers: { 'X-CSRF-TOKEN': currentCsrf },
            success: function(response) {
                if (response && response.success) {
                    var descEl = $(`#desc-${momentId}`);
                    if (descEl.length) {
                        descEl.find('.description-text').html(escapeHtml(response.description));
                        var direction = detectTextDirection(response.description);
                        descEl.attr('dir', direction).css('text-align', direction === 'rtl' ? 'right' : 'left');
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: texts.updated || 'Updated', text: texts.descUpdated || 'Description updated successfully', timer: 1500, showConfirmButton: false });
                    }
                } else {
                    var msg = (response && response.message) ? response.message : 'Update failed';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: texts.error || 'Error', text: msg });
                    } else {
                        alert(msg);
                    }
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ('Error ' + xhr.status + ': ' + xhr.statusText);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: texts.error || 'Error', text: msg });
                } else {
                    alert(msg);
                }
            }
        });
    }

    window.deleteMoment = function(momentId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        $('.post-dropdown').hide();
        $('.dropdown-menu').removeClass('show');

        var currentCsrf = $('meta[name="csrf-token"]').attr('content') || csrf;

        var doDelete = function() {
            var deleteUrl = routes.deleteMoment.replace(':id', momentId);
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { _token: currentCsrf },
                headers: { 'X-CSRF-TOKEN': currentCsrf },
                success: function(response) {
                    if (response && response.success) {
                        $(`.moment-post[data-moment-id="${momentId}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'success', title: texts.deleted || 'Deleted!', text: texts.momentDeleted || 'Moment deleted successfully', timer: 1500, showConfirmButton: false });
                        }
                    } else {
                        var msg = (response && response.message) ? response.message : 'Delete failed';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: texts.error || 'Error', text: msg });
                        } else {
                            alert(msg);
                        }
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ('Error ' + xhr.status + ': ' + xhr.statusText);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: texts.error || 'Error', text: msg });
                    } else {
                        alert(msg);
                    }
                }
            });
        };

        Swal.fire({
            title: texts.sure || 'Are you sure?',
            text: texts.noRevert || 'You will not be able to revert this!',
            type: 'warning',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e4405f',
            cancelButtonColor: '#65676b',
            confirmButtonText: texts.yesDelete || 'Yes, delete it!',
            cancelButtonText: texts.cancel || 'Cancel',
            preConfirm: function() {
                doDelete();
            }
        });
    };

    window.deleteComment = function(commentId, momentId) {
        deleteCommentFromModal(commentId, momentId);
    };

    window.deleteCommentFromModal = function(commentId, momentId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        var doDelete = function() {
            var currentCsrf = $('meta[name="csrf-token"]').attr('content') || csrf;
            $.ajax({
                url: routes.deleteComment.replace(':id', commentId),
                type: 'DELETE',
                data: { _token: currentCsrf },
                headers: { 'X-CSRF-TOKEN': currentCsrf },
                success: function(response) {
                    if (response.success) {
                        loadCommentsInModal(momentId);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'success', title: texts.deleted || 'Deleted!', text: texts.commentDeleted || 'Comment deleted successfully', timer: 1200, showConfirmButton: false });
                        }
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || texts.failDeleteComment || 'Failed to delete comment';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: texts.error || 'Error', text: msg });
                    } else {
                        alert('Error: ' + msg);
                    }
                    console.error('Delete comment error:', xhr.status, xhr.responseText);
                }
            });
        };

        try {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: texts.sure || 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e4405f',
                    cancelButtonColor: '#65676b',
                    confirmButtonText: texts.yesDelete || 'Yes, delete it!',
                    cancelButtonText: texts.cancel || 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) doDelete();
                });
            } else {
                if (confirm('Are you sure you want to delete this comment?')) {
                    doDelete();
                }
            }
        } catch(e) {
            console.error('deleteComment error:', e);
            if (confirm('Are you sure?')) doDelete();
        }
    };

    window.clearFilters = function() {
        $('#userIdFilter').val('');
        $('#userSearch').val('');
        userIdFilter = '';
        searchQuery = '';
        currentPage = 1;
        loadMoments();
    };

    window.retryLoad = function() {
        currentPage = 1;
        loadMoments();
    };

    // Helper Functions
    function getUserAvatar(user) {
        if (user.profile?.avatar) return getImagePath(user.profile.avatar);
        if (user.avatar) return getImagePath(user.avatar);
        return defaultAvatar;
    }

    function getImagePath(path) {
        if (!path) return defaultAvatar;
        if (path.startsWith('http')) return path;
        return storageUrl ? storageUrl + '/' + path : '/storage/' + path;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function detectTextDirection(text) {
        if (!text) return 'ltr';

        // تحقق من وجود أحرف عربية أو عبرية أو فارسية
        const rtlChars = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF\u0590-\u05FF]/;

        // ابحث عن أول حرف في النص
        const firstChar = text.trim().charAt(0);

        // إذا كان أول حرف من اليمين لليسار
        if (rtlChars.test(firstChar)) {
            return 'rtl';
        }

        // تحقق من نسبة الأحرف RTL في النص
        const rtlCount = (text.match(rtlChars) || []).length;
        const totalChars = text.replace(/\s/g, '').length;

        // إذا كانت أكثر من 30% من الأحرف RTL
        if (totalChars > 0 && (rtlCount / totalChars) > 0.3) {
            return 'rtl';
        }

        return 'ltr';
    }

    function applyTextDirection(text) {
        const direction = detectTextDirection(text);
        return `<div dir="${direction}" style="text-align: ${direction === 'rtl' ? 'right' : 'left'};">${text}</div>`;
    }

    function getTimeAgo(date) {
        // تحويل التاريخ مع مراعاة timezone
        const momentDate = new Date(date);
        const now = new Date();
        const seconds = Math.floor((now - momentDate) / 1000);

        const intervals = [
            { label: texts.years || 'y', seconds: 31536000 },
            { label: texts.months || 'mo', seconds: 2592000 },
            { label: texts.days || 'd', seconds: 86400 },
            { label: texts.hours || 'h', seconds: 3600 },
            { label: texts.minutes || 'min', seconds: 60 }
        ];

        for (const interval of intervals) {
            const count = Math.floor(seconds / interval.seconds);
            if (count >= 1) return count + interval.label;
        }
        return texts.now || 'now';
    }

    function formatDateTime(date) {
        // عرض التاريخ والوقت الكامل مع timezone
        const momentDate = new Date(date);
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            timeZoneName: 'short'
        };
        return momentDate.toLocaleString('en-US', options);
    }

    window.loadMoments = loadMoments;

    // Users List Variables
    let usersPage = 1, usersLastPage = 1, usersSearch = '', selectedUserId = null, usersLoading = false;

    async function loadUsers(reset = false) {
        if (usersLoading) return;
        usersLoading = true;

        const container = document.getElementById('usersListContainer');
        const loadMoreBtn = document.getElementById('loadMoreUsersBtn');

        if (reset) {
            usersPage = 1;
            container.innerHTML = '<div class="loading-container"><div class="spinner"></div></div>';
        } else {
            document.getElementById('usersLoadingMore')?.classList.add('visible');
        }

        try {
            const res = await fetch(`${MomentViewerConfig.routes.usersWithMoments}?page=${usersPage}&search=${encodeURIComponent(usersSearch)}`);
            const data = await res.json();

            if (data.success) {
                if (reset) container.innerHTML = '';
                document.getElementById('usersTotalCount').textContent = `(${data.pagination.total})`;
                usersLastPage = data.pagination.last_page;

                data.data.forEach(user => {
                    const avatar = user.profile?.avatar
                        ? `${MomentViewerConfig.storageUrl}/${user.profile.avatar}`
                        : MomentViewerConfig.defaultAvatar;
                    container.innerHTML += `
                    <div class="user-list-item ${selectedUserId == user.id ? 'active' : ''}"
                         data-user-id="${user.id}"
                         onclick="selectUser(${user.id})">
                        <img src="${avatar}" class="user-list-avatar" onerror="this.src='${MomentViewerConfig.defaultAvatar}'">
                        <div class="user-list-info">
                            <div class="user-list-name">${user.name}</div>
                            <div class="user-list-meta">ID: ${user.id} • ${user.uuid}</div>
                        </div>
                        <span class="user-list-count">${user.moments_count}</span>
                    </div>`;
                });

                if (loadMoreBtn) {
                    loadMoreBtn.parentElement.style.display = usersPage < usersLastPage ? 'block' : 'none';
                }
            }
        } catch (error) {
            console.error('Error loading users:', error);
        } finally {
            usersLoading = false;
            document.getElementById('usersLoadingMore')?.classList.remove('visible');
        }
    }

// ✅ Fixed selectUser function
    window.selectUser = function(userId) {
        selectedUserId = userId;

        // Update both DOM and JavaScript variable
        document.getElementById('userIdFilter').value = userId;
        userIdFilter = String(userId);  // Update the global variable

        document.getElementById('usersFilterInfo').classList.add('visible');

        // Update active state
        document.querySelectorAll('.user-list-item').forEach(el => {
            el.classList.toggle('active', el.dataset.userId == userId);
        });

        // Reset and reload moments
        currentPage = 1;
        allMomentsLoaded = [];
        currentlyVisibleCount = 0;
        searchQuery = ''; // Clear search when filtering by user
        document.getElementById('userSearch').value = '';

        loadMoments(false);
    };

// ✅ Fixed clearFilter function
    window.clearUserFilter = function() {
        selectedUserId = null;
        userIdFilter = '';
        document.getElementById('userIdFilter').value = '';
        document.getElementById('usersFilterInfo').classList.remove('visible');
        document.querySelectorAll('.user-list-item').forEach(el => el.classList.remove('active'));

        currentPage = 1;
        allMomentsLoaded = [];
        currentlyVisibleCount = 0;

        loadMoments(false);
    };

    // Users sidebar initialization function (called from $(document).ready for PJAX compatibility)
    function initUsersSidebar() {
        loadUsers(true);

        // Scroll handler for infinite scroll
        const usersContainer = document.getElementById('usersListContainer');
        if (usersContainer) {
            usersContainer.addEventListener('scroll', function() {
                const distanceFromBottom = this.scrollHeight - (this.scrollTop + this.clientHeight);
                if (distanceFromBottom < 100 && !usersLoading && usersPage < usersLastPage) {
                    usersPage++;
                    loadUsers(false);
                }
            });
        }

        // Load more button click
        document.getElementById('loadMoreUsersBtn')?.addEventListener('click', () => {
            if (!usersLoading && usersPage < usersLastPage) {
                usersPage++;
                loadUsers(false);
            }
        });

        // Clear filter button
        document.getElementById('clearFilterBtn')?.addEventListener('click', clearUserFilter);

        // Users search
        document.getElementById('usersListSearch')?.addEventListener('input', e => {
            clearTimeout(window.usersSearchTimeout);
            window.usersSearchTimeout = setTimeout(() => {
                usersSearch = e.target.value;
                loadUsers(true);
            }, 500);
        });
    }

})(jQuery);

