<?php

use App\Models\{Student, Teacher, Course, Payment, LiveClass, Enrollment, Announcement};
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class extends Component
{
    public function with(): array
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminData();
        } elseif ($user->isTeacher()) {
            return $this->teacherData($user);
        } elseif ($user->isStudent()) {
            return $this->studentData($user);
        } elseif ($user->isParent()) {
            return $this->parentData($user);
        }

        return [];
    }

    private function adminData(): array
    {
        return [
            'stats' => [
                ['label' => __('lms.total_students'),  'value' => Student::count(),          'icon' => 'o-users',      'color' => 'text-primary'],
                ['label' => __('lms.total_teachers'),  'value' => Teacher::count(),           'icon' => 'o-academic-cap','color' => 'text-secondary'],
                ['label' => __('lms.active_courses'),  'value' => Course::where('status','published')->count(), 'icon' => 'o-cube', 'color' => 'text-success'],
                ['label' => __('lms.total_revenue'),   'value' => number_format(Payment::where('status','completed')->sum('amount')) . ' DA', 'icon' => 'o-banknotes', 'color' => 'text-warning'],
            ],
            'recent_payments'  => Payment::with('user')->latest()->take(5)->get(),
            'upcoming_classes' => LiveClass::with('teacher.user','course')->where('start_time', '>=', now())->orderBy('start_time')->take(5)->get(),
            'announcements'    => Announcement::latest()->take(3)->get(),
            'monthly_revenue'  => $this->monthlyRevenue(),
            'enrollments_chart'=> $this->enrollmentsChart(),
        ];
    }

    private function teacherData($user): array
    {
        $teacher = $user->teacher;
        return [
            'stats' => [
                ['label' => __('lms.my_courses'),     'value' => $teacher?->courses()->count() ?? 0,         'icon' => 'o-cube',        'color' => 'text-primary'],
                ['label' => __('lms.my_students'),    'value' => Enrollment::whereIn('course_id', $teacher?->courses()->pluck('id') ?? [])->distinct('student_id')->count(), 'icon' => 'o-users', 'color' => 'text-secondary'],
                ['label' => __('lms.live_classes'),   'value' => $teacher?->liveClasses()->where('status','scheduled')->count() ?? 0, 'icon' => 'o-video-camera', 'color' => 'text-success'],
                ['label' => __('lms.assignments'),    'value' => $teacher?->assignments()->count() ?? 0,      'icon' => 'o-pencil-square','color' => 'text-warning'],
            ],
            'upcoming_classes' => LiveClass::where('teacher_id', $teacher?->id)->where('start_time', '>=', now())->orderBy('start_time')->take(5)->get(),
            'announcements'    => Announcement::latest()->take(3)->get(),
        ];
    }

    private function studentData($user): array
    {
        $student = $user->student;
        $enrollments = $student?->enrollments()->with('course')->get() ?? collect();
        return [
            'stats' => [
                ['label' => __('lms.my_courses'),      'value' => $enrollments->count(),                                       'icon' => 'o-cube',            'color' => 'text-primary'],
                ['label' => __('lms.completed'),       'value' => $enrollments->where('status','completed')->count(),          'icon' => 'o-check-circle',    'color' => 'text-success'],
                ['label' => __('lms.in_progress'),     'value' => $enrollments->where('status','active')->count(),             'icon' => 'o-play-circle',     'color' => 'text-warning'],
                ['label' => __('lms.average_score'),   'value' => '—',                                                         'icon' => 'o-star',            'color' => 'text-secondary'],
            ],
            'enrollments'      => $enrollments->take(4),
            'upcoming_classes' => LiveClass::whereHas('students', fn($q) => $q->where('student_id', $student?->id))->where('start_time', '>=', now())->orderBy('start_time')->take(3)->get(),
            'announcements'    => Announcement::latest()->take(3)->get(),
        ];
    }

    private function parentData($user): array
    {
        $parent  = $user->parentProfile;
        $students = $parent?->students()->with('user','grade.level','enrollments')->get() ?? collect();
        return [
            'children'         => $students,
            'upcoming_classes' => collect(),
            'announcements'    => Announcement::latest()->take(3)->get(),
        ];
    }

    private function monthlyRevenue(): array
    {
        $months = collect(range(5, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'month'  => $date->translatedFormat('M'),
                'amount' => Payment::where('status', 'completed')
                    ->whereYear('paid_at', $date->year)
                    ->whereMonth('paid_at', $date->month)
                    ->sum('amount'),
            ];
        });

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $months->pluck('month')->toArray(),
                'datasets' => [[
                    'label'           => __('lms.total_revenue'),
                    'data'            => $months->pluck('amount')->toArray(),
                    'backgroundColor' => 'rgba(99,102,241,0.7)',
                    'borderRadius'    => 6,
                ]],
            ],
            'options' => ['plugins' => ['legend' => ['display' => false]]],
        ];
    }

    private function enrollmentsChart(): array
    {
        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => [__('lms.active'), __('lms.completed'), __('lms.inactive')],
                'datasets' => [[
                    'data'            => [
                        Enrollment::where('status','active')->count(),
                        Enrollment::where('status','completed')->count(),
                        Enrollment::where('status','cancelled')->count(),
                    ],
                    'backgroundColor' => ['#6366f1','#22c55e','#f59e0b'],
                ]],
            ],
        ];
    }
}; ?>

