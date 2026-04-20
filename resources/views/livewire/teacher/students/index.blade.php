<?php
use App\Models\{Student, Enrollment};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Mes étudiants')]
class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array {
        $teacherId = auth()->user()->teacher?->id;
        $courseIds = \App\Models\Course::where('teacher_id', $teacherId)->pluck('id');

        return [
            'students' => Student::with(['user','enrollments.course'])
                ->whereHas('enrollments', fn($q) => $q->whereIn('course_id', $courseIds))
                ->when($this->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name','like',"%{$this->search}%")))
                ->paginate(15),
            'totalStudents' => Student::whereHas('enrollments', fn($q) => $q->whereIn('course_id', $courseIds))->count(),
        ];
    }
} ?>

<div>
    <x-header title="Mes étudiants" subtitle="Étudiants inscrits dans vos cours" separator>
        <x-slot:actions>
            <x-input placeholder="Rechercher..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:actions>
    </x-header>

    <x-stat title="Total étudiants" :value="$totalStudents" icon="o-users" color="text-primary" class="mb-6 max-w-xs" />

    <x-card>
        <x-table :headers="[['key'=>'user','label'=>'Étudiant'],['key'=>'courses','label'=>'Cours inscrits'],['key'=>'created_at','label'=>'Inscrit le']]" :rows="$students">
            @scope('cell_user', $student)
                <div class="flex items-center gap-3">
                    <x-avatar :image="$student->user->avatar_url ?? ''" class="w-9 h-9" />
                    <div>
                        <div class="font-semibold">{{ $student->user->full_name }}</div>
                        <div class="text-xs opacity-50">{{ $student->user->email }}</div>
                    </div>
                </div>
            @endscope
            @scope('cell_courses', $student)
                <div class="flex flex-wrap gap-1">
                    @foreach($student->enrollments->take(3) as $enrollment)
                        <x-badge :value="$enrollment->course->title" class="badge-ghost badge-sm" />
                    @endforeach
                    @if($student->enrollments->count() > 3)
                        <x-badge :value="'+'.($student->enrollments->count()-3)" class="badge-primary badge-sm" />
                    @endif
                </div>
            @endscope
            @scope('cell_created_at', $student)
                {{ $student->created_at->format('d/m/Y') }}
            @endscope
        </x-table>
        {{ $students->links() }}
    </x-card>
</div>
