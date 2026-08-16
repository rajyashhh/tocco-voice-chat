    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" as="style">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" media="print" onload="this.media='all'">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Reels Styles -->
    <style>
        @include('reals::admin.reels.partials.styles')

    </style>
    @php
        $reelsManagerFile = public_path('modules/reals/js/reels-manager.js');
        $reelsManagerVersion = file_exists($reelsManagerFile) ? filemtime($reelsManagerFile) : time();
        $storageBase = rtrim(substr(\App\Helpers\StorageHelper::url('__base__'), 0, -strlen('__base__')), '/');
    @endphp
    <script data-exec-on-popstate>
        window.initialReelsData = @json($reels);
        window.reelsSeed = {{ $seed }};
    </script>
    <script data-exec-on-popstate src="{{ asset('modules/reals/js/reels-manager.js') }}?v={{ $reelsManagerVersion }}" data-reels-manager="true"></script>
    <script data-exec-on-popstate>
        (function () {
            const updateNavbarLayout = () => {
                const nav = document.querySelector('.navbar');
                const isMobile = window.innerWidth <= 768;

                if (nav) {
                    if (isMobile) {
                        nav.classList.remove('navbar-hidden');
                        nav.style.display = '';
                    } else {
                        nav.classList.add('navbar-hidden');
                    }
                }

                const hasHidden = nav && nav.classList.contains('navbar-hidden');
                document.querySelectorAll('.app-class').forEach((el) => {
                    if (window.innerWidth <= 768) {
                        el.style.setProperty('margin-top', '50px', 'important');
                    } else {
                        el.style.setProperty('margin-top', hasHidden ? '0' : '7%', 'important');
                    }
                });
            };

            const initNavbarTweaks = () => {
                updateNavbarLayout();

                if (!window.__reelsNavbarResizeBound) {
                    window.addEventListener('resize', updateNavbarLayout, { passive: true });
                    window.__reelsNavbarResizeBound = true;
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initNavbarTweaks, { once: true });
            } else {
                initNavbarTweaks();
            }

            const ensureAdminGrid = () => {
                if (window.$ && $.admin) {
                    if (!$.admin.grid) {
                        $.admin.grid = {
                            selects: {},
                            select(id) {
                                this.selects[id] = id;
                            },
                            unselect(id) {
                                delete this.selects[id];
                            },
                            selected() {
                                return Object.keys(this.selects);
                            },
                        };
                    }
                }
            };

            ensureAdminGrid();
            if (!window.__reelsEnsureAdminGridBound) {
                document.addEventListener('pjax:complete', ensureAdminGrid);
                window.__reelsEnsureAdminGridBound = true;
            }
        })();
    </script>
    <style>
                .content-header,
        .skin-black-light .content-header,
        body .content-header,
        .wrapper .content-header {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            min-height: 0 !important;
            max-height: 0 !important;
            overflow: hidden !important;
            padding: 0 !important;
            margin: 0 !important;
            opacity: 0 !important;
        }
    </style>

    <!-- مؤشر التحميل المسبق للموبايل -->
    <style>
        .preload-indicator {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }
        
        .preload-indicator .spinner {
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

<div class="reels-main-container flex"
     :class="{'panel-open': showInteractionPanel}"
     x-data="reelsManager()" x-cloak>
     
    <!-- مؤشر التحميل في الخلفية -->
    <div x-show="isPreloading && isMobile" 
         class="preload-indicator"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="spinner"></div>
        <span>جاري التحميل في الخلفية...</span>
    </div>
    
    <!-- Mobile Overlay Background -->
    <div class="mobile-sidebar-overlay"
         :class="{ 'active': isMobileSidebarOpen }"
         @click="closeMobileSidebar()"
         x-show="isMobileSidebarOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    <!-- Mobile Toggle Button -->
    <button class="mobile-reels-toggle md:hidden"
            :class="{ 'active': isMobileSidebarOpen }"
            @click="toggleMobileSidebar()">
        <i class="fas" :class="isMobileSidebarOpen ? 'fa-times' : 'fa-list'"></i>
        <!-- Badge for reels count -->
        <span x-show="!isMobileSidebarOpen && filteredReels.length > 0"
              class="absolute -top-1 -left-1 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center"
              x-text="filteredReels.length"></span>
    </button>

    <!-- Reels List (Sidebar) -->
    <div class="reels-sidebar border-gray-200 shadow-lg flex flex-col"
         :class="{ 'mobile-open': isMobileSidebarOpen }">
        <!-- Search Filter -->
        <div class="p-4 border-b relative">
            <!-- Close Button for Mobile -->
            <button @click="closeMobileSidebar()"
                    class="md:hidden absolute top-3 left-3 w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="text-lg font-bold mb-3 flex items-center">
                <i class="fas fa-film ml-2" style="margin: 7px 10px;"></i>
                {{ __('reels_admin.list_title') }}
            </h2>

            <!-- Search Box -->
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       @input="filterReels()"
                       placeholder="{{ __('reels_admin.search_placeholder') }}"
                       class="w-full px-3 py-2 ps-10 rounded-md bg-white text-gray-800 text-sm focus:outline-none border border-white/30"
                       style="box-shadow: 0 0 0 1px black;">
                <i class="fas fa-search absolute start-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-sm"></i>
            </div>

            <div class="flex items-center justify-between mt-2">
                <p class="text-sm opacity-90" x-text="filteredReels.length + ' {{ __('reels_admin.reel_label') }}'"></p>
                <button @click="loadMoreReels()"
                        x-show="hasMore && !loading"
                        class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded">
                    <i class="fas fa-sync-alt ml-1"></i>
                    {{ __('reels_admin.refresh') }}
                </button>
            </div>
        </div>

        <!-- Reels Grid with Scroll -->
        <div class="flex-1 overflow-y-auto sidebarContainer"
             x-ref="sidebarContainer"
             @scroll="handleSidebarScroll()"
             style="height: calc(100% - 130px);">
            <!-- Skeleton Loader for Initial Load -->
            <template x-if="!reelsLoaded && filteredReels.length === 0">
                <div class="grid grid-cols-3 gap-2 p-2">
                    <template x-for="i in 9" :key="i">
                        <div class="rounded-md overflow-hidden shadow">
                            <div class="relative bg-gray-200 dark:bg-gray-700 skeleton" style="padding-bottom: 177.78%;"></div>
                        </div>
                    </template>
                </div>
            </template>

            <div class="grid grid-cols-3 gap-2 p-2" x-show="reelsLoaded || filteredReels.length > 0">
                <template x-for="reel in filteredReels" :key="reel.id">
                    <div @click="selectReel(reel.id); closeMobileSidebar()"
                         :data-reel-id="reel.id"
                         :class="selectedReelId === reel.id ? 'ring-2 reel-ring shadow-lg' : ''"
                         class="cursor-pointer rounded-md overflow-hidden shadow hover:shadow-md transition relative group fade-in">
                        <div class="relative bg-gray-200 dark:bg-gray-700" style="padding-bottom: 177.78%; /* 16:9 ratio */">
                            <!-- Skeleton until image loads -->
                            <div class="absolute inset-0 skeleton" x-show="!reel.thumbnailLoaded"></div>

                            <img :src="reel.thumbnail_url"
                                 :alt="reel.title"
                                 class="absolute inset-0 w-full h-full object-cover"
                                 x-show="reel.thumbnailLoaded"
                                 x-on:load="onThumbnailLoad(reel, $event)"
                                 x-on:error="onThumbnailError(reel, $event)">

                            <!-- User Info Overlay on Hover -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs border border-white/50 flex-shrink-0" style="background: var(--primary-gradient);">
                                        <span x-text="reel.user?.name?.charAt(0)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-white text-xs font-bold truncate" x-text="reel.user?.name"></p>
                                        <p class="text-white/70 text-[10px]" x-text="'{{ __('reels_admin.id_label') }}' + reel.user?.id"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-white text-[10px]">
                                    <span class="flex items-center">
                                        <i class="fas fa-eye ml-1"></i>
                                        <span x-text="formatNumber(reel.views_count)"></span>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-heart ml-1"></i>
                                        <span x-text="formatNumber(reel.likes_count)"></span>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-comment ml-1"></i>
                                        <span x-text="formatNumber(reel.comments_count)"></span>
                                    </span>
                                </div>
                            </div>

                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-1.5 group-hover:opacity-0 transition-opacity">
                                <p class="text-white text-xs font-semibold truncate" x-text="reel.title"></p>
                                <div class="flex items-center gap-2 text-white text-xs mt-0.5">
                                    <span class="flex items-center">
                                        <i class="fas fa-eye ml-1"></i>
                                        <span x-text="formatNumber(reel.views_count)"></span>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-heart ml-1"></i>
                                        <span x-text="formatNumber(reel.likes_count)"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Sidebar Loading Indicator -->
            <div x-show="loading" class="p-3 text-center">
                <div class="inline-flex items-center space-x-2 space-x-reverse px-3 py-2 rounded-md" style="background-color: var(--primary-hover-alpha);">
                    <div class="animate-spin rounded-full h-4 w-4 border-t-2 border-b-2" style="border-color: var(--primary-color);"></div>
                    <span class="text-xs font-semibold" style="color: var(--primary-color);">{{ __('reels_admin.loading') }}</span>
                </div>
            </div>

            <!-- Load More Button -->
            <div x-show="hasMore && !loading && filteredReels.length > 0" class="p-2">
                <button @click="loadMoreReels()"
                        class="w-full py-2 text-white rounded-md font-semibold text-sm transition shadow"
                        style="background-color: var(--primary-color);"
                        onmouseover="this.style.backgroundColor='{{ config('themes.secondaryColor') }}'"
                        onmouseout="this.style.backgroundColor='{{ config('themes.primaryColor') }}'">
                    <i class="fas fa-plus ml-1"></i>
                    {{ __('reels_admin.load_more') }}
                    <span class="text-xs opacity-90 mr-2">
                        (<span x-text="filteredReels.length"></span>)
                    </span>
                </button>
            </div>

            <!-- No More Message -->
            <div x-show="!hasMore && filteredReels.length > 0" class="p-3 text-center text-gray-500 text-xs">
                <i class="fas fa-check-circle mb-1 text-green-500"></i>
                <p class="font-semibold">{{ __('reels_admin.all_loaded') }}</p>
            </div>
        </div>
    </div>

    <!-- Main Video Player (Center) -->
    <div class="reels-video-container overflow-y-auto snap-y snap-mandatory"
         style="-webkit-overflow-scrolling: touch; scroll-behavior: smooth;"
         x-ref="reelsContainer"
         @scroll.passive="handleScroll()">
        <template x-for="(reel, index) in (visibleReels || [])" :key="reel.id">
            <div class="video-item-height snap-start flex items-center justify-center relative"
                 :data-reel-id="reel.id"
                 :data-index="index">
                <div class="w-full max-w-2xl h-full relative">
                    <!-- Video Container -->
                    <div class="video-container relative h-full bg-black flex items-center justify-center">
                        <!-- Skeleton Loader while video loading -->
                        <template x-if="!shouldLoadVideo(index)">
                            <div class="w-full h-full flex items-center justify-center bg-gray-900 dark:bg-gray-950">
                                <div class="text-center text-white/50">
                                    <div class="skeleton skeleton-circle w-20 h-20 mx-auto mb-4 bg-gray-700 dark:bg-gray-800"></div>
                                    <div class="skeleton skeleton-text w-32 mx-auto bg-gray-700 dark:bg-gray-800"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Lazy load video only when needed -->
                        <template x-if="shouldLoadVideo(index)">
                            <div class="w-full h-full relative">
                                <!-- Loading skeleton while video loads -->
                                <div x-show="!isVideoReady(reel.id)" class="absolute inset-0 flex items-center justify-center bg-gray-900 dark:bg-gray-950">
                                    <div class="text-center text-white/50">
                                        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-purple-500 dark:border-purple-400 mx-auto mb-4"></div>
                                        <p class="text-sm">{{ __('reels_admin.video_loading') }}</p>
                                    </div>
                                </div>

                                <video :src="reel.video_url"
                                       :id="'video-' + reel.id"
                                       class="w-full h-full object-contain"
                                       x-show="isVideoReady(reel.id)"
                                       loop
                                       :muted="isGlobalMuted"
                                       :preload="index === currentVideoIndex ? 'auto' : 'metadata'"
                                       :poster="reel.thumbnail_url || null"
                                       playsinline
                                       webkit-playsinline
                                       x-ref="video"
                                       @click.stop="togglePlay($event)"
                                       @touchstart.stop
                                       @loadedmetadata="updateProgress($event); onVideoLoaded($event, reel.id)"
                                       @canplay="markVideoReady(reel.id)"
                                       @timeupdate.throttle.500ms="updateProgress($event)"
                                       @play="updateProgress($event)"
                                       @pause="updateProgress($event)"
                                       @ended="onVideoEnded($event)">
                                </video>
                            </div>
                        </template>

                        <!-- Loading Spinner -->
                        <div class="loading-spinner absolute inset-0 flex items-center justify-center bg-black/50 hidden">
                            <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4" style="border-color: var(--primary-color);"></div>
                        </div>

                        <!-- User Profile Overlay (Top) -->
                        <div class="absolute top-4 right-4 left-4 flex items-center justify-between z-10">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg border-2 border-white shadow-lg" style="margin: 10px; background: var(--primary-gradient);">
                                    <span x-text="reel.user?.name?.charAt(0)"></span>
                                </div>
                                <div class="mr-3">
                                    <p class="text-white font-bold text-lg drop-shadow-lg" x-text="reel.user?.name"></p>
                                    <p class="text-white/80 text-sm drop-shadow" x-text="'{{ __('reels_admin.id_label') }}' + reel.user?.id"></p>
                                </div>
                            </div>

                            <!-- Admin Actions -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click.stop="open = !open"
                                        class="w-10 h-10 backdrop-blur-md rounded-full flex items-center justify-center text-white transition shadow-lg"
                                        style="background-color: var(--primary-color);"
                                        onmouseover="this.style.backgroundColor='{{ config('themes.secondaryColor') }}'"
                                        onmouseout="this.style.backgroundColor='{{ config('themes.primaryColor') }}'">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="open"
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute mt-2 w-44 rounded-lg shadow-2xl z-50 overflow-hidden border-2"
                                     style="left: -88px; background: var(--primary-color); border-color: var(--primary-color);">
                                    <button @click.stop="editReel(reel); open = false"
                                            class="w-full px-4 py-3 text-right flex items-center gap-3 transition text-white"
                                            style="background-color: rgba(255, 255, 255, 0.05);"
                                            onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.15)'"
                                            onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.05)'">
                                        <i class="fas fa-edit text-white"></i>
                                        <span class="font-semibold">{{ __('reels_admin.edit') }}</span>
                                    </button>
                                    <div class="border-t" style="border-color: rgba(255, 255, 255, 0.2);"></div>
                                    <button @click.stop="deleteReel(reel.id); open = false"
                                            class="w-full px-4 py-3 text-right flex items-center gap-3 transition text-white"
                                            style="background-color: rgba(239, 68, 68, 0.1);"
                                            onmouseover="this.style.backgroundColor='rgba(239, 68, 68, 0.25)'"
                                            onmouseout="this.style.backgroundColor='rgba(239, 68, 68, 0.1)'">
                                        <i class="fas fa-trash text-red-300"></i>
                                        <span class="font-semibold">{{ __('reels_admin.delete') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Video Info Overlay (Bottom) -->
                        <div class="absolute bottom-20 right-4 left-4 z-10">
                            <h3 class="text-white font-bold text-xl drop-shadow-lg mb-2" x-text="reel.title"></h3>
                            <p class="text-white/90 text-sm drop-shadow-lg" x-text="reel.description"></p>
                            <div class="flex items-center mt-3 text-white text-sm">
                                <i class="fas fa-eye ml-2"></i>
                                <span x-text="formatNumber(reel.views_count) + ' {{ __('reels_admin.views_label') }}'"></span>
                            </div>
                        </div>

                        <!-- Progress Bar with Mute Button -->
                        <div class="absolute bottom-4 left-4 right-4 z-20">
                            <div class="flex items-center gap-3">
                                <!-- Mute/Unmute Button -->
                                <button @click.stop="toggleMute(reel.id)"
                                        data-mute-btn="true"
                                        :style="isMuted(reel.id) ? 'background-color: #ef4444cc;' : 'background-color: rgba(255, 255, 255, 0.2);'"
                                        class="w-10 h-10 hover:bg-white/30 rounded-full backdrop-blur-md flex items-center justify-center text-white transition transform hover:scale-110 shadow-xl flex-shrink-0">
                                    <i :class="isMuted(reel.id) ? 'fa-volume-mute' : 'fa-volume-up'" class="fas text-lg"></i>
                                </button>

                                <!-- Progress Bar with Time -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-white text-xs mb-1 px-1">
                                        <span x-text="formatTime(getCurrentTime(reel.id))">0:00</span>
                                        <span x-text="formatTime(getDuration(reel.id))">0:00</span>
                                    </div>
                                    <div class="bg-white/20 backdrop-blur-sm rounded-full h-2 cursor-pointer"
                                         @click="seekVideo($event, reel.id)">
                                        <div class="h-full rounded-full transition-all duration-100"
                                             :style="'width: ' + getProgress(reel.id) + '%; background-color: var(--primary-color);'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Interaction Buttons (Dynamic Position Based on Language) -->
                        <div class="absolute bottom-24 flex flex-col gap-4 z-20
                                    {{ app()->getLocale() == 'ar' ? 'right-4 md:right-auto md:left-4' : 'left-4 md:left-auto md:right-4' }}">
                            <!-- Likes Button -->
                            <button @click.stop="toggleInteraction('likes', reel.id)"
                                    :class="activeTab === 'likes' && selectedReelId === reel.id ? 'scale-110' : 'hover:bg-white/30'"
                                    :style="activeTab === 'likes' && selectedReelId === reel.id ? 'background-color: var(--primary-color);' : 'background-color: rgba(255, 255, 255, 0.2);'"
                                    class="w-14 h-14 rounded-full backdrop-blur-md flex flex-col items-center justify-center text-white transition transform hover:scale-110 shadow-xl">
                                <i class="fas fa-heart text-xl"></i>
                                <span class="text-xs mt-1 font-semibold" x-text="formatNumber(reel.likes_count)"></span>
                            </button>

                            <!-- Comments Button -->
                            <button @click.stop="toggleInteraction('comments', reel.id)"
                                    :class="activeTab === 'comments' && selectedReelId === reel.id ? 'scale-110' : 'hover:bg-white/30'"
                                    :style="activeTab === 'comments' && selectedReelId === reel.id ? 'background-color: var(--primary-color);' : 'background-color: rgba(255, 255, 255, 0.2);'"
                                    class="w-14 h-14 rounded-full backdrop-blur-md flex flex-col items-center justify-center text-white transition transform hover:scale-110 shadow-xl">
                                <i class="fas fa-comment text-xl"></i>
                                <span class="text-xs mt-1 font-semibold" x-text="formatNumber(reel.comments_count)"></span>
                            </button>

                            <!-- Gifts Button -->
                            <button @click.stop="toggleInteraction('gifts', reel.id)"
                                    :class="activeTab === 'gifts' && selectedReelId === reel.id ? 'scale-110' : 'hover:bg-white/30'"
                                    :style="activeTab === 'gifts' && selectedReelId === reel.id ? 'background-color: var(--secondary-color);' : 'background-color: rgba(255, 255, 255, 0.2);'"
                                    class="w-14 h-14 rounded-full backdrop-blur-md flex flex-col items-center justify-center text-white transition transform hover:scale-110 shadow-xl">
                                <i class="fas fa-gift text-xl"></i>
                                <span class="text-xs mt-1 font-semibold" x-text="formatNumber(reel.gifts_count)"></span>
                            </button>


                        </div>

                        <!-- Scroll Indicator -->
                        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 text-white/60 animate-bounce z-10"
                             x-show="index < filteredReels.length - 1">
                            <i class="fas fa-chevron-down text-2xl"></i>
                        </div>

                        <!-- Loading Indicator -->
                        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-10"
                             x-show="loading && index === filteredReels.length - 1">
                            <div class="flex items-center space-x-2 space-x-reverse bg-black/70 backdrop-blur-md px-4 py-2 rounded-full">
                                <div class="animate-spin rounded-full h-5 w-5 border-t-2 border-b-2 border-white"></div>
                                <span class="text-white text-sm">{{ __('reels_admin.loading') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- End of List Message -->
        <template x-if="!hasMore && filteredReels.length > 0">
            <div class="h-32 flex items-center justify-center ">
                <div class="text-center text-white/60">
                    <i class="fas fa-check-circle text-3xl mb-2"></i>
                    <p>{{ __('reels_admin.no_more_reels') }}</p>
                    <p class="text-sm mt-2">{{ __('reels_admin.total') }} <span x-text="filteredReels.length"></span> {{ __('reels_admin.reel_label') }}</p>
                </div>
            </div>
        </template>

        <!-- Manual Load More Button -->
        <template x-if="hasMore && filteredReels.length > 0 && !loading">
            <div class="h-32 flex items-center justify-center ">
                <button @click="loadMoreReels()"
                        class="px-6 py-3 text-white rounded-full font-semibold transition shadow-lg"
                        style="background-color: var(--primary-color);"
                        onmouseover="this.style.backgroundColor='{{ config('themes.secondaryColor') }}'"
                        onmouseout="this.style.backgroundColor='{{ config('themes.primaryColor') }}'">
                    <i class="fas fa-arrow-down ml-2"></i>
                    {{ __('reels_admin.load_more') }}
                    <span class="text-sm block mt-1">(<span x-text="filteredReels.length"></span> {{ __('reels_admin.out_of_max') }})</span>
                </button>
            </div>
        </template>
    </div>


    <!-- Interactions Panel (Right Side) - Opens only when clicking interaction buttons -->
    <div class="interactions-panel"
         :class="{'show': showInteractionPanel}"
         x-show="showInteractionPanel">

        <!-- Close Button (works on all screens) -->
        <button @click.stop="showInteractionPanel = false"
                class="absolute top-4 right-4 ltr:right-4 rtl:left-4 w-10 h-10 sm:w-12 sm:h-12 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center text-white shadow-2xl z-[100] transition transform hover:scale-110 cursor-pointer">
            <i class="fas fa-times text-lg sm:text-xl pointer-events-none"></i>
        </button>

        <!-- Tabs Header -->
        <div class="p-3 sm:p-4 pt-5 sm:pt-6" style="background: var(--primary-gradient);">
            <!-- Refresh Button -->
            <!-- <button @click="refreshReelCounts(selectedReelId)"
                    class="absolute top-16 left-4 w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition text-white">
                <i class="fas fa-sync-alt text-sm"></i>
            </button> -->

            <div class="flex space-x-1 space-x-reverse">
                <button @click="activeTab = 'likes'; loadTabData()"
                        :class="activeTab === 'likes'? 'bg-white tab-active': 'bg-white/20 text-white hover:bg-white/30'"
                        class="flex-1 py-2 sm:py-3 px-2 sm:px-4 rounded-lg text-sm sm:text-base font-semibold transition">
                    <i class="fas fa-heart ms-1 sm:ms-2"></i>
                    <span class="hidden sm:inline">{{ __('reels_admin.likes') }}</span>
                    <span class="sm:hidden">{{ __('reels_admin.like') }}</span>
                    <span x-show="selectedReel"
                          class="block text-xs sm:text-sm mt-1"
                          x-text="selectedReel?.likes_count || 0"></span>
                </button>

                <button @click="activeTab = 'comments'; loadTabData()"
                        :class="activeTab === 'comments'? 'bg-white tab-active': 'bg-white/20 text-white hover:bg-white/30'"
                        class="flex-1 py-2 sm:py-3 px-2 sm:px-4 rounded-lg text-sm sm:text-base font-semibold transition">
                    <i class="fas fa-comment ms-1 sm:ms-2"></i>
                    <span class="hidden sm:inline">{{ __('reels_admin.comments') }}</span>
                    <span class="sm:hidden">{{ __('reels_admin.comment') }}</span>
                    <span x-show="selectedReel"
                          class="block text-xs sm:text-sm mt-1"
                          x-text="selectedReel?.comments_count || 0"></span>
                </button>

                <button @click="activeTab = 'gifts'; loadTabData()"
                        :class="activeTab === 'gifts'? 'bg-white tab-active': 'bg-white/20 text-white hover:bg-white/30'"
                        class="flex-1 py-2 sm:py-3 px-2 sm:px-4 rounded-lg text-sm sm:text-base font-semibold transition">
                    <i class="fas fa-gift ms-1 sm:ms-2"></i>
                    <span class="hidden sm:inline">{{ __('reels_admin.gifts') }}</span>
                    <span class="sm:hidden">{{ __('reels_admin.gift') }}</span>
                    <span x-show="selectedReel"
                          class="block text-xs sm:text-sm mt-1"
                          x-text="selectedReel?.gifts_count || 0"></span>
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="h-[calc(100vh-120px)] sm:h-[calc(100vh-140px)] overflow-y-auto p-3 sm:p-4">
            <!-- Likes Tab -->
            <div x-show="activeTab === 'likes'">
                <template x-if="likes.length > 0">
                    <div class="space-y-3">
                        <template x-for="like in likes" :key="like.id">
                            <div class="user-card flex items-start p-3 rounded-lg transition hover:shadow-md"
                                 style="background-color: var(--off-white); border: 1px solid var(--primary-hover-alpha);">
                                <img :src="like.user?.profile?.avatar ? '{{ $storageBase }}/' + like.user.profile.avatar : '{{ $storageBase }}/images/businessman-icon.jpg'"
                                     class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm"
                                     :alt="like.user?.name">
                                <div class="ms-3 flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <strong class="text-base font-bold truncate"
                                                x-text="like.user?.name"></strong>
                                        <img x-show="like.user?.country?.flag"
                                             :src="like.user?.country?.flag ? '{{ $storageBase }}/' + like.user.country.flag : ''"
                                             class="w-5 h-auto"
                                             :title="like.user?.country?.name"
                                             style="vertical-align: middle;">
                                    </div>
                                    <p class="text-xs mb-1">
                                        <span class="font-medium">{{ __('reels_admin.uid_label') }}</span>
                                        <span x-text="like.user?.original_uuid || like.user?.id"></span>
                                    </p>
                                    <p class="text-xs">
                                        <span class="font-medium">{{ __('reels_admin.special_label') }}</span>
                                        <span x-text="like.user?.uuid"></span>
                                    </p>
                                    <p class="text-xs mt-2" x-text="formatDate(like.created_at)"></p>
                                </div>
                                <i class="fas fa-heart text-red-500 text-xl" style="color: red !important;"></i>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="likes.length === 0 && selectedReel">
                    <div class="text-center py-12">
                        <i class="fas fa-heart text-5xl mb-3 opacity-30"></i>
                        <p>{{ __('reels_admin.no_likes') }}</p>
                    </div>
                </template>
            </div>

            <!-- Comments Tab -->
            <div x-show="activeTab === 'comments'">
                <template x-if="comments.length > 0">
                    <div class="space-y-3">
                        <template x-for="comment in comments" :key="comment.id">
                            <div class="user-card rounded-lg p-4 transition hover:shadow-md">
                                <div class="flex items-start gap-3">
                                    <img :src="comment.user?.profile?.avatar ? '{{ $storageBase }}/' + comment.user.profile.avatar : '{{ $storageBase }}/images/businessman-icon.jpg'"
                                         class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0"
                                         :alt="comment.user?.name">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <strong class="text-base font-bold truncate"
                                                    x-text="comment.user?.name"></strong>
                                            <img x-show="comment.user?.country?.flag"
                                                 :src="comment.user?.country?.flag ? '{{ $storageBase }}/' + comment.user.country.flag : ''"
                                                 class="w-5 h-auto"
                                                 :title="comment.user?.country?.name"
                                                 style="vertical-align: middle;">
                                        </div>
                                        <p class="text-xs text-gray-600 mb-1">
                                                <span class="font-medium">{{ __('reels_admin.uid_label') }}</span>
                                                <span x-text="comment.user?.original_uuid || comment.user?.id"></span>
                                            </p>
                                            <p class="text-xs text-gray-500 mb-2">
                                                <span class="font-medium">{{ __('reels_admin.special_label') }}</span>
                                                <span x-text="comment.user?.uuid"></span>
                                            </p>
                                        <p class="p-2 rounded text-sm"
                                           style="background-color: var(--dark-primary-colo);"
                                           x-text="comment.comment"></p>
                                        <p class="text-xs mt-2" x-text="formatDate(comment.created_at)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="comments.length === 0 && selectedReel">
                    <div class="text-center py-12">
                        <i class="fas fa-comment text-5xl mb-3 opacity-30"></i>
                        <p>{{ __('reels_admin.no_comments') }}</p>
                    </div>
                </template>
            </div>

            <!-- Gifts Tab -->
            <div x-show="activeTab === 'gifts'">
                <template x-if="gifts.length > 0">
                    <div class="space-y-3">
                        <template x-for="gift in gifts" :key="gift.id">
                            <div class="user-card rounded-lg p-4 transition hover:shadow-md">
                                <div class="flex items-start gap-3">
                                    <!-- صورة المستخدم -->
                                    <img :src="gift.user?.profile?.avatar ? '{{ $storageBase }}/' + gift.user.profile.avatar : '{{ $storageBase }}/images/businessman-icon.jpg'"
                                         class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0"
                                         :alt="gift.user?.name">

                                    <!-- معلومات المستخدم -->
                                    <div class="flex-1 min-w-0 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <strong class="text-base font-bold truncate"
                                                    x-text="gift.user?.name"></strong>
                                            <img x-show="gift.user?.country?.flag"
                                                 :src="gift.user?.country?.flag ? '{{ $storageBase }}/' + gift.user.country.flag : ''"
                                                 class="w-5 h-auto"
                                                 :title="gift.user?.country?.name"
                                                 style="vertical-align: middle;">
                                        </div>
                                        <p class="text-xs text-gray-600 mb-1">
                                            <span class="font-medium">{{ __('reels_admin.uid_label') }}</span>
                                            <span x-text="gift.user?.original_uuid || gift.user?.id"></span>
                                        </p>
                                        <p class="text-xs text-gray-500 mb-2">
                                            <span class="font-medium">{{ __('reels_admin.special_label') }}</span>
                                            <span x-text="gift.user?.uuid"></span>
                                        </p>

                                        <!-- معلومات الهدية -->
                                        <div class="mt-2 flex items-center gap-2 p-2 rounded-lg"
                                             style="background-color: var(--off-white); border: 1px solid var(--primary-hover-alpha);">
                                            <div class="text-3xl" x-text="getGiftEmoji(gift.gift_type)"></div>
                                            <div class="flex-1">
                                                <p class="text-sm font-semibold"
                                                   style="color: var(--text-primary-color);"
                                                   x-text="getGiftName(gift.gift_type)"></p>
                                                <p class="text-xs text-gray-500">{{ __('reels_admin.type_label') }} <span x-text="gift.gift_type"></span></p>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-xl font-bold" style="color: var(--secondary-color);" x-text="gift.gift_value"></span>
                                                <p class="text-xs">{{ __('reels_admin.point_label') }}</p>
                                            </div>
                                        </div>

                                        <p class="text-xs mt-2" x-text="formatDate(gift.created_at)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="gifts.length === 0 && selectedReel">
                    <div class="text-center py-12">
                        <i class="fas fa-gift text-5xl mb-3 opacity-30"></i>
                        <p>{{ __('reels_admin.no_gifts') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Overlay (only on small screens when panel is open) -->
    <div class="  inset-0  z-40"
         x-show="showInteractionPanel"
         @click.stop="showInteractionPanel = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
         x-show="showDeleteModal"
         @click.self="showDeleteModal = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-show="showDeleteModal"
             @click.stop
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90">

            <div class="flex flex-col items-center text-center">
                <!-- Icon -->
                <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-trash-alt text-4xl text-red-600 dark:text-red-400"></i>
                </div>

                <!-- Title -->
                <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                    {{ __('reels_admin.delete_title') }}
                </h3>

                <!-- Message -->
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    {{ __('reels_admin.delete_message') }}<br>
                    <span class="text-sm text-red-600 font-semibold">{{ __('reels_admin.delete_warning') }}</span>
                </p>

                <!-- Reel Info -->
                <template x-if="deletingReel">
                    <div class="w-full bg-gray-50 dark:bg-gray-700 rounded-lg p-3 mb-6">
                        <div class="flex items-center gap-3">
                            <img :src="deletingReel.thumbnail_url"
                                 class="w-16 h-16 rounded-lg object-cover">
                            <div class="flex-1 text-right">
                                <p class="font-semibold text-gray-800 dark:text-gray-100 truncate" x-text="deletingReel.title"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="deletingReel.user?.name"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Buttons -->
                <div class="flex gap-3 w-full">
                    <button @click="showDeleteModal = false"
                            class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-bold transition">
                        <i class="fas fa-times ml-2"></i>
                        {{ __('reels_admin.cancel') }}
                    </button>
                    <button @click="confirmDelete()"
                            class="flex-1 py-3 bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded-lg font-bold transition shadow-lg">
                        <i class="fas fa-trash ml-2"></i>
                        {{ __('reels_admin.delete_forever') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
         x-show="showEditModal"
         @click.self="showEditModal = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg p-6"
             x-show="showEditModal"
             @click.stop
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    <i class="fas fa-edit ml-2" style="color: var(--primary-color);"></i>
                    {{ __('reels_admin.edit_caption_title') }}
                </h3>
                <button @click="showEditModal = false"
                        class="w-10 h-10 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-full flex items-center justify-center transition">
                    <i class="fas fa-times text-gray-600 dark:text-gray-300"></i>
                </button>
            </div>

            <template x-if="editingReel">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('reels_admin.title_label') }}</label>
                        <input type="text"
                               x-model="editingReel.title"
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg focus:outline-none transition"
                               style="border-color: var(--primary-color) !important;"
                               placeholder="{{ __('reels_admin.title_placeholder') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('reels_admin.description_label') }}</label>
                        <textarea x-model="editingReel.description"
                                  rows="4"
                                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg focus:outline-none transition resize-none"
                                  style="border-color: var(--primary-color) !important;"
                                  placeholder="{{ __('reels_admin.description_placeholder') }}"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button @click="updateReelCaption()"
                                class="flex-1 py-3 text-white rounded-lg font-bold transition shadow-lg"
                                style="background: var(--primary-gradient);"
                                onmouseover="this.style.opacity='0.9'"
                                onmouseout="this.style.opacity='1'">
                            <i class="fas fa-check ml-2"></i>
                            {{ __('reels_admin.save_changes') }}
                        </button>
                        <button @click="showEditModal = false"
                                class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-bold transition">
                            <i class="fas fa-times ml-2"></i>
                            {{ __('reels_admin.cancel') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Mobile Reels List Toggle Button -->
    <button @click="toggleMobileSidebar()"
            class="md:hidden fixed top-16 right-3 z-[60] group">
        <div class="relative">
            <!-- Main Button -->
            <!-- <div class="w-14 h-14 rounded-2xl shadow-xl flex items-center justify-center transform transition-all duration-300 group-hover:scale-110" style="background: var(--primary-gradient);">
                <i class="fas fa-film text-white text-xl"></i>
            </div> -->
            <!-- Counter Badge -->
            <div class="absolute -top-1 -left-1 min-w-[24px] h-6 bg-red-500 rounded-full flex items-center justify-center shadow-lg">
                <span class="text-white text-xs font-bold px-1.5" x-text="filteredReels.length"></span>
            </div>
            <!-- Pulse Animation -->
            <!-- <div class="absolute inset-0 rounded-2xl animate-ping opacity-20" style="background-color: var(--primary-color);"></div> -->
        </div>
    </button>

    <!-- Mobile Sidebar Overlay Background -->
    <div class="md:hidden fixed inset-0 bg-black/50 z-[55] transition-opacity duration-300"
         x-show="isMobileSidebarOpen"
         @click="closeMobileSidebar()"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="Overlay-top md:hidden fixed top-0 right-0 bottom-0 w-full sm:w-[90%] max-w-[400px] shadow-2xl transform transition-transform duration-300 z-[56] overflow-y-auto"
         style="background-color: var(--box-background-color);"
         :class="isMobileSidebarOpen ? 'translate-x-0' : 'translate-x-full'"
         x-show="isMobileSidebarOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">

        <div class="p-3 sm:p-4 text-white flex items-center justify-between" style="background: var(--primary-gradient);">
            <h2 class="text-lg sm:text-xl font-bold">
                <i class="fas fa-list ml-2"></i>
                {{ __('reels_admin.list_title') }}
            </h2>
            <button @click="closeMobileSidebar()"
                    class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center">
                <i class="fas fa-times text-lg sm:text-xl"></i>
            </button>
        </div>

        <!-- Search Box -->
        <div class="p-3 sm:p-4">
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       @input="filterReels()"
                       placeholder="{{ __('reels_admin.search_placeholder') }}"
                       class="w-full px-3 sm:px-4 py-2 ps-10 text-sm sm:text-base rounded-lg border-2 border-purple-300 dark:border-purple-700 bg-white dark:bg-gray-800 text-gray-800 focus:border-purple-500 focus:outline-none">
                <i class="fas fa-search absolute start-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-sm"></i>
            </div>
            <p class="text-xs sm:text-sm mt-2 text-gray-600 dark:text-gray-400" x-text="filteredReels.length + ' {{ __('reels_admin.reel_label') }}'"></p>
        </div>

        <!-- Reels Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-2 sm:p-3">
            <template x-for="reel in filteredReels" :key="reel.id">
                <div @click="selectReel(reel.id); closeMobileSidebar()"
                     :data-reel-id="reel.id"
                     :class="selectedReelId === reel.id ? 'ring-2 sm:ring-4 ring-purple-500' : ''"
                     class="cursor-pointer rounded-lg overflow-hidden shadow-md hover:shadow-xl transition relative group">
                    <div class="relative aspect-[9/16] bg-gray-200 dark:bg-gray-700">
                        <!-- Skeleton Loader -->
                        <div class="absolute inset-0 skeleton" x-show="!reel.thumbnailLoaded"></div>

                        <img :src="reel.thumbnail_url"
                             :alt="reel.title"
                             class="absolute inset-0 w-full h-full object-cover"
                             x-show="reel.thumbnailLoaded"
                             x-on:load="onThumbnailLoad(reel, $event)"
                             x-on:error="onThumbnailError(reel, $event)">

                        <!-- User Info Overlay on Hover for Mobile -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-2">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs border border-white/50 flex-shrink-0" style="background: var(--primary-gradient);">
                                    <span x-text="reel.user?.name?.charAt(0)"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-xs font-bold truncate" x-text="reel.user?.name"></p>
                                    <p class="text-white/70 text-[10px]" x-text="'{{ __('reels_admin.id_label') }}' + reel.user?.id"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-white text-[10px]">
                                <span class="flex items-center">
                                    <i class="fas fa-eye ml-1"></i>
                                    <span x-text="formatNumber(reel.views_count)"></span>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-heart ml-1"></i>
                                    <span x-text="formatNumber(reel.likes_count)"></span>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-comment ml-1"></i>
                                    <span x-text="formatNumber(reel.comments_count)"></span>
                                </span>
                            </div>
                        </div>

                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-1.5 sm:p-2 group-hover:opacity-0 transition-opacity">
                            <p class="text-white text-xs font-semibold truncate" x-text="reel.title"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- Reels Data - stored in a hidden div to survive PJAX -->
<div id="reels-initial-data" style="display:none" data-reels='@json($reels)' data-seed="{{ $seed ?? '' }}"></div>
<script data-exec-on-popstate>
    (function() {
        // قراءة البيانات من الـ hidden div (يعمل مع PJAX لأن الـ div موجود في الـ DOM)
        var dataEl = document.getElementById('reels-initial-data');
        if (dataEl) {
            try {
                window.initialReelsData = JSON.parse(dataEl.getAttribute('data-reels') || '[]');
                window.randomSeed = parseInt(dataEl.getAttribute('data-seed')) || null;
                console.log('📦 [DATA] Reels loaded from DOM:', window.initialReelsData.length, 'reels');
            } catch(e) {
                console.error('❌ [DATA] Error parsing reels data:', e);
                window.initialReelsData = [];
                window.randomSeed = null;
            }
        } else {
            console.warn('⚠️ [DATA] reels-initial-data element not found');
            window.initialReelsData = window.initialReelsData || [];
        }
        
        // تحميل Alpine.js ديناميكياً إذا لم يكن محملاً
        var ensureAlpineLoaded = function(callback) {
            if (window.Alpine) { callback(); return; }
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
            script.defer = true;
            script.onload = function() { setTimeout(callback, 100); };
            document.head.appendChild(script);
        };
        
        // تحميل reels-manager.js ديناميكياً إذا لم يكن محملاً
        var ensureReelsManagerLoaded = function(callback) {
            if (window.reelsManager && typeof window.reelsManager === 'function') { callback(); return; }
            var existingScript = document.querySelector('script[data-reels-manager]');
            var script = document.createElement('script');
            script.src = existingScript ? existingScript.src : '/modules/reals/js/reels-manager.js?v=' + Date.now();
            script.onload = function() { callback(); };
            document.head.appendChild(script);
        };

        var initAlpine = function() {
            if (!window.Alpine || !window.reelsManager || typeof window.reelsManager !== 'function') return false;
            var container = document.querySelector('#pjax-container') || document.body;
            var reelsEl = container ? container.querySelector('.reels-main-container[x-data]') : null;
            if (!reelsEl) return false;

            console.log('📊 [INIT] initialReelsData:', window.initialReelsData ? window.initialReelsData.length + ' reels' : 'EMPTY');

            try {
                if (window.Alpine.destroyTree && reelsEl._x_dataStack) {
                    window.Alpine.destroyTree(reelsEl);
                }
                if (window.Alpine.initTree) {
                    window.Alpine.initTree(container);
                } else if (window.Alpine.start) {
                    window.Alpine.start();
                }
                container.querySelectorAll('[x-cloak]').forEach(function(el) { el.removeAttribute('x-cloak'); });
                console.log('✅ [INIT] Alpine initialized successfully');
                return true;
            } catch (error) {
                console.error('❌ [INIT] Alpine reinit error:', error);
                return false;
            }
        };

        var scheduleInit = function() {
            ensureAlpineLoaded(function() {
                ensureReelsManagerLoaded(function() {
                    var attempts = 0;
                    var attempt = function() {
                        if (initAlpine()) return;
                        if (attempts++ < 30) {
                            setTimeout(attempt, 150);
                        }
                    };
                    attempt();
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', scheduleInit, { once: true });
        } else {
            scheduleInit();
        }

        // PJAX events - only listen once, no duplicates
        if (!window.__reelsPjaxBound) {
            window.__reelsPjaxBound = true;
            document.addEventListener('pjax:complete', function() {
                // Re-read data from DOM after PJAX replaces content
                var dataEl2 = document.getElementById('reels-initial-data');
                if (dataEl2) {
                    try {
                        window.initialReelsData = JSON.parse(dataEl2.getAttribute('data-reels') || '[]');
                        window.randomSeed = parseInt(dataEl2.getAttribute('data-seed')) || null;
                        console.log('🔄 [PJAX] Re-loaded data from DOM:', window.initialReelsData.length, 'reels');
                    } catch(e) { /* ignore */ }
                }
                setTimeout(scheduleInit, 200);
            });
        }
    })();
</script>
