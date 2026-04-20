<?php

use App\Models\{Course, Subject, AcademicYear};
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
    public string $status  = '';

    public function with(): array
    {
        $teacher = auth()->user()->teacher;

        return [
            'courses'     => Course::query()
                ->with(['subject', 'enrollments'])
                ->where('teacher_id', $teacher?->id)
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(9),
            'total_students' => \App\Models\Enrollment::whereIn('course_id',
                Course::where('teacher_id', $teacher?->id)->pluck('id')
            )->distinct('student_id')->count(),
        ];
    }

    public function publish(Course $course): void
    {
        $course->update(['status' => 'published']);
        $this->success('Course published!');
    }

    public function archive(Course $course): void
    {
        $course->update(['status' => 'archived']);
        $this->success('Course archived.');
    }

    public function delete(Course $course): void
    {
        $course->delete();
        $this->success('Deleted.');
    }
}; ?>

<div>
<x-header :title="__('lms.my_courses')" separator>
        <x-slot:subtitle>{{ $total_students }} {{ __('lms.enrolled_students') }}</x-slot:subtitle>
        <x-slot:actions>
            <x-button :label="__('lms.create')" icon="o-plus"
                      link="/teacher/courses/create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="flex flex-wrap gap-3 mb-4">
        <x-input wire:model.live.debounce="search" :placeholder="__('lms.search')"
                 icon="o-magnifying-glass" clearable class="flex-1 min-w-40" />
        <x-select wire:model.live="status" :options="[
            ['id'=>'', 'name'=>'All'],
            ['id'=>'draft', 'name'=>'Draft'],
            ['id'=>'published', 'name'=>'Published'],
            ['id'=>'archived', 'name'=>'Archived'],
        ]" class="w-36" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($courses as $course)
            <x-card shadow class="hover:shadow-lg transition-all">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold truncate">{{ $course->translated_title }}</h3>
                        <p class="text-xs text-base-content/60">{{ $course->subject->name }}</p>
                    </div>
                    <x-badge :value="$course->status"
                             class="{{ match($course->status) {
                                 'published' => 'badge-success',
                                 'draft'     => 'badge-warning',
                                 default     => 'badge-neutral'
                             } }} badge-soft" />
                </div>

                <div class="flex justify-between text-sm mb-4">
                    <span class="flex items-center gap-1">
                        <x-icon name="o-users" class="w-4 h-4 text-primary" />
                        {{ $course->enrolled_count }}
                    </span>
                    <span class="flex items-center gap-1">
                        <x-icon name="o-cube" class="w-4 h-4 text-secondary" />
                        {{ $course->modules->count() }} modules
                    </span>
                    <span class="flex items-center gap-1">
                        <x-icon name="o-clock" class="w-4 h-4 text-warning" />
                        {{ $course->duration_hours }}h
                    </span>
                </div>

                <div class="flex gap-2">
                    <x-button icon="o-eye"         :link="'/teacher/courses/'.$course->id"        class="btn-ghost btn-sm flex-1" />
                    <x-button icon="o-pencil-square" :link="'/teacher/courses/'.$course->id.'/edit'" class="btn-ghost btn-sm" />
                    @if($course->status === 'draft')
                        <x-button icon="o-rocket-launch" wire:click="publish({{ $course->id }})"
                                  class="btn-primary btn-sm" />
                    @else
                        <x-button icon="o-archive-box" wire:click="archive({{ $course->id }})"
                                  class="btn-ghost btn-sm" />
                    @endif
                    <x-button icon="o-trash" wire:click="delete({{ $course->id }})"
                              wire:confirm="Delete this course?"
                              class="btn-ghost btn-sm text-error" />
                </div>
            </x-card>
        @empty
            <div class="col-span-3">
                <x-icon name="o-cube" label="{{ __('lms.no_results') }}" class="h-64" />
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $courses->links() }}</div>

</div>
