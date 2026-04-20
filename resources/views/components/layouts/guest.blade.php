<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}"
      data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('/favicon.ico') }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center font-sans antialiased">

<div class="w-full max-w-sm sm:max-w-md px-4 py-8">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary shadow-lg mb-4">
            <x-icon name="o-academic-cap" class="w-9 h-9 text-white" />
        </div>
        <h1 class="text-2xl font-bold">{{ config('app.name', 'EduPlatform') }}</h1>
        <p class="text-sm text-base-content/60 mt-1">{{ __('lms.learning_platform') }}</p>
    </div>

    {{ $slot }}

</div>

<x-toast />
<x-theme-toggle class="fixed bottom-4 right-4" />

</body>
</html>
