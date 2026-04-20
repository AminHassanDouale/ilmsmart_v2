<?php
use App\Models\{Assignment, Course, AssignmentSubmission};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Devoirs')]
class extends Component {

    public bool $modal = false;
    public bool $submissionsModal = false;
    public ?int $editId = null;
    public ?int $viewingId = null;
    public string $title = '';
    public string $description = '';
    public string $course_id = '';
    public string $due_date = '';
    public int $max_score = 20;

    public function with(): array {
        $teacherId = auth()->user()->teacher?->id;
        return [
            'assignments' => Assignment::whereHas('course', fn($q) => $q->where('teacher_id', $teacherId))
                ->with('course')->withCount('submissions')->latest()->get(),
            'courses' => Course::where('teacher_id', $teacherId)->get()->map(fn($c) => ['id'=>$c->id,'name'=>$c->title]),
            'submissions' => $this->viewingId
                ? AssignmentSubmission::where('assignment_id', $this->viewingId)->with('student.user')->get()
                : collect(),
        ];
    }

    public function openCreate(): void {
        $this->reset(['editId','title','description','course_id','due_date']);
        $this->max_score = 20;
        $this->modal = true;
    }

    public function openEdit(Assignment $a): void {
        $this->editId      = $a->id;
        $this->title       = $a->title;
        $this->description = $a->description ?? '';
        $this->course_id   = $a->course_id;
        $this->due_date    = $a->due_date?->format('Y-m-d') ?? '';
        $this->max_score   = $a->max_score ?? 20;
        $this->modal       = true;
    }

    public function save(): void {
        $this->validate(['title'=>'required','course_id'=>'required|exists:courses,id']);
        $data = ['title'=>$this->title,'description'=>$this->description,'course_id'=>$this->course_id,
                 'due_date'=>$this->due_date ?: null,'max_score'=>$this->max_score];
        $this->editId ? Assignment::find($this->editId)->update($data) : Assignment::create($data);
        $this->modal = false;
        $this->toast($this->editId ? 'Devoir mis à jour' : 'Devoir créé', type: 'success');
    }

    public function viewSubmissions(int $id): void {
        $this->viewingId = $id;
        $this->submissionsModal = true;
    }

    public function grade(AssignmentSubmission $sub, int $score): void {
        $sub->update(['score' => $score, 'status' => 'graded']);
        $this->toast('Note enregistrée', type: 'success');
    }

    public function delete(Assignment $a): void {
        $a->delete();
        $this->toast('Devoir supprimé', type: 'success');
    }
} ?>

<div>
    <x-header title="Devoirs" subtitle="Gérer les devoirs et les soumissions" separator>
        <x-slot:actions>
            <x-button label="Nouveau devoir" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="space-y-3">
        @forelse($assignments as $assignment)
        <x-card>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center">
                        <x-icon name="o-pencil-square" class="w-6 h-6 text-secondary" />
                    </div>
                    <div>
                        <div class="font-semibold">{{ $assignment->title }}</div>
                        <div class="text-sm opacity-60">{{ $assignment->course->title }}</div>
                        @if($assignment->due_date)
                            <div class="text-xs {{ $assignment->due_date->isPast() ? 'text-error' : 'opacity-40' }}">
                                Rendu le {{ $assignment->due_date->format('d/m/Y') }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <x-badge :value="$assignment->submissions_count.' soumissions'" class="badge-ghost" />
                    <x-button label="Soumissions" icon="o-document-text" wire:click="viewSubmissions({{ $assignment->id }})" class="btn-ghost btn-sm" />
                    <x-button icon="o-pencil" wire:click="openEdit({{ $assignment->id }})" class="btn-ghost btn-sm" />
                    <x-button icon="o-trash" wire:click="delete({{ $assignment->id }})" wire:confirm="Supprimer ?" class="btn-ghost btn-sm text-error" />
                </div>
            </div>
        </x-card>
        @empty
        <x-card>
            <div class="text-center py-12 text-base-content/40">
                <x-icon name="o-pencil-square" class="w-12 h-12 mx-auto mb-3" />
                <p>Aucun devoir créé</p>
            </div>
        </x-card>
        @endforelse
    </div>

    <x-modal wire:model="modal" title="{{ $editId ? 'Modifier le devoir' : 'Nouveau devoir' }}">
        <div class="space-y-4">
            <x-input label="Titre *" wire:model="title" />
            <x-select label="Cours *" :options="$courses" wire:model="course_id" />
            <x-datepicker label="Date limite" wire:model="due_date" />
            <x-input label="Note maximale" wire:model="max_score" type="number" min="1" max="100" />
            <x-textarea label="Description" wire:model="description" rows="4" />
        </div>
        <x-slot:actions>
            <x-button label="Annuler" wire:click="$set('modal', false)" />
            <x-button label="Enregistrer" wire:click="save" class="btn-primary" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="submissionsModal" title="Soumissions" box-class="max-w-2xl">
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($submissions as $sub)
            <div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                <div class="flex items-center gap-3">
                    <x-avatar :image="$sub->student->user->avatar_url ?? ''" class="w-8 h-8" />
                    <div>
                        <div class="font-medium text-sm">{{ $sub->student->user->full_name }}</div>
                        <div class="text-xs opacity-50">{{ $sub->submitted_at?->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($sub->score !== null)
                        <x-badge :value="$sub->score.'/'.(Assignment::find($this->viewingId)?->max_score ?? 20)" class="badge-success" />
                    @else
                        <x-input type="number" placeholder="Note" class="input-sm w-20"
                            wire:keydown.enter="grade({{ $sub->id }}, $event.target.value)" />
                    @endif
                </div>
            </div>
            @empty
            <p class="text-center text-base-content/40 py-6">Aucune soumission pour ce devoir.</p>
            @endforelse
        </div>
    </x-modal>
</div>
