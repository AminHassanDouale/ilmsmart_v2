<?php
use App\Models\{Quiz, QuizAttempt};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Mes Quiz')]
class extends Component {

    public function with(): array {
        $student   = auth()->user()->student;
        $courseIds = $student ? $student->enrollments()->pluck('course_id') : collect();
        $quizzes   = Quiz::whereIn('course_id', $courseIds)->with('course')->get();

        $attempts  = $student ? QuizAttempt::where('student_id', $student->id)->get()->keyBy('quiz_id') : collect();

        return [
            'quizzes'  => $quizzes,
            'attempts' => $attempts,
        ];
    }
} ?>

<div>
    <x-header title="Mes Quiz" subtitle="Quiz disponibles dans vos cours" separator />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($quizzes as $quiz)
        @php $attempt = $attempts->get($quiz->id); @endphp
        <x-card class="hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center">
                    <x-icon name="o-clipboard-document-list" class="w-5 h-5 text-accent" />
                </div>
                @if($attempt)
                    @if($attempt->score >= ($quiz->passing_score ?? 50))
                        <x-badge value="Réussi" class="badge-success" />
                    @else
                        <x-badge value="Échoué" class="badge-error" />
                    @endif
                @else
                    <x-badge value="Non tenté" class="badge-ghost" />
                @endif
            </div>
            <div class="font-semibold mb-1">{{ $quiz->title }}</div>
            <div class="text-sm opacity-60 mb-3">{{ $quiz->course->title }}</div>
            <div class="flex items-center justify-between text-xs opacity-50 mb-4">
                <span>{{ $quiz->questions()->count() }} questions</span>
                <span>Seuil: {{ $quiz->passing_score ?? 50 }}%</span>
            </div>
            @if($attempt && $attempt->score !== null)
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Score</span>
                        <span class="font-bold {{ $attempt->score >= ($quiz->passing_score ?? 50) ? 'text-success' : 'text-error' }}">{{ $attempt->score }}%</span>
                    </div>
                    <progress class="progress {{ $attempt->score >= ($quiz->passing_score ?? 50) ? 'progress-success' : 'progress-error' }} w-full" value="{{ $attempt->score }}" max="100"></progress>
                </div>
            @endif
            <x-button
                :label="$attempt ? 'Recommencer' : 'Commencer'"
                :icon="$attempt ? 'o-arrow-path' : 'o-play'"
                link="/student/quizzes/{{ $quiz->id }}/take"
                class="{{ $attempt ? 'btn-ghost btn-sm w-full' : 'btn-primary btn-sm w-full' }}" />
        </x-card>
        @empty
        <div class="col-span-3">
            <x-card>
                <div class="text-center py-12 text-base-content/40">
                    <x-icon name="o-clipboard-document-list" class="w-12 h-12 mx-auto mb-3" />
                    <p>Aucun quiz disponible</p>
                </div>
            </x-card>
        </div>
        @endforelse
    </div>
</div>
