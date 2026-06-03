<?php

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public Course $course;

    public function mount(Course $course): void
    {
        $this->course = $course->load(['modules.lessons']);
    }
};
?>

<div>
    <x-header :title="$course->title">
        <x-slot:middle>
            <div class="flex items-center gap-2 text-sm text-base-content/50">
                <a href="{{ route('individual.courses') }}" wire:navigate class="hover:text-primary">Courses</a>
                <x-icon name="o-chevron-right" class="w-4 h-4" />
                <span class="truncate max-w-xs">{{ $course->title }}</span>
            </div>
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            @if($course->description)
                <x-card>
                    <h3 class="font-bold mb-2">About</h3>
                    <p class="text-sm text-base-content/70">{{ $course->description }}</p>
                </x-card>
            @endif

            @foreach($course->modules as $idx => $module)
                <x-card>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">{{ $idx+1 }}</div>
                        <h3 class="font-bold">{{ $module->title }}</h3>
                    </div>
                    <div class="space-y-1.5">
                        @foreach($module->lessons->where('status','published') as $lesson)
                            <div class="flex items-center gap-3 p-2.5 rounded-lg bg-base-200/50">
                                <x-icon name="{{ $lesson->type_icon }}" class="w-4 h-4 text-base-content/60" />
                                <p class="text-sm flex-1 truncate">{{ $lesson->title }}</p>
                                @if($lesson->duration_minutes)
                                    <span class="text-xs text-base-content/40">{{ $lesson->duration_minutes }}m</span>
                                @endif
                                @if($lesson->is_free_preview)
                                    <span class="badge badge-success badge-xs">Free</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>

        <div>
            <x-card class="sticky top-4">
                <div class="text-center mb-3">
                    <p class="text-3xl font-bold">
                        @if($course->type === 'free') Free
                        @else {{ number_format($course->price, 0) }} DZD @endif
                    </p>
                    <p class="text-xs text-base-content/50 capitalize">{{ $course->type }}</p>
                </div>
                <div class="divider my-2"></div>
                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between"><span class="text-base-content/60">Modules</span><span class="font-semibold">{{ $course->modules->count() }}</span></div>
                    <div class="flex justify-between"><span class="text-base-content/60">Lessons</span><span class="font-semibold">{{ $course->modules->sum(fn($m) => $m->lessons->count()) }}</span></div>
                    <div class="flex justify-between"><span class="text-base-content/60">Level</span><span class="font-semibold capitalize">{{ $course->level }}</span></div>
                </div>
                <x-button label="Enroll Now" icon="o-plus" class="btn-primary w-full" />
            </x-card>
        </div>
    </div>
</div>
