<?php
use App\Models\{Course, Module, Lesson};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
#[Title('Gérer le cours')]
class extends Component {
    use Toast;

    public Course $course;

    // Module modal
    public bool    $moduleModal    = false;
    public ?Module $editingModule  = null;
    public string  $mod_title      = '';
    public string  $mod_title_fr   = '';
    public string  $mod_title_ar   = '';
    public string  $mod_title_en   = '';
    public string  $mod_description = '';
    public int     $mod_order       = 0;

    // Lesson modal
    public bool    $lessonModal     = false;
    public ?Lesson $editingLesson   = null;
    public ?int    $lessonModuleId  = null;
    public string  $les_title       = '';
    public string  $les_title_fr    = '';
    public string  $les_title_ar    = '';
    public string  $les_title_en    = '';
    public string  $les_type        = 'text';
    public string  $les_video_url   = '';
    public string  $les_video_provider = 'youtube';
    public string  $les_content     = '';
    public int     $les_duration    = 0;
    public bool    $les_free_preview = false;
    public string  $les_status      = 'published';
    public int     $les_order       = 0;

    public function mount(Course $course): void
    {
        $this->course = $course->load(['modules.lessons', 'teacher.user', 'subject']);
    }

    public function with(): array
    {
        $this->course->load(['modules.lessons', 'teacher.user', 'subject']);
        return [
            'lessonTypes' => [
                ['id' => 'text',       'name' => 'Texte'],
                ['id' => 'video',      'name' => 'Vidéo'],
                ['id' => 'document',   'name' => 'Document'],
                ['id' => 'quiz',       'name' => 'Quiz'],
                ['id' => 'assignment', 'name' => 'Devoir'],
                ['id' => 'live',       'name' => 'Cours en direct'],
            ],
            'videoProviders' => [
                ['id' => 'youtube', 'name' => 'YouTube'],
                ['id' => 'vimeo',   'name' => 'Vimeo'],
                ['id' => 'other',   'name' => 'Autre URL'],
            ],
            'statusOptions' => [
                ['id' => 'published', 'name' => 'Publié'],
                ['id' => 'draft',     'name' => 'Brouillon'],
            ],
        ];
    }

    // ─── MODULE CRUD ────────────────────────────────────────────────────

    public function createModule(): void
    {
        $this->reset(['mod_title','mod_title_fr','mod_title_ar','mod_title_en','mod_description']);
        $this->mod_order     = $this->course->modules->count() + 1;
        $this->editingModule = null;
        $this->moduleModal   = true;
    }

    public function editModule(Module $module): void
    {
        $this->editingModule   = $module;
        $this->mod_title       = $module->title       ?? '';
        $this->mod_title_fr    = $module->title_fr    ?? '';
        $this->mod_title_ar    = $module->title_ar    ?? '';
        $this->mod_title_en    = $module->title_en    ?? '';
        $this->mod_description = $module->description ?? '';
        $this->mod_order       = $module->order;
        $this->moduleModal     = true;
    }

    public function saveModule(): void
    {
        $this->validate([
            'mod_title' => 'required|string|max:255',
            'mod_order' => 'integer|min:1',
        ]);

        $data = [
            'course_id'   => $this->course->id,
            'title'       => $this->mod_title,
            'title_fr'    => $this->mod_title_fr    ?: $this->mod_title,
            'title_ar'    => $this->mod_title_ar,
            'title_en'    => $this->mod_title_en,
            'description' => $this->mod_description,
            'order'       => $this->mod_order,
        ];

        if ($this->editingModule) {
            $this->editingModule->update($data);
            $this->success('Module mis à jour !');
        } else {
            Module::create($data);
            $this->success('Module ajouté !');
        }

        $this->moduleModal = false;
    }

    public function deleteModule(Module $module): void
    {
        $module->lessons()->delete();
        $module->delete();
        $this->success('Module supprimé.');
    }

    // ─── LESSON CRUD ────────────────────────────────────────────────────

