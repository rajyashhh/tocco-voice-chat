@extends('Form::layouts.app')

@section('content')
<div style="display: flex; align-items: center; justify-content: center; height: 80vh;">
    <div style="text-align: center; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 500px;">
        <h2 style="color: #c0392b; margin-bottom: 15px;">Access Denied</h2>
        <p style="font-size: 16px; color: #555;">{{ $message ?? 'You are not authorized to access this form.' }}</p>
        <a href="/" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #c0392b; color: white; border-radius: 6px; text-decoration: none;">Back to Home</a>
    </div>
</div>
@endsection
