<?php

use App\Models\Program;
use App\Models\Course;
use App\Models\ProgramEnrollment;
use App\Models\Enrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public function with(): array
    {
        $user = auth()->user();

        $programEnrollments = ProgramEnrollment::with('program')
            ->where('user_id', $user->id)
            ->latest('enrolled_at')
            ->take(4)
            ->get();

        // For courses we still use Enrollment->student_id model — individuals don't have student profile.
        // Browse public programs/courses count
        $featuredPrograms = Program::withCount(['courses','enrollments'])
            ->where('status', 'published')
            ->whereIn('audience', ['individuals','both'])
            ->latest()
            ->take(3)
            ->get();

        $featuredCourses = Course::withCount(['lessons','enrollments'])
            ->where('status', 'published')
            ->where('is_islamic', true)
            ->take(3)
            ->get();

        return [
            'programEnrollments'   => $programEnrollments,
            'featuredPrograms'     => $featuredPrograms,
            'featuredCourses'      => $featuredCourses,
            'activeSubscription'   => $user->activeSubscription,
            'totalProgress'        => $programEnrollments->avg('progress_percent') ?? 0,
            'completedPrograms'    => ProgramEnrollment::where('user_id', $user->id)->where('status','completed')->count(),
            'activePrograms'       => ProgramEnrollment::where('user_id', $user->id)->where('status','active')->count(),
        ];
    }
};
?>

<div>
    <x-header :title="'Assalamu Alaikum, ' . (auth()->user()->full_name ?? auth()->user()->name) . '!'"
              subtitle="Continue your journey of sacred knowledge" />

    {{-- Stats grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <x-stat title="Active Programs"    :value="$activePrograms"    icon="o-rectangle-stack" color="text-primary" />
        <x-stat title="Completed"          :value="$completedPrograms" icon="o-trophy"          color="text-warning" />
        <x-stat title="Overall Progress"   :value="number_format($totalProgress, 0) . '%'" icon="o-chart-bar" color="text-success" />
        <x-stat title="Subscription"
                :value="$activeSubscription?->plan?->name ?? 'Free'"
                icon="o-star"
                color="text-info" />
    </div>

    {{-- Active subscription banner --}}
    @if($activeSubscription)
        <div class="bg-gradient-to-r from-primary to-purple-600 text-white rounded-xl p-4 sm:p-6 mb-6 shadow-lg">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="text-sm opacity-90">Current Plan</p>
                    <h3 class="text-xl sm:text-2xl font-bold">{{ $activeSubscription->plan->name ?? '—' }}</h3>
                    <p class="text-xs sm:text-sm opacity-80 mt-1">
                        Renews {{ $activeSubscription->ends_at?->format('M d, Y') ?? '—' }}
                    </p>
                </div>
                <a href="{{ route('individual.subscriptions') }}" wire:navigate
                   class="btn btn-sm bg-white text-primary border-0 hover:bg-white/90">
                    Manage
                </a>
            </div>
        </div>
    @else
        <div class="bg-base-200 rounded-xl p-4 sm:p-6 mb-6 border border-base-300">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <x-icon name="o-star" class="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Upgrade to Premium</p>
                        <p class="text-xs text-base-content/60">Unlock all premium programs and courses</p>
                    </div>
                </div>
                <a href="{{ route('individual.subscriptions') }}" wire:navigate class="btn btn-primary btn-sm">
                    View Plans
                </a>
            </div>
        </div>
    @endif

    {{-- My active programs --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">My Programs</h2>
            <a href="{{ route('individual.my-programs') }}" wire:navigate class="text-sm text-primary hover:underline">View all →</a>
        </div>

        @if($programEnrollments->isEmpty())
            <x-card class="text-center py-10">
                <x-icon name="o-rectangle-stack" class="w-12 h-12 mx-auto text-base-content/20 mb-3" />
                <p class="text-base-content/50 text-sm">You haven't enrolled in any programs yet.</p>
                <a href="{{ route('individual.programs') }}" wire:navigate class="btn btn-primary btn-sm mt-3">
                    Browse Programs
                </a>
            </x-card>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                @foreach($programEnrollments as $e)
                    @php $prog = $e->program; @endphp
                    <a href="{{ route('individual.programs.show', $prog) }}" wire:navigate
                       class="card bg-base-100 shadow-sm hover:shadow-lg transition-all">
                        <div class="card-body p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h3 class="font-bold text-sm sm:text-base leading-tight">{{ $prog->title }}</h3>
                                <span @class([
                                    'badge badge-sm',
                                    'badge-success' => $e->status==='active',
                                    'badge-info'    => $e->status==='completed',
                                    'badge-ghost'   => $e->status==='cancelled',
                                ])>{{ ucfirst($e->status) }}</span>
                            </div>
                            <div class="mb-1">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-base-content/60">Progress</span>
                                    <span class="font-semibold text-primary">{{ number_format($e->progress_percent, 0) }}%</span>
                                </div>
                                <progress class="progress progress-primary w-full h-1.5"
                                          value="{{ $e->progress_percent }}" max="100"></progress>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Featured programs --}}
    @if($featuredPrograms->isNotEmpty())
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">Featured Programs</h2>
            <a href="{{ route('individual.programs') }}" wire:navigate class="text-sm text-primary hover:underline">Explore all →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            @foreach($featuredPrograms as $p)
                <a href="{{ route('individual.programs.show', $p) }}" wire:navigate
                   class="card bg-base-100 shadow-sm hover:shadow-lg transition-all overflow-hidden">
                    <div class="h-32 bg-gradient-to-br from-emerald-500 to-teal-600 relative">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <x-icon name="o-rectangle-stack" class="w-14 h-14 text-white/30" />
                        </div>
                        <span class="badge badge-sm bg-white/90 text-gray-800 border-0 absolute top-3 left-3 capitalize">{{ $p->level }}</span>
                    </div>
                    <div class="card-body p-4">
                        <h3 class="font-bold text-sm sm:text-base">{{ $p->title }}</h3>
                        @if($p->description)
                            <p class="text-xs text-base-content/60 line-clamp-2">{{ $p->description }}</p>
                        @endif
                        <div class="flex items-center gap-3 text-xs text-base-content/50 mt-1">
                            <span>{{ $p->courses_count }} courses</span>
                            <span>·</span>
                            <span>{{ $p->enrollments_count }} learners</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