    public function createLesson(int $moduleId): void
    {
        $this->reset(['les_title','les_title_fr','les_title_ar','les_title_en',
                      'les_video_url','les_content']);
        $this->lessonModuleId     = $moduleId;
        $this->les_type           = 'text';
        $this->les_video_provider = 'youtube';
        $this->les_duration       = 0;
        $this->les_free_preview   = false;
        $this->les_status         = 'published';
        $module = Module::find($moduleId);
        $this->les_order          = $module ? $module->lessons->count() + 1 : 1;
        $this->editingLesson      = null;
        $this->lessonModal        = true;
    }

    public function editLesson(Lesson $lesson): void
    {
        $this->editingLesson      = $lesson;
        $this->lessonModuleId     = $lesson->module_id;
        $this->les_title          = $lesson->title          ?? '';
        $this->les_title_fr       = $lesson->title_fr       ?? '';
        $this->les_title_ar       = $lesson->title_ar       ?? '';
        $this->les_title_en       = $lesson->title_en       ?? '';
        $this->les_type           = $lesson->type;
        $this->les_video_url      = $lesson->video_url      ?? '';
        $this->les_video_provider = $lesson->video_provider ?? 'youtube';
        $this->les_content        = $lesson->content        ?? '';
        $this->les_duration       = $lesson->duration_minutes;
        $this->les_free_preview   = $lesson->is_free_preview;
        $this->les_status         = $lesson->status;
        $this->les_order          = $lesson->order;
        $this->lessonModal        = true;
    }

    public function saveLesson(): void
    {
        $this->validate([
            'les_title'       => 'required|string|max:255',
            'lessonModuleId'  => 'required|exists:modules,id',
            'les_type'        => 'required|in:text,video,document,quiz,assignment,live',
            'les_duration'    => 'integer|min:0',
        ]);

        $data = [
            'module_id'      => $this->lessonModuleId,
            'title'          => $this->les_title,
            'title_fr'       => $this->les_title_fr    ?: $this->les_title,
            'title_ar'       => $this->les_title_ar,
            'title_en'       => $this->les_title_en,
            'type'           => $this->les_type,
            'video_url'      => $this->les_video_url,
            'video_provider' => $this->les_video_provider,
            'content'        => $this->les_content,
            'duration_minutes' => $this->les_duration,
            'is_free_preview'  => $this->les_free_preview,
            'status'           => $this->les_status,
            'order'            => $this->les_order,
        ];

        if ($this->editingLesson) {
            $this->editingLesson->update($data);
            $this->success('Leçon mise à jour !');
        } else {
            Lesson::create($data);
            $this->success('Leçon ajoutée !');
        }

        $this->lessonModal = false;
    }

    public function deleteLesson(Lesson $lesson): void
    {
        $lesson->delete();
        $this->success('Leçon supprimée.');
    }

    public function publishCourse(): void
    {
        $this->course->update(['status' => 'published']);
        $this->success('Cours publié !');
    }
}; ?>

