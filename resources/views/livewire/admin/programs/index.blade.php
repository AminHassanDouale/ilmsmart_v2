<?php

use App\Models\Program;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public bool $showModal = false;
    public ?int $editId = null;

    public string $title         = '';
    public string $description   = '';
    public string $level         = 'beginner';
    public string $type          = 'free';
    public float  $price         = 0;
    public ?int   $durationWeeks = null;
    public ?int   $capacity      = null;
    public ?string $startDate    = null;
    public ?string $endDate      = null;
    public string $scheduleType  = 'weekly';
    public bool   $isIslamic     = false;
    public string $audience      = 'both';
    public string $status        = 'draft';
    public $thumbnail            = null;

    public string $search        = '';
    public string $filterLevel   = '';
    public string $filterStatus  = '';

    public function programs()
    {
        return Program::withCount(['courses','sessions','enrollments'])
            ->when($this->search, fn($q) => $q->where('title','like',"%{$this->search}%"))
            ->when($this->filterLevel, fn($q) => $q->where('level', $this->filterLevel))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->get();
    }

    public function openCreate(): void
    {
        $this->reset(['title','description','level','type','price','durationWeeks',
                      'capacity','startDate','endDate','scheduleType','isIslamic',
                      'audience','status','thumbnail','editId']);
        $this->level        = 'beginner';
        $this->type         = 'free';
        $this->scheduleType = 'weekly';
        $this->audience     = 'both';
        $this->status       = 'draft';
        $this->showModal    = true;
    }

    public function openEdit(int $id): void
    {
        $p = Program::findOrFail($id);
        $this->editId        = $id;
        $this->title         = $p->title;
        $this->description   = $p->description ?? '';
        $this->level         = $p->level;
        $this->type          = $p->type;
        $this->price         = (float) $p->price;
        $this->durationWeeks = $p->duration_weeks;
        $this->capacity      = $p->capacity;
        $this->startDate     = $p->start_date?->format('Y-m-d');
        $this->endDate       = $p->end_date?->format('Y-m-d');
        $this->scheduleType  = $p->schedule_type;
        $this->isIslamic     = (bool) $p->is_islamic;
        $this->audience      = $p->audience;
        $this->status        = $p->status;
        $this->showModal     = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'    => 'required|min:3|max:255',
            'level'    => 'required|in:beginner,intermediate,premium,custom',
            'type'     => 'required|in:free,paid,subscription',
            'status'   => 'required|in:draft,published,archived',
            'audience' => 'required|in:students,individuals,both',
        ]);

        $data = [
            'title'          => $this->title,
            'description'    => $this->description,
            'level'          => $this->level,
            'type'           => $this->type,
            'price'          => $this->price,
            'duration_weeks' => $this->durationWeeks,
            'capacity'       => $this->capacity,
            'start_date'     => $this->startDate,
            'end_date'       => $this->endDate,
            'schedule_type'  => $this->scheduleType,
            'is_islamic'     => $this->isIslamic,
            'audience'       => $this->audience,
            'status'         => $this->status,
        ];

        if ($this->thumbnail) {
            $data['thumbnail'] = $this->thumbnail->store('programs/thumbnails', 'public');
        }

        if ($this->editId) {
            Program::findOrFail($this->editId)->update($data);
            $this->success('Program updated.');
        } else {
            Program::create($data);
            $this->success('Program created.');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        Program::findOrFail($id)->delete();
        $this->warning('Program deleted.');
    }

    public function togglePublish(int $id): void
    {
        $p = Program::findOrFail($id);
        $p->status = $p->status === 'published' ? 'draft' : 'published';
        $p->save();
        $this->success('Status updated.');
    }
};
?>

