<?php
use App\Models\{Assignment, AssignmentSubmission};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Mes devoirs')]
class extends Component {

    public bool $modal = false;
    public ?int $submittingId = null;
    public string $content = '';

    public function with(): array {
        $student   = auth()->user()->student;
        $courseIds = $student ? $student->enrollments()->pluck('course_id') : collect();
        $assignments = Assignment::whereIn('course_id', $courseIds)
            ->with('course')
            ->withCount(['submissions as my_submission_count' => fn($q) => $q->where('student_id', $student?->id)])
            ->orderBy('due_date')
            ->get();

        return [
            'pending' => $assignments->filter(fn($a) => $a->my_submission_count === 0),
            'submitted' => $assignments->filter(fn($a) => $a->my_submission_count > 0),
        ];
    }

    public function openSubmit(int $id): void {
        $this->submittingId = $id;
        $this->content      = '';
        $this->modal        = true;
    }

    public function submit(): void {
        $this->validate(['content' => 'required|min:10']);
        $student = auth()->user()->student;
        AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $this->submittingId, 'student_id' => $student->id],
            ['content' => $this->content, 'submitted_at' => now(), 'status' => 'submitted']
        );
        $this->modal = false;
        $this->toast('Devoir soumis avec succès!', type: 'success');
    }
} ?>

<div>
    <x-header title="Mes devoirs" subtitle="Devoirs à rendre et soumissions" separator />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                <x-badge :value="$pending->count()" class="badge-warning" />
                À rendre
            </h3>
            @forelse($pending as $assignment)
            <x-card class="mb-3">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-semibold">{{ $assignment->title }}</div>
                        <div class="text-sm opacity-60">{{ $assignment->course->title }}</div>
                        @if($assignment->due_date)
                            <div class="text-xs mt-1 {{ $assignment->due_date->isPast() ? 'text-error font-medium' : 'opacity-40' }}">
                                <x-icon name="o-clock" class="w-3 h-3 inline" />
                                {{ $assignment->due_date->isPast() ? 'En retard — ' : 'Rendu le ' }}
                                {{ $assignment->due_date->format('d/m/Y') }}
                            </div>
                        @endif
                    </div>
                    <x-button label="Rendre" icon="o-paper-airplane" wire:click="openSubmit({{ $assignment->id }})" class="btn-primary btn-sm" />
                </div>
            </x-card>
            @empty
            <x-card>
                <div class="text-center py-6 text-base-content/40">
                    <x-icon name="o-check-circle" class="w-10 h-10 mx-auto mb-2 text-success" />
                    <p>Tous les devoirs sont rendus!</p>
                </div>
            </x-card>
            @endforelse
        </div>

        <div>
            <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                <x-badge :value="$submitted->count()" class="badge-success" />
                Soumis
            </h3>
            @forelse($submitted as $assignment)
            <x-card class="mb-3 opacity-80">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium">{{ $assignment->title }}</div>
                        <div class="text-sm opacity-60">{{ $assignment->course->title }}</div>
                    </div>
                    <x-badge value="Soumis" class="badge-success" />
                </div>
            </x-card>
            @empty
            <x-card>
                <div class="text-center py-6 text-base-content/40">
                    <p>Aucun devoir soumis</p>
                </div>
            </x-card>
            @endforelse
        </div>
    </div>

    <x-modal wire:model="modal" title="Soumettre le devoir">
        <x-textarea label="Votre réponse *" wire:model="content" rows="8" placeholder="Écrivez votre réponse ici..." />
        <x-slot:actions>
            <x-button label="Annuler" wire:click="$set('modal', false)" />
            <x-button label="Soumettre" icon="o-paper-airplane" wire:click="submit" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