<div>
    {{-- Header --}}
    <x-header separator>
        <x-slot:title>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.courses') }}" class="btn btn-ghost btn-sm btn-circle">
                    <x-icon name="o-arrow-left" class="w-5 h-5" />
                </a>
                <div>
                    <div class="text-lg font-bold">{{ $course->title_fr ?: $course->title }}</div>
                    @if($course->title_ar)
                        <div class="text-sm text-base-content/50 font-arabic" dir="rtl">{{ $course->title_ar }}</div>
                    @endif
                </div>
            </div>
        </x-slot:title>
        <x-slot:actions>
            @if($course->status !== 'published')
                <x-button label="Publier" icon="o-globe-alt" wire:click="publishCourse" class="btn-success btn-sm" />
            @endif
            <a href="{{ route('admin.courses') }}" class="btn btn-ghost btn-sm">Retour à la liste</a>
        </x-slot:actions>
    </x-header>

    {{-- Course summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat title="Modules" :value="$course->modules->count()" icon="o-folder" color="text-primary" />
        <x-stat title="Leçons" :value="$course->modules->sum(fn($m) => $m->lessons->count())" icon="o-book-open" color="text-secondary" />
        <x-stat title="Statut" :value="ucfirst($course->status)" icon="o-signal" color="text-info" />
        <x-stat title="Durée" :value="$course->duration_hours . 'h'" icon="o-clock" color="text-warning" />
    </div>

    {{-- Info bar --}}
    <div class="flex flex-wrap gap-3 mb-6">
        @if($course->subject)
            <x-badge :value="'Matière: ' . ($course->subject->name_fr ?: $course->subject->name)" class="badge-outline" />
        @endif
        @if($course->teacher)
            <x-badge :value="'Enseignant: ' . ($course->teacher->user->full_name ?? '?')" class="badge-outline" />
        @endif
        @php
            $tc = ['published' => 'badge-success', 'draft' => 'badge-warning', 'archived' => 'badge-ghost'];
        @endphp
        <x-badge :value="ucfirst($course->status)" class="{{ $tc[$course->status] ?? 'badge-ghost' }}" />
        @if($course->type === 'free')
            <x-badge value="Gratuit" class="badge-success" />
        @else
            <x-badge :value="number_format($course->price) . ' DZD'" class="badge-warning" />
        @endif
    </div>

    {{-- Modules & Lessons --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Modules & Leçons</h2>
        <x-button label="Ajouter un module" icon="o-plus" wire:click="createModule" class="btn-primary btn-sm" />
    </div>

    @forelse($course->modules as $module)
        <x-card shadow class="mb-4">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-primary text-primary-content flex items-center justify-center text-xs font-bold">
                            {{ $module->order }}
                        </div>
                        <h3 class="font-semibold">{{ $module->title_fr ?: $module->title }}</h3>
                        @if($module->title_ar)
                            <span class="text-sm text-base-content/50 font-arabic" dir="rtl">— {{ $module->title_ar }}</span>
                        @endif
                    </div>
                    @if($module->description)
                        <p class="text-sm text-base-content/60 mt-1 ms-9">{{ $module->description }}</p>
                    @endif
                </div>
                <div class="flex gap-1">
                    <x-button icon="o-plus" label="Leçon" wire:click="createLesson({{ $module->id }})"
                              class="btn-ghost btn-xs" />
                    <x-button icon="o-pencil-square" wire:click="editModule({{ $module->id }})"
                              class="btn-ghost btn-xs" />
                    <x-button icon="o-trash" wire:click="deleteModule({{ $module->id }})"
                              wire:confirm="Supprimer ce module et toutes ses leçons ?"
                              class="btn-ghost btn-xs text-error" />
                </div>
            </div>

            {{-- Lessons --}}
            @if($module->lessons->isNotEmpty())
                <div class="space-y-2 ms-4 border-l-2 border-base-300 ps-4">
                    @foreach($module->lessons as $lesson)
                        <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded bg-base-200 flex items-center justify-center">
                                    @php
                                        $icon = match($lesson->type) {
                                            'video'      => 'o-play-circle',
                                            'document'   => 'o-document-text',
                                            'quiz'       => 'o-clipboard-document-list',
                                            'assignment' => 'o-pencil-square',
                                            'live'       => 'o-video-camera',
                                            default      => 'o-book-open',
                                        };
                                    @endphp
                                    <x-icon :name="$icon" class="w-3.5 h-3.5 text-base-content/60" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium">
                                        <span class="text-base-content/40 text-xs me-1">{{ $lesson->order }}.</span>
                                        {{ $lesson->title_fr ?: $lesson->title }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <x-badge :value="$lesson->type" class="badge-ghost badge-xs" />
                                        @if($lesson->duration_minutes)
                                            <span class="text-xs text-base-content/40">{{ $lesson->duration_minutes }} min</span>
                                        @endif
                                        @if($lesson->is_free_preview)
                                            <x-badge value="Aperçu gratuit" class="badge-success badge-xs" />
                                        @endif
                                        @if($lesson->status === 'draft')
                                            <x-badge value="Brouillon" class="badge-warning badge-xs" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <x-button icon="o-pencil-square" wire:click="editLesson({{ $lesson->id }})"
                                          class="btn-ghost btn-xs" />
                                <x-button icon="o-trash" wire:click="deleteLesson({{ $lesson->id }})"
                                          wire:confirm="Supprimer cette leçon ?"
                                          class="btn-ghost btn-xs text-error" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ms-9 text-sm text-base-content/40 italic">
                    Aucune leçon — <button wire:click="createLesson({{ $module->id }})"
                                          class="link link-primary">ajouter la première</button>
                </div>
            @endif
        </x-card>
    @empty
        <x-card shadow>
            <div class="text-center py-12">
                <x-icon name="o-folder-open" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
                <p class="text-base-content/50 mb-4">Ce cours n'a pas encore de modules.</p>
                <x-button label="Créer le premier module" icon="o-plus"
                          wire:click="createModule" class="btn-primary" />
            </div>
        </x-card>
    @endforelse

    {{-- Module Modal --}}
    <x-modal wire:model="moduleModal"
             :title="$editingModule ? 'Modifier le module' : 'Nouveau module'"
             class="backdrop-blur max-w-xl">
        <x-form wire:submit="saveModule">
            <x-input label="Titre principal *" wire:model="mod_title" placeholder="ex: Introduction" required />
            <div class="grid grid-cols-3 gap-3">
                <x-input label="FR" wire:model="mod_title_fr" placeholder="Français" />
                <x-input label="AR" wire:model="mod_title_ar" placeholder="عربي" />
                <x-input label="EN" wire:model="mod_title_en" placeholder="English" />
            </div>
            <x-textarea label="Description" wire:model="mod_description" rows="2"
                        placeholder="Description optionnelle du module..." />
            <x-input label="Ordre" wire:model="mod_order" type="number" min="1" />
            <x-slot:actions>
                <x-button label="Annuler" @click="$wire.moduleModal = false" />
                <x-button :label="$editingModule ? 'Mettre à jour' : 'Créer'"
                          class="btn-primary" type="submit" spinner="saveModule" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Lesson Modal --}}
    <x-modal wire:model="lessonModal"
             :title="$editingLesson ? 'Modifier la leçon' : 'Nouvelle leçon'"
             class="backdrop-blur max-w-2xl">
        <x-form wire:submit="saveLesson">

            <x-input label="Titre principal *" wire:model="les_title" placeholder="ex: Introduction à l'algèbre" required />
            <div class="grid grid-cols-3 gap-3">
                <x-input label="FR" wire:model="les_title_fr" placeholder="Français" />
                <x-input label="AR" wire:model="les_title_ar" placeholder="عربي" />
                <x-input label="EN" wire:model="les_title_en" placeholder="English" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-select label="Type de leçon *" wire:model.live="les_type" :options="$lessonTypes" required />
                <x-input label="Durée (minutes)" wire:model="les_duration" type="number" min="0" />
            </div>

            @if(in_array($les_type, ['video', 'live']))
                <div class="grid grid-cols-2 gap-3">
                    <x-select label="Fournisseur vidéo" wire:model="les_video_provider" :options="$videoProviders" />
                    <x-input label="URL vidéo" wire:model="les_video_url"
                             placeholder="https://youtube.com/watch?v=..." icon="o-link" />
                </div>
                @if($les_video_url && str_contains($les_video_url, 'youtube'))
                    @php
                        preg_match('/[?&]v=([^&]+)/', $les_video_url, $m);
                        $ytId = $m[1] ?? null;
                    @endphp
                    @if($ytId)
                        <div class="rounded-lg overflow-hidden aspect-video">
                            <iframe src="https://www.youtube.com/embed/{{ $ytId }}"
                                    class="w-full h-full" frameborder="0" allowfullscreen></iframe>
                        </div>
                    @endif
                @endif
            @endif

            <x-textarea label="Contenu / Description" wire:model="les_content" rows="4"
                        placeholder="Contenu textuel de la leçon, description, instructions..." />

            <div class="grid grid-cols-2 gap-3">
                <x-select label="Statut" wire:model="les_status" :options="$statusOptions" />
                <x-input label="Ordre" wire:model="les_order" type="number" min="1" />
            </div>

            <x-toggle label="Aperçu gratuit (visible sans inscription)" wire:model="les_free_preview" right />

            <x-slot:actions>
                <x-button label="Annuler" @click="$wire.lessonModal = false" />
                <x-button :label="$editingLesson ? 'Mettre à jour' : 'Ajouter la leçon'"
                          class="btn-primary" type="submit" spinner="saveLesson" />
            </x-slot:actions>
        </x-form>
    </x-modal>

</div>
