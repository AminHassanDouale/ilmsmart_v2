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
    public string $filterType  = '';

    public function with(): array
    {
        $user = auth()->user();

        $programs = Program::withCount(['courses','sessions','enrollments'])
            ->where('status', 'published')
            ->whereIn('audience', ['individuals','both'])
            ->when($this->search,      fn($q) => $q->where('title','like',"%{$this->search}%"))
            ->when($this->filterLevel, fn($q) => $q->where('level', $this->filterLevel))
            ->when($this->filterType,  fn($q) => $q->where('type', $this->filterType))
            ->latest()
            ->get();

        $enrolledIds = ProgramEnrollment::where('user_id', $user->id)
            ->whereIn('program_id', $programs->pluck('id'))
            ->pluck('program_id')->toArray();

        return compact('programs', 'enrolledIds');
    }
};
?>

<div>
    <x-header title="Browse Programs" subtitle="Discover bundled curricula crafted for your learning journey">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search programs..." wire:model.live.debounce="search"
                     icon="o-magnifying-glass" class="w-full sm:w-64" />
        </x-slot:middle>
    </x-header>

    <div class="flex gap-2 overflow-x-auto pb-2 mb-4">
        <button wire:click="$set('filterLevel','')"
                @class(['btn btn-sm shrink-0', 'btn-primary' => $filterLevel==='', 'btn-ghost' => $filterLevel!==''])>All Levels</button>
        <button wire:click="$set('filterLevel','beginner')"
                @class(['btn btn-sm shrink-0', 'btn-success' => $filterLevel==='beginner', 'btn-ghost' => $filterLevel!=='beginner'])>Beginner</button>
        <button wire:click="$set('filterLevel','intermediate')"
                @class(['btn btn-sm shrink-0', 'btn-info' => $filterLevel==='intermediate', 'btn-ghost' => $filterLevel!=='intermediate'])>Intermediate</button>
        <button wire:click="$set('filterLevel','premium')"
                @class(['btn btn-sm shrink-0', 'btn-warning' => $filterLevel==='premium', 'btn-ghost' => $filterLevel!=='premium'])>Premium</button>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
        <button wire:click="$set('filterType','')"
                @class(['btn btn-xs shrink-0', 'btn-primary' => $filterType==='', 'btn-ghost' => $filterType!==''])>All</button>
        <button wire:click="$set('filterType','free')"
                @class(['btn btn-xs shrink-0', 'btn-success' => $filterType==='free', 'btn-ghost' => $filterType!=='free'])>Free</button>
        <button wire:click="$set('filterType','paid')"
                @class(['btn btn-xs shrink-0', 'btn-info' => $filterType==='paid', 'btn-ghost' => $filterType!=='paid'])>Paid</button>
        <button wire:click="$set('filterType','subscription')"
                @class(['btn btn-xs shrink-0', 'btn-warning' => $filterType==='subscription', 'btn-ghost' => $filterType!=='subscription'])>Subscription</button>
    </div>

    @if($programs->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-rectangle-stack" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No programs match your search.</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach($programs as $p)
                @php $isEnrolled = in_array($p->id, $enrolledIds); @endphp
                <a href="{{ route('individual.programs.show', $p) }}" wire:navigate
                   class="card bg-base-100 shadow-sm hover:shadow-xl transition-all overflow-hidden">
                    {{-- Banner --}}
                    <div @class([
                        'h-32 sm:h-40 relative',
                        'bg-gradient-to-br from-emerald-400 to-teal-600' => $p->level === 'beginner',
                        'bg-gradient-to-br from-blue-400 to-indigo-600'   => $p->level === 'intermediate',
                        'bg-gradient-to-br from-amber-400 to-orange-600'  => $p->level === 'premium',
                        'bg-gradient-to-br from-gray-400 to-gray-600'     => $p->level === 'custom',
                    ])>
                        @if($p->thumbnail)
                            <img src="{{ Storage::url($p->thumbnail) }}" alt="{{ $p->title }}"
                                 class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <x-icon name="o-rectangle-stack" class="w-16 h-16 text-white/30" />
                            </div>
                        @endif
                        <span class="badge badge-sm bg-white/90 text-gray-800 border-0 capitalize font-semibold absolute top-3 left-3">{{ $p->level }}</span>
                        @if($isEnrolled)
                            <span class="badge badge-success badge-sm absolute top-3 right-3">Enrolled</span>
                        @endif
                    </div>

                    <div class="card-body p-4 sm:p-5">
                        <h3 class="font-bold text-base sm:text-lg leading-tight">{{ $p->title }}</h3>

                        @if($p->description)
                            <p class="text-xs sm:text-sm text-base-content/60 line-clamp-2">{{ $p->description }}</p>
                        @endif

                        <div class="flex flex-wrap gap-2 text-xs text-base-content/50 mt-1">
                            <span class="flex items-center gap-1">
                                <x-icon name="o-book-open" class="w-4 h-4" /> {{ $p->courses_count }} courses
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-clock" class="w-4 h-4" /> {{ $p->sessions_count }} sessions
                            </span>
                            @if($p->duration_weeks)
                                <span class="flex items-center gap-1">
                                    <x-icon name="o-calendar" class="w-4 h-4" /> {{ $p->duration_weeks }} weeks
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between mt-2">
                            <span class="text-sm font-bold">
                                @if($p->type === 'free') Free
                                @else {{ number_format($p->price, 0) }} DZD @endif
                            </span>
                            <span class="text-xs text-base-content/40">{{ $p->enrollments_count }} enrolled</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
