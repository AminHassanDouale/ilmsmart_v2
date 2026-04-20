<?php
use App\Models\LiveClass;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Classes en direct')]
class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    public function with(): array {
        return [
            'classes' => LiveClass::with('teacher.user','course')
                ->when($this->search, fn($q) => $q->where('title','like',"%{$this->search}%"))
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->orderByDesc('start_time')->paginate(15),
            'stats' => [
                'scheduled' => LiveClass::where('status','scheduled')->count(),
                'live'      => LiveClass::where('status','live')->count(),
                'ended'     => LiveClass::where('status','ended')->count(),
            ],
        ];
    }

    public function cancel(LiveClass $class): void {
        $class->update(['status' => 'cancelled']);
        $this->toast('Classe annulée', type: 'warning');
    }
} ?>

<div>
    <x-header title="Classes en direct" subtitle="Gérer les sessions en direct" separator>
        <x-slot:actions>
            <x-input placeholder="Rechercher..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
            <x-select placeholder="Tous statuts" :options="[['id'=>'scheduled','name'=>'Planifiée'],['id'=>'live','name'=>'En direct'],['id'=>'ended','name'=>'Terminée'],['id'=>'cancelled','name'=>'Annulée']]" wire:model.live="filterStatus" class="select-sm" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <x-stat title="Planifiées" :value="$stats['scheduled']" icon="o-calendar" color="text-info" />
        <x-stat title="En direct" :value="$stats['live']" icon="o-signal" color="text-error" />
        <x-stat title="Terminées" :value="$stats['ended']" icon="o-check" color="text-success" />
    </div>

    <x-card>
        <x-table :headers="[['key'=>'title','label'=>'Titre'],['key'=>'teacher','label'=>'Enseignant'],['key'=>'start_time','label'=>'Début'],['key'=>'platform','label'=>'Plateforme'],['key'=>'status','label'=>'Statut'],['key'=>'actions','label'=>'']]" :rows="$classes">
            @scope('cell_title', $class)
                <div>
                    <div class="font-semibold">{{ $class->title }}</div>
                    @if($class->course) <div class="text-xs opacity-50">{{ $class->course->title }}</div> @endif
                </div>
            @endscope
            @scope('cell_teacher', $class)
                <span class="text-sm">{{ $class->teacher->user->full_name ?? 'N/A' }}</span>
            @endscope
            @scope('cell_start_time', $class)
                <div class="text-sm">{{ $class->start_time->format('d/m/Y H:i') }}</div>
            @endscope
            @scope('cell_platform', $class)
                <x-badge :value="$class->platform" class="badge-ghost uppercase" />
            @endscope
            @scope('cell_status', $class)
                @php $colors = ['scheduled'=>'badge-info','live'=>'badge-error','ended'=>'badge-success','cancelled'=>'badge-ghost']; @endphp
                <x-badge :value="$class->status" class="{{ $colors[$class->status] ?? 'badge-ghost' }}" />
            @endscope
            @scope('actions', $class)
                @if($class->status === 'scheduled')
                    <x-button icon="o-x-circle" wire:click="cancel({{ $class->id }})" wire:confirm="Annuler cette classe ?" class="btn-ghost btn-xs text-error" />
                @endif
                @if($class->meeting_link)
                    <x-button icon="o-arrow-top-right-on-square" link="{{ $class->meeting_link }}" external class="btn-ghost btn-xs text-primary" />
                @endif
            @endscope
        </x-table>
        {{ $classes->links() }}
    </x-card>
</div>
