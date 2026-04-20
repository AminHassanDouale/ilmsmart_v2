<?php
use App\Models\{Course, Subject, Teacher, AcademicYear};
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
#[Title('Gestion des cours')]
class extends Component {
    use WithPagination, Toast;

    public string $search       = '';
    public string $filterStatus = '';
    public string $filterType   = '';
    public bool   $modal        = false;
    public ?Course $editing     = null;

    // Course form fields
    public string $form_title          = '';
    public string $form_title_fr       = '';
    public string $form_title_ar       = '';
    public string $form_title_en       = '';
    public string $form_description    = '';
    public string $form_description_fr = '';
    public string $form_description_ar = '';
    public string $form_description_en = '';
    public ?int   $form_subject_id     = null;
    public ?int   $form_teacher_id     = null;
    public ?int   $form_academic_year_id = null;
    public string $form_status         = 'draft';
    public string $form_type           = 'free';
    public float  $form_price          = 0;
    public float  $form_duration_hours = 0;
    public string $form_thumbnail      = '';

    public function with(): array
    {
        return [
            'courses' => Course::with(['teacher.user', 'subject'])
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('title_fr', 'like', "%{$this->search}%")
                    ->orWhere('title_ar', 'like', "%{$this->search}%"))
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->filterType,   fn($q) => $q->where('type',   $this->filterType))
                ->withCount('enrollments')
                ->latest()
                ->paginate(15),

            'stats' => [
                'total'     => Course::count(),
                'published' => Course::where('status', 'published')->count(),
                'draft'     => Course::where('status', 'draft')->count(),
                'archived'  => Course::where('status', 'archived')->count(),
            ],

            'subjects'      => Subject::orderBy('name')->get(['id','name','name_fr','name_ar']),
            'teachers'      => Teacher::with('user')->get(),
            'academicYears' => AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get(['id','name','is_current']),

            'statusOptions' => [
                ['id' => 'draft',     'name' => 'Brouillon'],
                ['id' => 'published', 'name' => 'Publié'],
                ['id' => 'archived',  'name' => 'Archivé'],
            ],
            'typeOptions' => [
                ['id' => 'free',         'name' => 'Gratuit'],
                ['id' => 'paid',         'name' => 'Payant'],
                ['id' => 'subscription', 'name' => 'Abonnement'],
            ],

            'headers' => [
                ['key' => 'title',             'label' => 'Cours'],
                ['key' => 'subject',           'label' => 'Matière'],
                ['key' => 'teacher',           'label' => 'Enseignant'],
                ['key' => 'type',              'label' => 'Type'],
                ['key' => 'enrollments_count', 'label' => 'Inscrits'],
                ['key' => 'status',            'label' => 'Statut'],
                ['key' => 'actions',           'label' => '',         'class' => 'w-28'],
            ],
        ];
    }

    public function create(): void
    {
        $this->reset([
            'form_title','form_title_fr','form_title_ar','form_title_en',
            'form_description','form_description_fr','form_description_ar','form_description_en',
            'form_subject_id','form_teacher_id','form_academic_year_id',
            'form_thumbnail','form_price','form_duration_hours',
        ]);
        $this->form_status = 'draft';
        $this->form_type   = 'free';
        $this->editing     = null;
        $this->modal       = true;
    }

    public function edit(Course $course): void
    {
        $this->editing              = $course;
        $this->form_title           = $course->title          ?? '';
        $this->form_title_fr        = $course->title_fr       ?? '';
        $this->form_title_ar        = $course->title_ar       ?? '';
        $this->form_title_en        = $course->title_en       ?? '';
        $this->form_description     = $course->description    ?? '';
        $this->form_description_fr  = $course->description_fr ?? '';
        $this->form_description_ar  = $course->description_ar ?? '';
        $this->form_description_en  = $course->description_en ?? '';
        $this->form_subject_id      = $course->subject_id;
        $this->form_teacher_id      = $course->teacher_id;
        $this->form_academic_year_id = $course->academic_year_id;
        $this->form_status          = $course->status;
        $this->form_type            = $course->type;
        $this->form_price           = $course->price;
        $this->form_duration_hours  = $course->duration_hours;
        $this->form_thumbnail       = $course->thumbnail      ?? '';
        $this->modal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_title'       => 'required|string|max:255',
            'form_subject_id'  => 'required|exists:subjects,id',
            'form_teacher_id'  => 'required|exists:teachers,id',
            'form_status'      => 'required|in:draft,published,archived',
            'form_type'        => 'required|in:free,paid,subscription',
            'form_price'       => 'numeric|min:0',
            'form_duration_hours' => 'numeric|min:0',
        ]);

        $data = [
            'title'            => $this->form_title,
            'title_fr'         => $this->form_title_fr       ?: $this->form_title,
            'title_ar'         => $this->form_title_ar,
            'title_en'         => $this->form_title_en,
            'description'      => $this->form_description,
            'description_fr'   => $this->form_description_fr,
            'description_ar'   => $this->form_description_ar,
            'description_en'   => $this->form_description_en,
            'subject_id'       => $this->form_subject_id,
            'teacher_id'       => $this->form_teacher_id,
            'academic_year_id' => $this->form_academic_year_id,
            'status'           => $this->form_status,
            'type'             => $this->form_type,
            'price'            => $this->form_type === 'free' ? 0 : $this->form_price,
            'duration_hours'   => $this->form_duration_hours,
            'thumbnail'        => $this->form_thumbnail,
        ];

        if ($this->editing) {
            $this->editing->update($data);
            $this->success('Cours mis à jour !');
        } else {
            Course::create($data);
            $this->success('Cours créé !');
        }

        $this->modal = false;
    }

    public function toggleStatus(Course $course): void
    {
        $next = match($course->status) {
            'draft'     => 'published',
            'published' => 'archived',
            default     => 'draft',
        };
        $course->update(['status' => $next]);
        $this->success('Statut mis à jour → ' . $next);
    }

    public function delete(Course $course): void
    {
        $course->delete();
        $this->success('Cours supprimé.');
    }
}; ?>

