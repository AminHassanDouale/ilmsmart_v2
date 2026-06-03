<?php

use App\Models\Program;
use App\Models\ProgramEnrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {
    public Program $program;

    public function mount(Program $program): void
    {
        $this->program = $program->load(['courses','sessions.teacher.user']);
    }

    public function with(): array
    {
        return [
            'enrollment' => ProgramEnrollment::where('program_id', $this->program->id)
                ->where('user_id', auth()->id())->first(),
        ];
    }
};
?>

<div>
    <x-header :title="$program->title">
        <x-slot:middle>
            <a href="{{ route('student.programs') }}" wire:navigate class="text-sm text-base-content/50 hover:text-primary">← Programs</a>
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            @if($program->description)
                <x-card>
                    <p>{{ $program->description }}</p>
                </x-card>
            @endif

            @if($program->courses->isNotEmpty())
                <x-card title="Courses in this program">
                    <div class="space-y-2">
                        @foreach($program->courses as $idx => $c)
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-base-200/50">
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">{{ $idx+1 }}</div>
                                <p class="flex-1 font-medium text-sm">{{ $c->title }}</p>
                                @if($c->pivot->is_required)
                                    <span class="badge badge-warning badge-xs">Required</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            @if($program->sessions->isNotEmpty())
                <x-card title="Weekly Schedule">
                    <div class="space-y-2">
                        @foreach($program->sessions as $s)
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-base-200/50">
                                <div class="w-12 h-12 rounded-xl bg-primary/10 flex flex-col items-center justify-center">
                                    <span class="text-[10px] uppercase">{{ $s->day_label ? substr($s->day_label,0,3) : 'Once' }}</span>
                                    <span class="text-xs font-bold text-primary">{{ $s->start_time ? substr($s->start_time,0,5) : '—' }}</span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm">{{ $s->title }}</p>
                                    <p class="text-xs text-base-content/60">{{ $s->time_range }} · {{ $s->location ?? 'Online' }}</p>
                                </div>
                                @if($enrollment && $s->meeting_link)
                                    <a href="{{ $s->meeting_link }}" target="_blank" class="btn btn-info btn-xs">Join</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>

        <div>
            <x-card class="sticky top-4">
                <h3 class="font-bold mb-2">Your Progress</h3>
                @if($enrollment)
                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-base-content/60">Completion</span>
                            <span class="font-semibold text-primary">{{ number_format($enrollment->progress_percent,0) }}%</span>
                        </div>
                        <progress class="progress progress-primary w-full" value="{{ $enrollment->progress_percent }}" max="100"></progress>
                    </div>
                    <p class="text-xs text-base-content/50">Enrolled {{ $enrollment->enrolled_at?->diffForHumans() }}</p>
                @else
                    <p class="text-sm text-base-content/60">Not enrolled yet.</p>
                @endif
            </x-card>
        </div>
    </div>
</div>
