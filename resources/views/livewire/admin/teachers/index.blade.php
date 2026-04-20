<?php

use App\Models\{Teacher, Subject, User};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use WithPagination, Toast;

    public string $search  = '';
    public bool   $modal   = false;
    public ?Teacher $editing = null;

    public string $form_first_name      = '';
    public string $form_last_name       = '';
    public string $form_email           = '';
    public string $form_phone           = '';
    public string $form_bio             = '';
    public string $form_qualification   = '';
    public int    $form_experience      = 0;
    public float  $form_hourly_rate     = 0;
    public bool   $form_is_tutor        = false;
    public array  $form_subjects        = [];

    public function with(): array
    {
        return [
            'teachers' => Teacher::query()
                ->with(['user', 'subjects'])
                ->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->paginate(15),
            'subjects' => Subject::orderBy('name')->get(['id','name']),
            'headers'  => [
                ['key' => 'user.full_name',     'label' => __('lms.name')],
                ['key' => 'qualification',      'label' => 'Qualification'],
                ['key' => 'experience_years',   'label' => 'Exp. (yrs)'],
                ['key' => 'rating',             'label' => '⭐'],
                ['key' => 'is_tutor',           'label' => 'Tutor?'],
                ['key' => 'is_verified',        'label' => __('lms.status')],
                ['key' => 'actions',            'label' => __('lms.actions'), 'class' => 'w-24'],
            ],
        ];
    }

    public function create(): void
    {
        $this->reset(['form_first_name','form_last_name','form_email','form_phone',
                      'form_bio','form_qualification','form_experience',
                      'form_hourly_rate','form_is_tutor','form_subjects']);
        $this->editing = null;
        $this->modal = true;
    }

    public function edit(Teacher $teacher): void
    {
        $this->editing              = $teacher;
        $this->form_first_name      = $teacher->user->first_name ?? '';
        $this->form_last_name       = $teacher->user->last_name ?? '';
        $this->form_email           = $teacher->user->email;
        $this->form_phone           = $teacher->user->phone ?? '';
        $this->form_bio             = $teacher->bio ?? '';
        $this->form_qualification   = $teacher->qualification ?? '';
        $this->form_experience      = $teacher->experience_years;
        $this->form_hourly_rate     = $teacher->hourly_rate;
        $this->form_is_tutor        = $teacher->is_tutor;
        $this->form_subjects        = $teacher->subjects->pluck('id')->toArray();
        $this->modal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_first_name'    => 'required|string|max:100',
            'form_last_name'     => 'required|string|max:100',
            'form_email'         => 'required|email|unique:users,email,' . ($this->editing?->user_id ?? 'NULL'),
            'form_experience'    => 'integer|min:0',
            'form_hourly_rate'   => 'numeric|min:0',
        ]);

        if ($this->editing) {
            $this->editing->user->update([
                'first_name' => $this->form_first_name,
                'last_name'  => $this->form_last_name,
                'name'       => $this->form_first_name . ' ' . $this->form_last_name,
                'email'      => $this->form_email,
                'phone'      => $this->form_phone,
            ]);
            $this->editing->update([
                'bio'              => $this->form_bio,
                'qualification'    => $this->form_qualification,
                'experience_years' => $this->form_experience,
                'hourly_rate'      => $this->form_hourly_rate,
                'is_tutor'         => $this->form_is_tutor,
            ]);
            $this->editing->subjects()->sync($this->form_subjects);
        } else {
            $user = User::create([
                'first_name' => $this->form_first_name,
                'last_name'  => $this->form_last_name,
                'name'       => $this->form_first_name . ' ' . $this->form_last_name,
                'email'      => $this->form_email,
                'phone'      => $this->form_phone,
                'password'   => bcrypt('password'),
                'role'       => 'teacher',
            ]);
            $teacher = Teacher::create([
                'user_id'          => $user->id,
                'bio'              => $this->form_bio,
                'qualification'    => $this->form_qualification,
                'experience_years' => $this->form_experience,
                'hourly_rate'      => $this->form_hourly_rate,
                'is_tutor'         => $this->form_is_tutor,
            ]);
            $teacher->subjects()->sync($this->form_subjects);
        }

        $this->success(__('lms.save') . ' — OK');
        $this->modal = false;
    }

    public function toggleVerified(Teacher $teacher): void
    {
        $teacher->update(['is_verified' => !$teacher->is_verified]);
        $this->success('Updated!');
    }

    public function delete(Teacher $teacher): void
    {
        $teacher->user->delete();
        $this->success(__('lms.delete') . ' — OK');
    }
}; ?>

