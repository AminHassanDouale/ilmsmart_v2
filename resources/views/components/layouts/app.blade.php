<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}"
      data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('/favicon.ico') }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Currency --}}
    <script src="https://cdn.jsdelivr.net/gh/robsontenorio/mary@0.44.2/libs/currency/currency.js"></script>

    {{-- ChartJS --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{-- Flatpickr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    {{-- Cropper.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

    {{-- Sortable.js --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.1/Sortable.min.js"></script>

    {{-- TinyMCE --}}
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    {{-- PhotoSwipe --}}
    <script src="https://cdn.jsdelivr.net/npm/photoswipe@5.4.3/dist/umd/photoswipe.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/photoswipe@5.4.3/dist/umd/photoswipe-lightbox.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/photoswipe@5.4.3/dist/photoswipe.min.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200/50 dark:bg-base-200">

{{-- Mobile Navbar --}}
<x-nav sticky class="lg:hidden">
    <x-slot:brand>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
                <x-icon name="o-academic-cap" class="w-5 h-5 text-white" />
            </div>
            <span class="font-bold text-lg">{{ config('app.name', 'EduPlatform') }}</span>
        </div>
    </x-slot:brand>
    <x-slot:actions>
        @auth
            <livewire:components.notification-bell />
        @endauth
        <label for="main-drawer" class="mr-3 lg:hidden">
            <x-icon name="o-bars-2" class="cursor-pointer" />
        </label>
    </x-slot:actions>
</x-nav>

{{-- Desktop Top Bar (notification bell only) --}}
@auth
    <div class="hidden lg:flex fixed top-3 right-4 z-40">
        <livewire:components.notification-bell />
    </div>
@endauth

<x-main>
    {{-- Sidebar --}}
    <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

        {{-- Logo --}}
        <div class="p-5 pt-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center shadow">
                <x-icon name="o-academic-cap" class="w-6 h-6 text-white" />
            </div>
            <div>
                <div class="font-bold text-base leading-tight">{{ config('app.name', 'EduPlatform') }}</div>
                <div class="text-xs text-base-content/50">{{ __('lms.learning_platform') }}</div>
            </div>
        </div>

        <x-menu activate-by-route>

            {{-- User info --}}
            @if($user = auth()->user())
                <x-menu-separator />
                <x-list-item :item="$user" value="full_name" sub-value="email"
                             no-separator no-hover class="-mx-2 !-my-2 rounded">
                    <x-slot:avatar>
                        <div class="avatar">
                            <div class="w-9 rounded-full ring ring-primary ring-offset-base-100 ring-offset-1">
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" />
                            </div>
                        </div>
                    </x-slot:avatar>
                    <x-slot:actions>
                        <x-dropdown>
                            <x-slot:trigger>
                                <x-button icon="o-cog-6-tooth" class="btn-circle btn-ghost btn-xs" />
                            </x-slot:trigger>
                            <x-menu-item icon="o-user-circle" :label="__('lms.profile')" link="/profile" />
                            <x-menu-item icon="o-language" :label="__('lms.language')" link="/language" />
                            <x-menu-item icon="o-swatch" :label="__('lms.toggle_theme')" @click.stop="$dispatch('mary-toggle-theme')" />
                            <x-menu-separator />
                            <x-menu-item icon="o-power" :label="__('lms.logout')" link="/logout" no-wire-navigate class="text-error" />
                        </x-dropdown>
                    </x-slot:actions>
                </x-list-item>
                <x-menu-separator />
            @endif

            {{-- ADMIN MENU --}}
            @if(auth()->user()?->isAdmin())
                <x-menu-item :title="__('lms.dashboard')" icon="o-chart-pie" link="/dashboard" />

                <x-menu-sub :title="__('lms.users')" icon="o-users">
                    <x-menu-item :title="__('lms.all_users')" icon="o-user-group" link="/admin/users" />
                    <x-menu-item :title="__('lms.students')" icon="o-user" link="/admin/students" />
                    <x-menu-item :title="__('lms.teachers')" icon="o-academic-cap" link="/admin/teachers" />
                    <x-menu-item :title="__('lms.parents')" icon="o-home" link="/admin/parents" />
                </x-menu-sub>

                <x-menu-sub :title="__('lms.academics')" icon="o-book-open">
                    <x-menu-item :title="__('lms.academic_years')" icon="o-calendar" link="/admin/academic-years" />
                    <x-menu-item :title="__('lms.levels')" icon="o-building-library" link="/admin/levels" />
                    <x-menu-item :title="__('lms.grades')" icon="o-squares-2x2" link="/admin/grades" />
                    <x-menu-item :title="__('lms.subjects')" icon="o-tag" link="/admin/subjects" />
                </x-menu-sub>

                <x-menu-sub :title="__('lms.courses')" icon="o-cube">
                    <x-menu-item :title="__('lms.all_courses')" icon="o-cube" link="/admin/courses" />
                    <x-menu-item :title="__('lms.live_classes')" icon="o-video-camera" link="/admin/live-classes" />
                </x-menu-sub>

                <x-menu-sub title="Programs" icon="o-rectangle-stack">
                    <x-menu-item title="All Programs"   icon="o-rectangle-stack" link="/admin/programs" />
                </x-menu-sub>

                <x-menu-sub :title="__('lms.finance')" icon="o-banknotes">
                    <x-menu-item :title="__('lms.payments')"      icon="o-credit-card" link="/admin/payments" />
                    <x-menu-item :title="__('lms.subscriptions')" icon="o-star" link="/admin/subscriptions" />
                    <x-menu-item title="Subscription History"    icon="o-clock" link="/admin/subscriptions/history" />
                    <x-menu-item :title="__('lms.plans')"         icon="o-clipboard-document-list" link="/admin/plans" />
                </x-menu-sub>

                <x-menu-item :title="__('lms.announcements')" icon="o-megaphone" link="/admin/announcements" />
                <x-menu-item :title="__('lms.reports')" icon="o-chart-bar" link="/admin/reports" />

                <x-menu-sub title="Islamic API" icon="o-moon">
                    <x-menu-item title="Hijri Date"     icon="o-calendar-days"          link="/admin/islamic/hijri" />
                    <x-menu-item title="Quran"          icon="o-book-open"              link="/admin/islamic/quran" />
                    <x-menu-item title="Hadith"         icon="o-chat-bubble-left-right" link="/admin/islamic/hadith" />
                    <x-menu-item title="Asma ul Husna"  icon="o-sparkles"               link="/admin/islamic/asma" />
                    <x-menu-item title="Duas"           icon="o-hand-raised"            link="/admin/islamic/duas" />
                    <x-menu-item title="Prayer Times"   icon="o-clock"                  link="/admin/islamic/prayer" />
                    <x-menu-item title="Qibla"          icon="o-map-pin"                link="/admin/islamic/qibla" />
                    <x-menu-item title="Islamic Books"   icon="o-book-open"              link="/admin/islamic/books" />
                    <x-menu-item title="Islamic Courses" icon="o-academic-cap"            link="/admin/islamic/courses" />
                </x-menu-sub>
            @endif

            {{-- TEACHER MENU --}}
            @if(auth()->user()?->isTeacher())
                <x-menu-item :title="__('lms.dashboard')" icon="o-chart-pie" link="/dashboard" />
                <x-menu-item :title="__('lms.my_courses')" icon="o-cube" link="/teacher/courses" />
                <x-menu-item title="My Programs" icon="o-rectangle-stack" link="/teacher/programs" />
                <x-menu-item :title="__('lms.my_students')" icon="o-users" link="/teacher/students" />
                <x-menu-item :title="__('lms.live_classes')" icon="o-video-camera" link="/teacher/live-classes" />
                <x-menu-item :title="__('lms.assignments')" icon="o-pencil-square" link="/teacher/assignments" />
                <x-menu-item :title="__('lms.quizzes')" icon="o-clipboard-document-list" link="/teacher/quizzes" />
                <x-menu-item :title="__('lms.attendance')" icon="o-check-circle" link="/teacher/attendance" />
                <x-menu-item :title="__('lms.messages')" icon="o-envelope" link="/messages" />
            @endif

            {{-- STUDENT MENU --}}
            @if(auth()->user()?->isStudent())
                <x-menu-item :title="__('lms.dashboard')" icon="o-chart-pie" link="/dashboard" />
                <x-menu-item :title="__('lms.my_courses')" icon="o-cube" link="/student/courses" />
                <x-menu-item title="Programs" icon="o-rectangle-stack" link="/student/programs" />
                <x-menu-item title="Islamic Learning" icon="o-moon" link="/student/islamic-courses" />
                <x-menu-item :title="__('lms.live_classes')" icon="o-video-camera" link="/student/live-classes" />
                <x-menu-item :title="__('lms.assignments')" icon="o-pencil-square" link="/student/assignments" />
                <x-menu-item :title="__('lms.quizzes')" icon="o-clipboard-document-list" link="/student/quizzes" />
                <x-menu-item :title="__('lms.my_progress')" icon="o-chart-bar" link="/student/progress" />
                <x-menu-item title="My Subscription" icon="o-star" link="/student/subscriptions" />
                <x-menu-item :title="__('lms.messages')" icon="o-envelope" link="/messages" />
            @endif

            {{-- INDIVIDUAL MENU --}}
            @if(auth()->user()?->isIndividual())
                <x-menu-item title="Dashboard"       icon="o-chart-pie"        link="/individual/dashboard" />
                <x-menu-item title="Browse Programs" icon="o-rectangle-stack"  link="/individual/programs" />
                <x-menu-item title="My Programs"     icon="o-bookmark"         link="/individual/my-programs" />
                <x-menu-item title="Courses"         icon="o-book-open"        link="/individual/courses" />
                <x-menu-separator />
                <x-menu-sub title="Subscription" icon="o-star">
                    <x-menu-item title="Plans"   icon="o-credit-card" link="/individual/subscriptions" />
                    <x-menu-item title="History" icon="o-clock"       link="/individual/subscriptions/history" />
                </x-menu-sub>
                <x-menu-item title="Profile"         icon="o-user-circle"      link="/individual/profile" />
                <x-menu-item title="Messages"        icon="o-envelope"         link="/messages" />
            @endif

            {{-- PARENT MENU --}}
            @if(auth()->user()?->isParent())
                <x-menu-item :title="__('lms.dashboard')" icon="o-chart-pie" link="/dashboard" />
                <x-menu-item :title="__('lms.my_children')" icon="o-users" link="/parent/children" />
                <x-menu-item :title="__('lms.progress')" icon="o-chart-bar" link="/parent/progress" />
                <x-menu-item :title="__('lms.schedule')" icon="o-calendar" link="/parent/schedule" />
                <x-menu-item :title="__('lms.payments')" icon="o-credit-card" link="/parent/payments" />
                <x-menu-item :title="__('lms.messages')" icon="o-envelope" link="/messages" />
            @endif

            <x-menu-separator />
            <x-menu-item :title="__('lms.search')" @click.stop="$dispatch('mary-search-open')"
                         icon="o-magnifying-glass" badge="Ctrl+K" />

        </x-menu>
    </x-slot:sidebar>

    {{-- Main content --}}
    <x-slot:content>
        {{ $slot }}

        <div class="flex justify-center mt-8 pb-4">
            <span class="text-xs text-base-content/30">
                &copy; {{ date('Y') }} {{ config('app.name') }} — {{ __('lms.all_rights_reserved') }}
            </span>
        </div>
    </x-slot:content>
</x-main>

{{-- Toast notifications --}}
<x-toast />

{{-- Global search spotlight --}}
<x-spotlight search-text="{{ __('lms.search_placeholder') }}" />

{{-- Theme Toggle --}}
<x-theme-toggle class="hidden" />

</body>
</html>
