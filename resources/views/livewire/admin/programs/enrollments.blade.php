<?php

use App\Models\Program;
use App\Models\ProgramEnrollment;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Program $program;
    public string $search       = '';
    public string $filterStatus = '';

    public function mount(Program $program): void
    {
        $this->program = $program;
    }

    public function with(): array
    {
        $enrollments = ProgramEnrollment::with('user')
            ->where('program_id', $this->program->id)
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn($q) => $q->whereHas('user', fn($u) =>
                $u->where('name','like',"%{$this->search}%")
                  ->orWhere('email','like',"%{$this->search}%")
            ))
            ->latest('enrolled_at')
            ->paginate(20);

        $stats = [
            'total'     => ProgramEnrollment::where('program_id', $this->program->id)->count(),
            'active'    => ProgramEnrollment::where('program_id', $this->program->id)->where('status','active')->count(),
            'completed' => ProgramEnrollment::where('program_id', $this->program->id)->where('status','completed')->count(),
            'cancelled' => ProgramEnrollment::where('program_id', $this->program->id)->where('status','cancelled')->count(),
        ];

        return compact('enrollments', 'stats');
    }

    public function cancel(int $id): void
    {
        $e = ProgramEnrollment::findOrFail($id);
        $e->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->warning('Enrollment cancelled.');
    }

    public function reactivate(int $id): void
    {
        $e = ProgramEnrollment::findOrFail($id);
        $e->update(['status' => 'active', 'cancelled_at' => null]);
        $this->success('Enrollment reactivated.');
    }
};
?>

<div>
    <x-header :title="'Enrollments: ' . $program->title">
        <x-slot:middle>
            <div class="flex items-center gap-2 text-sm text-base-content/50">
                <a href="{{ route('admin.programs') }}" wire:navigate class="hover:text-primary">Programs</a>
                <x-icon name="o-chevron-right" class="w-4 h-4" />
                <span class="truncate max-w-xs">{{ $program->title }}</span>
            </div>
        </x-slot:middle>
    </x-header>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <x-stat title="Total" :value="$stats['total']" icon="o-users" />
        <x-stat title="Active" :value="$stats['active']" icon="o-check-circle" color="text-success" />
        <x-stat title="Completed" :value="$stats['completed']" icon="o-trophy" color="text-warning" />
        <x-stat title="Cancelled" :value="$stats['cancelled']" icon="o-x-circle" color="text-error" />
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <x-input placeholder="Search by name or email..." wire:model.live.debounce="search"
                 icon="o-magnifying-glass" class="w-full sm:w-72" />
        <x-select wire:model.live="filterStatus" placeholder="All statuses" :options="[
            ['id'=>'active','name'=>'Active'],
            ['id'=>'completed','name'=>'Completed'],
            ['id'=>'cancelled','name'=>'Cancelled'],
            ['id'=>'pending','name'=>'Pending'],
        ]" option-value="id" option-label="name" class="w-full sm:w-48" />
    </div>

    {{-- Table (desktop) / Cards (mobile) --}}
    @if($enrollments->isEmpty())
        <x-card class="text-center py-12">
            <x-icon name="o-users" class="w-14 h-14 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50">No enrollments found.</p>
        </x-card>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block">
            <x-card>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Enrolled</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($enrollments as $e)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar"><div class="w-8 rounded-full">
                                            <img src="{{ $e->user->avatar_url }}" alt="">
                                        </div></div>
                                        <span class="font-medium">{{ $e->user->full_name }}</span>
                                    </div>
                                </td>
                                <td class="text-xs text-base-content/60">{{ $e->user->email }}</td>
                                <td class="text-xs">{{ $e->enrolled_at?->format('M d, Y') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <progress class="progress progress-primary w-20 h-1.5"
                                                  value="{{ $e->progress_percent }}" max="100"></progress>
                                        <span class="text-xs">{{ number_format($e->progress_percent, 0) }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <span @class([
                                        'badge badge-sm capitalize',
                                        'badge-success' => $e->status==='active',
                                        'badge-info'    => $e->status==='completed',
                                        'badge-error'   => $e->status==='cancelled',
                                        'badge-warning' => $e->status==='pending',
                                    ])>{{ $e->status }}</span>
                                </td>
                                <td class="text-end">
                                    @if($e->status === 'active')
                                        <x-button icon="o-x-circle" wire:click="cancel({{ $e->id }})"
                                                  wire:confirm="Cancel this enrollment?"
                                                  class="btn-ghost btn-xs text-error" tooltip="Cancel" />
                                    @elseif($e->status === 'cancelled')
                                        <x-button icon="o-arrow-path" wire:click="reactivate({{ $e->id }})"
                                                  class="btn-ghost btn-xs text-success" tooltip="Reactivate" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @foreach($enrollments as $e)
                <x-card class="!p-3">
                    <div class="flex items-start gap-3">
                        <div class="avatar"><div class="w-10 rounded-full">
                            <img src="{{ $e->user->avatar_url }}" alt="">
                        </div></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ $e->user->full_name }}</p>
                            <p class="text-xs text-base-content/60 truncate">{{ $e->user->email }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <progress class="progress progress-primary w-16 h-1"
                                          value="{{ $e->progress_percent }}" max="100"></progress>
                                <span class="text-xs">{{ number_format($e->progress_percent, 0) }}%</span>
                                <span @class([
                                    'badge badge-xs capitalize ml-auto',
                                    'badge-success' => $e->status==='active',
                                    'badge-info'    => $e->status==='completed',
                                    'badge-error'   => $e->status==='cancelled',
                                ])>{{ $e->status }}</span>
                            </div>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-4">{{ $enrollments->links() }}</div>
    @endif
</div>
