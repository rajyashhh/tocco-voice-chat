<script>
    $(document).on('change', '.libraryRealTime', function () {
        $(this).closest('form').submit();
    });
</script>

<script>
    function updateLibrary(selectedLibrary, inputName) {
        let data = {
            _token: "{{ csrf_token() }}",
        };
        data[inputName] = selectedLibrary;

        $.ajax({
            url: "{{ route('admin.update-agora-zego') }}",
            type: "POST",
            data: data,
            success: function (response) {
                console.log("Library updated via AJAX:", response);
                toastr.success('Library preference saved!');
            },
            error: function (xhr) {
                console.error("AJAX Error:", xhr.responseText);
                toastr.error('Failed to update library');
            }
        });
    }

    function updateSwitches() {
        $(".custom-radio").each(function () {
            if ($(this).is(":checked")) {
                $(this).next(".switch").addClass("active");
            } else {
                $(this).next(".switch").removeClass("active");
            }
        });
    }

    function updatePaymentSwitches() {
        $(".custom-payment-radio").each(function () {
            if ($(this).is(":checked")) {
                $(this).next(".switch").addClass("active");
            } else {
                $(this).next(".switch").removeClass("active");
            }
        });
    }

    $(document).on("change", ".custom-radio", function () {
        let selectedLibrary = $(this).val();
        let inputName = $(this).attr('name');
        console.log("Selected library:", selectedLibrary);
        console.log("library Name:", inputName);
        // Skip AJAX for live_library - it has its own form that submits to update-library route
        if (inputName !== 'live_library') {
            updateLibrary(selectedLibrary, inputName);
        }
        updateSwitches();
    });

    $(document).on("change", ".custom-payment-radio", function () {
        updatePaymentSwitches();
    });

    $(document).on("click", ".switch", function () {
        const radio = $(this).prev(".custom-radio");

        if (!radio.prop("checked")) {
            $("input[name='library']").prop("checked", false);
            $(".switch").removeClass("active");

            radio.prop("checked", true).trigger("change");
        }
    });

    $(document).ready(function () {
        updateSwitches();
        updatePaymentSwitches();
    });
</script>

