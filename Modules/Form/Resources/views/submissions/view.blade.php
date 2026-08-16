@extends('Form::layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <a href="{{ route('submissions.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                <i class="fas fa-arrow-left"></i> {{ __('Back to Submissions') }}
            </a>
            <h1 class="text-3xl font-bold text-gray-900">
                {{ __('Submission Details') }} #{{ $submission->id }}
            </h1>
        </div>
        
        <div class="flex gap-2">
            <form action="{{ route('submissions.updateStatus', $submission->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="draft" {{ $submission->submission_status === 'draft' ? 'selected' : '' }}>
                        {{ __('Draft') }}
                    </option>
                    <option value="submitted" {{ $submission->submission_status === 'submitted' ? 'selected' : '' }}>
                        {{ __('Submitted') }}
                    </option>
                    <option value="approved" {{ $submission->submission_status === 'approved' ? 'selected' : '' }}>
                        {{ __('Approved') }}
                    </option>
                    <option value="rejected" {{ $submission->submission_status === 'rejected' ? 'selected' : '' }}>
                        {{ __('Rejected') }}
                    </option>
                </select>
            </form>
        </div>
    </div>

    {{-- Submission Meta Info --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500">{{ __('Form Template') }}</p>
                <p class="font-semibold">{{ $submission->template->getTranslation('form_name', app()->getLocale()) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">{{ __('Submitted By') }}</p>
                <p class="font-semibold">{{ $submission->submitter ? $submission->submitter->name : __('Guest') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">{{ __('Submitted At') }}</p>
                <p class="font-semibold">{{ $submission->created_at->format('Y-m-d H:i:s') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">{{ __('IP Address') }}</p>
                <p class="font-semibold font-mono text-sm">{{ $submission->ip_address ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    {{-- Submission Data --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ __('Submitted Data') }}</h2>

        @foreach($submission->template->sections->sortBy('display_order') as $section)
            <div class="mb-8 pb-8 border-b   last:border-b-0">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    {{ $section->getTranslation('section_name', app()->getLocale()) }}
                </h3>

                <div class="space-y-4">
                    @foreach($section->fields->sortBy('display_order') as $field)
                        @php
                            $value = $submission->values->where('field_id', $field->id)->first();
                        @endphp

                        <div class=" rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">
                                {{ $field->getTranslation('field_name', app()->getLocale()) }}
                            </p>

                            @if($value)
                                {{-- Handle Custom Widget Fields --}}
                                @if($field->hasCustomWidget() && $field->widget->allows_multiple)
                                    @php
                                        $selectedValues = $value->getValue();
                                        $widgetData = $field->widget->getData($field->widget_config);
                                    @endphp

                                    @if(is_array($selectedValues) && count($selectedValues) > 0)
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach($selectedValues as $selectedId)
                                                @php
                                                    $item = collect($widgetData)->firstWhere('id', $selectedId);
                                                @endphp
                                                @if($item)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                        {{ $item['name'] ?? $item['label'] ?? $selectedId }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-gray-500 italic">{{ __('No selection') }}</p>
                                    @endif

                                {{-- Handle File Fields --}}
                                @elseif($field->field_type === 'file' && $value->file_path)
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-file text-gray-400"></i>
                                        <a href="{{ asset('storage/' . $value->file_path) }}" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800">
                                            {{ $value->file_name }}
                                        </a>
                                        <span class="text-sm text-gray-500">
                                            ({{ number_format($value->file_size / 1024, 2) }} KB)
                                        </span>
                                    </div>

                                {{-- Handle Regular Fields --}}
                                @else
                                    <p class="text-gray-900">
                                        {{ $value->field_value ?: __('No answer provided') }}
                                    </p>
                                @endif
                            @else
                                <p class="text-gray-500 italic">{{ __('No answer provided') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
