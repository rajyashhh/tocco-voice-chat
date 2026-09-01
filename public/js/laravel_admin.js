$(document).ready(function () {
    // Delegated (not direct) so the bindings survive pjax navigation: pjax
    // replaces #pjax-container and never re-runs this already-loaded script,
    // so a direct $('.view-description').click() binding dies after the first
    // page transition and the <a href="#"> falls back to native navigation,
    // leaving the URL with a trailing "#".
    $(document).on('click', '.view-description', function (e) {
        e.preventDefault();

        var description = $(this).data('description');

        $('#modalDescriptionTitle').text("Full Description");
        $('#modalDescriptionContent').text(description);

        $('#descriptionModal').modal('show');
    });

    $(document).on('click', '.view-image', function (e) {
        e.preventDefault();
        var imgSrc = $(this).data('img');
        $('#modalImageContent').attr('src', imgSrc);
        $('#imageModal').modal('show');
    });

    $(document).on('click', '[data-toggle="modal"]', function () {
       setTimeout(function () {
           $('html, body').animate({ scrollTop: 300 }, 300);
       }, 200);
    });
});

// ── Global navigation hardening ───────────────────────────────────────────
// 1) An <a href="#"> must never actually navigate: that is what leaves the
//    URL ending in "#". pjax deliberately ignores hash-only links (so it does
//    NOT preventDefault them) and every handler that did preventDefault is
//    either direct-bound (dead after pjax) or plugin-bound (AdminLTE/Bootstrap
//    — gone when their document.ready init is skipped). This capture-phase
//    guard covers every such anchor no matter which handler is missing. It
//    only targets the literal href="#" (tab links like href="#tab1" are
//    intentional fragment navigation and are left alone).
document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a[href="#"]') : null;
    if (a && !e.defaultPrevented) {
        e.preventDefault();
    }
}, true);

// 2) Pjax overlay cleanup: the footer modals live OUTSIDE #pjax-container, so
//    when a modal is open during a pjax navigation its full-screen
//    .modal-backdrop and body.modal-open survive on the new page and swallow
//    every click ("sidebar stops responding until refresh"). Close modals and
//    drop stray backdrops/popovers/tooltips at the START of every pjax
//    navigation so the next page is always interactive.
//
// 3) Hash cleanup: the dashboard tab code calls history.pushState(null, null,
//    '#game') etc.  If the user then navigates away via pjax, the hash stays
//    in the URL.  The next page's pjax:complete would re-render, but a stale
//    hash like #game can confuse the AdminLTE active-menu matcher
//    (location.pathname + location.hash) and – on some browsers – prevent
//    sidebar link clicks from reaching the pjax handler.  Strip the hash at
//    the start of every pjax navigation.
$(document).on('pjax:start', function () {
    // Strip stale fragment identifiers so the URL is clean for the new page.
    if (window.location.hash) {
        history.replaceState(history.state, '', window.location.pathname + window.location.search);
    }

    // Close any open modals / backdrops that would swallow clicks.
    try {
        $('.modal.in, .modal.show').each(function () {
            if ($.fn.modal) {
                $(this).modal('hide');
            }
        });
    } catch (_e) { /* $.fn.modal may be unavailable if a duplicate jQuery was loaded */ }
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('.crs-popover, .crs-tooltip').remove();
});

$(document).on('ajaxComplete', function (event, xhr, settings) {
    if (xhr.responseJSON && xhr.responseJSON.data) {
        let data = xhr.responseJSON.data;

        let btn = $('.grid-row-action[data-key="' + data.id + '"] a');
        if (btn.length) {
            btn.find('i').attr('class', data.icon);
            btn.find('span').text(data.label);
        }
    }
});



// Delegated: these row-action buttons live inside #pjax-container, so a direct
// binding (querySelectorAll + addEventListener on DOMContentLoaded) dies after
// the first pjax navigation and the buttons silently stop working.
function sendRequest(url) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': LA.token,
            'Accept': 'application/json',
        },
    }).then(res => res.json());
}

