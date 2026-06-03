<?php

use App\Models\ProgramEnrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public string $filter = 'all';

    public function with(): array
    {
        $query = ProgramEnrollment::with('program')->where('user_id', auth()->id());

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return [
            'enrollments' => $query->latest('enrolled_at')->get(),
            'counts' => [
                'all'       => ProgramEnrollment::where('user_id', auth()->id())->count(),
                'active'    => ProgramEnrollment::where('user_id', auth()->id())->where('status','active')->count(),
                'completed' => ProgramEnrollment::where('user_id', auth()->id())->where('status','completed')->count(),
                'cancelled' => ProgramEnrollment::where('user_id', auth()->id())->where('status','cancelled')->count(),
            ],
        ];
    }
};
?>

<div>
    <x-header title="My Programs" subtitle="Programs you are enrolled in" />

    {{-- Filter tabs --}}
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        @foreach([
            ['all','All','badge-ghost'],
            ['active','Active','badge-success'],
            ['completed','Completed','badge-info'],
            ['cancelled','Cancelled','badge-error'],
        ] as [$key, $label, $badgeClass])
            <button wire:click="$set('filter','{{ $key }}')"
                    @class(['btn btn-sm shrink-0', 'btn-primary' => $filter === $key, 'btn-ghost' => $filter !== $key])>
                {{ $label }}
                <span class="badge badge-sm ml-1">{{ $counts[$key] }}</span>
            </button>
        @endforeach
    </div>

    @if($enrollments->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-rectangle-stack" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No programs in this category.</p>
            <a href="{{ route('individual.programs') }}" wire:navigate class="btn btn-primary mt-4">Browse Programs</a>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach($enrollments as $e)
                @php $p = $e->program; @endphp
                <a href="{{ route('individual.programs.show', $p) }}" wire:navigate
                   class="card bg-base-100 shadow-sm hover:shadow-lg transition-all overflow-hidden">
                    <div @class([
                        'h-28 relative',
                        'bg-gradient-to-br from-emerald-400 to-teal-600' => $p->level === 'beginner',
                        'bg-gradient-to-br from-blue-400 to-indigo-600'   => $p->level === 'intermediate',
                        'bg-gradient-to-br from-amber-400 to-orange-600'  => $p->level === 'premium',
                    ])>
                        @if($p->thumbnail)
                            <img src="{{ Storage::url($p->thumbnail) }}" class="absolute inset-0 w-full h-full object-cover" alt="">
                        @endif
                        <span @class([
                            'badge badge-sm absolute top-2 right-2',
                            'badge-success' => $e->status==='active',
                            'badge-info'    => $e->status==='completed',
                            'badge-error'   => $e->status==='cancelled',
                        ])>{{ ucfirst($e->status) }}</span>
                    </div>
                    <div class="card-body p-4">
                        <h3 class="font-bold text-sm sm:text-base">{{ $p->title }}</h3>
                        <div class="mt-1">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-base-content/60">Progress</span>
                                <span class="font-semibold text-primary">{{ number_format($e->progress_percent, 0) }}%</span>
                            </div>
                            <progress class="progress progress-primary w-full h-1.5"
                                      value="{{ $e->progress_percent }}" max="100"></progress>
                        </div>
                        <p class="text-xs text-base-content/40 mt-1">
                            Enrolled {{ $e->enrolled_at?->diffForHumans() }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