<script>
    document.addEventListener('openSettingsTab', function(e) {
    showSection(e.detail);
});
    function previewImage(event) {
        let file = event.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                let preview = document.getElementById('imagePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    function previewFavIcon(event) {
        let file = event.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                let preview = document.getElementById('favIconPreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    function previewCoinImage(event) {
        let file = event.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                let preview = document.getElementById('coinImagePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
                preview.closest('.img-preview-card').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

function getQueryParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Define functions in global scope FIRST (before initialization)
window.showSection = function(sectionId) {
    // Show/hide sections
    document.querySelectorAll('.settings-section').forEach(section => {
        section.classList.remove('active');
    });
    const section = document.getElementById(sectionId);
    if (section) section.classList.add('active');
    const hashValue = window.location.hash.substring(1);
    // Update all forms with current tab information
    document.querySelectorAll('input[name="current_tab"]').forEach(input => {
        input.value = sectionId;
    });

    

    // Add current_tab as hidden input to all forms in settings (including both settings-form and no-background-form)
    document.querySelectorAll('.settings-form, .no-background-form, form[action*="admin"]').forEach(form => {
        let tabInput = form.querySelector('input[name="current_tab"]');
        if (!tabInput) {
            tabInput = document.createElement('input');
            tabInput.type = 'hidden';
            tabInput.name = 'current_tab';
            form.appendChild(tabInput);
        }
        tabInput.value = sectionId;
    });

    // Update URL without reloading
    const url = new URL(window.location);
    url.searchParams.set("tab", sectionId);
    window.history.pushState({}, "", url);

    // Highlight the active main tab button
    document.querySelectorAll(".settings-menu button").forEach(btn => {
        btn.classList.remove("active");
    });
    const activeBtn = document.querySelector(`.settings-menu button[onclick="showSection('${sectionId}')"]`);
    if (activeBtn) activeBtn.classList.add("active");

    // Work settings now has a single (Experience) inner section; reveal it.
    if (sectionId === 'workSettings') {
        showInnerContent('Experience');
    }
};

window.showInnerContent = function(type) {
    document.querySelectorAll(".inner-tab-content").forEach(content => {
        content.style.display = "none";
    });

    const section = document.getElementById(type + "_tab");
    if (section) section.style.display = "block";
};

window.openFullScreen = function(imgElement) {
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("fullImage");

    modal.style.display = "block";
    modalImg.src = imgElement.src;
};

window.closeFullScreen = function() {
    document.getElementById("imageModal").style.display = "none";
};

// Initialize settings tabs - supports both regular page load and pjax navigation.
// The active section is revealed server-side by an inline <style id=
// "settings-initial-reveal"> in the parent view, so the page is never blank even
// if this script is delayed or throws. That <style> uses an ID selector whose
// specificity would otherwise beat the .active class and pin one section open
// forever (two sections stacked). So the FIRST thing we do here is drop it,
// leaving .settings-section.active as the single source of truth.
(function initSettingsTabs() {
    function doInit() {
        const initialReveal = document.getElementById('settings-initial-reveal');
        if (initialReveal) initialReveal.remove();

        let activeTab = getQueryParam("tab") || "brandSettings";
        // App links moved into the Landing Page section; old bookmarks keep working.
        if (activeTab === 'mobileLinks') {
            activeTab = 'landPageSettings';
        }
        // A section may be absent on pages that don't render every tab (e.g. the
        // Third Party page), or a removed tab may linger in a bookmark. Fall back
        // to the first section actually present so we never target a missing id.
        if (!document.getElementById(activeTab)) {
            const firstSection = document.querySelector('.settings-section');
            activeTab = firstSection ? firstSection.id : 'brandSettings';
        }
        try {
            showSection(activeTab);
        } catch (e) {
            // Never let a downstream error leave the panel blank; the
            // server-rendered section stays visible regardless.
            console.error('settings init failed', e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener("DOMContentLoaded", doInit);
    } else {
        doInit();
    }
    // laravel-admin navigates via pjax; DOMContentLoaded does not refire, so
    // re-init when a pjax load swaps in this page.
    $(document).on('pjax:complete', doInit);
})();
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener("input", function () {
                this.style.background = this.value;
                this.value = this.value;
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.copy-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-copy-target');
                const input = document.getElementById(targetId);
                if (input) {
                    input.select();
                    input.setSelectionRange(0, 99999);
                    document.execCommand('copy');
                }
            });
        });
    });
</script>

<script>

    $('.colorpicker-element').colorpicker({
        align: 'left',
        horizontal: true
    });

    document.addEventListener("DOMContentLoaded", function () {
        const firstRow = document.querySelector('.content .row');
        if (firstRow) {
            const firstDiv = firstRow.querySelector('div');
            if (firstDiv && firstDiv.classList.contains('col-md-12')) {
                firstDiv.classList.add('col-sm-6');
            }
        }
    });
    (
        function () {
            const tabButtons = document.querySelectorAll('#landPageSettings .tab-btn');
            const panes = document.querySelectorAll('#landPageSettings .tab-pane');

            function activateTab(btn) {
                tabButtons.forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });

                panes.forEach(p => {
                    p.classList.remove('show', 'active');
                    p.setAttribute('aria-hidden', 'true');
                });

                btn.classList.add('active');
                btn.setAttribute('aria-selected', 'true');

                const target = btn.getAttribute('data-target');
                if (!target) return;

                const pane = document.querySelector(target);
                if (pane) {
                    pane.classList.add('show', 'active');
                    pane.setAttribute('aria-hidden', 'false');

                    const firstInput = pane.querySelector('input, select, textarea, button');
                    if (firstInput) {
                        firstInput.focus({preventScroll: true});
                    }
                }
            }

            tabButtons.forEach(btn => {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    activateTab(btn);

                    if (window.innerWidth < 768) {
                        const tabContent = document.querySelector('#landPageSettings .tab-content');
                        if (tabContent) {
                            tabContent.scrollIntoView({behavior: 'smooth'});
                        }
                    }

                    const target = btn.getAttribute('data-target');
                    if (target) {
                        history.replaceState(null, null, target);
                    }
                });
            });

            const initiallyActive = document.querySelector('#landPageSettings .tab-btn.active') || tabButtons[0];
            if (initiallyActive) {
                activateTab(initiallyActive);
            }

            function checkHash() {
                if (location.hash) {
                    const btn = document.querySelector('#landPageSettings .tab-btn[data-target="' + location.hash + '"]');
                    if (btn) {
                        activateTab(btn);
                    }
                }
            }

            window.addEventListener('hashchange', checkHash);
            checkHash();
        })();
</script>

<script>
    $(document).ready(function () {
        $('.select2-country').select2({
            placeholder: "{{ __('Select a country') }}",
            allowClear: true,
            width: '100%'
        });

        $('.settings-form').on('submit', function(e) {
            const btn = $(this).find('.btn-save');
            const originalText = btn.html();
            
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}');
            
            setTimeout(() => {
                btn.prop('disabled', false);
                btn.html(originalText);
            }, 5000);
        });
    });
</script>