<div>
<x-header :title="__('lms.teachers')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.add')" icon="o-plus"
                      wire:click="create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="mb-4">
        <x-input wire:model.live.debounce="search" :placeholder="__('lms.search')"
                 icon="o-magnifying-glass" clearable />
    </div>

    <x-card shadow>
        <x-table :headers="$headers" :rows="$teachers" with-pagination striped>
            @scope('cell_user.full_name', $teacher)
                <x-list-item :item="$teacher->user" value="full_name" sub-value="email"
                             no-separator no-hover class="!p-0">
                    <x-slot:avatar>
                        <div class="avatar">
                            <div class="w-9 rounded-full">
                                <img src="{{ $teacher->user->avatar_url }}" alt="">
                            </div>
                        </div>
                    </x-slot:avatar>
                </x-list-item>
            @endscope

            @scope('cell_rating', $teacher)
                <span class="font-semibold">{{ number_format($teacher->rating, 1) }}</span>
                <span class="text-xs text-base-content/50">({{ $teacher->rating_count }})</span>
            @endscope

            @scope('cell_is_tutor', $teacher)
                @if($teacher->is_tutor)
                    <x-badge value="Tutor" class="badge-secondary badge-soft" />
                @endif
            @endscope

            @scope('cell_is_verified', $teacher)
                <x-toggle wire:click="toggleVerified({{ $teacher->id }})"
                          :value="$teacher->is_verified" />
            @endscope

            @scope('cell_actions', $teacher)
                <div class="flex gap-1">
                    <x-button icon="o-pencil-square" wire:click="edit({{ $teacher->id }})"
                              class="btn-ghost btn-xs" />
                    <x-button icon="o-trash"
                              wire:click="delete({{ $teacher->id }})"
                              wire:confirm="{{ __('lms.confirm') }}?"
                              class="btn-ghost btn-xs text-error" />
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- Modal --}}
    <x-modal wire:model="modal" :title="$editing ? __('lms.edit') : __('lms.add')" class="backdrop-blur max-w-2xl">
        <x-form wire:submit="save">
            <div class="grid grid-cols-2 gap-4">
                <x-input :label="__('lms.name') . ' (First)'" wire:model="form_first_name" required />
                <x-input :label="__('lms.name') . ' (Last)'"  wire:model="form_last_name"  required />
            </div>
            <x-input :label="__('lms.email_label')" wire:model="form_email" type="email" icon="o-envelope" required />
            <x-input :label="__('lms.phone')"       wire:model="form_phone" icon="o-phone" />
            <x-input label="Qualification"          wire:model="form_qualification" />
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Experience (years)" wire:model="form_experience" type="number" min="0" />
                <x-input label="Hourly Rate (DA)"   wire:model="form_hourly_rate" type="number" min="0" prefix="DA" />
            </div>
            <x-choices :label="__('lms.subjects')" wire:model="form_subjects"
                       :options="$subjects" option-value="id" option-label="name" />
            <x-toggle label="Is Tutor" wire:model="form_is_tutor" right />

            <x-slot:actions>
                <x-button :label="__('lms.cancel')" @click="$wire.modal = false" />
                <x-button :label="__('lms.save')" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

</div>
