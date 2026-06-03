<?php

use App\Models\Program;
use App\Models\ProgramEnrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public string $search      = '';
    public string $filterLevel = '';

    public function with(): array
    {
        $programs = Program::withCount(['courses','sessions','enrollments'])
            ->where('status','published')
            ->whereIn('audience',['students','both'])
            ->when($this->search, fn($q) => $q->where('title','like',"%{$this->search}%"))
            ->when($this->filterLevel, fn($q) => $q->where('level', $this->filterLevel))
            ->latest()->get();

        $enrolledIds = ProgramEnrollment::where('user_id', auth()->id())
            ->pluck('program_id')->toArray();

        return compact('programs','enrolledIds');
    }

    public function enroll(int $programId): void
    {
        $program = Program::findOrFail($programId);
        if ($program->is_full) { $this->error('Program is full.'); return; }

        ProgramEnrollment::firstOrCreate(
            ['program_id' => $programId, 'user_id' => auth()->id()],
            ['enrolled_at' => now(), 'status' => 'active', 'progress_percent' => 0]
        );
        $this->success('Enrolled in program.');
    }
};
?>

<div>
    <x-header title="Programs" subtitle="Curated learning paths for students">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" class="w-full sm:w-64" />
        </x-slot:middle>
    </x-header>

    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button wire:click="$set('filterLevel','')" @class(['btn btn-sm shrink-0', 'btn-primary' => $filterLevel==='', 'btn-ghost' => $filterLevel!==''])>All</button>
        <button wire:click="$set('filterLevel','beginner')" @class(['btn btn-sm shrink-0', 'btn-success' => $filterLevel==='beginner', 'btn-ghost' => $filterLevel!=='beginner'])>Beginner</button>
        <button wire:click="$set('filterLevel','intermediate')" @class(['btn btn-sm shrink-0', 'btn-info' => $filterLevel==='intermediate', 'btn-ghost' => $filterLevel!=='intermediate'])>Intermediate</button>
        <button wire:click="$set('filterLevel','premium')" @class(['btn btn-sm shrink-0', 'btn-warning' => $filterLevel==='premium', 'btn-ghost' => $filterLevel!=='premium'])>Premium</button>
    </div>

    @if($programs->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-rectangle-stack" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50">No programs available.</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($programs as $p)
                @php $enrolled = in_array($p->id, $enrolledIds); @endphp
                <div class="card bg-base-100 shadow-sm overflow-hidden">
                    <div @class([
                        'h-28 relative',
                        'bg-gradient-to-br from-emerald-400 to-teal-600' => $p->level==='beginner',
                        'bg-gradient-to-br from-blue-400 to-indigo-600' => $p->level==='intermediate',
                        'bg-gradient-to-br from-amber-400 to-orange-600' => $p->level==='premium',
                    ])>
                        <span class="badge badge-sm bg-white/90 text-gray-800 border-0 capitalize absolute top-2 left-2">{{ $p->level }}</span>
                        @if($enrolled)
                            <span class="badge badge-success badge-sm absolute top-2 right-2">Enrolled</span>
                        @endif
                    </div>
                    <div class="card-body p-4">
                        <a href="{{ route('student.programs.show', $p) }}" wire:navigate><h3 class="font-bold text-sm hover:text-primary">{{ $p->title }}</h3></a>
                        @if($p->description)
                            <p class="text-xs text-base-content/60 line-clamp-2">{{ $p->description }}</p>
                        @endif
                        <div class="flex gap-3 text-xs text-base-content/50 mt-1">
                            <span>{{ $p->courses_count }} courses</span>
                            <span>{{ $p->sessions_count }} sessions</span>
                        </div>
                        @if(!$enrolled)
                            <button wire:click="enroll({{ $p->id }})" class="btn btn-primary btn-sm w-full mt-2">Enroll</button>
                        @else
                            <a href="{{ route('student.programs.show', $p) }}" wire:navigate class="btn btn-ghost btn-sm w-full mt-2">Continue</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
