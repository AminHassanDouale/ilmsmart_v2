<?php

use App\Models\{Course, Enrollment, LessonProgress, Lesson};
use App\Services\ProgressService;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use Toast;

    public int     $courseId;
    public Course  $course;
    public ?int    $activeLessonId = null;

    public function mount(Course $course): void
    {
        $this->course   = $course->load(['modules.lessons.documents', 'teacher.user', 'subject']);
        $this->courseId = $course->id;

        // Check enrollment
        $student = auth()->user()->student;
        if ($student && !$student->courses()->where('courses.id', $course->id)->where('enrollments.status','active')->exists()) {
            abort(403, 'Not enrolled');
        }

        // Open first incomplete lesson by default
        $this->activeLessonId = $this->firstIncompleteLesson()?->id;
    }

    public function with(): array
    {
        $student     = auth()->user()->student;
        $completedIds = LessonProgress::where('student_id', $student?->id)
            ->where('course_id', $this->courseId)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->toArray();

        $enrollment = Enrollment::where('student_id', $student?->id)
                                 ->where('course_id', $this->courseId)
                                 ->first();

        $activeLesson = $this->activeLessonId
            ? Lesson::with('documents','quiz','assignment')->find($this->activeLessonId)
            : null;

        return compact('completedIds', 'enrollment', 'activeLesson');
    }

    public function openLesson(int $lessonId): void
    {
        $this->activeLessonId = $lessonId;
    }

    public function markComplete(int $lessonId): void
    {
        $student = auth()->user()->student;
        if (!$student) return;

        app(ProgressService::class)->markLessonComplete($student, $lessonId, $this->courseId);
        $this->success('Lesson completed!');
    }

    private function firstIncompleteLesson(): ?Lesson
    {
        $student      = auth()->user()->student;
        $completedIds = LessonProgress::where('student_id', $student?->id)
            ->where('course_id', $this->courseId)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->toArray();

        foreach ($this->course->modules as $module) {
            foreach ($module->lessons->where('status', 'published') as $lesson) {
                if (!in_array($lesson->id, $completedIds)) return $lesson;
            }
        }
        return null;
    }
}; ?>

<div>
<x-header :title="$course->translated_title" :subtitle="$course->subject->name" separator>
        <x-slot:actions>
            <x-button :label="__('lms.back')" icon="o-arrow-left" link="/student/courses" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    {{-- Progress Bar --}}
    @if($enrollment)
        <div class="mb-6 p-4 bg-base-100 rounded-xl shadow flex items-center gap-4">
            <div class="flex-1">
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-semibold">{{ __('lms.progress') }}</span>
                    <span class="text-primary font-bold">{{ number_format($enrollment->progress_percent, 0) }}%</span>
                </div>
                <progress class="progress progress-primary w-full"
                          value="{{ $enrollment->progress_percent }}" max="100"></progress>
            </div>
            @if($enrollment->status === 'completed')
                <x-badge value="✓ Completed" class="badge-success badge-lg" />
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Sidebar: Curriculum --}}
        <div class="lg:col-span-1 order-2 lg:order-1">
            <x-card :title="__('lms.modules')" shadow>
                <div class="space-y-3">
                    @foreach($course->modules as $module)
                        <div>
                            <p class="font-bold text-sm text-primary mb-2">{{ $module->translated_title }}</p>
                            <div class="space-y-1">
                                @foreach($module->lessons->where('status','published') as $lesson)
                                    <div wire:click="openLesson({{ $lesson->id }})"
                                         class="lesson-item {{ $activeLessonId === $lesson->id ? 'bg-primary/10 border border-primary/20' : '' }}">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0
                                            {{ in_array($lesson->id, $completedIds) ? 'bg-success text-white' : 'bg-base-200' }}">
                                            @if(in_array($lesson->id, $completedIds))
                                                <x-icon name="o-check" class="w-4 h-4" />
                                            @else
                                                <x-icon :name="$lesson->type_icon" class="w-4 h-4" />
                                            @endif
                                        </div>
                                        <span class="text-sm truncate {{ in_array($lesson->id, $completedIds) ? 'line-through text-base-content/40' : '' }}">
                                            {{ $lesson->translated_title }}
                                        </span>
                                        @if($lesson->duration_minutes)
                                            <span class="text-xs text-base-content/40 ms-auto shrink-0">{{ $lesson->duration_minutes }}m</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        {{-- Main: Lesson Content --}}
        <div class="lg:col-span-2 order-1 lg:order-2">
            @if($activeLesson)
                <x-card shadow>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <x-icon :name="$activeLesson->type_icon" class="w-5 h-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="font-bold">{{ $activeLesson->translated_title }}</h2>
                            <p class="text-xs text-base-content/60 capitalize">{{ $activeLesson->type }}</p>
                        </div>
                    </div>

                    {{-- Video Player --}}
                    @if($activeLesson->type === 'video' && $activeLesson->video_url)
                        <div class="aspect-video rounded-xl overflow-hidden bg-black mb-4">
                            @if($activeLesson->video_provider === 'youtube')
                                <iframe class="w-full h-full"
                                        src="https://www.youtube.com/embed/{{ $activeLesson->video_url }}"
                                        allowfullscreen></iframe>
                            @else
                                <video controls class="w-full h-full" src="{{ $activeLesson->video_url }}"></video>
                            @endif
                        </div>
                    @endif

                    {{-- Content --}}
                    @if($activeLesson->content)
                        <div class="prose max-w-none mb-4">
                            {!! $activeLesson->content !!}
                        </div>
                    @endif

                    {{-- Documents --}}
                    @if($activeLesson->documents->count())
                        <div class="mb-4">
                            <h4 class="font-semibold mb-2">{{ __('lms.lessons') }} Documents</h4>
                            <div class="space-y-2">
                                @foreach($activeLesson->documents as $doc)
                                    <a href="{{ $doc->url }}" target="_blank"
                                       class="flex items-center gap-2 p-2 rounded-lg bg-base-200 hover:bg-base-300 transition-colors text-sm">
                                        <x-icon :name="$doc->icon" class="w-5 h-5 text-primary" />
                                        {{ $doc->title }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Mark complete button --}}
                    @if(!in_array($activeLesson->id, $completedIds))
                        <x-button :label="'✓ ' . __('lms.completed')"
                                  wire:click="markComplete({{ $activeLesson->id }})"
                                  class="btn-success w-full" spinner="markComplete({{ $activeLesson->id }})" />
                    @else
                        <div class="flex items-center justify-center gap-2 p-3 bg-success/10 rounded-xl text-success font-semibold">
                            <x-icon name="o-check-circle" class="w-5 h-5" />
                            {{ __('lms.completed') }}
                        </div>
                    @endif
                </x-card>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <x-icon name="o-play-circle" class="w-16 h-16 mb-2" />
                    <p>Select a lesson to begin</p>
                </div>
            @endif
        </div>
    </div>

</div>
