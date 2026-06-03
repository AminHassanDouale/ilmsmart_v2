<?php

use App\Models\ProgramSession;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public function with(): array
    {
        $teacher = auth()->user()->teacher;
        if (!$teacher) return ['sessions' => collect(), 'programs' => collect()];

        $sessions = ProgramSession::with('program')
            ->where('teacher_id', $teacher->id)
            ->orderByRaw("FIELD(day_of_week,'mon','tue','wed','thu','fri','sat','sun')")
            ->orderBy('start_time')
            ->get();

        $programs = $sessions->pluck('program')->unique('id');

        return compact('sessions','programs');
    }
};
?>

<div>
    <x-header title="My Programs" subtitle="Programs and sessions you teach" />

    {{-- Programs --}}
    @if($programs->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-rectangle-stack" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50">No programs assigned yet.</p>
        </x-card>
    @else
        <h3 class="font-bold mb-3">Programs ({{ $programs->count() }})</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            @foreach($programs as $p)
                <x-card class="!p-4">
                    <h4 class="font-bold text-sm sm:text-base">{{ $p->title }}</h4>
                    <p class="text-xs text-base-content/60 line-clamp-2 mt-1">{{ $p->description }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="badge badge-sm capitalize">{{ $p->level }}</span>
                        @if($p->is_islamic)
                            <span class="badge badge-info badge-sm">☪</span>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>

        <h3 class="font-bold mb-3">Your Sessions ({{ $sessions->count() }})</h3>
        <div class="space-y-2">
            @foreach($sessions as $s)
                <x-card class="!p-3 sm:!p-4">
                    <div class="flex items-start gap-3 flex-wrap sm:flex-nowrap">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex flex-col items-center justify-center shrink-0">
                            <span class="text-[10px] uppercase">{{ $s->day_label ? substr($s->day_label,0,3) : 'Once' }}</span>
                            <span class="text-xs font-bold text-primary">{{ $s->start_time ? substr($s->start_time,0,5) : '—' }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">{{ $s->title }}</p>
                            <p class="text-xs text-base-content/60">{{ $s->program->title }}</p>
                            <div class="flex gap-2 mt-1 text-xs text-base-content/50 flex-wrap">
                                <span>{{ $s->time_range }}</span>
                                @if($s->location)
                                    <span>· {{ $s->location }}</span>
                                @endif
                            </div>
                        </div>
                        @if($s->meeting_link)
                            <a href="{{ $s->meeting_link }}" target="_blank" class="btn btn-info btn-sm">
                                <x-icon name="o-video-camera" class="w-4 h-4" /> Start
                            </a>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>
