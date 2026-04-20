<?php

use App\Models\{Student, Grade, Level, User};
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
    public string $grade   = '';
    public string $gender  = '';
    public string $status  = '';
    public bool   $drawer  = false;
    public bool   $modal   = false;
    public ?Student $editing = null;

    // Form fields
    public string $form_first_name    = '';
    public string $form_last_name     = '';
    public string $form_email         = '';
    public string $form_phone         = '';
    public string $form_gender        = '';
    public string $form_birth_date    = '';
    public string $form_grade_id      = '';
    public string $form_school_name   = '';
    public string $form_student_type  = 'school';

    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function with(): array
    {
        $query = Student::query()
            ->with(['user', 'grade.level'])
            ->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            })
            ->when($this->grade,  fn($q) => $q->where('grade_id', $this->grade))
            ->when($this->gender, fn($q) => $q->where('gender', $this->gender));

        return [
            'students' => $query->paginate(15),
            'grades'   => Grade::with('level')->orderBy('order')->get(),
            'levels'   => Level::with('grades')->orderBy('order')->get(),
            'headers'  => [
                ['key' => 'user.full_name',     'label' => __('lms.name'),     'sortable' => true],
                ['key' => 'student_number',     'label' => '#',                'class' => 'w-20'],
                ['key' => 'grade.name',         'label' => __('lms.grades')],
                ['key' => 'user.phone',         'label' => __('lms.phone')],
                ['key' => 'gender',             'label' => __('lms.gender')],
                ['key' => 'user.status',        'label' => __('lms.status')],
                ['key' => 'actions',            'label' => __('lms.actions'),  'class' => 'w-24'],
            ],
        ];
    }

    public function create(): void
    {
        $this->reset(['form_first_name','form_last_name','form_email','form_phone',
                      'form_gender','form_birth_date','form_grade_id','form_school_name']);
        $this->editing = null;
        $this->modal = true;
    }

    public function edit(Student $student): void
    {
        $this->editing          = $student;
        $this->form_first_name  = $student->user->first_name ?? '';
        $this->form_last_name   = $student->user->last_name ?? '';
        $this->form_email       = $student->user->email;
        $this->form_phone       = $student->user->phone ?? '';
        $this->form_gender      = $student->gender ?? '';
        $this->form_birth_date  = $student->birth_date?->format('Y-m-d') ?? '';
        $this->form_grade_id    = $student->grade_id ?? '';
        $this->form_school_name = $student->school_name ?? '';
        $this->form_student_type = $student->student_type;
        $this->modal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_first_name' => 'required|string|max:100',
            'form_last_name'  => 'required|string|max:100',
            'form_email'      => 'required|email|unique:users,email,' . ($this->editing?->user_id ?? 'NULL'),
            'form_phone'      => 'nullable|string|max:20',
            'form_grade_id'   => 'nullable|exists:grades,id',
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
                'grade_id'    => $this->form_grade_id ?: null,
                'gender'      => $this->form_gender ?: null,
                'birth_date'  => $this->form_birth_date ?: null,
                'school_name' => $this->form_school_name,
                'student_type'=> $this->form_student_type,
            ]);
            $this->success(__('lms.save') . ' — OK');
        } else {
            $user = User::create([
                'first_name' => $this->form_first_name,
                'last_name'  => $this->form_last_name,
                'name'       => $this->form_first_name . ' ' . $this->form_last_name,
                'email'      => $this->form_email,
                'phone'      => $this->form_phone,
                'password'   => bcrypt('password'),
                'role'       => 'student',
            ]);
            Student::create([
                'user_id'        => $user->id,
                'grade_id'       => $this->form_grade_id ?: null,
                'gender'         => $this->form_gender ?: null,
                'birth_date'     => $this->form_birth_date ?: null,
                'school_name'    => $this->form_school_name,
                'student_type'   => $this->form_student_type,
                'student_number' => 'STU-' . strtoupper(uniqid()),
            ]);
            $this->success(__('lms.create') . ' — OK');
        }

        $this->modal = false;
    }

    public function delete(Student $student): void
    {
        $student->user->delete();
        $this->success(__('lms.delete') . ' — OK');
    }
}; ?>

