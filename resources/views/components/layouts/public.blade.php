<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}"
      data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cairo:wght@300;400;500;600;700;800&family=Figtree:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-amiri { font-family: 'Amiri', serif; }
        .arabic-text { font-family: 'Cairo', 'Amiri', sans-serif; direction: rtl; text-align: right; }
        .islamic-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%236366f1' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 font-sans antialiased">

{{-- ═══════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════ --}}
<div class="navbar bg-base-100/95 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-base-200">
    <div class="navbar-start">
        <div class="dropdown">
            <label tabindex="0" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/>
                </svg>
            </label>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-10 p-2 shadow bg-base-100 rounded-box w-52">
                <li><a href="{{ route('home') }}">Accueil</a></li>
                <li><a href="#quran">Coran</a></li>
                <li><a href="#hadith">Hadith</a></li>
                <li><a href="#asma">Asma ul Husna</a></li>
                <li><a href="{{ route('courses') }}">Cours</a></li>
            </ul>
        </div>
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 ms-1">
            <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span class="font-bold text-lg hidden sm:block">{{ config('app.name') }}</span>
        </a>
    </div>

    <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal px-1 gap-1 text-sm">
            <li><a href="{{ route('home') }}" class="rounded-lg hover:bg-primary/10 hover:text-primary">Accueil</a></li>
            <li><a href="#quran" class="rounded-lg hover:bg-primary/10 hover:text-primary">Coran</a></li>
            <li><a href="#hadith" class="rounded-lg hover:bg-primary/10 hover:text-primary">Hadith</a></li>
            <li><a href="#asma" class="rounded-lg hover:bg-primary/10 hover:text-primary">Asma ul Husna</a></li>
            <li><a href="{{ route('courses') }}" class="rounded-lg hover:bg-primary/10 hover:text-primary">Cours</a></li>
        </ul>
    </div>

    <div class="navbar-end gap-2">
        {{-- Language Switcher --}}
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-ghost btn-sm btn-circle">
                <span class="text-base">🌐</span>
            </label>
            <ul tabindex="0" class="dropdown-content z-10 menu p-2 shadow-lg bg-base-100 rounded-xl w-36 mt-2 border border-base-200">
                <li><a href="{{ route('language.switch', 'fr') }}">🇫🇷 Français</a></li>
                <li><a href="{{ route('language.switch', 'ar') }}">🇩🇿 عربي</a></li>
                <li><a href="{{ route('language.switch', 'en') }}">🇬🇧 English</a></li>
            </ul>
        </div>
        <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Connexion</a>
        <a href="{{ route('register') }}" class="btn btn-primary btn-sm shadow-sm">S'inscrire</a>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════ --}}
<main>
    {{ $slot }}
</main>

{{-- ═══════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════ --}}
<footer class="bg-base-200 border-t border-base-300 py-12 mt-16">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="font-bold text-lg">{{ config('app.name') }}</span>
                </div>
                <p class="text-base-content/60 text-sm mb-4">Plateforme d'apprentissage islamique et académique pour les étudiants algériens.</p>
                <p class="arabic-text text-primary font-bold text-xl leading-relaxed">طَلَبُ الْعِلْمِ فَرِيضَةٌ عَلَى كُلِّ مُسْلِمٍ</p>
                <p class="text-xs text-base-content/50 mt-1 italic">« L'acquisition du savoir est un devoir pour tout musulman » — Ibn Mâjah</p>
            </div>
            <div>
                <h4 class="font-semibold mb-3 text-sm uppercase tracking-wider text-base-content/60">Navigation</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-base-content/70 hover:text-primary transition-colors">Accueil</a></li>
                    <li><a href="{{ route('courses') }}" class="text-base-content/70 hover:text-primary transition-colors">Nos cours</a></li>
                    <li><a href="{{ route('login') }}" class="text-base-content/70 hover:text-primary transition-colors">Connexion</a></li>
                    <li><a href="{{ route('register') }}" class="text-base-content/70 hover:text-primary transition-colors">S'inscrire</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-3 text-sm uppercase tracking-wider text-base-content/60">Ressources</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#quran" class="text-base-content/70 hover:text-primary transition-colors">Coran</a></li>
                    <li><a href="#hadith" class="text-base-content/70 hover:text-primary transition-colors">Hadith</a></li>
                    <li><a href="#asma" class="text-base-content/70 hover:text-primary transition-colors">Asma ul Husna</a></li>
                    <li><a href="#dua" class="text-base-content/70 hover:text-primary transition-colors">Duas</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-base-300 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-base-content/40">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</span>
            <span>Propulsé par UmmahAPI 🌙</span>
        </div>
    </div>
</footer>

<x-toast />
</body>
</html>
