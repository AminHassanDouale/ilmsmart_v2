<?php

use App\Models\{Enrollment, QuizAttempt, AssignmentSubmission, Certificate};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new
#[Layout('components.layouts.app')]
class extends Component
{
    public function with(): array
    {
        $student = auth()->user()->student;

        $enrollments = Enrollment::with(['course.subject', 'course.teacher.user'])
            ->where('student_id', $student?->id)
            ->get();

        $quizAttempts = QuizAttempt::with('quiz.course')
            ->where('student_id', $student?->id)
            ->where('status', 'submitted')
            ->latest()
            ->take(10)
            ->get();

        $certificates = Certificate::with('course')
            ->where('student_id', $student?->id)
            ->latest()
            ->get();

        $progressChart = [
            'type' => 'radar',
            'data' => [
                'labels'   => $enrollments->map(fn($e) => $e->course->subject->name ?? $e->course->title)->take(6)->values()->toArray(),
                'datasets' => [[
                    'label'           => 'Progress %',
                    'data'            => $enrollments->pluck('progress_percent')->take(6)->values()->toArray(),
                    'backgroundColor' => 'rgba(99,102,241,0.2)',
                    'borderColor'     => '#6366f1',
                ]],
            ],
        ];

        return compact('enrollments', 'quizAttempts', 'certificates', 'progressChart');
    }
}; ?>

<div>
<x-header :title="__('lms.my_progress')" separator />

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat :title="__('lms.my_courses')"
                :value="$enrollments->count()"
                icon="o-cube" color="text-primary" />
        <x-stat :title="__('lms.completed')"
                :value="$enrollments->where('status','completed')->count()"
                icon="o-check-circle" color="text-success" />
        <x-stat title="Quizzes Passed"
                :value="$quizAttempts->where('passed', true)->count()"
                icon="o-star" color="text-warning" />
        <x-stat title="Certificates"
                :value="$certificates->count()"
                icon="o-trophy" color="text-secondary" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Radar Chart --}}
        @if($enrollments->count())
            <x-card title="Subject Progress" shadow>
                <x-chart wire:model="progressChart" class="h-64" />
            </x-card>
        @endif

        {{-- Recent Quiz Results --}}
        <x-card title="Recent Quiz Results" shadow>
            @forelse($quizAttempts as $attempt)
                <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                    <div>
                        <p class="font-semibold text-sm">{{ $attempt->quiz->title }}</p>
                        <p class="text-xs text-base-content/60">{{ $attempt->submitted_at?->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold {{ $attempt->passed ? 'text-success' : 'text-error' }}">
                            {{ number_format($attempt->score, 1) }}%
                        </p>
                        <x-badge :value="$attempt->passed ? __('lms.passed') : __('lms.failed')"
                                 :class="$attempt->passed ? 'badge-success' : 'badge-error'" />
                    </div>
                </div>
            @empty
                <p class="text-center text-base-content/40 py-6">No quizzes taken yet</p>
            @endforelse
        </x-card>
    </div>

    {{-- Course Progress Table --}}
    <x-card :title="__('lms.courses')" shadow class="mb-6">
        <div class="space-y-4">
            @forelse($enrollments as $enrollment)
                <div class="flex items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between mb-1">
                            <span class="font-semibold text-sm truncate">{{ $enrollment->course->translated_title }}</span>
                            <span class="text-sm font-bold text-primary">{{ number_format($enrollment->progress_percent) }}%</span>
                        </div>
                        <progress class="progress progress-primary w-full h-2"
                                  value="{{ $enrollment->progress_percent }}" max="100"></progress>
                    </div>
                    <x-badge :value="__('lms.'.$enrollment->status)"
                             class="{{ $enrollment->status === 'completed' ? 'badge-success' : 'badge-warning' }} badge-soft shrink-0" />
                </div>
            @empty
                <p class="text-center text-base-content/40 py-6">{{ __('lms.no_results') }}</p>
            @endforelse
        </div>
    </x-card>

    {{-- Certificates --}}
    @if($certificates->count())
        <x-card title="My Certificates" shadow>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($certificates as $cert)
                    <div class="border-2 border-warning rounded-xl p-4 bg-warning/5 text-center">
                        <x-icon name="o-trophy" class="w-10 h-10 text-warning mx-auto mb-2" />
                        <p class="font-bold text-sm">{{ $cert->course->translated_title }}</p>
                        <p class="text-xs text-base-content/60">{{ $cert->issued_at->format('d/m/Y') }}</p>
                        <p class="text-xs font-mono text-base-content/40 mt-1">{{ $cert->certificate_number }}</p>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

</div>
