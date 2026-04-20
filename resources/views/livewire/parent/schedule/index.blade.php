<?php
use App\Models\LiveClass;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Planning')]
class extends Component {

    public function with(): array {
        $parent    = auth()->user()->parentProfile;
        $children  = $parent ? $parent->students : collect();
        $courseIds = collect();
        foreach ($children as $child) {
            $courseIds = $courseIds->merge($child->enrollments()->pluck('course_id'));
        }

        return [
            'upcoming' => LiveClass::whereIn('course_id', $courseIds->unique())
                ->where('status', 'scheduled')
                ->where('start_time', '>=', now())
                ->with('teacher.user','course')
                ->orderBy('start_time')
                ->get(),
        ];
    }
} ?>

<div>
    <x-header title="Planning" subtitle="Prochaines sessions de vos enfants" separator />

    <div class="space-y-3">
        @forelse($upcoming as $class)
        <x-card>
            <div class="flex items-center gap-4">
                <div class="min-w-[60px] text-center bg-primary/10 rounded-xl p-2">
                    <div class="text-2xl font-bold text-primary">{{ $class->start_time->format('d') }}</div>
                    <div class="text-xs text-primary/70 uppercase">{{ $class->start_time->locale('fr')->isoFormat('MMM') }}</div>
                </div>
                <div class="flex-1">
                    <div class="font-semibold">{{ $class->title }}</div>
                    <div class="text-sm opacity-60">{{ $class->teacher->user->full_name }} · {{ $class->start_time->format('H:i') }}–{{ $class->end_time->format('H:i') }}</div>
                    @if($class->course) <div class="text-xs opacity-40">{{ $class->course->title }}</div> @endif
                </div>
                <div class="text-right">
                    <x-badge :value="strtoupper($class->platform)" class="badge-ghost" />
                    <div class="text-xs opacity-50 mt-1">{{ $class->start_time->diffForHumans() }}</div>
                </div>
            </div>
        </x-card>
        @empty
        <x-card>
            <div class="text-center py-12 text-base-content/40">
                <x-icon name="o-calendar" class="w-12 h-12 mx-auto mb-3" />
                <p>Aucune session planifiée</p>
            </div>
        </x-card>
        @endforelse
    </div>
</div>
