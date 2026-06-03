<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public string $search      = '';
    public string $filterLevel = '';

    public function with(): array
    {
        $student = auth()->user()->student;
        $enrolledIds = $student
            ? Enrollment::where('student_id', $student->id)
                ->where('status', 'active')
                ->pluck('course_id')
                ->toArray()
            : [];

        $courses = Course::withCount(['modules', 'lessons', 'enrollments'])
            ->where('is_islamic', true)
            ->where('status', 'published')
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterLevel, fn($q) => $q->where('level', $this->filterLevel))
            ->orderByRaw("FIELD(level, 'beginner', 'intermediate', 'premium')")
            ->get();

        // Attach enrollment data
        $enrollments = $student
            ? Enrollment::where('student_id', $student->id)
                ->whereIn('course_id', $courses->pluck('id'))
                ->get()
                ->keyBy('course_id')
            : collect();

        return compact('courses', 'enrolledIds', 'enrollments');
    }

    public function enroll(int $courseId): void
    {
        $student = auth()->user()->student;
        if (!$student) {
            $this->error('Student profile not found.');
            return;
        }

        $course = Course::findOrFail($courseId);

        // For paid courses, redirect to payment instead of free enroll
        if ($course->type !== 'free' && $course->price > 0) {
            $this->warning('This course requires payment. Redirecting to checkout...');
            return;
        }

        app(EnrollmentService::class)->enroll($student, $course);
        $this->success('Enrolled successfully! Check your email for details.');
    }

    public function levelColor(string $level): string
    {
        return match($level) {
            'beginner'     => 'from-emerald-400 to-teal-500',
            'intermediate' => 'from-blue-400 to-indigo-500',
            'premium'      => 'from-amber-400 to-orange-500',
            default        => 'from-gray-400 to-gray-500',
        };
    }

    public function levelIcon(string $level): string
    {
        return match($level) {
            'beginner'     => 'o-academic-cap',
            'intermediate' => 'o-star',
            'premium'      => 'o-sparkles',
            default        => 'o-cube',
        };
    }
};
?>

<div>
    <x-header title="Islamic Learning Path" subtitle="Begin your journey of seeking sacred knowledge">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search courses..." wire:model.live.debounce="search"
                     icon="o-magnifying-glass" class="w-full sm:w-64" />
        </x-slot:middle>
    </x-header>

    {{-- Level filter chips --}}
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button wire:click="$set('filterLevel', '')"
                @class(['btn btn-sm shrink-0', 'btn-primary' => $filterLevel === '', 'btn-ghost' => $filterLevel !== ''])>
            All Levels
        </button>
        <button wire:click="$set('filterLevel', 'beginner')"
                @class(['btn btn-sm shrink-0', 'btn-success' => $filterLevel === 'beginner', 'btn-ghost' => $filterLevel !== 'beginner'])>
            <x-icon name="o-academic-cap" class="w-4 h-4" /> Beginner
        </button>
        <button wire:click="$set('filterLevel', 'intermediate')"
                @class(['btn btn-sm shrink-0', 'btn-info' => $filterLevel === 'intermediate', 'btn-ghost' => $filterLevel !== 'intermediate'])>
            <x-icon name="o-star" class="w-4 h-4" /> Intermediate
        </button>
        <button wire:click="$set('filterLevel', 'premium')"
                @class(['btn btn-sm shrink-0', 'btn-warning' => $filterLevel === 'premium', 'btn-ghost' => $filterLevel !== 'premium'])>
            <x-icon name="o-sparkles" class="w-4 h-4" /> Premium
        </button>
    </div>

    {{-- Course grid --}}
    @if($courses->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-moon" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No courses match your search.</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
            @foreach($courses as $course)
                @php
                    $enrollment = $enrollments->get($course->id);
                    $isEnrolled = $enrollment && $enrollment->status === 'active';
                    $isCompleted = $enrollment && $enrollment->status === 'completed';
                @endphp
                <div class="card bg-base-100 shadow-sm hover:shadow-xl transition-all overflow-hidden">
                    {{-- Banner --}}
                    <div class="relative h-32 sm:h-40 bg-gradient-to-br {{ $this->levelColor($course->level ?? 'beginner') }}">
                        @if($course->thumbnail)
                            <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}"
                                 class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <x-icon name="{{ $this->levelIcon($course->level ?? 'beginner') }}" class="w-16 h-16 text-white/40" />
                            </div>
                        @endif

                        {{-- Level badge --}}
                        <div class="absolute top-3 left-3">
                            <span class="badge badge-sm bg-white/90 text-gray-800 border-0 capitalize font-semibold">
                                {{ $course->level ?? '—' }}
                            </span>
                        </div>

                        {{-- Status badge --}}
                        @if($isCompleted)
                            <div class="absolute top-3 right-3">
                                <span class="badge badge-success badge-sm gap-1">
                                    <x-icon name="o-check-circle" class="w-3 h-3" />
                                    Completed
                                </span>
                            </div>
                        @elseif($isEnrolled)
                            <div class="absolute top-3 right-3">
                                <span class="badge badge-info badge-sm">Enrolled</span>
                            </div>
                        @endif
                    </div>

                    <div class="card-body p-4 sm:p-5">
                        <h3 class="font-bold text-base sm:text-lg leading-tight">{{ $course->title }}</h3>

                        @if($course->description)
                            <p class="text-xs sm:text-sm text-base-content/60 line-clamp-2">{{ $course->description }}</p>
                        @endif

                        {{-- Meta --}}
                        <div class="flex flex-wrap gap-3 text-xs text-base-content/50 mt-2">
                            <span class="flex items-center gap-1">
                                <x-icon name="o-rectangle-stack" class="w-4 h-4" />
                                {{ $course->modules_count }} modules
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-book-open" class="w-4 h-4" />
                                {{ $course->lessons_count }} lessons
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-users" class="w-4 h-4" />
                                {{ $course->enrollments_count }}
                            </span>
                        </div>

                        {{-- Progress bar --}}
                        @if($isEnrolled && $enrollment->progress_percent > 0)
                            <div class="mt-2">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-base-content/60">Progress</span>
                                    <span class="font-semibold text-primary">{{ number_format($enrollment->progress_percent, 0) }}%</span>
                                </div>
                                <progress class="progress progress-primary w-full h-1.5"
                                          value="{{ $enrollment->progress_percent }}" max="100"></progress>
                            </div>
                        @endif

                        {{-- Action button --}}
                        <div class="card-actions justify-end mt-3">
                            @if($isEnrolled || $isCompleted)
                                <a href="{{ route('student.courses.show', $course) }}" wire:navigate
                                   class="btn btn-primary btn-sm w-full">
                                    @if($isCompleted)
                                        <x-icon name="o-arrow-path" class="w-4 h-4" /> Review
                                    @else
                                        <x-icon name="o-play" class="w-4 h-4" /> Continue
                                    @endif
                                </a>
                            @elseif($course->type === 'free')
                                <button wire:click="enroll({{ $course->id }})"
                                        class="btn btn-primary btn-sm w-full"
                                        wire:loading.attr="disabled">
                                    <x-icon name="o-plus" class="w-4 h-4" />
                                    Enroll Free
                                </button>
                            @else
                                <button wire:click="enroll({{ $course->id }})"
                                        class="btn btn-warning btn-sm w-full">
                                    <x-icon name="o-lock-closed" class="w-4 h-4" />
                                    {{ number_format($course->price, 0) }} DZD
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
