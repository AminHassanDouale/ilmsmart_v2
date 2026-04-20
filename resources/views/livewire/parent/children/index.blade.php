<?php

use App\Models\{Student, Enrollment, Attendance, QuizAttempt};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new
#[Layout('components.layouts.app')]
class extends Component
{
    public function with(): array
    {
        $parent   = auth()->user()->parentProfile;
        $children = $parent?->students()->with([
            'user',
            'grade.level',
            'enrollments.course',
            'quizAttempts' => fn($q) => $q->latest()->take(5),
        ])->get() ?? collect();

        return compact('children');
    }
}; ?>

<div>
<x-header :title="__('lms.my_children')" separator>
        <x-slot:subtitle>{{ $children->count() }} {{ __('lms.students') }}</x-slot:subtitle>
    </x-header>

    @forelse($children as $child)
        <x-card shadow class="mb-6">

            {{-- Child header --}}
            <div class="flex items-center gap-4 mb-6 pb-4 border-b border-base-200">
                <div class="avatar">
                    <div class="w-16 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                        <img src="{{ $child->user->avatar_url }}" alt="{{ $child->user->full_name }}" />
                    </div>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold">{{ $child->user->full_name }}</h2>
                    <p class="text-base-content/60">
                        {{ $child->grade?->translated_name }} &bull; {{ $child->student_number }}
                    </p>
                </div>
                <x-button :label="__('lms.view')" icon="o-eye"
                          :link="'/parent/children/'.$child->id"
                          class="btn-ghost btn-sm" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div class="text-center p-3 rounded-xl bg-primary/5">
                    <div class="text-2xl font-bold text-primary">{{ $child->enrollments->count() }}</div>
                    <div class="text-xs text-base-content/60">{{ __('lms.courses') }}</div>
                </div>
                <div class="text-center p-3 rounded-xl bg-success/5">
                    <div class="text-2xl font-bold text-success">{{ $child->enrollments->where('status','completed')->count() }}</div>
                    <div class="text-xs text-base-content/60">{{ __('lms.completed') }}</div>
                </div>
                <div class="text-center p-3 rounded-xl bg-warning/5">
                    <div class="text-2xl font-bold text-warning">{{ $child->quizAttempts->count() }}</div>
                    <div class="text-xs text-base-content/60">Quiz attempts</div>
                </div>
            </div>

            {{-- Enrolled Courses --}}
            @if($child->enrollments->count())
                <h4 class="font-semibold mb-2 text-sm">{{ __('lms.my_courses') }}</h4>
                <div class="space-y-2">
                    @foreach($child->enrollments->take(3) as $enrollment)
                        <div class="flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="truncate">{{ $enrollment->course->translated_title }}</span>
                                    <span class="font-bold text-primary shrink-0">{{ number_format($enrollment->progress_percent) }}%</span>
                                </div>
                                <progress class="progress progress-primary w-full h-1.5"
                                          value="{{ $enrollment->progress_percent }}" max="100"></progress>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </x-card>
    @empty
        <x-icon name="o-users" label="{{ __('lms.no_results') }}" class="h-64" />
    @endforelse

</div>
