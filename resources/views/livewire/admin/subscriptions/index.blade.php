<?php
use App\Models\Subscription;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Abonnements')]
class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    public function with(): array {
        return [
            'subscriptions' => Subscription::with('user','plan')
                ->when($this->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name','like',"%{$this->search}%")))
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->latest()->paginate(15),
            'stats' => [
                'active'    => Subscription::where('status','active')->count(),
                'expired'   => Subscription::where('status','expired')->count(),
                'cancelled' => Subscription::where('status','cancelled')->count(),
            ],
        ];
    }

    public function cancel(Subscription $sub): void {
        $sub->update(['status' => 'cancelled']);
        $this->toast('Abonnement annulé', type: 'warning');
    }
} ?>

<div>
    <x-header title="Abonnements" subtitle="Gérer les abonnements utilisateurs" separator>
        <x-slot:actions>
            <x-input placeholder="Rechercher utilisateur..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
            <x-select placeholder="Tous statuts" :options="[['id'=>'active','name'=>'Actif'],['id'=>'expired','name'=>'Expiré'],['id'=>'cancelled','name'=>'Annulé'],['id'=>'pending','name'=>'En attente']]" wire:model.live="filterStatus" class="select-sm" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <x-stat title="Actifs" :value="$stats['active']" icon="o-check-circle" color="text-success" />
        <x-stat title="Expirés" :value="$stats['expired']" icon="o-clock" color="text-warning" />
        <x-stat title="Annulés" :value="$stats['cancelled']" icon="o-x-circle" color="text-error" />
    </div>

    <x-card>
        <x-table :headers="[['key'=>'user','label'=>'Utilisateur'],['key'=>'plan','label'=>'Plan'],['key'=>'starts_at','label'=>'Début'],['key'=>'ends_at','label'=>'Fin'],['key'=>'status','label'=>'Statut'],['key'=>'actions','label'=>'']]" :rows="$subscriptions">
            @scope('cell_user', $sub)
                <div class="flex items-center gap-2">
                    <x-avatar :image="$sub->user->avatar_url ?? ''" class="w-7 h-7" />
                    <div>
                        <div class="font-medium text-sm">{{ $sub->user->name }}</div>
                        <div class="text-xs opacity-50">{{ $sub->user->email }}</div>
                    </div>
                </div>
            @endscope
            @scope('cell_plan', $sub)
                <x-badge :value="$sub->plan->name" class="badge-primary" />
            @endscope
            @scope('cell_starts_at', $sub)
                {{ \Carbon\Carbon::parse($sub->starts_at)->format('d/m/Y') }}
            @endscope
            @scope('cell_ends_at', $sub)
                {{ $sub->ends_at ? \Carbon\Carbon::parse($sub->ends_at)->format('d/m/Y') : '∞' }}
            @endscope
            @scope('cell_status', $sub)
                @php $colors = ['active'=>'badge-success','expired'=>'badge-warning','cancelled'=>'badge-error','pending'=>'badge-ghost']; @endphp
                <x-badge :value="$sub->status" class="{{ $colors[$sub->status] ?? 'badge-ghost' }}" />
            @endscope
            @scope('actions', $sub)
                @if($sub->status === 'active')
                    <x-button icon="o-x-circle" wire:click="cancel({{ $sub->id }})" wire:confirm="Annuler ?" class="btn-ghost btn-xs text-error" />
                @endif
            @endscope
        </x-table>
        {{ $subscriptions->links() }}
    </x-card>
</div>
