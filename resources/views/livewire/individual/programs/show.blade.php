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
        $enrollment = ProgramEnrollment::where('program_id', $this->program->id)
            ->where('user_id', auth()->id())
            ->first();

        return compact('enrollment');
    }

    public function enroll(): void
    {
        $user = auth()->user();

        if ($this->program->is_full) {
            $this->error('This program is full.');
            return;
        }

        if ($this->program->type !== 'free' && $this->program->price > 0) {
            $this->warning('This program requires payment. Redirecting to checkout...');
            return;
        }

        ProgramEnrollment::firstOrCreate(
            ['program_id' => $this->program->id, 'user_id' => $user->id],
            ['enrolled_at' => now(), 'status' => 'active', 'progress_percent' => 0]
        );

        // Notify
        if ($firstCourse = $this->program->courses->first()) {
            $user->notify(new \App\Notifications\CourseEnrollmentNotification($firstCourse));
        }

        $this->success('You are now enrolled in this program!');
    }

    public function unenroll(): void
    {
        ProgramEnrollment::where('program_id', $this->program->id)
            ->where('user_id', auth()->id())
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->warning('You have been removed from this program.');
    }
};
?>

<div>
    <x-header :title="$program->title">
        <x-slot:middle>
            <div class="flex items-center gap-2 text-sm text-base-content/50">
                <a href="{{ route('individual.programs') }}" wire:navigate class="hover:text-primary">Programs</a>
                <x-icon name="o-chevron-right" class="w-4 h-4" />
                <span class="truncate max-w-xs">{{ $program->title }}</span>
            </div>
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Hero --}}
            <div class="card bg-base-100 shadow-sm overflow-hidden">
                @if($program->thumbnail)
                    <img src="{{ Storage::url($program->thumbnail) }}" class="w-full h-48 sm:h-64 object-cover" alt="">
                @else
                    <div @class([
                        'w-full h-48 sm:h-64 flex items-center justify-center',
                        'bg-gradient-to-br from-emerald-500 to-teal-600' => $program->level === 'beginner',
                        'bg-gradient-to-br from-blue-500 to-indigo-600'   => $program->level === 'intermediate',
                        'bg-gradient-to-br from-amber-500 to-orange-600'  => $program->level === 'premium',
                    ])>
                        <x-icon name="o-rectangle-stack" class="w-24 h-24 text-white/30" />
                    </div>
                @endif
                <div class="card-body">
                    <div class="flex items-center gap-2 flex-wrap mb-2">
                        <span class="badge badge-outline capitalize">{{ $program->level }}</span>
                        @if($program->is_islamic)
                            <span class="badge badge-info">☪ Islamic</span>
                        @endif
                        @if($program->duration_weeks)
                            <span class="badge badge-ghost">{{ $program->duration_weeks }} weeks</span>
                        @endif
                    </div>
                    @if($program->description)
                        <p class="text-base-content/70">{{ $program->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Curriculum --}}
            @if($program->courses->isNotEmpty())
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="font-bold text-lg mb-3">Curriculum</h3>
                        <div class="space-y-2">
                            @foreach($program->courses as $idx => $course)
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-base-200/50">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm shrink-0">
                                        {{ $idx + 1 }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm">{{ $course->title }}</p>
                                        @if($course->description)
                                            <p class="text-xs text-base-content/60 line-clamp-1">{{ $course->description }}</p>
                                        @endif
                                    </div>
                                    @if($course->pivot->is_required)
                                        <span class="badge badge-warning badge-xs">Required</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Sessions schedule --}}
            @if($program->sessions->isNotEmpty())
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="font-bold text-lg mb-3 flex items-center gap-2">
                            <x-icon name="o-calendar" class="w-5 h-5" />
                            Weekly Schedule
                        </h3>
                        <div class="space-y-2">
                            @foreach($program->sessions as $s)
                                <div class="flex items-start gap-3 p-3 rounded-lg bg-base-200/50">
                                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex flex-col items-center justify-center shrink-0">
                                        <span class="text-[10px] text-base-content/60 uppercase">{{ $s->day_label ? substr($s->day_label, 0, 3) : 'Once' }}</span>
                                        <span class="text-xs font-bold text-primary">{{ $s->start_time ? substr($s->start_time, 0, 5) : '—' }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm">{{ $s->title }}</p>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-base-content/60 flex-wrap">
                                            <span class="capitalize">{{ $s->recurrence }}</span>
                                            @if($s->day_label) <span>· {{ $s->day_label }}</span> @endif
                                            <span>· {{ $s->time_range }}</span>
                                            @if($s->location) <span>· {{ $s->location }}</span> @endif
                                        </div>
                                        @if($enrollment && $s->meeting_link)
                                            <a href="{{ $s->meeting_link }}" target="_blank"
                                               class="text-xs text-info hover:underline mt-1 inline-flex items-center gap-1">
                                                <x-icon name="o-video-camera" class="w-3 h-3" /> Join
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: Enrollment card --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-sm sticky top-4">
                <div class="card-body">
                    <div class="text-center">
                        <p class="text-3xl font-bold mb-1">
                            @if($program->type === 'free') Free
                            @else {{ number_format($program->price, 0) }} <span class="text-base font-normal text-base-content/60">DZD</span>
                            @endif
                        </p>
                        <p class="text-xs text-base-content/50 capitalize">{{ $program->type }} access</p>
                    </div>

                    <div class="divider my-2"></div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">Courses</span>
                            <span class="font-semibold">{{ $program->courses->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">Sessions</span>
                            <span class="font-semibold">{{ $program->sessions->count() }}</span>
                        </div>
                        @if($program->duration_weeks)
                            <div class="flex justify-between">
                                <span class="text-base-content/60">Duration</span>
                                <span class="font-semibold">{{ $program->duration_weeks }} weeks</span>
                            </div>
                        @endif
                        @if($program->capacity)
                            <div class="flex justify-between">
                                <span class="text-base-content/60">Seats left</span>
                                <span class="font-semibold">{{ max(0, $program->capacity - $program->enrolled_count) }} / {{ $program->capacity }}</span>
                            </div>
                        @endif
                        @if($program->start_date)
                            <div class="flex justify-between">
                                <span class="text-base-content/60">Starts</span>
                                <span class="font-semibold">{{ $program->start_date->format('M d, Y') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="divider my-2"></div>

                    @if($enrollment && $enrollment->status === 'active')
                        <div class="mb-3">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-base-content/60">Your progress</span>
                                <span class="font-semibold text-primary">{{ number_format($enrollment->progress_percent, 0) }}%</span>
                            </div>
                            <progress class="progress progress-primary w-full" value="{{ $enrollment->progress_percent }}" max="100"></progress>
                        </div>
                        <button wire:click="unenroll" wire:confirm="Cancel this enrollment?"
                                class="btn btn-error btn-outline btn-sm w-full">
                            <x-icon name="o-x-circle" class="w-4 h-4" /> Cancel Enrollment
                        </button>
                    @elseif($program->is_full)
                        <button class="btn btn-disabled w-full">Program Full</button>
                    @else
                        <button wire:click="enroll" class="btn btn-primary w-full">
                            <x-icon name="o-plus" class="w-4 h-4" />
                            @if($program->type === 'free') Enroll Free
                            @else Enroll Now @endif
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
