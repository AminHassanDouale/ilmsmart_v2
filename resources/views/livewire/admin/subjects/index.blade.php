<?php
use App\Models\{Subject, Grade, Level};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Matières')]
class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterGrade = '';
    public bool $modal = false;
    public ?int $editId = null;
    public string $name = '';
    public string $name_ar = '';
    public string $name_fr = '';
    public string $name_en = '';
    public string $grade_id = '';
    public string $icon = '';
    public string $color = '#6366f1';
    public int $coefficient = 1;

    public function with(): array {
        return [
            'subjects' => Subject::with('grade.level')
                ->when($this->search, fn($q) => $q->where('name','like',"%{$this->search}%"))
                ->when($this->filterGrade, fn($q) => $q->where('grade_id', $this->filterGrade))
                ->orderBy('order')->paginate(20),
            'grades' => Grade::with('level')->orderBy('order')->get()
                ->map(fn($g) => ['id'=>$g->id,'name'=>$g->level->name.' - '.$g->name]),
        ];
    }

    public function openCreate(): void {
        $this->reset(['editId','name','name_ar','name_fr','name_en','grade_id','icon','coefficient']);
        $this->color = '#6366f1';
        $this->modal = true;
    }

    public function openEdit(Subject $subject): void {
        $this->editId      = $subject->id;
        $this->name        = $subject->name;
        $this->name_ar     = $subject->name_ar ?? '';
        $this->name_fr     = $subject->name_fr ?? '';
        $this->name_en     = $subject->name_en ?? '';
        $this->grade_id    = $subject->grade_id;
        $this->icon        = $subject->icon ?? '';
        $this->color       = $subject->color ?? '#6366f1';
        $this->coefficient = $subject->coefficient;
        $this->modal       = true;
    }

    public function save(): void {
        $this->validate(['name'=>'required','grade_id'=>'required|exists:grades,id','coefficient'=>'integer|min:1']);
        $data = ['name'=>$this->name,'name_ar'=>$this->name_ar,'name_fr'=>$this->name_fr,'name_en'=>$this->name_en,
                 'grade_id'=>$this->grade_id,'icon'=>$this->icon,'color'=>$this->color,'coefficient'=>$this->coefficient];
        $this->editId ? Subject::find($this->editId)->update($data) : Subject::create($data);
        $this->modal = false;
        $this->toast($this->editId ? 'Matière mise à jour' : 'Matière créée', type: 'success');
    }

    public function delete(Subject $subject): void {
        $subject->delete();
        $this->toast('Matière supprimée', type: 'success');
    }
} ?>

<div>
    <x-header title="Matières" subtitle="Gérer les matières par classe" separator>
        <x-slot:actions>
            <x-input placeholder="Rechercher..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
            <x-select placeholder="Toutes les classes" :options="$grades" wire:model.live="filterGrade" class="select-sm" />
            <x-button label="Nouvelle matière" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[['key'=>'name','label'=>'Matière'],['key'=>'grade','label'=>'Classe'],['key'=>'coefficient','label'=>'Coeff.'],['key'=>'actions','label'=>'']]" :rows="$subjects">
            @scope('cell_name', $subject)
                <div class="flex items-center gap-2">
                    @if($subject->color)
                        <div class="w-3 h-3 rounded-full" style="background:{{ $subject->color }}"></div>
                    @endif
                    <div>
                        <div class="font-semibold">{{ $subject->name }}</div>
                        @if($subject->name_ar) <div class="text-xs opacity-60" dir="rtl">{{ $subject->name_ar }}</div> @endif
                    </div>
                </div>
            @endscope
            @scope('cell_grade', $subject)
                <div class="text-sm">
                    <div class="font-medium">{{ $subject->grade->name }}</div>
                    <div class="text-xs opacity-50">{{ $subject->grade->level->name }}</div>
                </div>
            @endscope
            @scope('cell_coefficient', $subject)
                <x-badge :value="$subject->coefficient" class="badge-secondary" />
            @endscope
            @scope('actions', $subject)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" wire:click="openEdit({{ $subject->id }})" class="btn-ghost btn-xs" />
                    <x-button icon="o-trash" wire:click="delete({{ $subject->id }})" wire:confirm="Supprimer ?" class="btn-ghost btn-xs text-error" />
                </div>
            @endscope
        </x-table>
        {{ $subjects->links() }}
    </x-card>

    <x-modal wire:model="modal" title="{{ $editId ? 'Modifier la matière' : 'Nouvelle matière' }}">
        <div class="grid grid-cols-2 gap-4">
            <x-select label="Classe *" :options="$grades" wire:model="grade_id" class="col-span-2" />
            <x-input label="Nom *" wire:model="name" placeholder="ex: Mathématiques" class="col-span-2" />
            <x-input label="Nom (Arabe)" wire:model="name_ar" placeholder="الرياضيات" dir="rtl" />
            <x-input label="Nom (Français)" wire:model="name_fr" placeholder="Mathématiques" />
            <x-input label="Nom (Anglais)" wire:model="name_en" placeholder="Mathematics" />
            <x-input label="Coefficient" wire:model="coefficient" type="number" min="1" />
        </div>
        <x-slot:actions>
            <x-button label="Annuler" wire:click="$set('modal', false)" />
            <x-button label="Enregistrer" wire:click="save" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
