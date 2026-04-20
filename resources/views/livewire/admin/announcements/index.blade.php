<?php

use App\Models\{Announcement, Grade};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use WithPagination, Toast;

    public bool   $modal   = false;
    public ?Announcement $editing = null;

    public string  $form_title     = '';
    public string  $form_content   = '';
    public string  $form_target    = 'all';
    public ?int    $form_grade_id  = null;
    public bool    $form_is_pinned = false;

    public function with(): array
    {
        return [
            'announcements' => Announcement::with(['user', 'grade'])
                ->latest()
                ->paginate(10),
            'grades' => Grade::orderBy('order')->get(['id','name']),
        ];
    }

    public function create(): void
    {
        $this->reset(['form_title','form_content','form_target','form_grade_id','form_is_pinned']);
        $this->form_target = 'all';
        $this->editing = null;
        $this->modal = true;
    }

    public function edit(Announcement $announcement): void
    {
        $this->editing         = $announcement;
        $this->form_title      = $announcement->title;
        $this->form_content    = $announcement->content;
        $this->form_target     = $announcement->target;
        $this->form_grade_id   = $announcement->grade_id;
        $this->form_is_pinned  = $announcement->is_pinned;
        $this->modal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_title'   => 'required|string|max:200',
            'form_content' => 'required|string',
        ]);

        $data = [
            'title'        => $this->form_title,
            'content'      => $this->form_content,
            'target'       => $this->form_target,
            'grade_id'     => $this->form_grade_id,
            'is_pinned'    => $this->form_is_pinned,
            'user_id'      => auth()->id(),
            'published_at' => now(),
        ];

        if ($this->editing) {
            $this->editing->update($data);
            $this->success('Updated!');
        } else {
            Announcement::create($data);
            $this->success('Announcement published!');
        }

        $this->modal = false;
    }

    public function delete(Announcement $announcement): void
    {
        $announcement->delete();
        $this->success('Deleted.');
    }

    public function togglePin(Announcement $announcement): void
    {
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);
        $this->success($announcement->is_pinned ? 'Pinned!' : 'Unpinned.');
    }
}; ?>

<div>
<x-header :title="__('lms.announcements')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.create')" icon="o-plus" wire:click="create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="space-y-4">
        @forelse($announcements as $announcement)
            <x-card shadow :class="$announcement->is_pinned ? 'border-l-4 border-warning' : ''">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            @if($announcement->is_pinned)
                                <x-icon name="o-map-pin" class="w-4 h-4 text-warning shrink-0" />
                            @endif
                            <h3 class="font-bold">{{ $announcement->title }}</h3>
                            <x-badge :value="$announcement->target" class="badge-soft badge-primary capitalize" />
                        </div>
                        <p class="text-sm text-base-content/70 line-clamp-2">{{ $announcement->content }}</p>
                        <p class="text-xs text-base-content/40 mt-2">
                            {{ $announcement->user->full_name }} &bull; {{ $announcement->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <x-button icon="{{ $announcement->is_pinned ? 'o-map-pin' : 'o-map-pin' }}"
                                  wire:click="togglePin({{ $announcement->id }})"
                                  class="btn-ghost btn-xs {{ $announcement->is_pinned ? 'text-warning' : '' }}" />
                        <x-button icon="o-pencil-square" wire:click="edit({{ $announcement->id }})"
                                  class="btn-ghost btn-xs" />
                        <x-button icon="o-trash" wire:click="delete({{ $announcement->id }})"
                                  wire:confirm="Delete?" class="btn-ghost btn-xs text-error" />
                    </div>
                </div>
            </x-card>
        @empty
            <x-icon name="o-megaphone" label="No announcements yet" class="h-64" />
        @endforelse
    </div>

    <div class="mt-4">{{ $announcements->links() }}</div>

    <x-modal wire:model="modal" :title="$editing ? 'Edit Announcement' : 'New Announcement'" class="backdrop-blur max-w-2xl">
        <x-form wire:submit="save">
            <x-input label="Title" wire:model="form_title" required />

            <x-select label="Target Audience" wire:model="form_target"
                      :options="[
                          ['id'=>'all',     'name'=>'Everyone'],
                          ['id'=>'students','name'=> __('lms.students')],
                          ['id'=>'teachers','name'=> __('lms.teachers')],
                          ['id'=>'parents', 'name'=> __('lms.parents')],
                          ['id'=>'grade',   'name'=>'Specific Grade'],
                      ]" />

            @if($form_target === 'grade')
                <x-select label="Grade" wire:model="form_grade_id"
                          :options="$grades" option-value="id" option-label="name" />
            @endif

            <x-textarea label="Content" wire:model="form_content" rows="5" required />
            <x-toggle label="Pin this announcement" wire:model="form_is_pinned" />

            <x-slot:actions>
                <x-button :label="__('lms.cancel')" @click="$wire.modal = false" />
                <x-button :label="__('lms.save')" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

</div>
