<?php
use App\Models\{Grade, Level};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Classes')]
class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterLevel = '';
    public bool $modal = false;
    public ?int $editId = null;
    public string $name = '';
    public string $name_ar = '';
    public string $name_fr = '';
    public string $name_en = '';
    public string $level_id = '';
    public int $order = 0;

    public function with(): array {
        return [
            'grades' => Grade::with('level')
                ->when($this->search, fn($q) => $q->where('name','like',"%{$this->search}%"))
                ->when($this->filterLevel, fn($q) => $q->where('level_id', $this->filterLevel))
                ->orderBy('order')->paginate(15),
            'levels' => Level::orderBy('order')->get()->map(fn($l) => ['id'=>$l->id,'name'=>$l->name]),
        ];
    }

    public function openCreate(): void {
        $this->reset(['editId','name','name_ar','name_fr','name_en','level_id','order']);
        $this->modal = true;
    }

    public function openEdit(Grade $grade): void {
        $this->editId   = $grade->id;
        $this->name     = $grade->name;
        $this->name_ar  = $grade->name_ar ?? '';
        $this->name_fr  = $grade->name_fr ?? '';
        $this->name_en  = $grade->name_en ?? '';
        $this->level_id = $grade->level_id;
        $this->order    = $grade->order;
        $this->modal    = true;
    }

    public function save(): void {
        $this->validate(['name'=>'required','level_id'=>'required|exists:levels,id','order'=>'integer|min:0']);
        $data = ['name'=>$this->name,'name_ar'=>$this->name_ar,'name_fr'=>$this->name_fr,'name_en'=>$this->name_en,'level_id'=>$this->level_id,'order'=>$this->order];
        $this->editId ? Grade::find($this->editId)->update($data) : Grade::create($data);
        $this->modal = false;
        $this->toast($this->editId ? 'Classe mise à jour' : 'Classe créée', type: 'success');
    }

    public function delete(Grade $grade): void {
        $grade->delete();
        $this->toast('Classe supprimée', type: 'success');
    }
} ?>

<div>
    <x-header title="Classes" subtitle="Gérer les classes par niveau" separator>
        <x-slot:actions>
            <x-input placeholder="Rechercher..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
            <x-select placeholder="Tous les niveaux" :options="$levels" wire:model.live="filterLevel" class="select-sm" />
            <x-button label="Nouvelle classe" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[['key'=>'name','label'=>'Classe'],['key'=>'level','label'=>'Niveau'],['key'=>'order','label'=>'Ordre'],['key'=>'actions','label'=>'']]" :rows="$grades">
            @scope('cell_name', $grade)
                <div class="font-semibold">{{ $grade->name }}</div>
                @if($grade->name_ar) <div class="text-xs text-base-content/50" dir="rtl">{{ $grade->name_ar }}</div> @endif
            @endscope
            @scope('cell_level', $grade)
                <x-badge :value="$grade->level->name" class="badge-primary badge-outline" />
            @endscope
            @scope('cell_order', $grade)
                <x-badge :value="$grade->order" class="badge-ghost" />
            @endscope
            @scope('actions', $grade)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" wire:click="openEdit({{ $grade->id }})" class="btn-ghost btn-xs" />
                    <x-button icon="o-trash" wire:click="delete({{ $grade->id }})" wire:confirm="Supprimer ?" class="btn-ghost btn-xs text-error" />
                </div>
            @endscope
        </x-table>
        {{ $grades->links() }}
    </x-card>

    <x-modal wire:model="modal" title="{{ $editId ? 'Modifier la classe' : 'Nouvelle classe' }}">
        <div class="grid grid-cols-2 gap-4">
            <x-select label="Niveau *" :options="$levels" wire:model="level_id" class="col-span-2" />
            <x-input label="Nom *" wire:model="name" placeholder="ex: 1ère année" class="col-span-2" />
            <x-input label="Nom (Arabe)" wire:model="name_ar" placeholder="السنة الأولى" dir="rtl" />
            <x-input label="Nom (Français)" wire:model="name_fr" placeholder="1ère année" />
            <x-input label="Nom (Anglais)" wire:model="name_en" placeholder="1st grade" />
            <x-input label="Ordre" wire:model="order" type="number" min="0" />
        </div>
        <x-slot:actions>
            <x-button label="Annuler" wire:click="$set('modal', false)" />
            <x-button label="Enregistrer" wire:click="save" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