<div>
<x-header :title="__('lms.students')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.filters')" icon="o-funnel"
                      @click="$wire.drawer = true" class="btn-ghost" />
            <x-button :label="__('lms.add')" icon="o-plus"
                      wire:click="create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    {{-- Search --}}
    <div class="mb-4">
        <x-input wire:model.live.debounce="search" :placeholder="__('lms.search')"
                 icon="o-magnifying-glass" clearable />
    </div>

    {{-- Table --}}
    <x-card shadow>
        <x-table :headers="$headers" :rows="$students" :sort-by="$sortBy"
                 with-pagination striped>
            @scope('cell_user.full_name', $student)
                <x-list-item :item="$student->user" value="full_name" sub-value="email"
                             no-separator no-hover class="!p-0">
                    <x-slot:avatar>
                        <div class="avatar">
                            <div class="w-9 rounded-full">
                                <img src="{{ $student->user->avatar_url }}" alt="">
                            </div>
                        </div>
                    </x-slot:avatar>
                </x-list-item>
            @endscope

            @scope('cell_gender', $student)
                @if($student->gender)
                    <x-badge :value="__('lms.'.$student->gender)"
                             class="{{ $student->gender === 'male' ? 'badge-info' : 'badge-secondary' }} badge-soft" />
                @endif
            @endscope

            @scope('cell_user.status', $student)
                <x-badge :value="__('lms.'.$student->user->status)"
                         class="{{ $student->user->status === 'active' ? 'badge-success' : 'badge-error' }} badge-soft" />
            @endscope

            @scope('cell_actions', $student)
                <div class="flex gap-1">
                    <x-button icon="o-pencil-square" wire:click="edit({{ $student->id }})"
                              class="btn-ghost btn-xs" />
                    <x-button icon="o-trash"
                              wire:click="delete({{ $student->id }})"
                              wire:confirm="{{ __('lms.confirm') }}?"
                              class="btn-ghost btn-xs text-error" />
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- Create/Edit Modal --}}
    <x-modal wire:model="modal" :title="$editing ? __('lms.edit') : __('lms.add')" class="backdrop-blur">
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input :label="__('lms.name') . ' (FR)'" wire:model="form_first_name"
                         :placeholder="__('lms.name')" required />
                <x-input :label="__('lms.name') . ' (Last)'" wire:model="form_last_name"
                         :placeholder="__('lms.name')" required />
            </div>
            <x-input :label="__('lms.email_label')" wire:model="form_email"
                     type="email" icon="o-envelope" required />
            <x-input :label="__('lms.phone')" wire:model="form_phone"
                     icon="o-phone" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-select :label="__('lms.gender')" wire:model="form_gender"
                          :options="[
                              ['id'=>'male',   'name'=> __('lms.male')],
                              ['id'=>'female', 'name'=> __('lms.female')],
                          ]" placeholder="—" />
                <x-datepicker :label="__('lms.birth_date')" wire:model="form_birth_date"
                              icon="o-calendar" />
            </div>
            <x-select :label="__('lms.grades')" wire:model="form_grade_id"
                      :options="$grades" option-value="id" option-label="name"
                      placeholder="—" />
            <x-input :label="__('lms.school_name', [], app()->getLocale()) ?? 'School'"
                     wire:model="form_school_name" />

            <x-slot:actions>
                <x-button :label="__('lms.cancel')" @click="$wire.modal = false" />
                <x-button :label="__('lms.save')" class="btn-primary" type="submit"
                          spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Filter Drawer --}}
    <x-drawer wire:model="drawer" :title="__('lms.filters')" right class="w-72">
        <div class="space-y-4">
            <x-select :label="__('lms.grades')" wire:model.live="grade"
                      :options="$grades" option-value="id" option-label="name"
                      placeholder="All grades" />
            <x-select :label="__('lms.gender')" wire:model.live="gender"
                      :options="[
                          ['id'=>'male',   'name'=> __('lms.male')],
                          ['id'=>'female', 'name'=> __('lms.female')],
                      ]" placeholder="All" />
        </div>
        <x-slot:actions>
            <x-button :label="__('lms.cancel')" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>

</div>
