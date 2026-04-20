<?php
use App\Models\{Course, Lesson, LessonProgress, Enrollment};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public Lesson $lesson;

    public bool $isCompleted = false;

    public function mount(Course $course, Lesson $lesson): void
    {
        $this->course = $course->load(['modules.lessons']);
        $this->lesson = $lesson->load(['module', 'documents']);

        // Verify lesson belongs to this course
        if ($lesson->module->course_id !== $course->id) {
            abort(404);
        }

        // Check if student is enrolled (or lesson is free preview)
        $user = auth()->user();
        if (!$lesson->is_free_preview && $user->student) {
            $enrolled = Enrollment::where('student_id', $user->student->id)
                                  ->where('course_id', $course->id)
                                  ->exists();
            if (!$enrolled) {
                abort(403, 'Veuillez vous inscrire au cours pour accéder à cette leçon.');
            }
        }

        // Check completion
        if ($user->student) {
            $this->isCompleted = LessonProgress::where('student_id', $user->student->id)
                                               ->where('lesson_id', $lesson->id)
                                               ->where('completed', true)
                                               ->exists();
        }
    }

    public function title(): string
    {
        return ($this->lesson->title_fr ?: $this->lesson->title) . ' — ' . ($this->course->title_fr ?: $this->course->title);
    }

    public function markComplete(): void
    {
        $student = auth()->user()->student;
        if (!$student) return;

        LessonProgress::updateOrCreate(
            ['student_id' => $student->id, 'lesson_id' => $this->lesson->id],
            [
                'course_id'          => $this->course->id,
                'completed'          => true,
                'completion_percent' => 100,
                'completed_at'       => now(),
            ]
        );

        $this->isCompleted = true;

        // Recalculate course progress
        $totalLessons     = $this->course->lessons()->count();
        $completedLessons = LessonProgress::where('student_id', $student->id)
                                          ->where('course_id', $this->course->id)
                                          ->where('completed', true)
                                          ->count();

        $percent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        Enrollment::where('student_id', $student->id)
                  ->where('course_id', $this->course->id)
                  ->update(['progress_percent' => $percent]);

        $this->success('Leçon terminée ! Progression : ' . $percent . '%');
    }

    public function with(): array
    {
        // Build flat lesson list for prev/next navigation
        $allLessons = collect();
        foreach ($this->course->modules as $module) {
            foreach ($module->lessons as $l) {
                $allLessons->push($l);
            }
        }

        $currentIndex = $allLessons->search(fn($l) => $l->id === $this->lesson->id);
        $prevLesson   = $currentIndex > 0 ? $allLessons->get($currentIndex - 1) : null;
        $nextLesson   = $currentIndex < $allLessons->count() - 1 ? $allLessons->get($currentIndex + 1) : null;

        // Progress for this student
        $student = auth()->user()->student;
        $completedIds = $student
            ? LessonProgress::where('student_id', $student->id)
                            ->where('course_id', $this->course->id)
                            ->where('completed', true)
                            ->pluck('lesson_id')
                            ->toArray()
            : [];

        $enrollment = $student
            ? Enrollment::where('student_id', $student->id)
                        ->where('course_id', $this->course->id)
                        ->first()
            : null;

        return compact('allLessons', 'prevLesson', 'nextLesson', 'completedIds', 'enrollment');
    }

    private function youtubeId(string $url): ?string
    {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $url, $m);
        return $m[1] ?? null;
    }
}; ?>