<div>
    <x-header title="Programs" subtitle="Bundled course curricula with scheduled sessions">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search programs..." wire:model.live.debounce="search"
                     icon="o-magnifying-glass" class="w-full sm:w-64" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="New Program" icon="o-plus" wire:click="openCreate" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <x-select wire:model.live="filterLevel" placeholder="All Levels" :options="[
            ['id'=>'beginner','name'=>'Beginner'],
            ['id'=>'intermediate','name'=>'Intermediate'],
            ['id'=>'premium','name'=>'Premium'],
            ['id'=>'custom','name'=>'Custom'],
        ]" option-value="id" option-label="name" class="w-full sm:w-48" />

        <x-select wire:model.live="filterStatus" placeholder="All Statuses" :options="[
            ['id'=>'draft','name'=>'Draft'],
            ['id'=>'published','name'=>'Published'],
            ['id'=>'archived','name'=>'Archived'],
        ]" option-value="id" option-label="name" class="w-full sm:w-48" />
    </div>

    @php $programs = $this->programs(); @endphp

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <x-stat title="Total Programs" :value="$programs->count()" icon="o-rectangle-stack" />
        <x-stat title="Published" :value="$programs->where('status','published')->count()" icon="o-check-circle" color="text-success" />
        <x-stat title="Total Enrollments" :value="$programs->sum('enrollments_count')" icon="o-users" />
        <x-stat title="Active Sessions" :value="$programs->sum('sessions_count')" icon="o-clock" />
    </div>

    @if($programs->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-rectangle-stack" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No programs yet.</p>
            <x-button label="Create First Program" icon="o-plus" wire:click="openCreate" class="btn-primary mt-4" />
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
            @foreach($programs as $p)
                <x-card class="hover:shadow-lg transition-shadow">
                    {{-- Thumb --}}
                    @if($p->thumbnail)
                        <img src="{{ Storage::url($p->thumbnail) }}" class="w-full h-32 sm:h-40 object-cover rounded-lg mb-4" alt="">
                    @else
                        <div class="w-full h-32 sm:h-40 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg mb-4 flex items-center justify-center">
                            <x-icon name="o-rectangle-stack" class="w-12 h-12 text-white/80" />
                        </div>
                    @endif

                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-bold text-sm sm:text-base leading-tight">{{ $p->title }}</h3>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span @class([
                                'badge badge-sm capitalize',
                                'badge-success' => $p->level==='beginner',
                                'badge-info'    => $p->level==='intermediate',
                                'badge-warning' => $p->level==='premium',
                                'badge-ghost'   => $p->level==='custom',
                            ])>{{ $p->level }}</span>
                        </div>
                    </div>

                    @if($p->description)
                        <p class="text-xs sm:text-sm text-base-content/60 line-clamp-2 mb-3">{{ $p->description }}</p>
                    @endif

                    <div class="flex flex-wrap gap-2 text-xs text-base-content/50 mb-3">
                        <span class="flex items-center gap-1">
                            <x-icon name="o-book-open" class="w-4 h-4" />
                            {{ $p->courses_count }} courses
                        </span>
                        <span class="flex items-center gap-1">
                            <x-icon name="o-clock" class="w-4 h-4" />
                            {{ $p->sessions_count }} sessions
                        </span>
                        <span class="flex items-center gap-1">
                            <x-icon name="o-users" class="w-4 h-4" />
                            {{ $p->enrollments_count }}{{ $p->capacity ? '/'.$p->capacity : '' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span @class([
                                'badge badge-sm',
                                'badge-success' => $p->status==='published',
                                'badge-ghost'   => $p->status==='draft',
                                'badge-error'   => $p->status==='archived',
                            ])>{{ ucfirst($p->status) }}</span>
                            @if($p->is_islamic)
                                <span class="badge badge-info badge-sm">☪ Islamic</span>
                            @endif
                        </div>
                        <span class="text-sm font-semibold">
                            @if($p->type === 'free') Free
                            @else {{ number_format($p->price, 0) }} DZD @endif
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-1">
                        <a href="{{ route('admin.programs.courses', $p) }}" wire:navigate
                           class="btn btn-primary btn-xs sm:btn-sm flex-1 min-w-0">
                            <x-icon name="o-book-open" class="w-4 h-4" /> Courses
                        </a>
                        <a href="{{ route('admin.programs.sessions', $p) }}" wire:navigate
                           class="btn btn-info btn-xs sm:btn-sm flex-1 min-w-0">
                            <x-icon name="o-calendar" class="w-4 h-4" /> Sessions
                        </a>
                        <a href="{{ route('admin.programs.enrollments', $p) }}" wire:navigate
                           class="btn btn-ghost btn-xs sm:btn-sm" title="Enrollments">
                            <x-icon name="o-users" class="w-4 h-4" />
                        </a>
                        <x-button icon="o-pencil" wire:click="openEdit({{ $p->id }})" class="btn-ghost btn-xs sm:btn-sm" tooltip="Edit" />
                        <x-button icon="{{ $p->status === 'published' ? 'o-eye-slash' : 'o-eye' }}"
                                  wire:click="togglePublish({{ $p->id }})" class="btn-ghost btn-xs sm:btn-sm" />
                        <x-button icon="o-trash" wire:click="delete({{ $p->id }})"
                                  wire:confirm="Delete this program?" class="btn-ghost btn-xs sm:btn-sm text-error" />
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Modal --}}
    <x-modal wire:model="showModal" title="{{ $editId ? 'Edit Program' : 'New Program' }}" max-width="3xl" class="backdrop-blur">
        <div class="space-y-4">
            <x-input label="Program Title" wire:model="title" placeholder="e.g. Hafiz Program — Quran Memorization" required />
            <x-textarea label="Description" wire:model="description" rows="3" />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-select label="Level" wire:model="level" :options="[
                    ['id'=>'beginner','name'=>'Beginner'],
                    ['id'=>'intermediate','name'=>'Intermediate'],
                    ['id'=>'premium','name'=>'Premium'],
                    ['id'=>'custom','name'=>'Custom'],
                ]" option-value="id" option-label="name" />

                <x-select label="Access Type" wire:model="type" :options="[
                    ['id'=>'free','name'=>'Free'],
                    ['id'=>'paid','name'=>'Paid'],
                    ['id'=>'subscription','name'=>'Subscription'],
                ]" option-value="id" option-label="name" />

                <x-select label="Audience" wire:model="audience" :options="[
                    ['id'=>'students','name'=>'Students Only'],
                    ['id'=>'individuals','name'=>'Individuals Only'],
                    ['id'=>'both','name'=>'Both'],
                ]" option-value="id" option-label="name" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @if($type !== 'free')
                    <x-input label="Price (DZD)" wire:model="price" type="number" min="0" step="100" />
                @endif
                <x-input label="Duration (weeks)" wire:model="durationWeeks" type="number" min="1" />
                <x-input label="Capacity (max enrollments)" wire:model="capacity" type="number" min="1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-input label="Start Date" wire:model="startDate" type="date" />
                <x-input label="End Date" wire:model="endDate" type="date" />
                <x-select label="Schedule Type" wire:model="scheduleType" :options="[
                    ['id'=>'none','name'=>'None'],
                    ['id'=>'daily','name'=>'Daily'],
                    ['id'=>'weekly','name'=>'Weekly'],
                    ['id'=>'custom','name'=>'Custom'],
                ]" option-value="id" option-label="name" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-select label="Status" wire:model="status" :options="[
                    ['id'=>'draft','name'=>'Draft'],
                    ['id'=>'published','name'=>'Published'],
                    ['id'=>'archived','name'=>'Archived'],
                ]" option-value="id" option-label="name" />

                <x-toggle label="Islamic Program" wire:model="isIslamic" hint="Mark as Islamic for the Islamic section" />
            </div>

            <x-file label="Thumbnail" wire:model="thumbnail" accept="image/*" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showModal = false" />
            <x-button label="{{ $editId ? 'Update' : 'Create' }}" icon="o-check" class="btn-primary" wire:click="save" />
        </x-slot:actions>
    </x-modal>
</div>
