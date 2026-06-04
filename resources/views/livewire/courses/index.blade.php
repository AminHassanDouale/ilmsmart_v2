<?php

use App\Models\{Course, Subject, Grade, Level, Teacher};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use WithPagination;

    public string $search    = '';
    public string $status    = '';
    public string $subject   = '';
    public string $grade     = '';
    public bool   $drawer    = false;

    public function with(): array
    {
        $query = Course::query()
            ->with(['subject.grade.level', 'teacher.user', 'enrollments'])
            ->when($this->search,  fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->status,  fn($q) => $q->where('status', $this->status))
            ->when($this->subject, fn($q) => $q->where('subject_id', $this->subject))
            ->when($this->grade,   fn($q) => $q->whereHas('subject', fn($sq) => $sq->where('grade_id', $this->grade)));

        // Teacher sees only their own
        if (auth()->user()->isTeacher()) {
            $query->where('teacher_id', auth()->user()->teacher?->id);
        }

        return [
            'courses'  => $query->latest()->paginate(12),
            'subjects' => Subject::orderBy('name')->get(['id','name']),
            'grades'   => Grade::with('level')->orderBy('order')->get(),
            'headers'  => [
                ['key' => 'title',           'label' => __('lms.course_title')],
                ['key' => 'subject.name',    'label' => __('lms.subjects')],
                ['key' => 'teacher.user.name','label' => __('lms.teachers')],
                ['key' => 'enrolled_count',  'label' => __('lms.enrolled_students')],
                ['key' => 'status',          'label' => __('lms.status')],
            ],
        ];
    }

    public function deleteCourse(Course $course): void
    {
        $course->delete();
        $this->success(__('lms.delete') . ' — OK');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'subject', 'grade']);
    }
}; ?>

<div>
<x-header :title="__('lms.courses')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.filters')" icon="o-funnel"
                      @click="$wire.drawer = true" class="btn-ghost" />
            @can('create', App\Models\Course::class)
                <x-button :label="__('lms.create')" icon="o-plus"
                          link="/admin/courses" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- Search bar --}}
    <div class="mb-4">
        <x-input wire:model.live.debounce="search" :placeholder="__('lms.search')"
                 icon="o-magnifying-glass" clearable />
    </div>

    {{-- Courses Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($courses as $course)
            <div class="card bg-base-100 shadow hover:shadow-lg transition-all hover:-translate-y-0.5 rounded-xl overflow-hidden">
                {{-- Thumbnail --}}
                <figure class="h-36 bg-gradient-to-br from-primary/20 to-secondary/20 relative">
                    @if($course->thumbnail)
                        <img src="{{ asset('storage/'.$course->thumbnail) }}"
                             class="w-full h-full object-cover" alt="">
                    @else
                        <x-icon name="o-cube" class="w-14 h-14 text-primary/40" />
                    @endif
                    <div class="absolute top-2 right-2">
                        @if($course->type === 'free')
                            <x-badge :value="__('lms.free')" class="badge-success badge-soft" />
                        @else
                            <x-badge :value="number_format($course->price) . ' DA'" class="badge-warning badge-soft" />
                        @endif
                    </div>
                    <div class="absolute bottom-2 left-2">
                        <x-badge :value="__('lms.'.$course->status)"
                                 class="{{ $course->status === 'published' ? 'badge-primary' : 'badge-ghost' }} badge-soft" />
                    </div>
                </figure>

                <div class="card-body p-4">
                    <h3 class="card-title text-sm font-bold line-clamp-2">
                        {{ $course->translated_title }}
                    </h3>
                    <p class="text-xs text-base-content/60 line-clamp-2 mt-1">
                        {{ $course->translated_description }}
                    </p>

                    <div class="flex items-center gap-2 mt-2">
                        <div class="avatar">
                            <div class="w-6 rounded-full">
                                <img src="{{ $course->teacher->user->avatar_url ?? '' }}" alt="">
                            </div>
                        </div>
                        <span class="text-xs text-base-content/70">
                            {{ $course->teacher->user->full_name ?? '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-base-content/50 mt-1">
                        <span>
                            <x-icon name="o-users" class="w-3.5 h-3.5 inline" />
                            {{ $course->enrolled_count }} {{ __('lms.enrolled_students') }}
                        </span>
                        <span>
                            <x-icon name="o-clock" class="w-3.5 h-3.5 inline" />
                            {{ $course->duration_hours }}h
                        </span>
                    </div>

                    @php
                        $u = auth()->user();
                        $viewLink = match(true) {
                            $u?->isStudent()    => '/student/courses/'.$course->id,
                            $u?->isIndividual() => '/individual/courses/'.$course->id,
                            $u?->isAdmin()      => '/admin/courses/'.$course->id.'/manage',
                            default             => '/teacher/courses',
                        };
                    @endphp
                    <div class="card-actions mt-3">
                        <x-button :label="__('lms.view')" icon="o-eye"
                                  :link="$viewLink"
                                  class="btn-sm btn-ghost flex-1" />
                        @can('update', $course)
                            <x-button icon="o-pencil-square"
                                      :link="'/admin/courses/'.$course->id.'/manage'"
                                      class="btn-sm btn-ghost" />
                        @endcan
                        @can('delete', $course)
                            <x-button icon="o-trash"
                                      wire:click="deleteCourse({{ $course->id }})"
                                      wire:confirm="{{ __('lms.confirm') }}?"
                                      class="btn-sm btn-ghost text-error" />
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <x-icon name="o-cube" label="{{ __('lms.no_results') }}" class="h-64" />
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $courses->links() }}
    </div>

    {{-- Filter Drawer --}}
    <x-drawer wire:model="drawer" :title="__('lms.filters')" right class="w-72">
        <div class="space-y-4">
            <x-select :label="__('lms.status')" wire:model.live="status"
                      :options="[
                          ['id'=>'','name'=> __('lms.all_users')],
                          ['id'=>'published','name'=> __('lms.active')],
                          ['id'=>'draft','name'=>'Draft'],
                          ['id'=>'archived','name'=>'Archived'],
                      ]" placeholder="All" />

            <x-select :label="__('lms.subjects')" wire:model.live="subject"
                      :options="$subjects" option-value="id" option-label="name"
                      placeholder="All subjects" />

            <x-select :label="__('lms.grades')" wire:model.live="grade"
                      :options="$grades" option-value="id" option-label="name"
                      placeholder="All grades" />
        </div>

        <x-slot:actions>
            <x-button :label="__('lms.cancel')" @click="$wire.drawer = false" />
            <x-button :label="__('lms.filters')" wire:click="resetFilters" class="btn-ghost" />
        </x-slot:actions>
    </x-drawer>

</div>
