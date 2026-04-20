<?php
use App\Models\AcademicYear;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Années scolaires')]
class extends Component {

    public bool $modal = false;
    public ?int $editId = null;
    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_current = false;

    public function with(): array {
        return ['years' => AcademicYear::orderByDesc('start_date')->get()];
    }

    public function openCreate(): void {
        $this->reset(['editId','name','start_date','end_date','is_current']);
        $this->modal = true;
    }

    public function openEdit(AcademicYear $year): void {
        $this->editId     = $year->id;
        $this->name       = $year->name;
        $this->start_date = $year->start_date->format('Y-m-d');
        $this->end_date   = $year->end_date->format('Y-m-d');
        $this->is_current = $year->is_current;
        $this->modal      = true;
    }

    public function save(): void {
        $this->validate(['name'=>'required','start_date'=>'required|date','end_date'=>'required|date|after:start_date']);
        if ($this->is_current) AcademicYear::where('is_current', true)->update(['is_current' => false]);
        $data = ['name'=>$this->name,'start_date'=>$this->start_date,'end_date'=>$this->end_date,'is_current'=>$this->is_current];
        $this->editId ? AcademicYear::find($this->editId)->update($data) : AcademicYear::create($data);
        $this->modal = false;
        $this->toast('Année scolaire enregistrée', type: 'success');
    }

    public function setCurrent(AcademicYear $year): void {
        AcademicYear::where('is_current', true)->update(['is_current' => false]);
        $year->update(['is_current' => true]);
        $this->toast("Année {$year->name} définie comme courante", type: 'success');
    }

    public function delete(AcademicYear $year): void {
        $year->delete();
        $this->toast('Supprimée', type: 'success');
    }
} ?>

<div>
    <x-header title="Années scolaires" subtitle="Gérer les années scolaires" separator>
        <x-slot:actions>
            <x-button label="Nouvelle année" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($years as $year)
        <x-card class="{{ $year->is_current ? 'border-2 border-primary' : '' }}">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-lg font-bold">{{ $year->name }}</div>
                    <div class="text-sm text-base-content/60 mt-1">
                        {{ $year->start_date->format('d/m/Y') }} → {{ $year->end_date->format('d/m/Y') }}
                    </div>
                    @if($year->is_current)
                        <x-badge value="Année courante" class="badge-primary mt-2" />
                    @endif
                </div>
                <div class="flex gap-1">
                    @if(!$year->is_current)
                        <x-button icon="o-check-circle" wire:click="setCurrent({{ $year->id }})" class="btn-ghost btn-xs text-success" tooltip="Définir comme courante" />
                    @endif
                    <x-button icon="o-pencil" wire:click="openEdit({{ $year->id }})" class="btn-ghost btn-xs" />
                    <x-button icon="o-trash" wire:click="delete({{ $year->id }})" wire:confirm="Supprimer ?" class="btn-ghost btn-xs text-error" />
                </div>
            </div>
        </x-card>
        @endforeach
    </div>

    <x-modal wire:model="modal" title="{{ $editId ? 'Modifier' : 'Nouvelle année scolaire' }}">
        <div class="space-y-4">
            <x-input label="Nom *" wire:model="name" placeholder="ex: 2025-2026" />
            <x-datepicker label="Date de début *" wire:model="start_date" />
            <x-datepicker label="Date de fin *" wire:model="end_date" />
            <x-toggle label="Année courante" wire:model="is_current" />
        </div>
        <x-slot:actions>
            <x-button label="Annuler" wire:click="$set('modal', false)" />
            <x-button label="Enregistrer" wire:click="save" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