<div>
    <x-header separator>
        <x-slot:title>
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ route('student.courses.show', $course->id) }}" class="link link-hover text-base-content/60">
                    {{ $course->title_fr ?: $course->title }}
                </a>
                <x-icon name="o-chevron-right" class="w-4 h-4 text-base-content/40" />
                <span class="font-semibold">{{ $lesson->title_fr ?: $lesson->title }}</span>
            </div>
        </x-slot:title>
        <x-slot:actions>
            @if(!$isCompleted)
                <x-button label="Marquer terminée" icon="o-check-circle"
                          wire:click="markComplete" class="btn-success btn-sm" spinner="markComplete" />
            @else
                <x-badge value="✓ Terminée" class="badge-success badge-lg" />
            @endif
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- ── Main content ── --}}
        <div class="lg:col-span-3 space-y-4">

            {{-- Video embed --}}
            @if(in_array($lesson->type, ['video','live']) && $lesson->video_url)
                @php
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $lesson->video_url, $ym);
                    preg_match('/vimeo\.com\/(\d+)/', $lesson->video_url, $vm);
                    $ytId   = $ym[1] ?? null;
                    $vimId  = $vm[1] ?? null;
                @endphp

                <x-card shadow class="overflow-hidden p-0">
                    <div class="aspect-video bg-black">
                        @if($ytId)
                            <iframe src="https://www.youtube.com/embed/{{ $ytId }}?rel=0"
                                    class="w-full h-full" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                        @elseif($vimId)
                            <iframe src="https://player.vimeo.com/video/{{ $vimId }}"
                                    class="w-full h-full" frameborder="0"
                                    allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                        @else
                            <video src="{{ $lesson->video_url }}" controls class="w-full h-full"></video>
                        @endif
                    </div>
                </x-card>
            @endif

            {{-- Lesson info header --}}
            <x-card shadow>
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            @php
                                $icon = match($lesson->type) {
                                    'video'      => 'o-play-circle',
                                    'document'   => 'o-document-text',
                                    'quiz'       => 'o-clipboard-document-list',
                                    'assignment' => 'o-pencil-square',
                                    'live'       => 'o-video-camera',
                                    default      => 'o-book-open',
                                };
                                $typeName = match($lesson->type) {
                                    'video'      => 'Vidéo',
                                    'document'   => 'Document',
                                    'quiz'       => 'Quiz',
                                    'assignment' => 'Devoir',
                                    'live'       => 'Cours en direct',
                                    default      => 'Texte',
                                };
                            @endphp
                            <x-icon :name="$icon" class="w-5 h-5 text-primary" />
                            <x-badge :value="$typeName" class="badge-primary badge-sm" />
                            @if($lesson->duration_minutes)
                                <span class="text-xs text-base-content/50">
                                    <x-icon name="o-clock" class="w-3 h-3 inline" />
                                    {{ $lesson->duration_minutes }} min
                                </span>
                            @endif
                            @if($lesson->is_free_preview)
                                <x-badge value="Aperçu gratuit" class="badge-success badge-xs" />
                            @endif
                        </div>
                        <h1 class="text-xl font-bold">{{ $lesson->title_fr ?: $lesson->title }}</h1>
                        @if($lesson->title_ar)
                            <p class="text-base-content/60 font-arabic mt-0.5" dir="rtl">{{ $lesson->title_ar }}</p>
                        @endif
                    </div>
                    @if($isCompleted)
                        <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center">
                            <x-icon name="o-check-circle" class="w-6 h-6 text-success" />
                        </div>
                    @endif
                </div>
            </x-card>

            {{-- Content --}}
            @if($lesson->content && $lesson->content !== 'Contenu de la leçon...')
                <x-card shadow>
                    <div class="prose prose-sm max-w-none">
                        {!! nl2br(e($lesson->content)) !!}
                    </div>
                </x-card>
            @endif

            {{-- Documents --}}
            @if($lesson->documents->isNotEmpty())
                <x-card shadow title="Documents">
                    <div class="space-y-2">
                        @foreach($lesson->documents as $doc)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-base-300 hover:bg-base-50">
                                <div class="flex items-center gap-2">
                                    <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                                    <span class="text-sm font-medium">{{ $doc->title ?? $doc->filename }}</span>
                                </div>
                                <a href="{{ asset('storage/' . $doc->path) }}" target="_blank"
                                   class="btn btn-ghost btn-xs">
                                    <x-icon name="o-arrow-down-tray" class="w-4 h-4" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- Quiz placeholder --}}
            @if($lesson->type === 'quiz')
                <x-card shadow class="border-2 border-primary/20">
                    <div class="text-center py-8">
                        <x-icon name="o-clipboard-document-list" class="w-16 h-16 mx-auto text-primary/40 mb-4" />
                        <h3 class="font-bold text-lg mb-2">Quiz : {{ $lesson->title_fr ?: $lesson->title }}</h3>
                        <p class="text-base-content/60 mb-4">Testez vos connaissances sur ce chapitre.</p>
                        <x-button label="Commencer le quiz" icon="o-play" class="btn-primary" />
                    </div>
                </x-card>
            @endif

            {{-- Prev / Next navigation --}}
            <div class="flex justify-between gap-4 pt-2">
                @if($prevLesson)
                    <a href="{{ route('student.lessons.show', [$course->id, $prevLesson->id]) }}"
                       class="btn btn-ghost btn-sm flex-1 justify-start gap-2">
                        <x-icon name="o-arrow-left" class="w-4 h-4" />
                        <span class="truncate text-left">{{ $prevLesson->title_fr ?: $prevLesson->title }}</span>
                    </a>
                @else
                    <div></div>
                @endif

                @if($nextLesson)
                    <a href="{{ route('student.lessons.show', [$course->id, $nextLesson->id]) }}"
                       class="btn btn-primary btn-sm flex-1 justify-end gap-2">
                        <span class="truncate text-right">{{ $nextLesson->title_fr ?: $nextLesson->title }}</span>
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                @else
                    @if(!$isCompleted)
                        <x-button label="Terminer le cours ✓" wire:click="markComplete"
                                  class="btn-success btn-sm flex-1" spinner="markComplete" />
                    @else
                        <a href="{{ route('student.courses.show', $course->id) }}"
                           class="btn btn-success btn-sm flex-1 justify-center gap-2">
                            <x-icon name="o-academic-cap" class="w-4 h-4" />
                            Voir le cours
                        </a>
                    @endif
                @endif
            </div>
        </div>

        {{-- ── Sidebar: Module/Lesson list ── --}}
        <div class="lg:col-span-1">
            <x-card shadow class="sticky top-20">
                {{-- Progress bar --}}
                @if($enrollment)
                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-base-content/60 mb-1">
                            <span>Progression</span>
                            <span class="font-semibold text-primary">{{ round($enrollment->progress_percent) }}%</span>
                        </div>
                        <progress class="progress progress-primary w-full h-2"
                                  value="{{ $enrollment->progress_percent }}" max="100"></progress>
                    </div>
                    <div class="divider my-2"></div>
                @endif

                <div class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">
                    Contenu du cours
                </div>

                @foreach($course->modules as $module)
                    <div class="mb-3">
                        <div class="text-xs font-semibold text-base-content/70 mb-1 px-1">
                            {{ $module->title_fr ?: $module->title }}
                        </div>
                        @foreach($module->lessons as $l)
                            @php $done = in_array($l->id, $completedIds); @endphp
                            <a href="{{ route('student.lessons.show', [$course->id, $l->id]) }}"
                               class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs
                                      {{ $l->id === $lesson->id ? 'bg-primary text-primary-content font-semibold' : 'hover:bg-base-200 text-base-content/70' }}">
                                @if($done)
                                    <x-icon name="o-check-circle" class="w-3.5 h-3.5 shrink-0 {{ $l->id === $lesson->id ? 'text-primary-content' : 'text-success' }}" />
                                @else
                                    @php
                                        $ico = match($l->type) {
                                            'video'      => 'o-play-circle',
                                            'document'   => 'o-document-text',
                                            'quiz'       => 'o-clipboard-document-list',
                                            'assignment' => 'o-pencil-square',
                                            'live'       => 'o-video-camera',
                                            default      => 'o-book-open',
                                        };
                                    @endphp
                                    <x-icon :name="$ico" class="w-3.5 h-3.5 shrink-0 opacity-50" />
                                @endif
                                <span class="truncate">{{ $l->title_fr ?: $l->title }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </x-card>
        </div>

    </div>
</div>
