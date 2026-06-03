<?php

use App\Models\Course;
use App\Models\Enrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public string $search      = '';
    public string $filterLevel = '';

    public function with(): array
    {
        $courses = Course::withCount(['lessons','enrollments'])
            ->where('status', 'published')
            ->when($this->search, fn($q) => $q->where('title','like',"%{$this->search}%"))
            ->when($this->filterLevel, fn($q) => $q->where('level', $this->filterLevel))
            ->orderByRaw("FIELD(level, 'beginner','intermediate','premium')")
            ->get();

        return compact('courses');
    }
};
?>

<div>
    <x-header title="Browse Courses" subtitle="Self-paced courses to learn at your own pace">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search courses..." wire:model.live.debounce="search"
                     icon="o-magnifying-glass" class="w-full sm:w-64" />
        </x-slot:middle>
    </x-header>

    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button wire:click="$set('filterLevel','')"
                @class(['btn btn-sm shrink-0', 'btn-primary' => $filterLevel==='', 'btn-ghost' => $filterLevel!==''])>All</button>
        <button wire:click="$set('filterLevel','beginner')"
                @class(['btn btn-sm shrink-0', 'btn-success' => $filterLevel==='beginner', 'btn-ghost' => $filterLevel!=='beginner'])>Beginner</button>
        <button wire:click="$set('filterLevel','intermediate')"
                @class(['btn btn-sm shrink-0', 'btn-info' => $filterLevel==='intermediate', 'btn-ghost' => $filterLevel!=='intermediate'])>Intermediate</button>
        <button wire:click="$set('filterLevel','premium')"
                @class(['btn btn-sm shrink-0', 'btn-warning' => $filterLevel==='premium', 'btn-ghost' => $filterLevel!=='premium'])>Premium</button>
    </div>

    @if($courses->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-book-open" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No courses match your search.</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($courses as $c)
                <a href="{{ route('individual.courses.show', $c) }}" wire:navigate
                   class="card bg-base-100 shadow-sm hover:shadow-lg transition-all">
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="font-bold text-sm sm:text-base leading-tight">{{ $c->title }}</h3>
                            <span class="badge badge-outline badge-sm capitalize shrink-0">{{ $c->level }}</span>
                        </div>
                        @if($c->description)
                            <p class="text-xs text-base-content/60 line-clamp-2 mb-2">{{ $c->description }}</p>
                        @endif
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-base-content/50">{{ $c->lessons_count }} lessons</span>
                            <span class="text-sm font-bold">
                                @if($c->type === 'free') Free
                                @else {{ number_format($c->price, 0) }} DZD @endif
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
