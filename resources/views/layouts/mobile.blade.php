<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    
    <!-- 用户可以在这里添加自己的样式 -->
    @stack('styles')
    @livewireStyles
</head>
<body>
    <div class="scan-login-mobile-wrapper">
        {{ $slot }}
    </div>
    
    @livewireScripts
    @stack('scripts')
</body>
</html>