function handleAction(button, actionType, e) {
    if (e) {
        e.preventDefault();

            // رسائل متعددة اللغات
            const messages = {
                approve: {
                    title: 'هل أنت متأكد من الموافقة على هذا الطلب؟',
                    confirm: 'نعم',
                    cancel: 'إلغاء',
                    color: '#28a745'
                },
                reject: {
                    title: 'هل أنت متأكد من الرفض على هذا الطلب؟',
                    confirm: 'نعم',
                    cancel: 'إلغاء',
                    color: '#dc3545'
                },
                success: {
                    en: 'Action completed successfully!',
                    ar: 'تمت العملية بنجاح!',
                    hi: 'क्रिया सफलतापूर्वक पूरी हुई!',
                    tr: 'İşlem başarıyla tamamlandı!'
                },
                error: {
                    en: 'An error occurred!',
                    ar: 'حدث خطأ أثناء العملية',
                    hi: 'एक त्रुटि हुई!',
                    tr: 'İşlem sırasında hata oluştu!'
                }
            };

            const locale = document.documentElement.lang || 'ar'; // افتراض لغة الموقع

            Swal.fire({
                title: messages[actionType].title,
                type: 'question',
                showCancelButton: true,
                confirmButtonText: messages[actionType].confirm,
                cancelButtonText: messages[actionType].cancel,
                confirmButtonColor: messages[actionType].color,
                cancelButtonColor: '#6c757d',
            }).then((result) => {

                if(result.value){
                    const url = button.dataset.url;
                    sendRequest(url).then(res => {
                        if(res.success){
                            Swal.fire({
                                title: res.message || messages.success[locale],
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $.pjax.reload('#pjax-container'); // تحديث الجدول بدلاً من إزالة الصف
                        } else {
                            Swal.fire('خطأ', res.message || messages.error[locale], 'error');
                        }
                    }).catch(() => {
                        Swal.fire('خطأ', messages.error[locale], 'error');
                    });
                }
            });
    }
}

$(document).on('click', '.approve-btn', function (e) { handleAction(this, 'approve', e); });
$(document).on('click', '.reject-btn', function (e) { handleAction(this, 'reject', e); });



// if (window.__countryMapInitialized) return;
// window.__countryMapInitialized = true;

function loadScriptsSequentially(scripts, callback = () => {}) {
    if (!scripts.length) return callback();
    const [first, ...rest] = scripts;
    $.getScript(first)
        .done(() => loadScriptsSequentially(rest, callback))
        .fail((xhr, status, error) => {
            // Log only — never reload the page on a CDN failure: these map libs
            // are loaded on EVERY admin page, so a reload here turns a blocked
            // CDN into an infinite reload loop for the whole panel.
            console.error('[Map Error] فشل تحميل:', first, error);
        });
}

// `var` (not const/let): laravel-admin re-runs page scripts on PJAX navigation,
// and a top-level const/let would throw "Identifier 'scripts' has already been
// declared" on the second run, aborting the whole file. var tolerates re-decl.
var scripts = [
    'https://cdn.jsdelivr.net/npm/jvectormap-next/jquery-jvectormap.min.js',
    'https://cdn.jsdelivr.net/npm/jvectormap-content/world-mill.js'
];

loadScriptsSequentially(scripts);

// Navbar hide on scroll down, show on scroll up
(function() {
    'use strict';

    const navbar = document.querySelector('.navbar.navbar-static-top');
    if (!navbar) {
        return;
    }

    let lastScrollTop = 0;
    const scrollThreshold = 30;

    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

        // Always show at top of page
        if (currentScroll <= 0) {
            navbar.classList.remove('navbar-hidden');
            lastScrollTop = 0;
            return;
        }

        // Check scroll direction
        if (currentScroll > lastScrollTop + scrollThreshold) {
            // Scrolling DOWN - hide navbar
            navbar.classList.add('navbar-hidden');
            lastScrollTop = currentScroll;
        } else if (currentScroll < lastScrollTop - scrollThreshold) {
            // Scrolling UP - show navbar
            navbar.classList.remove('navbar-hidden');
            lastScrollTop = currentScroll;
        }
    });
})();
