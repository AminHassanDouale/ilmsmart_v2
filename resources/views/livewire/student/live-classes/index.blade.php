<?php
use App\Models\LiveClass;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Classes en direct')]
class extends Component {

    public function with(): array {
        $student = auth()->user()->student;
        $courseIds = $student ? $student->enrollments()->pluck('course_id') : collect();

        return [
            'upcoming' => LiveClass::whereIn('course_id', $courseIds)
                ->where('status', 'scheduled')
                ->where('start_time', '>=', now())
                ->with('teacher.user','course')
                ->orderBy('start_time')
                ->get(),
            'past' => LiveClass::whereIn('course_id', $courseIds)
                ->whereIn('status', ['ended','live'])
                ->with('teacher.user','course')
                ->orderByDesc('start_time')
                ->limit(10)
                ->get(),
        ];
    }
} ?>

<div>
    <x-header title="Classes en direct" subtitle="Vos sessions en direct" separator />

    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-semibold mb-3">Prochaines sessions</h3>
            @forelse($upcoming as $class)
            <x-card class="mb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-primary/10 flex flex-col items-center justify-center">
                            <div class="text-lg font-bold text-primary">{{ $class->start_time->format('d') }}</div>
                            <div class="text-xs text-primary/70">{{ $class->start_time->format('M') }}</div>
                        </div>
                        <div>
                            <div class="font-semibold">{{ $class->title }}</div>
                            <div class="text-sm opacity-60">{{ $class->teacher->user->full_name }}</div>
                            <div class="text-xs opacity-40">{{ $class->start_time->format('H:i') }} — {{ $class->end_time->format('H:i') }} · {{ strtoupper($class->platform) }}</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <x-badge :value="$class->start_time->diffForHumans()" class="badge-ghost" />
                        @if($class->meeting_link)
                            <x-button label="Rejoindre" icon="o-arrow-top-right-on-square" link="{{ $class->meeting_link }}" external class="btn-primary btn-sm" />
                        @endif
                    </div>
                </div>
            </x-card>
            @empty
            <x-card>
                <div class="text-center py-8 text-base-content/40">
                    <x-icon name="o-calendar" class="w-10 h-10 mx-auto mb-2" />
                    <p>Aucune session planifiée</p>
                </div>
            </x-card>
            @endforelse
        </div>

        @if($past->count())
        <div>
            <h3 class="text-lg font-semibold mb-3">Sessions passées</h3>
            @foreach($past as $class)
            <x-card class="mb-2 opacity-70">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-icon name="o-video-camera" class="w-5 h-5 opacity-40" />
                        <div>
                            <div class="font-medium text-sm">{{ $class->title }}</div>
                            <div class="text-xs opacity-50">{{ $class->start_time->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    @if($class->recording_url)
                        <x-button label="Voir l'enregistrement" icon="o-play" link="{{ $class->recording_url }}" external class="btn-ghost btn-xs" />
                    @endif
                </div>
            </x-card>
            @endforeach
        </div>
        @endif
    </div>
</div>
