<?php

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\IslamicBook;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new class extends Component {

    public Course $course;

    // Module modal
    public bool   $showModuleModal = false;
    public ?int   $editModuleId    = null;
    public string $moduleTitle     = '';
    public string $moduleDesc      = '';

    // Lesson modal
    public bool   $showLessonModal     = false;
    public ?int   $editLessonId        = null;
    public ?int   $lessonModuleId      = null;
    public string $lessonTitle         = '';
    public string $lessonContent       = '';
    public string $lessonType          = 'text';
    public bool   $lessonIsFree        = false;
    public int    $lessonDuration      = 0;
    public string $lessonStatus        = 'published';
    public string $islamicContentType  = 'custom'; // quran_surah|hadith|dua|book|custom
    // Islamic pickers
    public int    $pickSurah           = 1;
    public string $pickHadithCollection = 'bukhari';
    public int    $pickHadithPage      = 1;
    public string $pickDuaCategory     = '';
    public ?int   $pickBookId          = null;

    // Loader data
    public array $surahs = [];
    public array $books  = [];

    public function mount(Course $course): void
    {
        $this->course = $course;
        $this->loadSurahs();
        $this->books = IslamicBook::where('is_published', true)
            ->orderBy('title')
            ->get(['id','title','category'])
            ->toArray();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function loadSurahs(): void
    {
        try {
            $res = Http::withHeader('X-API-Key', 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2')
                ->timeout(10)
                ->get('https://ummahapi.com/api/quran/surahs');
            $this->surahs = $res->json('data.surahs', []);
        } catch (\Throwable) {
            $this->surahs = [];
        }
    }

    public function modules(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->course->modules()->with('lessons')->get();
    }

    private function buildLessonMeta(): array
    {
        return match($this->islamicContentType) {
            'quran_surah' => [
                'type'  => 'quran_surah',
                'surah' => $this->pickSurah,
                'name'  => collect($this->surahs)->firstWhere('number', $this->pickSurah)['name'] ?? '',
            ],
            'hadith' => [
                'type'       => 'hadith',
                'collection' => $this->pickHadithCollection,
                'page'       => $this->pickHadithPage,
            ],
            'dua' => [
                'type'     => 'dua',
                'category' => $this->pickDuaCategory,
            ],
            'book' => [
                'type'    => 'book',
                'book_id' => $this->pickBookId,
            ],
            default => ['type' => 'custom'],
        };
    }

    // ─── Module CRUD ─────────────────────────────────────────────────────────

    public function openCreateModule(): void
    {
        $this->reset(['moduleTitle','moduleDesc','editModuleId']);
        $this->showModuleModal = true;
    }

    public function openEditModule(int $id): void
    {
        $m = Module::findOrFail($id);
        $this->editModuleId = $id;
        $this->moduleTitle  = $m->title;
        $this->moduleDesc   = $m->description ?? '';
        $this->showModuleModal = true;
    }

    public function saveModule(): void
    {
        $this->validate(['moduleTitle' => 'required|min:2|max:255']);

        $data = [
            'title'       => $this->moduleTitle,
            'description' => $this->moduleDesc,
            'course_id'   => $this->course->id,
            'order'       => Module::where('course_id', $this->course->id)->max('order') + 1,
        ];

        if ($this->editModuleId) {
            Module::findOrFail($this->editModuleId)->update($data);
            $this->success('Module updated.');
        } else {
            Module::create($data);
            $this->success('Module added.');
        }

        $this->showModuleModal = false;
        $this->course->refresh();
    }

    public function deleteModule(int $id): void
    {
        Module::findOrFail($id)->delete();
        $this->warning('Module deleted.');
        $this->course->refresh();
    }

    // ─── Lesson CRUD ─────────────────────────────────────────────────────────

    public function openCreateLesson(int $moduleId): void
    {
        $this->reset(['lessonTitle','lessonContent','lessonType','lessonIsFree','lessonDuration',
                      'lessonStatus','islamicContentType','pickSurah','pickHadithCollection',
                      'pickHadithPage','pickDuaCategory','pickBookId','editLessonId']);
        $this->lessonModuleId     = $moduleId;
        $this->lessonType         = 'text';
        $this->lessonStatus       = 'published';
        $this->islamicContentType = 'custom';
        $this->pickSurah          = 1;
        $this->pickHadithPage     = 1;
        $this->pickHadithCollection = 'bukhari';
        $this->showLessonModal    = true;
    }

    public function openEditLesson(int $id): void
    {
        $l = Lesson::findOrFail($id);
        $this->editLessonId        = $id;
        $this->lessonModuleId      = $l->module_id;
        $this->lessonTitle         = $l->title;
        $this->lessonContent       = $l->content ?? '';
        $this->lessonType          = $l->type;
        $this->lessonIsFree        = (bool) $l->is_free_preview;
        $this->lessonDuration      = (int) $l->duration_minutes;
        $this->lessonStatus        = $l->status;
        $meta = $l->lesson_meta ?? [];
        $this->islamicContentType  = $meta['type'] ?? 'custom';
        $this->pickSurah           = $meta['surah'] ?? 1;
        $this->pickHadithCollection = $meta['collection'] ?? 'bukhari';
        $this->pickHadithPage      = $meta['page'] ?? 1;
        $this->pickDuaCategory     = $meta['category'] ?? '';
        $this->pickBookId          = $meta['book_id'] ?? null;
        $this->showLessonModal     = true;
    }

    public function saveLesson(): void
    {
        $this->validate([
            'lessonTitle'    => 'required|min:2|max:255',
            'lessonModuleId' => 'required|exists:modules,id',
        ]);

        $meta = $this->buildLessonMeta();
        $autoTitle = $this->lessonTitle ?: $this->autoLessonTitle($meta);

        $data = [
            'module_id'       => $this->lessonModuleId,
            'title'           => $this->lessonTitle ?: $autoTitle,
            'content'         => $this->lessonContent,
            'type'            => $this->lessonType,
            'lesson_meta'     => $meta,
            'is_free_preview' => $this->lessonIsFree,
            'duration_minutes'=> $this->lessonDuration,
            'status'          => $this->lessonStatus,
            'order'           => Lesson::where('module_id', $this->lessonModuleId)->max('order') + 1,
        ];

        if ($this->editLessonId) {
            Lesson::findOrFail($this->editLessonId)->update($data);
            $this->success('Lesson updated.');
        } else {
            Lesson::create($data);
            $this->success('Lesson added.');
        }

        $this->showLessonModal = false;
        $this->course->refresh();
    }

    public function deleteLesson(int $id): void
    {
        Lesson::findOrFail($id)->delete();
        $this->warning('Lesson deleted.');
        $this->course->refresh();
    }

    private function autoLessonTitle(array $meta): string
    {
        return match($meta['type'] ?? 'custom') {
            'quran_surah' => 'Surah ' . ($meta['name'] ?? $meta['surah']),
            'hadith'      => 'Hadith — ' . ucfirst($meta['collection']) . ' p.' . $meta['page'],
            'dua'         => 'Duas — ' . ucfirst($meta['category'] ?? ''),
            'book'        => 'Book Study',
            default       => 'Lesson',
        };
    }

    public function islamicTypeLabel(string $type): string
    {
        return match($type) {
            'quran_surah' => 'Quran Surah',
            'hadith'      => 'Hadith',
            'dua'         => 'Dua',
            'book'        => 'Islamic Book',
            default       => 'Custom Content',
        };
    }

    public function islamicTypeBadge(string $type): string
    {
        return match($type) {
            'quran_surah' => 'badge-success',
            'hadith'      => 'badge-info',
            'dua'         => 'badge-warning',
            'book'        => 'badge-secondary',
            default       => 'badge-ghost',
        };
    }
};
?>

<div>
    {{-- Breadcrumb header --}}
    <x-header :title="$course->title" subtitle="Manage Modules & Lessons">
        <x-slot:middle>
            <div class="flex items-center gap-2 text-sm text-base-content/50">
                <a href="{{ route('admin.islamic.courses') }}" wire:navigate class="hover:text-primary">Islamic Courses</a>
                <x-icon name="o-chevron-right" class="w-4 h-4" />
                <span>{{ Str::limit($course->title, 40) }}</span>
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <div class="flex items-center gap-2">
                @if($course->level)
                    <span @class(['badge', 'badge-success'=>$course->level==='beginner','badge-info'=>$course->level==='intermediate','badge-warning'=>$course->level==='premium'])>
                        {{ ucfirst($course->level) }}
                    </span>
                @endif
                <span @class(['badge','badge-success'=>$course->status==='published','badge-ghost'=>$course->status==='draft'])>
                    {{ ucfirst($course->status) }}
                </span>
                <x-button label="Add Module" icon="o-plus" wire:click="openCreateModule" class="btn-primary btn-sm" />
            </div>
        </x-slot:actions>
    </x-header>

    {{-- Course info bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <x-stat title="Modules"  :value="$this->modules()->count()" icon="o-rectangle-stack" />
        <x-stat title="Lessons"  :value="$this->modules()->sum(fn($m) => $m->lessons->count())" icon="o-book-open" />
        <x-stat title="Enrolled" :value="$course->enrollments()->count()" icon="o-users" />
        <x-stat title="Type"     :value="ucfirst($course->type)" icon="o-tag" />
    </div>

    {{-- Modules & lessons --}}
    @php $modules = $this->modules(); @endphp

    @if($modules->isEmpty())
        <x-card class="text-center py-12">
            <x-icon name="o-rectangle-stack" class="w-14 h-14 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50">No modules yet. Add your first module to get started.</p>
            <x-button label="Add Module" icon="o-plus" wire:click="openCreateModule" class="btn-primary mt-4" />
        </x-card>
    @else
        <div class="space-y-4">
            @foreach($modules as $modIdx => $module)
                <x-card>
                    {{-- Module header --}}
                    <div class="flex items-start sm:items-center justify-between gap-3 mb-4 flex-wrap sm:flex-nowrap">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm shrink-0">
                                {{ $modIdx + 1 }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-sm sm:text-base">{{ $module->title }}</h3>
                                @if($module->description)
                                    <p class="text-xs text-base-content/50 line-clamp-2">{{ $module->description }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <x-button icon="o-plus" label="Add Lesson" wire:click="openCreateLesson({{ $module->id }})" class="btn-ghost btn-sm" responsive />
                            <x-button icon="o-pencil" wire:click="openEditModule({{ $module->id }})" class="btn-ghost btn-sm" tooltip="Edit" />
                            <x-button icon="o-trash" wire:click="deleteModule({{ $module->id }})" wire:confirm="Delete this module and all its lessons?" class="btn-ghost btn-sm text-error" tooltip="Delete" />
                        </div>
                    </div>

                    {{-- Lessons list --}}
                    @if($module->lessons->isEmpty())
                        <div class="text-center py-6 border border-dashed border-base-300 rounded-lg">
                            <p class="text-sm text-base-content/40">No lessons in this module yet.</p>
                            <x-button label="Add First Lesson" icon="o-plus" wire:click="openCreateLesson({{ $module->id }})" class="btn-ghost btn-xs mt-2" />
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($module->lessons as $lIdx => $lesson)
                                @php $meta = $lesson->lesson_meta ?? []; $metaType = $meta['type'] ?? 'custom'; @endphp
                                <div class="flex items-start gap-2 sm:gap-3 p-2.5 sm:p-3 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
                                    <span class="text-xs text-base-content/40 w-5 text-right pt-0.5 hidden sm:block">{{ $lIdx + 1 }}</span>

                                    <x-icon name="{{ $lesson->type_icon }}" class="w-5 h-5 text-base-content/50 shrink-0 mt-0.5" />

                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm leading-tight line-clamp-2 sm:truncate">{{ $lesson->title }}</p>
                                        <div class="flex items-center flex-wrap gap-1.5 mt-1">
                                            <span class="badge badge-xs {{ $this->islamicTypeBadge($metaType) }}">
                                                {{ $this->islamicTypeLabel($metaType) }}
                                            </span>
                                            @if($metaType === 'quran_surah' && isset($meta['surah']))
                                                <span class="text-xs text-base-content/40">Surah {{ $meta['name'] ?? $meta['surah'] }}</span>
                                            @elseif($metaType === 'hadith')
                                                <span class="text-xs text-base-content/40">{{ ucfirst($meta['collection'] ?? '') }} · p.{{ $meta['page'] ?? '' }}</span>
                                            @elseif($metaType === 'dua')
                                                <span class="text-xs text-base-content/40">{{ ucfirst($meta['category'] ?? '') }}</span>
                                            @elseif($metaType === 'book' && isset($meta['book_id']))
                                                @php $bk = collect($this->books)->firstWhere('id', $meta['book_id']); @endphp
                                                <span class="text-xs text-base-content/40 truncate">{{ $bk['title'] ?? 'Book #'.$meta['book_id'] }}</span>
                                            @endif
                                            @if($lesson->is_free_preview)
                                                <span class="badge badge-xs badge-success">Free</span>
                                            @endif
                                            <span @class(['badge badge-xs', 'badge-success'=>$lesson->status==='published', 'badge-ghost'=>$lesson->status==='draft'])>
                                                {{ ucfirst($lesson->status) }}
                                            </span>
                                            @if($lesson->duration_minutes)
                                                <span class="text-xs text-base-content/40">· {{ $lesson->duration_minutes }}min</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex gap-0.5 shrink-0">
                                        <x-button icon="o-pencil" wire:click="openEditLesson({{ $lesson->id }})" class="btn-ghost btn-xs" tooltip="Edit" />
                                        <x-button icon="o-trash" wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Delete this lesson?" class="btn-ghost btn-xs text-error" tooltip="Delete" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Module Modal --}}
    <x-modal wire:model="showModuleModal" title="{{ $editModuleId ? 'Edit Module' : 'New Module' }}" max-width="lg">
        <div class="space-y-4">
            <x-input label="Module Title" wire:model="moduleTitle" placeholder="e.g. Introduction to Quran" required />
            <x-textarea label="Description (optional)" wire:model="moduleDesc" rows="2" />
        </div>
        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showModuleModal = false" />
            <x-button label="{{ $editModuleId ? 'Update' : 'Create' }}" icon="o-check" class="btn-primary" wire:click="saveModule" />
        </x-slot:actions>
    </x-modal>

    {{-- Lesson Modal --}}
    <x-modal wire:model="showLessonModal" title="{{ $editLessonId ? 'Edit Lesson' : 'New Lesson' }}" class="backdrop-blur" max-width="3xl">
        <div class="space-y-4">

            <x-input label="Lesson Title" wire:model="lessonTitle" placeholder="e.g. Al-Fatiha — The Opening" />

            {{-- Islamic Content Type Picker --}}
            <div>
                <label class="label"><span class="label-text font-semibold">Islamic Content Source</span></label>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    @foreach([
                        ['quran_surah', 'o-book-open',              'Quran Surah'],
                        ['hadith',      'o-chat-bubble-left-right', 'Hadith'],
                        ['dua',         'o-hand-raised',            'Dua'],
                        ['book',        'o-document-text',          'Book'],
                        ['custom',      'o-pencil-square',          'Custom'],
                    ] as [$val, $icon, $label])
                        <button type="button"
                                wire:click="$set('islamicContentType','{{ $val }}')"
                                @class(['btn btn-sm w-full gap-1',
                                        'btn-primary' => $islamicContentType === $val,
                                        'btn-outline' => $islamicContentType !== $val])>
                            <x-icon name="{{ $icon }}" class="w-4 h-4" />
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Quran Surah picker --}}
            @if($islamicContentType === 'quran_surah')
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4">
                    <label class="label"><span class="label-text font-medium text-emerald-700 dark:text-emerald-400">Select Surah</span></label>
                    @if(empty($surahs))
                        <p class="text-sm text-base-content/50">Could not load surahs. Enter surah number manually:</p>
                        <x-input type="number" wire:model="pickSurah" min="1" max="114" class="mt-2" />
                    @else
                        <x-select wire:model="pickSurah"
                            :options="collect($surahs)->map(fn($s) => ['id'=>$s['number'],'name'=>$s['number'].'. '.$s['name'].' ('.$s['english_name'].')'])->toArray()"
                            option-value="id" option-label="name" />
                    @endif
                </div>
            @endif

            {{-- Hadith picker --}}
            @if($islamicContentType === 'hadith')
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 space-y-3">
                    <label class="label"><span class="label-text font-medium text-blue-700 dark:text-blue-400">Select Hadith Collection</span></label>
                    <x-select wire:model="pickHadithCollection" :options="[
                        ['id'=>'bukhari',  'name'=>'Sahih Al-Bukhari'],
                        ['id'=>'muslim',   'name'=>'Sahih Muslim'],
                        ['id'=>'tirmidhi', 'name'=>'Jami At-Tirmidhi'],
                        ['id'=>'abudawud', 'name'=>'Sunan Abu Dawud'],
                        ['id'=>'nasai',    'name'=>'Sunan An-Nasai'],
                        ['id'=>'ibnmajah', 'name'=>'Sunan Ibn Majah'],
                        ['id'=>'malik',    'name'=>'Muwatta Malik'],
                    ]" option-value="id" option-label="name" />
                    <x-input label="Page / Starting Hadith #" wire:model="pickHadithPage" type="number" min="1" />
                </div>
            @endif

            {{-- Dua picker --}}
            @if($islamicContentType === 'dua')
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4">
                    <label class="label"><span class="label-text font-medium text-amber-700 dark:text-amber-400">Dua Category</span></label>
                    <x-select wire:model="pickDuaCategory" :options="[
                        ['id'=>'morning',    'name'=>'Morning Adhkar'],
                        ['id'=>'evening',    'name'=>'Evening Adhkar'],
                        ['id'=>'sleep',      'name'=>'Before Sleep'],
                        ['id'=>'wakeup',     'name'=>'Upon Waking'],
                        ['id'=>'eating',     'name'=>'Eating & Drinking'],
                        ['id'=>'travel',     'name'=>'Travel'],
                        ['id'=>'prayer',     'name'=>'After Prayer'],
                        ['id'=>'distress',   'name'=>'In Distress'],
                        ['id'=>'forgiveness','name'=>'Seeking Forgiveness'],
                        ['id'=>'general',    'name'=>'General Duas'],
                    ]" option-value="id" option-label="name" placeholder="Select category..." />
                </div>
            @endif

            {{-- Book picker --}}
            @if($islamicContentType === 'book')
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4">
                    <label class="label"><span class="label-text font-medium text-purple-700 dark:text-purple-400">Select Islamic Book</span></label>
                    @if(empty($books))
                        <p class="text-sm text-base-content/50">No books available. Add books in Islamic Books section first.</p>
                    @else
                        <x-select wire:model="pickBookId"
                            :options="collect($books)->map(fn($b) => ['id'=>$b['id'],'name'=>$b['title'].' ('.ucfirst($b['category']).')'])->toArray()"
                            option-value="id" option-label="name" placeholder="Select a book..." />
                    @endif
                </div>
            @endif

            {{-- Custom content --}}
            @if($islamicContentType === 'custom')
                <x-textarea label="Lesson Content (HTML / Text)" wire:model="lessonContent" rows="5"
                    placeholder="Enter lesson text, notes, or instructions..." />
            @endif

            <div class="grid grid-cols-2 gap-4">
                <x-select label="Duration (minutes)" wire:model="lessonDuration" :options="[
                    ['id'=>0,  'name'=>'—'],
                    ['id'=>5,  'name'=>'5 min'],
                    ['id'=>10, 'name'=>'10 min'],
                    ['id'=>15, 'name'=>'15 min'],
                    ['id'=>20, 'name'=>'20 min'],
                    ['id'=>30, 'name'=>'30 min'],
                    ['id'=>45, 'name'=>'45 min'],
                    ['id'=>60, 'name'=>'60 min'],
                ]" option-value="id" option-label="name" />

                <x-select label="Status" wire:model="lessonStatus" :options="[
                    ['id'=>'published', 'name'=>'Published'],
                    ['id'=>'draft',     'name'=>'Draft'],
                ]" option-value="id" option-label="name" />
            </div>

            <x-toggle label="Free Preview (visible without enrollment)" wire:model="lessonIsFree" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showLessonModal = false" />
            <x-button label="{{ $editLessonId ? 'Update Lesson' : 'Add Lesson' }}"
                      icon="o-check" class="btn-primary" wire:click="saveLesson" />
        </x-slot:actions>
    </x-modal>
</div>
