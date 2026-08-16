@extends('Form::layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-16 px-4">
    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
        {{-- Success Icon --}}
        <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-green-600 text-3xl"></i>
            </div>
        </div>

        {{-- Success Message --}}
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
            {{ __('Form Submitted Successfully!') }}
        </h1>
        
        <p class="text-gray-600 mb-2">
            {{ __('Thank you for submitting the form.') }}
        </p>
        
        <p class="text-sm text-gray-500 mb-8">
            {{ __('Submission ID:') }} <span class="font-mono font-semibold">#{{ $submission->id }}</span>
        </p>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('forms.show', $submission->form_template_id) }}" 
               class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                {{ __('Submit Another Response') }}
            </a>
            
            <a href="{{ route('home') }}" 
               class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                {{ __('Go to Homepage') }}
            </a>
        </div>
    </div>
</div>
@endsection