<div>
    <x-header :title="__('lms.dashboard')" separator>
        <x-slot:subtitle>
            {{ __('lms.welcome_back') }}, {{ auth()->user()->full_name }}
        </x-slot:subtitle>
        <x-slot:actions>
            <x-badge :value="auth()->user()->role" class="badge-primary badge-soft capitalize" />
        </x-slot:actions>
    </x-header>

    {{-- Announcements --}}
    @if(!empty($announcements) && $announcements->count())
        <div class="mb-6 space-y-2">
            @foreach($announcements as $ann)
                <x-alert :title="$ann->title" :description="$ann->content"
                         icon="o-megaphone" class="alert-info" />
            @endforeach
        </div>
    @endif

    {{-- Stats Cards --}}
    @if(!empty($stats))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach($stats as $stat)
                <x-stat :title="$stat['label']" :value="$stat['value']"
                        :icon="$stat['icon']" :color="$stat['color'] ?? ''" />
            @endforeach
        </div>
    @endif

    {{-- Admin: Charts row --}}
    @if(auth()->user()->isAdmin())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2">
                <x-card :title="__('lms.total_revenue')" shadow separator>
                    <x-chart wire:model="monthly_revenue" class="h-56" />
                </x-card>
            </div>
            <div>
                <x-card title="Enrollments" shadow separator>
                    <x-chart wire:model="enrollments_chart" class="h-56" />
                </x-card>
            </div>
        </div>
    @endif

    {{-- Upcoming Live Classes --}}
    @if(!empty($upcoming_classes) && $upcoming_classes->count())
        <x-card :title="__('lms.upcoming_classes')" shadow separator class="mb-6">
            <div class="divide-y divide-base-200">
                @foreach($upcoming_classes as $class)
                    <div class="flex items-center justify-between py-3 px-1">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                <x-icon name="o-video-camera" class="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <div class="font-semibold text-sm">{{ $class->title }}</div>
                                <div class="text-xs text-base-content/60">
                                    {{ $class->teacher->user->full_name ?? '' }}
                                    &bull; {{ $class->start_time->format('d/m H:i') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :value="$class->platform" class="badge-soft badge-secondary" />
                            @if($class->meeting_link)
                                <x-button label="Join" icon="o-arrow-right-circle"
                                          :link="$class->meeting_link" external
                                          class="btn-sm btn-primary" />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    {{-- Student: My Courses progress --}}
    @if(auth()->user()->isStudent() && !empty($enrollments) && $enrollments->count())
        <x-card :title="__('lms.my_courses')" shadow separator>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($enrollments as $enrollment)
                    <div class="border border-base-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            @if($enrollment->course->thumbnail)
                                <img src="{{ asset('storage/'.$enrollment->course->thumbnail) }}"
                                     class="w-16 h-16 rounded-lg object-cover" alt="">
                            @else
                                <div class="w-16 h-16 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <x-icon name="o-cube" class="w-8 h-8 text-primary" />
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm truncate">{{ $enrollment->course->translated_title }}</div>
                                <div class="text-xs text-base-content/60 mb-2">{{ $enrollment->course->teacher->user->full_name ?? '' }}</div>
                                <progress class="progress progress-primary w-full h-2"
                                          value="{{ $enrollment->progress_percent }}" max="100"></progress>
                                <div class="text-xs text-right mt-1">{{ number_format($enrollment->progress_percent, 0) }}%</div>
                            </div>
                        </div>
                        <x-button :label="__('lms.continue')" icon="o-play-circle"
                                  :link="'/student/courses/'.$enrollment->course_id"
                                  class="btn-primary btn-sm w-full mt-3" />
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    {{-- Parent: Children overview --}}
    @if(auth()->user()->isParent() && !empty($children))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($children as $child)
                <x-card shadow class="hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar">
                            <div class="w-12 rounded-full">
                                <img src="{{ $child->user->avatar_url }}" alt="{{ $child->user->full_name }}" />
                            </div>
                        </div>
                        <div>
                            <div class="font-bold">{{ $child->user->full_name }}</div>
                            <div class="text-xs text-base-content/60">{{ $child->grade?->translated_name }}</div>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm mb-3">
                        <span class="text-base-content/60">{{ __('lms.courses') }}</span>
                        <span class="font-semibold">{{ $child->enrollments->count() }}</span>
                    </div>
                    <x-button :label="__('lms.view')" icon="o-eye"
                              :link="'/parent/children/'.$child->id"
                              class="btn-ghost btn-sm w-full" />
                </x-card>
            @endforeach
        </div>
    @endif
</div>
