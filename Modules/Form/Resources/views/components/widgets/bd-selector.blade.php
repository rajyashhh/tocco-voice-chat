{{-- BD Selector Widget Component --}}
@php
    $fieldId = $field->id ?? uniqid('field_');
    $fieldName = $field->field_name ?? 'bd_users';
    $required = $field->is_required ?? false;
    $multiple = $widget->allows_multiple ?? false;
    $selectedIds = is_array($value) ? $value : ($value ? [$value] : []);

    // Get BD users data
    $bdUsers = $widget->getData($config);
@endphp

<div class="custom-widget bd-selector-widget" data-widget-type="bd_selector" data-field-id="{{ $fieldId }}">
    <div class="mb-2">
        <label class="block text-sm font-semibold mb-2">
            {{ $field->getTranslation('field_label', app()->getLocale()) }}
            @if($required)
                <span class="text-red-600">*</span>
            @endif
        </label>

        @if($field->help_text)
            <p class="text-xs text-gray-600 mb-2">
                {{ $field->getTranslation('help_text', app()->getLocale()) }}
            </p>
        @endif
    </div>

    {{-- Search Input --}}
    <div class="mb-3">
        <input type="text"
               id="bd-search-{{ $fieldId }}"
               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
               placeholder="{{ __('Search BD by name or email...') }}">
    </div>

    {{-- BD Users List --}}
    <div class="bd-users-container max-h-96 overflow-y-auto border-2   rounded-lg bg-gray-50">
        @if($bdUsers->isEmpty())
            <div class="p-4 text-center text-gray-500">
                {{ __('No BD users available') }}
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($bdUsers as $user)
                    <label class="flex items-center p-4 hover:  cursor-pointer transition-colors bd-user-item"
                           data-user-id="{{ $user->id }}"
                           data-user-name="{{ $user->name }}"
                           data-user-email="{{ $user->email }}">
                        <input type="{{ $multiple ? 'checkbox' : 'radio' }}"
                               name="{{ $fieldName }}{{ $multiple ? '[]' : '' }}"
                               value="{{ $user->id }}"
                               {{ in_array($user->id, $selectedIds) ? 'checked' : '' }}
                               {{ $required && !$multiple ? 'required' : '' }}
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">

                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">{{ $user->email }}</div>
                        </div>

                        <div class="ml-3">
                            <svg class="w-5 h-5 text-green-500 checkmark hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Selected Users Summary (for multiple selection) --}}
    @if($multiple)
        <div class="mt-3 selected-users-summary">
            <div class="text-sm font-semibold text-gray-700 mb-2">
                {{ __('Selected') }}: <span class="selected-count">{{ count($selectedIds) }}</span>
            </div>
            <div class="flex flex-wrap gap-2" id="selected-tags-{{ $fieldId }}">
                {{-- Tags will be added dynamically via JavaScript --}}
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldId = '{{ $fieldId }}';
    const searchInput = document.getElementById('bd-search-' + fieldId);
    const container = document.querySelector('[data-field-id="' + fieldId + '"]');
    const userItems = container.querySelectorAll('.bd-user-item');
    const isMultiple = {{ $multiple ? 'true' : 'false' }};

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            userItems.forEach(item => {
                const name = item.dataset.userName.toLowerCase();
                const email = item.dataset.userEmail.toLowerCase();

                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    }

    // Update checkmark visibility
    function updateCheckmarks() {
        userItems.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"], input[type="radio"]');
            const checkmark = item.querySelector('.checkmark');

            if (checkbox && checkmark) {
                if (checkbox.checked) {
                    checkmark.classList.remove('hidden');
                    item.classList.add(' ');
                } else {
                    checkmark.classList.add('hidden');
                    item.classList.remove(' ');
                }
            }
        });

        if (isMultiple) {
            updateSelectedTags();
        }
    }

    // Update selected tags (for multiple selection)
    function updateSelectedTags() {
        const tagsContainer = document.getElementById('selected-tags-' + fieldId);
        const selectedCountSpan = container.querySelector('.selected-count');
        const checkedInputs = container.querySelectorAll('input[type="checkbox"]:checked');

        if (selectedCountSpan) {
            selectedCountSpan.textContent = checkedInputs.length;
        }

        if (tagsContainer) {
            tagsContainer.innerHTML = '';

            checkedInputs.forEach(input => {
                const item = input.closest('.bd-user-item');
                const userName = item.dataset.userName;
                const userId = item.dataset.userId;

                const tag = document.createElement('div');
                tag.className = 'inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm';
                tag.innerHTML = `
                    <span>${userName}</span>
                    <button type="button" class="ml-2 text-blue-600 hover:text-blue-800 remove-tag" data-user-id="${userId}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                `;

                tagsContainer.appendChild(tag);
            });

            // Add click handlers to remove tags
            tagsContainer.querySelectorAll('.remove-tag').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const checkbox = container.querySelector(`input[value="${userId}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                        updateCheckmarks();
                    }
                });
            });
        }
    }

    // Listen for checkbox/radio changes
    container.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(input => {
        input.addEventListener('change', updateCheckmarks);
    });

    // Initial update
    updateCheckmarks();
});
</script>
@endpush