<div>
    <x-header title="Gestion des cours" subtitle="Créer et organiser les cours avec modules et leçons" separator>
        <x-slot:actions>
            <x-button label="Nouveau cours" icon="o-plus" wire:click="create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat title="Total" :value="$stats['total']" icon="o-academic-cap" color="text-primary" />
        <x-stat title="Publiés" :value="$stats['published']" icon="o-check-circle" color="text-success" />
        <x-stat title="Brouillons" :value="$stats['draft']" icon="o-clock" color="text-warning" />
        <x-stat title="Archivés" :value="$stats['archived']" icon="o-archive-box" color="text-base-content/40" />
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-4">
        <x-input placeholder="Rechercher..." wire:model.live.debounce="search"
                 icon="o-magnifying-glass" clearable class="flex-1 min-w-48" />
        <x-select placeholder="Tous statuts" :options="[['id'=>'','name'=>'Tous statuts'],['id'=>'published','name'=>'Publié'],['id'=>'draft','name'=>'Brouillon'],['id'=>'archived','name'=>'Archivé']]"
                  wire:model.live="filterStatus" class="select-sm" />
        <x-select placeholder="Tous types" :options="[['id'=>'','name'=>'Tous types'],['id'=>'free','name'=>'Gratuit'],['id'=>'paid','name'=>'Payant'],['id'=>'subscription','name'=>'Abonnement']]"
                  wire:model.live="filterType" class="select-sm" />
    </div>

    {{-- Table --}}
    <x-card shadow>
        <x-table :headers="$headers" :rows="$courses" with-pagination striped>

            @scope('cell_title', $course)
                <div>
                    <div class="font-semibold text-sm">{{ $course->title_fr ?: $course->title }}</div>
                    @if($course->title_ar)
                        <div class="text-xs text-base-content/50 font-arabic" dir="rtl">{{ $course->title_ar }}</div>
                    @endif
                    <div class="text-xs text-base-content/40">{{ $course->duration_hours }}h</div>
                </div>
            @endscope

            @scope('cell_subject', $course)
                @if($course->subject)
                    <x-badge :value="$course->subject->name_fr ?: $course->subject->name" class="badge-outline badge-sm" />
                @endif
            @endscope

            @scope('cell_teacher', $course)
                @if($course->teacher)
                    <div class="flex items-center gap-2">
                        <x-avatar :image="$course->teacher->user->avatar_url ?? ''" class="w-7 h-7" />
                        <span class="text-sm">{{ $course->teacher->user->full_name ?? '—' }}</span>
                    </div>
                @else
                    <span class="text-base-content/30">—</span>
                @endif
            @endscope

            @scope('cell_type', $course)
                @php
                    $typeClass = match($course->type) {
                        'free'         => 'badge-success',
                        'paid'         => 'badge-warning',
                        'subscription' => 'badge-info',
                        default        => 'badge-ghost',
                    };
                    $typeLabel = match($course->type) {
                        'free'         => 'Gratuit',
                        'paid'         => number_format($course->price) . ' DZD',
                        'subscription' => 'Abonnement',
                        default        => $course->type,
                    };
                @endphp
                <x-badge :value="$typeLabel" class="{{ $typeClass }} badge-sm" />
            @endscope

            @scope('cell_enrollments_count', $course)
                <span class="font-semibold text-primary">{{ $course->enrollments_count }}</span>
            @endscope

            @scope('cell_status', $course)
                @php
                    $sc = ['published' => 'badge-success', 'draft' => 'badge-warning', 'archived' => 'badge-ghost'];
                    $sl = ['published' => 'Publié', 'draft' => 'Brouillon', 'archived' => 'Archivé'];
                @endphp
                <x-badge :value="$sl[$course->status] ?? $course->status"
                         class="{{ $sc[$course->status] ?? 'badge-ghost' }} badge-sm" />
            @endscope

            @scope('cell_actions', $course)
                <div class="flex gap-1">
                    <a href="{{ route('admin.courses.manage', $course->id) }}"
                       class="btn btn-ghost btn-xs" title="Gérer modules & leçons">
                        <x-icon name="o-squares-2x2" class="w-4 h-4" />
                    </a>
                    <x-button icon="o-pencil-square" wire:click="edit({{ $course->id }})"
                              class="btn-ghost btn-xs" tooltip="Modifier" />
                    <x-button icon="{{ $course->status === 'published' ? 'o-eye-slash' : 'o-eye' }}"
                              wire:click="toggleStatus({{ $course->id }})"
                              class="btn-ghost btn-xs"
                              tooltip="{{ $course->status === 'published' ? 'Dépublier' : 'Publier' }}" />
                    <x-button icon="o-trash" wire:click="delete({{ $course->id }})"
                              wire:confirm="Supprimer ce cours et toutes ses leçons ?"
                              class="btn-ghost btn-xs text-error" />
                </div>
            @endscope

        </x-table>
    </x-card>

    {{-- Create / Edit Modal --}}
    <x-modal wire:model="modal"
             :title="$editing ? 'Modifier le cours' : 'Nouveau cours'"
             class="backdrop-blur max-w-3xl">
        <x-form wire:submit="save">

            {{-- Basic titles --}}
            <div class="divider text-xs text-base-content/40">Titre du cours</div>
            <x-input label="Titre principal *" wire:model="form_title" placeholder="ex: Cours de mathématiques" required />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <x-input label="Titre FR" wire:model="form_title_fr" placeholder="Titre en français" />
                <x-input label="Titre AR" wire:model="form_title_ar" placeholder="العنوان بالعربية" />
                <x-input label="Titre EN" wire:model="form_title_en" placeholder="Title in English" />
            </div>

            {{-- Assignments --}}
            <div class="divider text-xs text-base-content/40">Affectation</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <x-select label="Matière *" wire:model="form_subject_id"
                          :options="$subjects" option-value="id"
                          option-label="name_fr"
                          placeholder="Choisir une matière" required />
                <x-select label="Enseignant *" wire:model="form_teacher_id"
                          :options="$teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->user->full_name ?? $t->user->name])"
                          option-value="id" option-label="name"
                          placeholder="Choisir un enseignant" required />
                <x-select label="Année scolaire" wire:model="form_academic_year_id"
                          :options="$academicYears->map(fn($y) => ['id' => $y->id, 'name' => $y->name . ($y->is_current ? ' ✓' : '')])"
                          option-value="id" option-label="name"
                          placeholder="Optionnel" />
            </div>

            {{-- Type & Price --}}
            <div class="divider text-xs text-base-content/40">Type & Tarification</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <x-select label="Type *" wire:model.live="form_type" :options="$typeOptions" required />
                <x-input label="Prix (DZD)" wire:model="form_price" type="number" min="0" step="100"
                         prefix="DZD" :disabled="$form_type === 'free'" />
                <x-input label="Durée (heures)" wire:model="form_duration_hours" type="number" min="0" step="0.5" />
            </div>

            {{-- Status --}}
            <x-select label="Statut" wire:model="form_status" :options="$statusOptions" />

            {{-- Thumbnail --}}
            <x-input label="URL Miniature" wire:model="form_thumbnail" placeholder="https://..." icon="o-photo" />
            @if($form_thumbnail)
                <div class="mt-1">
                    <img src="{{ $form_thumbnail }}" class="h-24 rounded-lg object-cover border border-base-300" alt="preview" />
                </div>
            @endif

            {{-- Descriptions --}}
            <div class="divider text-xs text-base-content/40">Description</div>
            <x-textarea label="Description FR" wire:model="form_description_fr" rows="3"
                        placeholder="Description du cours en français..." />
            <x-textarea label="Description AR" wire:model="form_description_ar" rows="3"
                        placeholder="وصف الدرس بالعربية..." />
            <x-textarea label="Description EN" wire:model="form_description_en" rows="2"
                        placeholder="Course description in English..." />

            <x-slot:actions>
                <x-button label="Annuler" @click="$wire.modal = false" />
                <x-button :label="$editing ? 'Mettre à jour' : 'Créer le cours'"
                          class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

</div>
