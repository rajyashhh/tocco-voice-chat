function reelsManager() {
    return {
        filteredReels: [],
        visibleReels: [],
        allReels: [],
        likes: [],
        comments: [],
        gifts: [],
        activeTab: 'likes',
        selectedReelId: null,
        selectedReel: null,
        showInteractionPanel: false,
        searchQuery: '',
        isMobileSidebarOpen: false,
        loading: false,
        hasMore: true,
        offset: 0,
        reelsLoaded: false,
        currentVideoIndex: 0,
        loadedVideos: new Set([0]),
        videoProgress: {},
        videoDurations: {},
        videoStates: {},
        videoReadyStates: {},
        isGlobalMuted: false,
        thumbnailGenerating: {},
        scrollingToSelection: false,
        showEditModal: false,
        editingReel: null,
        showDeleteModal: false,
        deletingReel: null,
        scrollRAF: null,
        videoCache: new Map(),
        isMobile: window.innerWidth <= 768,
        preloadQueue: [],
        isPreloading: false,
        loadedReelIds: new Set(),
        isAutoLoading: false,
        batchCache: [],
        
        init() {
            console.log('🚀 [REELS] init() called');
            console.log('🚀 [REELS] window.initialReelsData exists:', !!window.initialReelsData);
            console.log('🚀 [REELS] window.initialReelsData length:', window.initialReelsData ? window.initialReelsData.length : 'N/A');
            console.log('🚀 [REELS] window.Alpine exists:', !!window.Alpine);
            console.log('🚀 [REELS] window.reelsManager exists:', !!window.reelsManager);
            console.log('🚀 [REELS] this.$el exists:', !!this.$el);
            
            this.isGlobalMuted = false;
            this.isMobile = window.innerWidth <= 768;
            
            // تهيئة التخزين المؤقت
            this.initCache();
            
            // إضافة event delegation للضغط على الفيديو والأزرار (للتعامل مع PJAX)
            this.setupEventDelegation();
            
            this.$nextTick(() => {
                console.log('🚀 [REELS] $nextTick - about to loadInitialData');
                if (window.requestIdleCallback) {
                    requestIdleCallback(() => this.loadInitialData());
                } else {
                    setTimeout(() => this.loadInitialData(), 100);
                }
            });
        },
        
        // Event delegation للتعامل مع جميع الأحداث (PJAX support)
        setupEventDelegation() {
            const container = this.$el;
            if (!container) {
                // fallback إذا لم يكن الـ container متاحاً
                setTimeout(() => this.setupEventDelegation(), 100);
                return;
            }
            
            // إزالة أي listener سابق
            if (this._mainClickHandler) {
                container.removeEventListener('click', this._mainClickHandler);
            }
            
            this._mainClickHandler = (event) => {
                const target = event.target;
                
                // 1. الضغط على الفيديو للتشغيل/الإيقاف
                const video = target.closest('video');
                if (video && video.id && video.id.startsWith('video-')) {
                    event.stopPropagation();
                    if (video.paused) {
                        video.play().catch(err => {
                            console.log('تعذر تشغيل الفيديو:', err);
                        });
                    } else {
                        video.pause();
                    }
                    return;
                }
                
                // 2. زر الميوت
                const muteButton = target.closest('[data-mute-btn]') || 
                    (target.closest('button') && target.closest('button').querySelector('.fa-volume-up, .fa-volume-mute'));
                if (muteButton || (target.classList.contains('fa-volume-up') || target.classList.contains('fa-volume-mute'))) {
                    event.stopPropagation();
                    this.isGlobalMuted = !this.isGlobalMuted;
                    const allVideos = document.querySelectorAll('video');
                    allVideos.forEach(v => {
                        v.muted = this.isGlobalMuted;
                    });
                    return;
                }
                
                // 3. الضغط على thumbnail في الـ sidebar أو Mobile Overlay
                const sidebarItem = target.closest('[data-reel-id]');
                if (sidebarItem && !target.closest('.video-container')) {
                    const reelId = sidebarItem.dataset?.reelId;
                    if (reelId) {
                        this.selectReel(parseInt(reelId));
                        this.closeMobileSidebar();
                    }
                }
            };
            
            container.addEventListener('click', this._mainClickHandler);
        },
        
        loadInitialData() {
            let reelsData = window.initialReelsData || [];
            
            // إذا كانت البيانات فارغة، حاول تحميلها من الـ API مباشرة
            if (reelsData.length === 0) {
                console.log('⚠️ No initial data found, fetching from API...');
                this.fetchInitialReels();
                return;
            }
            
            // فلترة البيانات الأولية لتجنب أي تكرار
            const uniqueReels = [];
            const seenIds = new Set();
            
            reelsData.forEach(reel => {
                if (!seenIds.has(reel.id)) {
                    seenIds.add(reel.id);
                    this.loadedReelIds.add(reel.id);
                    uniqueReels.push({
                        ...reel,
                        thumbnailLoaded: Boolean(reel.thumbnail_url)
                    });
                }
            });
            
            this.allReels = uniqueReels;
            
            // تحميل سريع لأول فيديوهين على الموبايل
            const initialCount = this.isMobile ? 2 : 3;
            this.visibleReels = this.allReels.slice(0, initialCount);
            this.filteredReels = this.allReels.slice(0, 6);
            this.reelsLoaded = true;

            this.offset = this.allReels.length;
            this.hasMore = true; // دائماً true للتحميل العشوائي
            
            // تحميل أول فيديو فقط
            this.loadedVideos.add(0);
            if (this.isMobile && this.allReels.length > 1) {
                this.loadedVideos.add(1); // تحميل الثاني أيضاً
            }
            
            this.isMobileSidebarOpen = false;
            
            this.$nextTick(() => {
                this.playFirstVideo();
                this.setupInfiniteScroll();
                this.setupSidebarScroll();
                
                // التحقق الدوري من حالة الفيديوهات (للتعامل مع PJAX)
                this.checkExistingVideosReady();
                setTimeout(() => this.checkExistingVideosReady(), 500);
                setTimeout(() => this.checkExistingVideosReady(), 1000);
                
                // تحميل مسبق لأول فيديوهين فوراً
                if (this.isMobile) {
                    this.preloadFirstVideos();
                }
                
                this.refreshVisibleReelsCounts();
                this.captureMissingThumbnails(this.filteredReels.slice(0, 6));
                
                // تحميل batch إضافي في الخلفية
                setTimeout(() => {
                    this.preloadNextBatch();
                    
                    // بدء التحميل المسبق للباقي
                    if (this.isMobile) {
                        this.startBackgroundPreload();
                    }
                }, 1000);
                
                // تقليل تردد التحديث لتحسين الأداء
                setInterval(() => {
                    this.refreshVisibleReelsCounts();
                }, 60000);
            });
        },
        
        loadThumbnail(element, reel) {
        },
        
        isVideoReady(reelId) {
            return this.videoReadyStates[reelId] === true;
        },
        
        markVideoReady(reelId) {
            this.videoReadyStates[reelId] = true;
        },
        
        toggleMobileSidebar() {
            this.isMobileSidebarOpen = !this.isMobileSidebarOpen;
        },
        
        closeMobileSidebar() {
            this.isMobileSidebarOpen = false;
        },
        
        onThumbnailLoad(reel, event) {
            reel.thumbnailLoaded = true;
        },
        
        async onThumbnailError(reel, event) {
            // Attempt to regenerate the thumbnail from the video if loading fails
            await this.generateThumbnailFromVideo(reel);
        },
        
        onVideoLoaded(event, reelId) {
            const video = event.target;
            this.markVideoReady(reelId);
            
            // تطبيق حالة mute من الإعدادات العامة
            video.muted = this.isGlobalMuted;

            // عدم إيقاف الفيديو إذا كان يشتغل
            // السماح بالتشغيل بدون تدخل
            
            // تحميل مسبق للفيديو التالي
            const currentIndex = this.visibleReels.findIndex(r => r.id === reelId);
            if (currentIndex >= 0 && currentIndex < this.visibleReels.length - 1) {
                const nextReel = this.visibleReels[currentIndex + 1];
                const nextVideo = document.getElementById('video-' + nextReel.id);
                if (nextVideo && !nextVideo.src) {
                    nextVideo.preload = 'metadata';
                }
            }
        },
        
        // ==================== دوال التخزين المؤقت ====================
        
        initCache() {
            // تهيئة التخزين المؤقت
            try {
                const cached = localStorage.getItem('reels_cache_meta');
                if (cached) {
                    const meta = JSON.parse(cached);
                    // التحقق من صلاحية الكاش (24 ساعة)
                    if (Date.now() - meta.timestamp < 24 * 60 * 60 * 1000) {
                        console.log('✅ تم العثور على بيانات مخزنة صالحة');
                    } else {
                        localStorage.removeItem('reels_cache_meta');
                    }
                }
            } catch (e) {
                console.log('تعذر الوصول للتخزين المؤقت:', e);
            }
        },
        
        async preloadFirstVideos() {
            // تحميل مسبق لأول فيديوهين على الموبايل
            console.log('🚀 بدء التحميل المسبق للفيديوهات الأولى...');
            const startTime = performance.now();
            
            const videosToPreload = this.visibleReels.slice(0, 2);
            
            for (const reel of videosToPreload) {
                const video = document.getElementById('video-' + reel.id);
                if (video && video.src) {
                    // إجبار بدء التحميل
                    video.preload = 'auto';
                    video.load();
                    
                    // حفظ في الكاش
                    this.videoCache.set(reel.id, {
                        url: reel.video_url,
                        loadedAt: Date.now(),
                        element: video
                    });
                }
            }
            
            const endTime = performance.now();
            console.log(`✅ تم التحميل المسبق لـ ${videosToPreload.length} فيديو في ${(endTime - startTime).toFixed(2)}ms`);
        },
        
        startBackgroundPreload() {
            // بدء التحميل في الخلفية للفيديوهات المتبقية
            if (this.isPreloading) return;
            
            this.isPreloading = true;
            console.log('📦 بدء التحميل في الخلفية...');
            console.log(`📊 إجمالي الفيديوهات: ${this.visibleReels.length}`);
            
            // إضافة الفيديوهات للطابور (بدءاً من الفيديو الثالث)
            this.preloadQueue = this.visibleReels.slice(2).map(r => r.id);
            console.log(`📋 عدد الفيديوهات في الطابور: ${this.preloadQueue.length}`);
            
            // تحميل تدريجي
            this.processPreloadQueue();
        },
        
        async processPreloadQueue() {
            if (this.preloadQueue.length === 0) {
                this.isPreloading = false;
                console.log('✅ انتهى التحميل في الخلفية');
                
                // حفظ metadata في localStorage
                try {
                    localStorage.setItem('reels_cache_meta', JSON.stringify({
                        timestamp: Date.now(),
                        count: this.videoCache.size
                    }));
                } catch (e) {
                    console.log('تعذر حفظ metadata');
                }
                
                return;
            }
            
            // تحميل فيديو واحد في كل مرة لعدم إثقال الشبكة
            const reelId = this.preloadQueue.shift();
            const reel = this.visibleReels.find(r => r.id === reelId);
            
            if (reel) {
                const video = document.getElementById('video-' + reel.id);
                if (video && video.src && !this.videoCache.has(reel.id)) {
                    // استخدام preload='metadata' للتوفير
                    video.preload = 'metadata';
                    
                    // الانتظار حتى يتم تحميل الـ metadata
                    video.addEventListener('loadedmetadata', () => {
                        this.videoCache.set(reel.id, {
                            url: reel.video_url,
                            loadedAt: Date.now(),
                            duration: video.duration
                        });
                    }, { once: true });
                    
                    video.load();
                }
            }
            
            // تأخير ذكي: 1.5 ثانية على الموبايل، 1 ثانية على الديسكتوب
            const delay = this.isMobile ? 1500 : 1000;
            
            setTimeout(() => {
                if (window.requestIdleCallback) {
                    requestIdleCallback(() => this.processPreloadQueue());
                } else {
                    this.processPreloadQueue();
                }
            }, delay);
        },
        
        getCachedVideo(reelId) {
            return this.videoCache.get(reelId);
        },
        
        clearOldCache() {
            // تنظيف الكاش القديم (أكثر من 5 دقائق)
            const now = Date.now();
            const maxAge = 5 * 60 * 1000; // 5 دقائق
            let clearedCount = 0;
            
            for (const [id, data] of this.videoCache.entries()) {
                if (now - data.loadedAt > maxAge) {
                    this.videoCache.delete(id);
                    clearedCount++;
                }
            }
            
            if (clearedCount > 0) {
                console.log(`🧹 تم تنظيف ${clearedCount} فيديو من الكاش`);
            }
            
            // تنظيف localStorage أيضاً إذا كان ممتلئاً
            try {
                const usage = new Blob([localStorage.getItem('reels_cache_meta') || '']).size;
                if (usage > 50000) { // 50KB
                    localStorage.removeItem('reels_cache_meta');
                    console.log('🧹 تم تنظيف localStorage');
                }
            } catch (e) {
                // تجاهل الأخطاء
            }
        },
        
        async preloadNextBatch() {
            // تحميل batch إضافي في الخلفية
            if (this.batchCache.length > 0 || this.isAutoLoading) {
                return;
            }
            
            this.isAutoLoading = true;
            console.log('📦 تحميل batch جديد في الخلفية...');
            
            try {
                const excludeIds = Array.from(this.loadedReelIds);
                
                const response = await fetch(`/admin/view/reels/load-more?limit=10`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        exclude_ids: excludeIds
                    })
                });
                
                const data = await response.json();
                
                if (data.reels && data.reels.length > 0) {
                    // فلترة العناصر المكررة
                    const newReels = data.reels.filter(reel => !this.loadedReelIds.has(reel.id));
                    
                    this.batchCache = newReels.map(reel => ({
                        ...reel,
                        thumbnailLoaded: Boolean(reel.thumbnail_url)
                    }));
                    
                    console.log(`✅ تم تخزين ${this.batchCache.length} فيديو في الكاش (تم تجاهل ${data.reels.length - newReels.length} مكرر)`);
                }
            } catch (error) {
                console.error('خطأ في تحميل batch:', error);
            } finally {
                this.isAutoLoading = false;
            }
        },
        
        // ==================== نهاية دوال التخزين المؤقت ====================
        
        shouldLoadVideo(index) {
            const currentIndex = this.currentVideoIndex;
            // تحميل الفيديو الحالي والمجاور
            return Math.abs(index - currentIndex) <= 2 || this.loadedVideos.has(index);
        },
        
        setupSidebarScroll() {
            const sidebarContainer = this.$refs.sidebarContainer;
            if (!sidebarContainer) return;
            
            sidebarContainer.addEventListener('scroll', () => {
                this.handleSidebarScroll();
            });
        },
        
        handleSidebarScroll() {
            const container = this.$refs.sidebarContainer;
            if (!container || this.loading || !this.hasMore) return;
            
            const scrollHeight = container.scrollHeight;
            const scrollTop = container.scrollTop;
            const clientHeight = container.clientHeight;
            
            const scrollPercentage = ((scrollTop + clientHeight) / scrollHeight) * 100;
            
            if (scrollPercentage >= 75) {
                this.loadMoreReels();
            }
        },
        
        setupInfiniteScroll() {
            const container = this.$refs.reelsContainer;
            if (!container) return;
            
            let scrollTimeout;
            let lastScrollTop = 0;
            
            // تحسين الأداء باستخدام passive listener
            container.addEventListener('scroll', () => {
                const scrollTop = container.scrollTop;
                
                // استدعاء handleScroll مباشرة للتفاعل السريع
                if (Math.abs(scrollTop - lastScrollTop) > 100) {
                    this.handleScroll();
                    lastScrollTop = scrollTop;
                }
                
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    this.handleScroll();
                    
                    if (this.loading || !this.hasMore) return;
                    
                    const scrollHeight = container.scrollHeight;
                    const clientHeight = container.clientHeight;
                    const scrollPercentage = ((scrollTop + clientHeight) / scrollHeight) * 100;
                    
                    // تحميل أقل تكراراً
                    if (scrollPercentage >= 80) {
                        this.loadMoreReels();
                    }
                }, 150);
            }, { passive: true });
            
            // تنظيف الكاش القديم كل دقيقة
            if (this.isMobile) {
                setInterval(() => {
                    this.clearOldCache();
                }, 60000);
            }
        },
        
        async loadMoreReels() {
            if (this.loading || !this.hasMore || this.isAutoLoading) {
                return;
            }
            
            this.loading = true;
            
            try {
                const excludeIds = Array.from(this.loadedReelIds);
                
                const response = await fetch(`/admin/view/reels/load-more?limit=20`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        exclude_ids: excludeIds,
                        seed: window.randomSeed || null
                    })
                });
                
                const data = await response.json();
                
                if (data.reels && data.reels.length > 0) {
                    // فلترة العناصر المكررة من كل من allReels و loadedReelIds
                    const existingIds = new Set(this.allReels.map(r => r.id));
                    const newReels = data.reels.filter(reel => {
                        // التحقق من loadedReelIds أيضاً لضمان عدم التكرار
                        return !existingIds.has(reel.id) && !this.loadedReelIds.has(reel.id);
                    });
                    
                    if (newReels.length > 0) {
                        const normalized = newReels.map(reel => ({
                            ...reel,
                            thumbnailLoaded: Boolean(reel.thumbnail_url)
                        }));

                        // إضافة العناصر الجديدة فقط
                        const visibleReelsIds = new Set(this.visibleReels.map(r => r.id));
                        const filteredReelsIds = new Set(this.filteredReels.map(r => r.id));
                        
                        normalized.forEach(item => {
                            // إضافة إلى allReels
                            this.allReels.push(item);
                            this.loadedReelIds.add(item.id);
                            
                            // إضافة إلى visibleReels فقط إذا لم يكن موجوداً
                            if (!visibleReelsIds.has(item.id)) {
                                this.visibleReels.push(item);
                                visibleReelsIds.add(item.id);
                            }
                            
                            // إضافة إلى filteredReels فقط إذا لم يكن موجوداً ولا يوجد بحث
                            if (!this.searchQuery && !filteredReelsIds.has(item.id)) {
                                this.filteredReels.push(item);
                                filteredReelsIds.add(item.id);
                            }
                        });
                        
                        this.captureMissingThumbnails(normalized);
                        
                        if (this.searchQuery) {
                            this.filterReels();
                        }
                        
                        console.log(`✅ تم تحميل ${newReels.length} ريل جديد (تم تجاهل ${data.reels.length - newReels.length} مكرر)`);
                    } else {
                        console.log(`⚠️ جميع الريلز المستلمة (${data.reels.length}) كانت مكررة`);
                    }
                    
                    this.offset += newReels.length; // عد العناصر الجديدة فقط
                    this.hasMore = data.has_more !== false;
                } else {
                    this.hasMore = false;
                }
            } catch (error) {
                console.error('خطأ في تحميل الريلز:', error);
            } finally {
                this.loading = false;
            }
        },
        
        playFirstVideo() {
            this.selectedReelId = this.visibleReels[0]?.id;
            this.selectedReel = this.visibleReels[0] || null;
            
            // التحقق من جميع الفيديوهات المحملة مسبقاً وتحديث حالتها
            this.checkExistingVideosReady();
            
            this.$nextTick(() => {
                setTimeout(() => {
                    const firstVideo = document.getElementById('video-' + this.selectedReelId);
                    if (firstVideo) {
                        const cached = this.getCachedVideo(this.selectedReelId);
                        if (cached && cached.element) {
                            console.log('⚡ تشغيل من الكاش');
                        }
                        
                        // التحقق مرة أخرى وتحديث حالة الفيديو
                        if (firstVideo.readyState >= 2) {
                            this.markVideoReady(this.selectedReelId);
                            firstVideo.play().catch(err => {
                                console.log('تشغيل تلقائي معطل:', err);
                            });
                        }
                    }
                }, 300); 
            });
        },
        
        // التحقق من الفيديوهات الموجودة مسبقاً (للتعامل مع PJAX navigation)
        checkExistingVideosReady() {
            this.visibleReels.forEach((reel) => {
                const video = document.getElementById('video-' + reel.id);
                if (video && video.readyState >= 2) {
                    this.markVideoReady(reel.id);
                }
            });
        },
        
        handleScroll() {
            const container = this.$refs.reelsContainer;
            if (!container) return;
            
            const scrollTop = container.scrollTop;
            const screenHeight = window.innerHeight;
            
            const newIndex = Math.round(scrollTop / screenHeight);
            
            if (newIndex !== this.currentVideoIndex) {
                const oldIndex = this.currentVideoIndex;
                this.currentVideoIndex = newIndex;
                
                for (let i = newIndex - 1; i <= newIndex + 1; i++) {
                    if (i >= 0 && i < this.visibleReels.length) {
                        this.loadedVideos.add(i);
                    }
                }
                
                if (oldIndex !== newIndex && oldIndex >= 0 && oldIndex < this.visibleReels.length) {
                    const oldVideo = document.getElementById('video-' + this.visibleReels[oldIndex]?.id);
                    if (oldVideo && !oldVideo.paused) {
                        oldVideo.pause();
                        oldVideo.currentTime = 0;
                    }
                }
                
                if (newIndex >= 0 && newIndex < this.visibleReels.length) {
                    const newVideo = document.getElementById('video-' + this.visibleReels[newIndex]?.id);
                    if (newVideo) {
                        // تحديث حالة الفيديو إذا كان جاهزاً
                        if (newVideo.readyState >= 2) {
                            this.markVideoReady(this.visibleReels[newIndex]?.id);
                        }
                        if (newVideo.paused && newVideo.readyState >= 2) {
                            newVideo.play().catch(() => {});
                        }
                    }
                }
                
                if (newIndex >= 0 && newIndex < this.visibleReels.length) {
                    this.selectedReelId = this.visibleReels[newIndex]?.id;
                    this.selectedReel = this.visibleReels[newIndex];
                    
                    const remaining = this.visibleReels.length - newIndex;
                    if (remaining <= 3 && this.hasMore && !this.loading) {
                        console.log('🚀 تحميل تلقائي: متبقي', remaining, 'فيديوهات');
                        this.loadMoreReels();
                    }
                }
                
                const farVideos = Array.from(this.loadedVideos).filter(i => Math.abs(i - newIndex) > 5);
                farVideos.forEach(i => {
                    const video = document.getElementById('video-' + this.visibleReels[i]?.id);
                    if (video) {
                        video.pause();
                        video.removeAttribute('src');
                        video.load();
                    }
                    this.loadedVideos.delete(i);
                    delete this.videoReadyStates[this.visibleReels[i]?.id];
                });
            }
        },
        
        updateVisibleVideos(container, screenHeight) {
        },
        
        togglePlay(event) {
            event.stopPropagation();
            const video = event.target;
            
            if (video.paused) {
                video.play().catch(err => {
                    console.log('تعذر تشغيل الفيديو:', err);
                });
            } else {
                video.pause();
            }
        },
        
        onVideoEnded(event) {
            const video = event.target;
            video.currentTime = 0;
            video.play().catch(() => {});
        },

        async captureMissingThumbnails(reels) {
            const batch = Array.isArray(reels) ? reels : [reels];
            for (const reel of batch) {
                if (!reel) continue;
                if (reel.thumbnail_url) continue;
                await this.generateThumbnailFromVideo(reel);
            }
        },

        async generateThumbnailFromVideo(reel) {
            if (!reel || !reel.video_url) {
                return;
            }

            if (this.thumbnailGenerating[reel.id]) {
                return;
            }

            this.thumbnailGenerating[reel.id] = true;

            const thumbnail = await this.captureFrameFromVideo(reel.video_url);
            if (thumbnail) {
                reel.thumbnail_url = thumbnail;
                reel.thumbnailLoaded = true;
            } else {
                reel.thumbnail_url = `https://picsum.photos/400/700?random=${reel.id || Math.random()}`;
                reel.thumbnailLoaded = true;
            }

            delete this.thumbnailGenerating[reel.id];
        },

        captureFrameFromVideo(videoUrl) {
            return new Promise(resolve => {
                if (!videoUrl) {
                    return resolve(null);
                }

                const video = document.createElement('video');
                video.crossOrigin = 'anonymous';
                video.src = videoUrl;
                video.muted = true;
                video.playsInline = true;
                video.preload = 'auto';

                const handleError = () => resolve(null);
                video.onerror = handleError;

                video.onloadeddata = () => {
                    if (!video.videoWidth || !video.videoHeight) {
                        return resolve(null);
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        return resolve(null);
                    }

                    try {
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                        resolve(dataUrl);
                    } catch (e) {
                        resolve(null);
                    }
                };

                video.currentTime = 1;
                video.load();
            });
        },
        
        async toggleInteraction(tab, reelId) {
            this.activeTab = tab;
            this.selectedReelId = reelId;
            this.selectedReel = this.filteredReels.find(r => r.id === reelId);
            this.showInteractionPanel = true;
            
            // Load data in background without waiting and refresh counts
            await this.loadTabData();
        },
        
        closeInteractionPanel() {
            this.showInteractionPanel = false;
            // Refresh counts when closing to catch any updates
            if (this.selectedReelId) {
                this.refreshReelCounts(this.selectedReelId);
            }
        },
        
        async refreshReelCounts(reelId) {
            try {
                const response = await fetch(`/admin/view/reels/${reelId}`);
                const data = await response.json();
                
                if (data.reel) {
                    this.updateReelCounts(reelId, data.reel);
                }
            } catch (error) {
                console.error('Error refreshing reel counts:', error);
            }
        },
        
        async refreshVisibleReelsCounts() {
            // Get currently visible reels (current +/- 2)
            const visibleIndexes = [];
            for (let i = Math.max(0, this.currentVideoIndex - 2); 
                 i <= Math.min(this.visibleReels.length - 1, this.currentVideoIndex + 2); 
                 i++) {
                visibleIndexes.push(i);
            }
            
            // Batch update for better performance
            const reelIds = visibleIndexes.map(i => this.visibleReels[i]?.id).filter(Boolean);
            
            if (reelIds.length === 0) return;
            
            try {
                // Fetch counts for multiple reels in one request
                const response = await fetch(`/admin/view/reels/batch-counts`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ reel_ids: reelIds })
                });
                
                if (!response.ok) {
                    // Fallback: update one by one
                    for (const reelId of reelIds.slice(0, 3)) { // Limit to 3 to avoid too many requests
                        await this.refreshReelCounts(reelId);
                    }
                    return;
                }
                
                const data = await response.json();
                
                if (data.reels) {
                    data.reels.forEach(reel => {
                        this.updateReelCounts(reel.id, reel);
                    });
                }
            } catch (error) {
                console.error('Error refreshing visible reels counts:', error);
            }
        },
        
        updateReelCounts(reelId, reelData) {
            // Update in visibleReels (player)
            const visIndex = this.visibleReels.findIndex(r => r.id === reelId);
            if (visIndex !== -1) {
                this.visibleReels[visIndex] = {
                    ...this.visibleReels[visIndex],
                    likes_count: reelData.likes_count || 0,
                    comments_count: reelData.comments_count || 0,
                    gifts_count: reelData.gifts_count || 0,
                    views_count: reelData.views_count || 0
                };
            }

            // Update in filteredReels (sidebar)
            const reelIndex = this.filteredReels.findIndex(r => r.id === reelId);
            if (reelIndex !== -1) {
                this.filteredReels[reelIndex] = {
                    ...this.filteredReels[reelIndex],
                    likes_count: reelData.likes_count || 0,
                    comments_count: reelData.comments_count || 0,
                    gifts_count: reelData.gifts_count || 0,
                    views_count: reelData.views_count || 0
                };
            }
            
            // Update in allReels
            const allReelIndex = this.allReels.findIndex(r => r.id === reelId);
            if (allReelIndex !== -1) {
                this.allReels[allReelIndex] = {
                    ...this.allReels[allReelIndex],
                    likes_count: reelData.likes_count || 0,
                    comments_count: reelData.comments_count || 0,
                    gifts_count: reelData.gifts_count || 0,
                    views_count: reelData.views_count || 0
                };
            }
            
            // Force reactivity
            this.visibleReels = [...this.visibleReels];
            this.filteredReels = [...this.filteredReels];
            this.allReels = [...this.allReels];
        },
        
        async selectReel(reelId) {
            this.scrollingToSelection = true;
            this.pauseAllVideos();

            let targetIndex = this.visibleReels.findIndex(r => r.id === reelId);
            if (targetIndex === -1) {
                targetIndex = this.ensureReelVisible(reelId);
            }

            const targetReel = this.allReels.find(r => r.id === reelId) ||
                this.filteredReels.find(r => r.id === reelId);

            if (targetIndex === -1 || !targetReel) {
                this.scrollingToSelection = false;
                return;
            }

            this.selectedReelId = reelId;
            this.selectedReel = targetReel;

            this.currentVideoIndex = targetIndex;
            this.loadedVideos = new Set([
                Math.max(0, targetIndex - 1),
                targetIndex,
                Math.min(this.visibleReels.length - 1, targetIndex + 1)
            ]);

            const reelElement = document.querySelector(`[data-reel-id="${reelId}"]`);
            if (reelElement) {
                const container = this.$refs.reelsContainer;
                if (container) {
                    container.scrollTo({ top: reelElement.offsetTop, behavior: 'auto' });
                } else {
                    reelElement.scrollIntoView({ behavior: 'auto', block: 'center' });
                }
            }

            this.$nextTick(() => {
                this.playVideoById(reelId);
                this.scrollingToSelection = false;
            });
        },

        ensureReelVisible(reelId) {
            const targetIndexAll = this.allReels.findIndex(r => r.id === reelId);
            if (targetIndexAll === -1) {
                return -1;
            }

            if (this.visibleReels.length < targetIndexAll + 1) {
                const toAdd = this.allReels.slice(this.visibleReels.length, targetIndexAll + 1);
                this.visibleReels = [...this.visibleReels, ...toAdd];
            }

            return this.visibleReels.findIndex(r => r.id === reelId);
        },

        playVideoById(reelId) {
            const video = document.getElementById('video-' + reelId);
            if (!video) return;
            if (!video.src) return;
            video.muted = this.isGlobalMuted;
            if (video.readyState < 2) {
                const onCanPlay = () => {
                    video.removeEventListener('canplay', onCanPlay);
                    this.markVideoReady(reelId);
                    video.play().catch(() => {});
                };
                video.addEventListener('canplay', onCanPlay, { once: true });
                video.load();
            } else {
                // تحديث حالة الفيديو للتأكد من إظهاره
                this.markVideoReady(reelId);
                video.play().catch(() => {});
            }
        },

        pauseAllVideos() {
            const allVideos = document.querySelectorAll('video');
            allVideos.forEach(v => {
                if (!v.paused) {
                    v.pause();
                }
            });
        },
        
        async loadTabData() {
            if (!this.selectedReelId) return;
            
            try {
                const response = await fetch(`/admin/view/reels/${this.selectedReelId}`);
                const data = await response.json();
                
                // Update the selected reel with fresh data
                if (data.reel) {
                    this.selectedReel = data.reel;
                    
                    // Sync counts across lists
                    this.updateReelCounts(this.selectedReelId, data.reel);
                }
                
                this.likes = data.likes || [];
                this.comments = data.comments || [];
                this.gifts = data.gifts || [];
            } catch (error) {
                console.error('Error fetching reel data:', error);
                // Set empty arrays on error
                this.likes = [];
                this.comments = [];
                this.gifts = [];
            }
        },
        
        filterReels() {
            const query = this.searchQuery.toLowerCase().trim();
            if (!query) {
                this.filteredReels = this.allReels;
            } else {
                const matches = this.allReels.filter(reel => {
                    const titleMatch = reel.title.toLowerCase().includes(query);
                    const userNameMatch = reel.user?.name?.toLowerCase().includes(query);
                    const idMatch = reel.id.toString().includes(query);
                    const userIdMatch = reel.user?.id?.toString().includes(query);
                    return titleMatch || userNameMatch || idMatch || userIdMatch;
                });

                // عرض النتائج فقط حتى لو فارغة
                this.filteredReels = matches;
            }
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'الآن';
            if (diffMins < 60) return `منذ ${diffMins} دقيقة`;
            if (diffHours < 24) return `منذ ${diffHours} ساعة`;
            if (diffDays < 7) return `منذ ${diffDays} يوم`;
            
            return date.toLocaleDateString('ar-EG');
        },
        
        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },
        
        getGiftEmoji(giftType) {
            const emojis = {
                'rose': '🌹',
                'heart': '❤️',
                'diamond': '💎',
                'star': '⭐',
                'fire': '🔥',
                'crown': '👑'
            };
            return emojis[giftType] || '🎁';
        },
        
        getGiftName(giftType) {
            const names = {
                'rose': 'وردة حمراء',
                'heart': 'قلب',
                'diamond': 'ألماسة',
                'star': 'نجمة',
                'fire': 'نار',
                'crown': 'تاج'
            };
            return names[giftType] || 'هدية';
        },
        
        toggleMute(reelId) {
            this.isGlobalMuted = !this.isGlobalMuted;
            
            const allVideos = document.querySelectorAll('video');
            allVideos.forEach(video => {
                video.muted = this.isGlobalMuted;
            });
        },
        
        isMuted(reelId) {
            return this.isGlobalMuted;
        },
        
        updateProgress(event) {
            const video = event.target;
            const reelId = parseInt(video.id.replace('video-', ''));
            
            if (video.duration) {
                this.videoProgress[reelId] = (video.currentTime / video.duration) * 100;
                this.videoDurations[reelId] = video.duration;
                this.videoStates[reelId] = {
                    currentTime: video.currentTime,
                    duration: video.duration,
                    paused: video.paused
                };
            }
        },
        
        getProgress(reelId) {
            return this.videoProgress[reelId] || 0;
        },
        
        getCurrentTime(reelId) {
            return this.videoStates[reelId]?.currentTime || 0;
        },
        
        getDuration(reelId) {
            return this.videoDurations[reelId] || 0;
        },
        
        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },
        
        seekVideo(event, reelId) {
            const video = document.getElementById(`video-${reelId}`);
            if (!video) return;
            
            const rect = event.currentTarget.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const percentage = (clickX / rect.width) * 100;
            
            video.currentTime = (percentage / 100) * video.duration;
        },
        
        skipForward(reelId) {
            const video = document.getElementById(`video-${reelId}`);
            if (!video) return;
            
            video.currentTime = Math.min(video.currentTime + 10, video.duration);
        },
        
        skipBackward(reelId) {
            const video = document.getElementById(`video-${reelId}`);
            if (!video) return;
            
            video.currentTime = Math.max(video.currentTime - 10, 0);
        },
        
        togglePlayPause(reelId) {
            const video = document.getElementById(`video-${reelId}`);
            if (!video) return;
            
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        },
        
        isPlaying(reelId) {
            const video = document.getElementById(`video-${reelId}`);
            return video && !video.paused;
        },
        
        editReel(reel) {
            this.editingReel = { ...reel };
            this.showEditModal = true;
        },
        
        async updateReelCaption() {
            if (!this.editingReel) return;
            
            try {
                const response = await fetch(`/admin/view/reels/${this.editingReel.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        title: this.editingReel.title,
                        description: this.editingReel.description
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    const reelIndex = this.allReels.findIndex(r => r.id === this.editingReel.id);
                    if (reelIndex !== -1) {
                        this.allReels[reelIndex].title = this.editingReel.title;
                        this.allReels[reelIndex].description = this.editingReel.description;
                    }
                    
                    const filteredReelIndex = this.filteredReels.findIndex(r => r.id === this.editingReel.id);
                    if (filteredReelIndex !== -1) {
                        this.filteredReels[filteredReelIndex].title = this.editingReel.title;
                        this.filteredReels[filteredReelIndex].description = this.editingReel.description;
                    }
                    
                    if (this.selectedReel && this.selectedReel.id === this.editingReel.id) {
                        this.selectedReel.title = this.editingReel.title;
                        this.selectedReel.description = this.editingReel.description;
                    }
                    
                    // alert('✅ تم تحديث الكابشن بنجاح');
                    this.showEditModal = false;
                } else {
                    // alert('❌ حدث خطأ في التحديث: ' + (result.message || 'خطأ غير معروف'));
                }
            } catch (error) {
                console.error('❌ Error updating reel:', error);
                // alert('❌ حدث خطأ في الاتصال: ' + error.message);
            }
        },
        
        deleteReel(reelId) {
            // Find the reel to delete
            const reel = this.filteredReels.find(r => r.id === reelId);
            if (reel) {
                this.deletingReel = reel;
                this.showDeleteModal = true;
            }
        },
        
        async fetchInitialReels() {
            console.log('🔄 Fetching initial reels from API...');
            this.loading = true;
            
            try {
                const response = await fetch(`/admin/view/reels/load-more?limit=15`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        exclude_ids: []
                    })
                });
                
                const data = await response.json();
                
                if (data.reels && data.reels.length > 0) {
                    console.log(`✅ Fetched ${data.reels.length} reels from API`);
                    window.initialReelsData = data.reels;
                    this.loadInitialData();
                } else {
                    console.log('⚠️ No reels found from API');
                    this.reelsLoaded = true;
                    this.hasMore = false;
                }
            } catch (error) {
                console.error('❌ Error fetching initial reels:', error);
                this.reelsLoaded = true;
                this.hasMore = false;
            } finally {
                this.loading = false;
            }
        },
        
        async confirmDelete() {
            if (!this.deletingReel) return;
            
            const reelId = this.deletingReel.id;
            this.showDeleteModal = false;
            
            try {
                const response = await fetch(`/admin/view/reels/${reelId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    this.allReels = this.allReels.filter(r => r.id !== reelId);
                    this.filteredReels = this.filteredReels.filter(r => r.id !== reelId);
                    
                    if (this.selectedReelId === reelId) {
                        this.showInteractionPanel = false;
                        this.selectedReelId = null;
                        this.selectedReel = null;
                    }
                    
                    this.deletingReel = null;
                    
                    // Success notification
                    // alert('✅ تم حذف الريل بنجاح');
                } else {
                    this.deletingReel = null;
                    alert('❌ حدث خطأ في الحذف: ' + (result.message || 'خطأ غير معروف'));
                }
            } catch (error) {
                console.error('❌ Error deleting reel:', error);
                this.deletingReel = null;
                alert('❌ حدث خطأ في الاتصال: ' + error.message);
            }
        }
    }
}

if (typeof window !== 'undefined') {
    window.reelsManager = reelsManager;
}
