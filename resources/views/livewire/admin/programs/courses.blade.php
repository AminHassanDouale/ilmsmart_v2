<?php

use App\Models\Program;
use App\Models\Course;
use Livewire\Volt\Component;

new class extends Component {
    public Program $program;
    public string $search = '';

    public function mount(Program $program): void
    {
        $this->program = $program;
    }

    public function with(): array
    {
        $assignedIds = $this->program->courses()->pluck('courses.id')->toArray();

        $availableCourses = Course::query()
            ->whereNotIn('id', $assignedIds)
            ->when($this->search, fn($q) => $q->where('title','like',"%{$this->search}%"))
            ->limit(50)
            ->get();

        $assignedCourses = $this->program->courses()->get();

        return compact('availableCourses', 'assignedCourses');
    }

    public function add(int $courseId): void
    {
        $maxOrder = $this->program->courses()->max('program_courses.order') ?? 0;
        $this->program->courses()->attach($courseId, [
            'order' => $maxOrder + 1,
            'is_required' => true,
        ]);
        $this->success('Course added to program.');
    }

    public function remove(int $courseId): void
    {
        $this->program->courses()->detach($courseId);
        $this->warning('Course removed.');
    }

    public function toggleRequired(int $courseId): void
    {
        $pivot = $this->program->courses()->where('courses.id', $courseId)->first();
        $this->program->courses()->updateExistingPivot($courseId, [
            'is_required' => !$pivot->pivot->is_required,
        ]);
    }

    public function moveUp(int $courseId): void
    {
        $current = $this->program->courses()->where('courses.id', $courseId)->first();
        if (!$current) return;

        $above = $this->program->courses()
            ->where('program_courses.order', '<', $current->pivot->order)
            ->orderByDesc('program_courses.order')
            ->first();

        if ($above) {
            $tmp = $current->pivot->order;
            $this->program->courses()->updateExistingPivot($courseId, ['order' => $above->pivot->order]);
            $this->program->courses()->updateExistingPivot($above->id, ['order' => $tmp]);
        }
    }

    public function moveDown(int $courseId): void
    {
        $current = $this->program->courses()->where('courses.id', $courseId)->first();
        if (!$current) return;

        $below = $this->program->courses()
            ->where('program_courses.order', '>', $current->pivot->order)
            ->orderBy('program_courses.order')
            ->first();

        if ($below) {
            $tmp = $current->pivot->order;
            $this->program->courses()->updateExistingPivot($courseId, ['order' => $below->pivot->order]);
            $this->program->courses()->updateExistingPivot($below->id, ['order' => $tmp]);
        }
    }
};
?>

<div>
    <x-header :title="'Courses: ' . $program->title" subtitle="Bundle courses into this program (in order)">
        <x-slot:middle>
            <div class="flex items-center gap-2 text-sm text-base-content/50">
                <a href="{{ route('admin.programs') }}" wire:navigate class="hover:text-primary">Programs</a>
                <x-icon name="o-chevron-right" class="w-4 h-4" />
                <span class="truncate max-w-xs">{{ $program->title }}</span>
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <a href="{{ route('admin.programs.sessions', $program) }}" wire:navigate class="btn btn-sm">
                <x-icon name="o-calendar" class="w-4 h-4" /> Sessions
            </a>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- LEFT: Assigned courses (ordered curriculum) --}}
        <div>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
                <x-icon name="o-rectangle-stack" class="w-5 h-5" />
                Curriculum ({{ $assignedCourses->count() }})
            </h3>

            @if($assignedCourses->isEmpty())
                <x-card class="text-center py-10">
                    <p class="text-sm text-base-content/50">No courses assigned yet.</p>
                </x-card>
            @else
                <div class="space-y-2">
                    @foreach($assignedCourses as $idx => $course)
                        <x-card class="!p-3">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm shrink-0">
                                    {{ $idx + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm leading-tight truncate">{{ $course->title }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        @if($course->pivot->is_required)
                                            <span class="badge badge-warning badge-xs">Required</span>
                                        @else
                                            <span class="badge badge-ghost badge-xs">Optional</span>
                                        @endif
                                        @if($course->level)
                                            <span class="text-xs text-base-content/50 capitalize">{{ $course->level }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-col gap-0.5 shrink-0">
                                    <button wire:click="moveUp({{ $course->id }})"
                                            @if($idx === 0) disabled @endif
                                            class="btn btn-ghost btn-xs">
                                        <x-icon name="o-chevron-up" class="w-3 h-3" />
                                    </button>
                                    <button wire:click="moveDown({{ $course->id }})"
                                            @if($idx === $assignedCourses->count() - 1) disabled @endif
                                            class="btn btn-ghost btn-xs">
                                        <x-icon name="o-chevron-down" class="w-3 h-3" />
                                    </button>
                                </div>
                                <button wire:click="toggleRequired({{ $course->id }})"
                                        class="btn btn-ghost btn-xs" title="Toggle required">
                                    <x-icon name="o-star" class="w-4 h-4 {{ $course->pivot->is_required ? 'text-warning' : '' }}" />
                                </button>
                                <button wire:click="remove({{ $course->id }})"
                                        wire:confirm="Remove this course from the program?"
                                        class="btn btn-ghost btn-xs text-error">
                                    <x-icon name="o-x-mark" class="w-4 h-4" />
                                </button>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- RIGHT: Add courses --}}
        <div>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
                <x-icon name="o-plus" class="w-5 h-5" />
                Add Courses
            </h3>

            <x-input placeholder="Search courses..." wire:model.live.debounce="search"
                     icon="o-magnifying-glass" class="mb-3" />

            <div class="space-y-2 max-h-[600px] overflow-y-auto pr-1">
                @forelse($availableCourses as $course)
                    <x-card class="!p-3 hover:bg-base-200 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm leading-tight truncate">{{ $course->title }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if($course->level)
                                        <span class="badge badge-xs badge-outline capitalize">{{ $course->level }}</span>
                                    @endif
                                    @if($course->is_islamic)
                                        <span class="badge badge-xs badge-info">Islamic</span>
                                    @endif
                                    <span @class([
                                        'badge badge-xs',
                                        'badge-success' => $course->status==='published',
                                        'badge-ghost'   => $course->status==='draft',
                                    ])>{{ $course->status }}</span>
                                </div>
                            </div>
                            <button wire:click="add({{ $course->id }})" class="btn btn-primary btn-xs">
                                <x-icon name="o-plus" class="w-4 h-4" /> Add
                            </button>
                        </div>
                    </x-card>
                @empty
                    <p class="text-center text-sm text-base-content/40 py-6">No matching courses.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
