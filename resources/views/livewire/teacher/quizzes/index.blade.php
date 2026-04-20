<?php

use App\Models\{Quiz, Course};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    public bool $modal    = false;
    public ?Quiz $editing = null;

    // Form
    public string  $form_title        = '';
    public ?int    $form_course_id    = null;
    public int     $form_duration     = 30;
    public int     $form_passing      = 60;
    public int     $form_max_attempts = 3;
    public bool    $form_show_answers = true;
    public string  $form_status       = 'draft';

    public function with(): array
    {
        $teacher = auth()->user()->teacher;
        return [
            'quizzes' => Quiz::with(['course', 'questions'])
                ->where('teacher_id', $teacher?->id)
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
            'courses' => Course::where('teacher_id', $teacher?->id)
                ->where('status', 'published')
                ->get(['id','title']),
        ];
    }

    public function create(): void
    {
        $this->reset(['form_title','form_course_id','form_duration','form_passing',
                      'form_max_attempts','form_show_answers','form_status']);
        $this->form_duration     = 30;
        $this->form_passing      = 60;
        $this->form_max_attempts = 3;
        $this->form_show_answers = true;
        $this->editing = null;
        $this->modal = true;
    }

    public function edit(Quiz $quiz): void
    {
        $this->editing            = $quiz;
        $this->form_title         = $quiz->title;
        $this->form_course_id     = $quiz->course_id;
        $this->form_duration      = $quiz->duration_minutes;
        $this->form_passing       = $quiz->passing_score;
        $this->form_max_attempts  = $quiz->max_attempts;
        $this->form_show_answers  = $quiz->show_answers;
        $this->form_status        = $quiz->status;
        $this->modal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_title'    => 'required|string|max:200',
            'form_duration' => 'required|integer|min:5',
            'form_passing'  => 'required|integer|min:0|max:100',
        ]);

        $data = [
            'title'            => $this->form_title,
            'course_id'        => $this->form_course_id,
            'duration_minutes' => $this->form_duration,
            'passing_score'    => $this->form_passing,
            'max_attempts'     => $this->form_max_attempts,
            'show_answers'     => $this->form_show_answers,
            'status'           => $this->form_status,
            'teacher_id'       => auth()->user()->teacher->id,
        ];

        if ($this->editing) {
            $this->editing->update($data);
            $this->success('Quiz updated!');
        } else {
            Quiz::create($data);
            $this->success('Quiz created!');
        }

        $this->modal = false;
    }

    public function delete(Quiz $quiz): void
    {
        $quiz->delete();
        $this->success('Deleted.');
    }
}; ?>

<div>
<x-header :title="__('lms.quizzes')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.create')" icon="o-plus" wire:click="create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="mb-4">
        <x-input wire:model.live.debounce="search" :placeholder="__('lms.search')"
                 icon="o-magnifying-glass" clearable />
    </div>

    <x-card shadow>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>{{ __('lms.quiz_title') }}</th>
                        <th>Course</th>
                        <th>Questions</th>
                        <th>Duration</th>
                        <th>Pass %</th>
                        <th>{{ __('lms.status') }}</th>
                        <th>{{ __('lms.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quizzes as $quiz)
                        <tr>
                            <td class="font-semibold">{{ $quiz->title }}</td>
                            <td class="text-sm text-base-content/60">{{ $quiz->course?->title ?? '—' }}</td>
                            <td>
                                <x-badge :value="$quiz->questions->count() . ' Q'" class="badge-soft badge-primary" />
                            </td>
                            <td>{{ $quiz->duration_minutes }} min</td>
                            <td>{{ $quiz->passing_score }}%</td>
                            <td>
                                <x-badge :value="$quiz->status"
                                         class="{{ $quiz->status === 'published' ? 'badge-success' : 'badge-warning' }} badge-soft" />
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <x-button icon="o-pencil-square" wire:click="edit({{ $quiz->id }})"
                                              class="btn-ghost btn-xs" />
                                    <x-button icon="o-clipboard-document-list"
                                              :link="'/teacher/quizzes/'.$quiz->id.'/questions'"
                                              class="btn-ghost btn-xs" tooltip="Questions" />
                                    <x-button icon="o-trash" wire:click="delete({{ $quiz->id }})"
                                              wire:confirm="Delete?" class="btn-ghost btn-xs text-error" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-base-content/40">{{ __('lms.no_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $quizzes->links() }}</div>

    <x-modal wire:model="modal" :title="$editing ? 'Edit Quiz' : 'Create Quiz'" class="backdrop-blur">
        <x-form wire:submit="save">
            <x-input label="Quiz Title" wire:model="form_title" required />
            <x-select label="Course" wire:model="form_course_id"
                      :options="$courses" option-value="id" option-label="title"
                      placeholder="Standalone quiz" />
            <div class="grid grid-cols-3 gap-3">
                <x-input label="Duration (min)" wire:model="form_duration" type="number" min="5" />
                <x-input label="Pass Score (%)" wire:model="form_passing"  type="number" min="0" max="100" />
                <x-input label="Max Attempts"   wire:model="form_max_attempts" type="number" min="1" />
            </div>
            <x-toggle label="Show Correct Answers After" wire:model="form_show_answers" />
            <x-select label="Status" wire:model="form_status"
                      :options="[['id'=>'draft','name'=>'Draft'],['id'=>'published','name'=>'Published']]" />

            <x-slot:actions>
                <x-button :label="__('lms.cancel')" @click="$wire.modal = false" />
                <x-button :label="__('lms.save')" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

</div>
