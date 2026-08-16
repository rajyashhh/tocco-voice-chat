@extends('Form::layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-4 sm:py-8 px-3 sm:px-6">
    <div class="max-w-3xl mx-auto">
        {{-- Form Header --}}
        <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 shadow-2xl mb-4 sm:mb-6 relative overflow-hidden">
            {{-- Decorative Background Elements --}}
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
            
            <div class="relative z-10">
                <div class="flex items-center mb-3">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-3 backdrop-blur-sm">
                        <i class="fas fa-clipboard-list text-2xl"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold">
                        {{ $template->getTranslation('title', app()->getLocale()) }}
                    </h1>
                </div>
                
                @if($template->description)
                    <p class="text-blue-50 text-sm sm:text-base leading-relaxed">
                        {{ $template->getTranslation('description', app()->getLocale()) }}
                    </p>
                @endif

                @if($template->admin_notice)
                    <div class="mt-4 bg-blue-800 bg-opacity-40 border border-blue-300 border-opacity-30 rounded-xl p-3 sm:p-4 backdrop-blur-sm">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle mr-2 mt-0.5 flex-shrink-0"></i>
                            <span class="text-sm">{{ $template->getTranslation('admin_notice', app()->getLocale()) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Form Body --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl p-4 sm:p-8 mb-6">
            <form action="{{ route('forms.submit', $template->id) }}" method="POST" enctype="multipart/form-data" id="dynamicForm">
                @csrf

                @if($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 animate-shake">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation-circle text-red-500"></i>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm text-red-700 font-semibold mb-2">{{ __('Please correct the following errors:') }}</p>
                                <ul class="text-sm text-red-600 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li class="flex items-start">
                                            <i class="fas fa-chevron-right text-xs mr-2 mt-1"></i>
                                            <span>{{ $error }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Progress Indicator --}}
                <div class="mb-6 hidden sm:block">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">
                            <i class="fas fa-tasks mr-2"></i>
                            {{ __('Fill all required fields') }}
                        </span>
                        <span class="text-gray-500">
                            <span id="filled-count">0</span> / <span id="total-required">0</span> {{ __('completed') }}
                        </span>
                    </div>
                    <div class="mt-2 bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div id="progress-bar" class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                {{-- Render Sections and Fields --}}
                @foreach($template->sections->sortBy('section_order') as $sectionIndex => $section)
                    @if($section->is_visible)
                        <div class="mb-6 sm:mb-8 pb-6 sm:pb-8 border-b border-gray-100 last:border-b-0 animate-fade-in" style="animation-delay: {{ $sectionIndex * 0.1 }}s">
                            {{-- Section Header --}}
                            <div class="mb-5 sm:mb-6">
                                <div class="flex items-center mb-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-lg flex items-center justify-center font-bold text-sm mr-3 shadow-md">
                                        {{ $sectionIndex + 1 }}
                                    </div>
                                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">
                                        {{ $section->getTranslation('title', app()->getLocale()) }}
                                    </h2>
                                </div>

                                @if($section->description)
                                    <p class="text-gray-600 text-sm sm:text-base ml-11">
                                        {{ $section->getTranslation('description', app()->getLocale()) }}
                                    </p>
                                @endif
                            </div>

                            {{-- Fields --}}
                            <div class="space-y-5 sm:space-y-6">
                                @foreach($section->fields->sortBy('field_order') as $field)
                                    @if($field->is_enabled)
                                        <div class="field-wrapper group" data-field-id="{{ $field->id }}" data-required="{{ $field->is_required ? 'true' : 'false' }}">
                                            @if($field->hasCustomWidget())
                                                {{-- Render Custom Widget --}}
                                                @php
                                                    $config = array_merge(
                                                        $field->widget->default_config ?? [],
                                                        $field->widget_config ?? []
                                                    );
                                                @endphp
                                                @include($field->widget->component_path, [
                                                    'field' => $field,
                                                    'widget' => $field->widget,
                                                    'name' => 'fields[' . $field->id . '][]',
                                                    'value' => old('fields.' . $field->id, []),
                                                    'config' => $config
                                                ])
                                            @else
                                                {{-- Render Standard Input with Modern UI --}}
                                                @include('components.form.standard-input-modern', [
                                                    'field' => $field,
                                                    'name' => 'fields[' . $field->id . ']',
                                                    'value' => old('fields.'.$field->id, '')
                                                ])
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Submit Button --}}
                <div class="sticky bottom-0 left-0 right-0 bg-white pt-6 pb-4 sm:pb-0 -mx-4 px-4 sm:mx-0 sm:px-0 border-t sm:border-t-0 border-gray-100 sm:relative">
                    <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                        <button type="button" 
                                onclick="window.history.back()"
                                class="order-2 sm:order-1 px-6 py-3 sm:py-3.5 bg-gray-100 text-gray-700 rounded-xl sm:rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium text-center flex items-center justify-center">
                            <i class="fas fa-arrow-left {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" 
                                id="submit-btn"
                                class="order-1 sm:order-2 px-8 py-3 sm:py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl sm:rounded-lg hover:from-blue-700 hover:to-indigo-700 shadow-lg hover:shadow-xl transition-all duration-200 font-semibold text-center flex items-center justify-center transform hover:scale-105">
                            <i class="fas fa-paper-plane {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                            <span>{{ __('Submit Form') }}</span>
                            <div class="ml-2 hidden" id="submit-spinner">
                                <i class="fas fa-circle-notch fa-spin"></i>
                            </div>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Footer Info --}}
        <div class="text-center space-y-2 pb-4">
            <div class="flex items-center justify-center text-sm text-gray-500">
                <i class="fas fa-shield-alt mr-2 text-green-600"></i>
                <span>{{ __('Your data is secure and encrypted') }}</span>
            </div>
            <div class="flex items-center justify-center text-xs text-gray-400">
                <i class="fas fa-clock mr-2"></i>
                <span>{{ __('This form auto-saves your progress') }}</span>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .animate-fade-in {
        animation: fade-in 0.5s ease-out forwards;
        opacity: 0;
    }
    
    .animate-shake {
        animation: shake 0.5s ease-in-out;
    }
    
    /* Mobile optimizations */
    @media (max-width: 640px) {
        input, select, textarea {
            font-size: 16px !important; /* Prevents zoom on iOS */
        }
    }
    
    /* Focus states for better accessibility */
    input:focus, select:focus, textarea:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('dynamicForm');
    const progressBar = document.getElementById('progress-bar');
    const filledCount = document.getElementById('filled-count');
    const totalRequired = document.getElementById('total-required');
    const submitBtn = document.getElementById('submit-btn');
    
    // Count required fields
    const requiredFields = document.querySelectorAll('[data-required="true"]');
    if (totalRequired) {
        totalRequired.textContent = requiredFields.length;
    }
    
    // Update progress
    function updateProgress() {
        let filled = 0;
        requiredFields.forEach(wrapper => {
            const inputs = wrapper.querySelectorAll('input:not([type="hidden"]), select, textarea');
            let hasValue = false;
            
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    if (input.checked) hasValue = true;
                } else if (input.value.trim() !== '') {
                    hasValue = true;
                }
            });
            
            if (hasValue) filled++;
        });
        
        if (filledCount) filledCount.textContent = filled;
        if (progressBar && requiredFields.length > 0) {
            const percentage = (filled / requiredFields.length) * 100;
            progressBar.style.width = percentage + '%';
        }
    }
    
    // Listen to all inputs
    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
    
    // Initial progress check
    updateProgress();
    
    // Form submission with loading state
    form.addEventListener('submit', function(e) {
        const submitText = submitBtn.querySelector('span');
        const spinner = document.getElementById('submit-spinner');
        
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        if (submitText) submitText.textContent = '{{ __("Submitting...") }}';
        if (spinner) spinner.classList.remove('hidden');
    });
    
    // Auto-save to localStorage (optional)
    let saveTimeout;
    form.addEventListener('input', function(e) {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                if (!data[key]) data[key] = value;
            });
            localStorage.setItem('form_draft_{{ $template->id }}', JSON.stringify(data));
        }, 1000);
    });
    
    // Restore from localStorage
    const savedData = localStorage.getItem('form_draft_{{ $template->id }}');
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input && !input.value) {
                    input.value = data[key];
                }
            });
            updateProgress();
        } catch (e) {
            console.error('Error restoring form data:', e);
        }
    }
    
    // Clear draft on successful submission
    form.addEventListener('submit', function() {
        setTimeout(function() {
            localStorage.removeItem('form_draft_{{ $template->id }}');
        }, 1000);
    });
});
</script>
@endpush
@endsection
