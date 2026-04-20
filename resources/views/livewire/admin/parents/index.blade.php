<?php
use App\Models\{ParentModel, User};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Parents')]
class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array {
        return [
            'parents' => ParentModel::with(['user','children.user'])
                ->when($this->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name','like',"%{$this->search}%")->orWhere('email','like',"%{$this->search}%")))
                ->latest()->paginate(15),
        ];
    }
} ?>

<div>
    <x-header title="Parents" subtitle="Liste des parents inscrits" separator>
        <x-slot:actions>
            <x-input placeholder="Rechercher..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[['key'=>'user','label'=>'Parent'],['key'=>'children','label'=>'Enfants'],['key'=>'phone','label'=>'Téléphone'],['key'=>'created_at','label'=>'Inscrit le']]" :rows="$parents">
            @scope('cell_user', $parent)
                <div class="flex items-center gap-3">
                    <x-avatar :image="$parent->user->avatar_url ?? ''" class="w-9 h-9" />
                    <div>
                        <div class="font-semibold">{{ $parent->user->full_name }}</div>
                        <div class="text-xs opacity-50">{{ $parent->user->email }}</div>
                    </div>
                </div>
            @endscope
            @scope('cell_children', $parent)
                <div class="flex flex-wrap gap-1">
                    @forelse($parent->children as $child)
                        <x-badge :value="$child->user->full_name" class="badge-ghost badge-sm" />
                    @empty
                        <span class="text-xs opacity-40">Aucun enfant</span>
                    @endforelse
                </div>
            @endscope
            @scope('cell_phone', $parent)
                {{ $parent->phone ?? '—' }}
            @endscope
            @scope('cell_created_at', $parent)
                {{ $parent->created_at->format('d/m/Y') }}
            @endscope
        </x-table>
        {{ $parents->links() }}
    </x-card>
</div>
