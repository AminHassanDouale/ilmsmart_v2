<?php
use App\Models\{Student, LessonProgress};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Progression des enfants')]
class extends Component {

    public ?int $selectedChild = null;

    public function mount(): void {
        $parent = auth()->user()->parentProfile;
        if ($parent) {
            $this->selectedChild = $parent->students->first()?->id;
        }
    }

    public function with(): array {
        $parent   = auth()->user()->parentProfile;
        $children = $parent ? $parent->students()->with('user')->get() : collect();

        $child    = $this->selectedChild ? Student::find($this->selectedChild) : $children->first();
        $courses  = $child ? $child->enrollments()->with('course')->get()->map(function($e) use ($child) {
            $course    = $e->course;
            $total     = $course->lessons()->count();
            $completed = LessonProgress::where('student_id', $child->id)->where('course_id', $course->id)->where('completed', true)->count();
            return ['course'=>$course, 'total'=>$total, 'completed'=>$completed, 'percent'=> $total > 0 ? round($completed/$total*100) : 0];
        }) : collect();

        return [
            'children' => $children->map(fn($c) => ['id'=>$c->id,'name'=>$c->user->full_name]),
            'child'    => $child,
            'courses'  => $courses,
        ];
    }
} ?>

<div>
    <x-header title="Progression des enfants" subtitle="Suivre les progrès de vos enfants" separator />

    @if(count($children) > 1)
    <div class="flex gap-2 mb-6">
        @foreach($children as $c)
        <x-button :label="$c['name']" wire:click="$set('selectedChild', {{ $c['id'] }})"
                  class="{{ $selectedChild == $c['id'] ? 'btn-primary' : 'btn-ghost' }} btn-sm" />
        @endforeach
    </div>
    @endif

    @if($child)
    <div class="mb-4 flex items-center gap-3">
        <x-icon name="o-academic-cap" class="w-6 h-6 text-primary" />
        <span class="text-lg font-semibold">{{ $child->user->full_name ?? 'N/A' }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($courses as $item)
        <x-card>
            <div class="font-semibold mb-1">{{ $item['course']->title }}</div>
            <div class="text-sm opacity-60 mb-3">{{ $item['completed'] }}/{{ $item['total'] }} leçons complétées</div>
            <div class="flex items-center gap-3">
                <progress class="progress progress-primary flex-1" value="{{ $item['percent'] }}" max="100"></progress>
                <span class="text-sm font-bold w-10 text-right">{{ $item['percent'] }}%</span>
            </div>
        </x-card>
        @empty
        <div class="col-span-2">
            <x-card>
                <div class="text-center py-8 text-base-content/40">
                    <x-icon name="o-chart-bar" class="w-10 h-10 mx-auto mb-2" />
                    <p>Aucun cours inscrit</p>
                </div>
            </x-card>
        </div>
        @endforelse
    </div>
</div>
