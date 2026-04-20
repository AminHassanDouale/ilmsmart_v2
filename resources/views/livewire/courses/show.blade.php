<?php
use App\Models\{Course, Enrollment, LessonProgress};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Cours')]
class extends Component {

    public Course $course;
    public bool $enrolled = false;

    public function mount(Course $course): void {
        $this->course = $course->load('teacher.user','modules.lessons','subject');
        $student = auth()->user()->student;
        if ($student) {
            $this->enrolled = Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists();
        }
    }

    public function enroll(): void {
        $student = auth()->user()->student;
        if (!$student) { $this->toast('Vous devez être étudiant pour vous inscrire', type: 'error'); return; }
        Enrollment::firstOrCreate(['student_id'=>$student->id,'course_id'=>$this->course->id],['status'=>'active','enrolled_at'=>now()]);
        $this->enrolled = true;
        $this->toast('Inscription réussie!', type: 'success');
    }

    public function with(): array {
        $student   = auth()->user()->student;
        $progress  = [];
        if ($student && $this->enrolled) {
            $completed = LessonProgress::where('student_id', $student->id)->where('course_id', $this->course->id)->where('completed', true)->pluck('lesson_id');
            $progress  = $completed->toArray();
        }
        $total     = $this->course->lessons()->count();
        $done      = count($progress);
        return [
            'course'   => $this->course,
            'progress' => $progress,
            'percent'  => $total > 0 ? round($done/$total*100) : 0,
            'done'     => $done,
            'total'    => $total,
        ];
    }
} ?>

<div>
    {{-- Hero --}}
    <div class="bg-gradient-to-r from-primary to-secondary text-white rounded-2xl p-8 mb-6">
        <div class="flex items-start justify-between gap-6">
            <div class="flex-1">
                <div class="text-sm opacity-80 mb-2">{{ $course->subject?->name }}</div>
                <h1 class="text-3xl font-bold mb-3">{{ $course->title }}</h1>
                <p class="opacity-80 mb-4">{{ $course->description }}</p>
                <div class="flex items-center gap-4 text-sm opacity-80">
                    <span><x-icon name="o-academic-cap" class="w-4 h-4 inline" /> {{ $course->teacher->user->full_name }}</span>
                    <span><x-icon name="o-book-open" class="w-4 h-4 inline" /> {{ $total }} leçons</span>
                    @if($course->price > 0)
                        <span><x-icon name="o-banknotes" class="w-4 h-4 inline" /> {{ number_format($course->price) }} DZD</span>
                    @else
                        <span class="bg-white/20 px-2 py-0.5 rounded-full">Gratuit</span>
                    @endif
                </div>
            </div>
            <div class="text-right">
                @if($enrolled)
                    <div class="mb-2">
                        <div class="text-2xl font-bold">{{ $percent }}%</div>
                        <div class="text-sm opacity-70">{{ $done }}/{{ $total }} leçons</div>
                    </div>
                    <progress class="progress progress-accent w-40 mb-3" value="{{ $percent }}" max="100"></progress>
                    <div><x-badge value="Inscrit" class="badge-accent" /></div>
                @else
                    <x-button label="S'inscrire" icon="o-plus" wire:click="enroll" class="btn-accent btn-lg" />
                @endif
            </div>
        </div>
    </div>

    {{-- Modules & Lessons --}}
    <div class="space-y-4">
        @foreach($course->modules as $module)
        <x-collapse>
            <x-slot:heading>
                <div class="flex items-center justify-between w-full">
                    <span class="font-semibold">{{ $module->title }}</span>
                    <x-badge :value="$module->lessons->count().' leçons'" class="badge-ghost badge-sm" />
                </div>
            </x-slot:heading>
            <x-slot:content>
                <div class="space-y-1">
                    @foreach($module->lessons as $lesson)
                    <a href="/student/courses/{{ $course->id }}/lessons/{{ $lesson->id }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200 transition-colors">
                        @if(in_array($lesson->id, $progress))
                            <x-icon name="o-check-circle" class="w-5 h-5 text-success flex-shrink-0" />
                        @else
                            <x-icon name="{{ $lesson->type_icon ?? 'o-play-circle' }}" class="w-5 h-5 text-base-content/40 flex-shrink-0" />
                        @endif
                        <span class="{{ in_array($lesson->id, $progress) ? 'line-through opacity-60' : '' }} flex-1">{{ $lesson->title }}</span>
                        @if($lesson->duration_minutes)
                            <span class="text-xs opacity-40">{{ $lesson->duration_minutes }}min</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </x-slot:content>
        </x-collapse>
        @endforeach
    </div>
</div>
