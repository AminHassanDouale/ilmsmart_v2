<?php
use App\Models\Level;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Niveaux')]
class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $modal = false;
    public ?int $editId = null;
    public string $name = '';
    public string $name_ar = '';
    public string $name_fr = '';
    public string $name_en = '';
    public int $order = 0;

    public function with(): array {
        return [
            'levels' => Level::when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('order')->paginate(15),
        ];
    }

    public function openCreate(): void {
        $this->reset(['editId','name','name_ar','name_fr','name_en','order']);
        $this->modal = true;
    }

    public function openEdit(Level $level): void {
        $this->editId = $level->id;
        $this->name = $level->name;
        $this->name_ar = $level->name_ar ?? '';
        $this->name_fr = $level->name_fr ?? '';
        $this->name_en = $level->name_en ?? '';
        $this->order = $level->order;
        $this->modal = true;
    }

    public function save(): void {
        $this->validate([
            'name'    => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'name_fr' => 'nullable|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'order'   => 'integer|min:0',
        ]);
        $data = ['name'=>$this->name,'name_ar'=>$this->name_ar,'name_fr'=>$this->name_fr,'name_en'=>$this->name_en,'order'=>$this->order];
        $this->editId ? Level::find($this->editId)->update($data) : Level::create($data);
        $this->modal = false;
        $this->toast($this->editId ? 'Niveau mis à jour' : 'Niveau créé', type: 'success');
    }

    public function delete(Level $level): void {
        $level->delete();
        $this->toast('Niveau supprimé', type: 'success');
    }
} ?>

<div>
    <x-header title="Niveaux d'enseignement" subtitle="Gérer les niveaux scolaires" separator>
        <x-slot:actions>
            <x-input placeholder="Rechercher..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
            <x-button label="Nouveau niveau" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[['key'=>'name','label'=>'Nom'],['key'=>'name_ar','label'=>'Arabe'],['key'=>'name_fr','label'=>'Français'],['key'=>'order','label'=>'Ordre'],['key'=>'actions','label'=>'']]" :rows="$levels">
            @scope('cell_name', $level)
                <div class="font-semibold">{{ $level->name }}</div>
            @endscope
            @scope('cell_order', $level)
                <x-badge :value="$level->order" class="badge-ghost" />
            @endscope
            @scope('actions', $level)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" wire:click="openEdit({{ $level->id }})" class="btn-ghost btn-xs" />
                    <x-button icon="o-trash" wire:click="delete({{ $level->id }})" wire:confirm="Supprimer ce niveau ?" class="btn-ghost btn-xs text-error" />
                </div>
            @endscope
        </x-table>
        {{ $levels->links() }}
    </x-card>

    <x-modal wire:model="modal" title="{{ $editId ? 'Modifier le niveau' : 'Nouveau niveau' }}">
        <div class="grid grid-cols-2 gap-4">
            <x-input label="Nom *" wire:model="name" placeholder="ex: Primaire" class="col-span-2" />
            <x-input label="Nom (Arabe)" wire:model="name_ar" placeholder="ابتدائي" dir="rtl" />
            <x-input label="Nom (Français)" wire:model="name_fr" placeholder="Primaire" />
            <x-input label="Nom (Anglais)" wire:model="name_en" placeholder="Primary" />
            <x-input label="Ordre" wire:model="order" type="number" min="0" />
        </div>
        <x-slot:actions>
            <x-button label="Annuler" wire:click="$set('modal', false)" />
            <x-button label="Enregistrer" wire:click="save" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
