@extends('Form::layouts.app')

@section('title', __('Edit Form Template'))

@section('content')
<style>
    .secondary{
        background: var(--secondary-color) !important;
    }
</style>
<div class=" mx-auto px-4 sm:px-6 lg:px-8 secondary">
    <!-- <div class="flex justify-between items-center mb-6">
        <a href="{{ admin_url('form-templates') }}" class="text-blue-600 hover:underline">
             <i class="fas fa-arrow-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} {{ app()->getLocale() == 'ar' ? 'ml-1' : 'mr-1' }}"></i>
            {{ __('Back') }}
        </a>
    </div> -->

    <form action="{{ admin_url('form-templates/' . $template->id) }}"    method="POST" style="    padding: 16px;">
        @csrf
        @method('PUT')
        @include('Form::form-templates._form', ['template' => $template])
    </form>
</div>
@endsection
