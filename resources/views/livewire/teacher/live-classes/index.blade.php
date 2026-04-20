<?php
use App\Models\{LiveClass, Course};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Classes en direct')]
class extends Component {

    public bool $modal = false;
    public ?int $editId = null;
    public string $title = '';
    public string $description = '';
    public string $course_id = '';
    public string $start_time = '';
    public string $end_time = '';
    public string $platform = 'zoom';
    public string $meeting_link = '';
    public string $meeting_id = '';
    public string $meeting_password = '';
    public int $max_students = 0;

    public function with(): array {
        $teacherId = auth()->user()->teacher?->id;
        return [
            'classes' => LiveClass::where('teacher_id', $teacherId)
                ->with('course')->orderByDesc('start_time')->get(),
            'courses' => Course::where('teacher_id', $teacherId)->get()
                ->map(fn($c) => ['id'=>$c->id,'name'=>$c->title]),
            'platforms' => [['id'=>'zoom','name'=>'Zoom'],['id'=>'meet','name'=>'Google Meet'],['id'=>'teams','name'=>'MS Teams'],['id'=>'jitsi','name'=>'Jitsi'],['id'=>'other','name'=>'Autre']],
        ];
    }

    public function openCreate(): void {
        $this->reset(['editId','title','description','course_id','start_time','end_time','meeting_link','meeting_id','meeting_password','max_students']);
        $this->platform = 'zoom';
        $this->modal = true;
    }

    public function openEdit(LiveClass $class): void {
        $this->editId           = $class->id;
        $this->title            = $class->title;
        $this->description      = $class->description ?? '';
        $this->course_id        = $class->course_id ?? '';
        $this->start_time       = $class->start_time->format('Y-m-d\TH:i');
        $this->end_time         = $class->end_time->format('Y-m-d\TH:i');
        $this->platform         = $class->platform;
        $this->meeting_link     = $class->meeting_link ?? '';
        $this->meeting_id       = $class->meeting_id ?? '';
        $this->meeting_password = $class->meeting_password ?? '';
        $this->max_students     = $class->max_students ?? 0;
        $this->modal            = true;
    }

    public function save(): void {
        $this->validate(['title'=>'required','start_time'=>'required|date','end_time'=>'required|date|after:start_time','platform'=>'required']);
        $data = ['title'=>$this->title,'description'=>$this->description,'course_id'=>$this->course_id ?: null,
                 'teacher_id'=>auth()->user()->teacher->id,'start_time'=>$this->start_time,'end_time'=>$this->end_time,
                 'platform'=>$this->platform,'meeting_link'=>$this->meeting_link,'meeting_id'=>$this->meeting_id,
                 'meeting_password'=>$this->meeting_password,'max_students'=>$this->max_students ?: null];
        $this->editId ? LiveClass::find($this->editId)->update($data) : LiveClass::create($data);
        $this->modal = false;
        $this->toast($this->editId ? 'Classe mise à jour' : 'Classe créée', type: 'success');
    }

    public function delete(LiveClass $class): void {
        $class->delete();
        $this->toast('Supprimée', type: 'success');
    }
} ?>

<div>
    <x-header title="Classes en direct" subtitle="Planifier et gérer vos sessions en direct" separator>
        <x-slot:actions>
            <x-button label="Nouvelle session" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="space-y-3">
        @forelse($classes as $class)
        <x-card>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <x-icon name="o-video-camera" class="w-6 h-6 text-primary" />
                    </div>
                    <div>
                        <div class="font-semibold">{{ $class->title }}</div>
                        <div class="text-sm opacity-60">{{ $class->start_time->format('d/m/Y H:i') }} — {{ $class->end_time->format('H:i') }}</div>
                        @if($class->course) <div class="text-xs opacity-40">{{ $class->course->title }}</div> @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @php $colors = ['scheduled'=>'badge-info','live'=>'badge-error','ended'=>'badge-success','cancelled'=>'badge-ghost']; @endphp
                    <x-badge :value="$class->status" class="{{ $colors[$class->status] ?? 'badge-ghost' }}" />
                    @if($class->meeting_link)
                        <x-button label="Rejoindre" icon="o-arrow-top-right-on-square" link="{{ $class->meeting_link }}" external class="btn-primary btn-sm" />
                    @endif
                    <x-button icon="o-pencil" wire:click="openEdit({{ $class->id }})" class="btn-ghost btn-sm" />
                    <x-button icon="o-trash" wire:click="delete({{ $class->id }})" wire:confirm="Supprimer ?" class="btn-ghost btn-sm text-error" />
                </div>
            </div>
        </x-card>
        @empty
        <x-card>
            <div class="text-center py-12 text-base-content/40">
                <x-icon name="o-video-camera" class="w-12 h-12 mx-auto mb-3" />
                <p>Aucune classe planifiée</p>
            </div>
        </x-card>
        @endforelse
    </div>

    <x-modal wire:model="modal" title="{{ $editId ? 'Modifier la session' : 'Nouvelle session en direct' }}" box-class="max-w-2xl">
        <div class="grid grid-cols-2 gap-4">
            <x-input label="Titre *" wire:model="title" class="col-span-2" />
            <x-select label="Cours (optionnel)" :options="$courses" wire:model="course_id" placeholder="Aucun cours" class="col-span-2" />
            <x-input label="Début *" wire:model="start_time" type="datetime-local" />
            <x-input label="Fin *" wire:model="end_time" type="datetime-local" />
            <x-select label="Plateforme *" :options="$platforms" wire:model="platform" />
            <x-input label="Max étudiants" wire:model="max_students" type="number" min="0" placeholder="0 = illimité" />
            <x-input label="Lien de réunion" wire:model="meeting_link" class="col-span-2" placeholder="https://..." />
            <x-input label="ID réunion" wire:model="meeting_id" />
            <x-input label="Mot de passe" wire:model="meeting_password" />
            <x-textarea label="Description" wire:model="description" class="col-span-2" rows="3" />
        </div>
        <x-slot:actions>
            <x-button label="Annuler" wire:click="$set('modal', false)" />
            <x-button label="Enregistrer" wire:click="save" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
