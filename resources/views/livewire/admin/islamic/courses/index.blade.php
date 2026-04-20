<?php

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public bool $showModal = false;
    public ?int $editId = null;

    // Form fields
    public string $title       = '';
    public string $description = '';
    public string $level       = 'beginner';
    public string $type        = 'free';
    public float  $price       = 0;
    public string $status      = 'draft';
    public $thumbnail          = null;

    // Filters
    public string $search      = '';
    public string $filterLevel = '';

    public function courses(): \Illuminate\Support\Collection
    {
        return Course::withCount(['enrollments', 'modules', 'lessons'])
            ->where('is_islamic', true)
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterLevel, fn($q) => $q->where('level', $this->filterLevel))
            ->latest()
            ->get();
    }

    public function openCreate(): void
    {
        $this->reset(['title','description','level','type','price','status','thumbnail','editId']);
        $this->level  = 'beginner';
        $this->type   = 'free';
        $this->status = 'draft';
        $this->price  = 0;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $course = Course::findOrFail($id);
        $this->editId      = $id;
        $this->title       = $course->title;
        $this->description = $course->description ?? '';
        $this->level       = $course->level       ?? 'beginner';
        $this->type        = $course->type        ?? 'free';
        $this->price       = (float) ($course->price ?? 0);
        $this->status      = $course->status      ?? 'draft';
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'       => 'required|min:3|max:255',
            'level'       => 'required|in:beginner,intermediate,premium',
            'type'        => 'required|in:free,paid,subscription',
            'price'       => 'nullable|numeric|min:0',
            'status'      => 'required|in:draft,published,archived',
        ]);

        $data = [
            'title'       => $this->title,
            'description' => $this->description,
            'level'       => $this->level,
            'type'        => $this->type,
            'price'       => $this->price,
            'status'      => $this->status,
            'is_islamic'  => true,
        ];

        if ($this->thumbnail) {
            $data['thumbnail'] = $this->thumbnail->store('islamic-courses/thumbnails', 'public');
        }

        if ($this->editId) {
            Course::findOrFail($this->editId)->update($data);
            $this->success('Course updated successfully.');
        } else {
            Course::create($data);
            $this->success('Course created successfully.');
        }

        $this->showModal = false;
        $this->reset(['title','description','level','type','price','status','thumbnail','editId']);
    }

    public function delete(int $id): void
    {
        Course::findOrFail($id)->delete();
        $this->warning('Course deleted.');
    }

    public function toggleStatus(int $id): void
    {
        $course = Course::findOrFail($id);
        $course->status = ($course->status === 'published') ? 'draft' : 'published';
        $course->save();
        $this->success('Status updated.');
    }

    public function levelBadge(string $level): string
    {
        return match($level) {
            'beginner'     => 'badge-success',
            'intermediate' => 'badge-info',
            'premium'      => 'badge-warning',
            default        => 'badge-ghost',
        };
    }

    public function levelIcon(string $level): string
    {
        return match($level) {
            'beginner'     => 'o-academic-cap',
            'intermediate' => 'o-star',
            'premium'      => 'o-sparkles',
            default        => 'o-cube',
        };
    }
};
?>

<div>
    <x-header title="Islamic Courses" subtitle="Manage Islamic learning courses with Quran, Hadith, and Duas content">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search courses..." wire:model.live.debounce="search" icon="o-magnifying-glass" class="w-64" />
        </x-slot:middle>
        <x-slot:actions>
            <x-select wire:model.live="filterLevel" placeholder="All Levels" :options="[
                ['id'=>'beginner',     'name'=>'Beginner'],
                ['id'=>'intermediate', 'name'=>'Intermediate'],
                ['id'=>'premium',      'name'=>'Premium'],
            ]" option-value="id" option-label="name" class="select-sm" />
            <x-button label="New Course" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    {{-- Stats row --}}
    @php $courses = $this->courses(); @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat title="Total Courses"  :value="$courses->count()" icon="o-cube" />
        <x-stat title="Published"      :value="$courses->where('status','published')->count()" icon="o-check-circle" color="text-success" />
        <x-stat title="Total Enrolled" :value="$courses->sum('enrollments_count')" icon="o-users" />
        <x-stat title="Free Courses"   :value="$courses->where('type','free')->count()" icon="o-gift" color="text-info" />
    </div>

    {{-- Course grid --}}
    @if($courses->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-moon" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No Islamic courses yet.</p>
            <x-button label="Create First Course" icon="o-plus" wire:click="openCreate" class="btn-primary mt-4" />
        </x-card>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($courses as $course)
                <x-card class="hover:shadow-lg transition-shadow">
                    {{-- Thumbnail --}}
                    @if($course->thumbnail)
                        <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}"
                             class="w-full h-36 object-cover rounded-lg mb-4">
                    @else
                        <div class="w-full h-36 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-lg mb-4 flex items-center justify-center">
                            <x-icon name="{{ $this->levelIcon($course->level ?? 'beginner') }}" class="w-12 h-12 text-white/80" />
                        </div>
                    @endif

                    {{-- Title & badges --}}
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-bold text-base leading-tight">{{ $course->title }}</h3>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="badge {{ $this->levelBadge($course->level ?? '') }} badge-sm capitalize">
                                {{ $course->level ?? '—' }}
                            </span>
                            <span class="badge badge-outline badge-sm capitalize">{{ $course->type }}</span>
                        </div>
                    </div>

                    @if($course->description)
                        <p class="text-sm text-base-content/60 line-clamp-2 mb-3">{{ $course->description }}</p>
                    @endif

                    {{-- Stats --}}
                    <div class="flex gap-4 text-xs text-base-content/50 mb-4">
                        <span class="flex items-center gap-1">
                            <x-icon name="o-rectangle-stack" class="w-4 h-4" />
                            {{ $course->modules_count }} modules
                        </span>
                        <span class="flex items-center gap-1">
                            <x-icon name="o-book-open" class="w-4 h-4" />
                            {{ $course->lessons_count }} lessons
                        </span>
                        <span class="flex items-center gap-1">
                            <x-icon name="o-users" class="w-4 h-4" />
                            {{ $course->enrollments_count }} enrolled
                        </span>
                    </div>

                    {{-- Status + Price --}}
                    <div class="flex items-center justify-between mb-4">
                        <span @class(['badge badge-sm', 'badge-success' => $course->status==='published', 'badge-ghost' => $course->status==='draft', 'badge-error' => $course->status==='archived'])>
                            {{ ucfirst($course->status) }}
                        </span>
                        <span class="font-semibold text-sm">
                            @if($course->type === 'free') Free
                            @else {{ number_format($course->price, 2) }} DZD
                            @endif
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('admin.islamic.courses.manage', $course) }}" wire:navigate
                           class="btn btn-primary btn-sm flex-1 gap-1">
                            <x-icon name="o-cog-6-tooth" class="w-4 h-4" />
                            Manage Content
                        </a>
                        <x-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="openEdit({{ $course->id }})" tooltip="Edit" />
                        <x-button icon="{{ $course->status === 'published' ? 'o-eye-slash' : 'o-eye' }}"
                                  class="btn-ghost btn-sm"
                                  wire:click="toggleStatus({{ $course->id }})"
                                  tooltip="{{ $course->status === 'published' ? 'Unpublish' : 'Publish' }}" />
                        <x-button icon="o-trash" class="btn-ghost btn-sm text-error"
                                  wire:click="delete({{ $course->id }})"
                                  wire:confirm="Delete this course and all its content?"
                                  tooltip="Delete" />
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Create / Edit Modal --}}
    <x-modal wire:model="showModal" title="{{ $editId ? 'Edit Course' : 'New Islamic Course' }}" class="backdrop-blur" max-width="2xl">
        <div class="space-y-4">
            <x-input label="Course Title" wire:model="title" placeholder="e.g. Quran Recitation for Beginners" required />

            <x-textarea label="Description" wire:model="description" placeholder="Describe what students will learn..." rows="3" />

            <div class="grid grid-cols-2 gap-4">
                <x-select label="Level" wire:model="level" :options="[
                    ['id'=>'beginner',     'name'=>'Beginner — Free access'],
                    ['id'=>'intermediate', 'name'=>'Intermediate — Paid access'],
                    ['id'=>'premium',      'name'=>'Premium — Subscription'],
                ]" option-value="id" option-label="name" required />

                <x-select label="Access Type" wire:model="type" :options="[
                    ['id'=>'free',         'name'=>'Free'],
                    ['id'=>'paid',         'name'=>'Paid (one-time)'],
                    ['id'=>'subscription', 'name'=>'Subscription'],
                ]" option-value="id" option-label="name" required />
            </div>

            @if($type !== 'free')
                <x-input label="Price (DZD)" wire:model="price" type="number" min="0" step="100" />
            @endif

            <x-select label="Status" wire:model="status" :options="[
                ['id'=>'draft',     'name'=>'Draft'],
                ['id'=>'published', 'name'=>'Published'],
                ['id'=>'archived',  'name'=>'Archived'],
            ]" option-value="id" option-label="name" />

            <x-file label="Thumbnail Image" wire:model="thumbnail" accept="image/*" hint="Optional course cover image" />
            @if($thumbnail)
                <img src="{{ $thumbnail->temporaryUrl() }}" class="w-40 h-28 object-cover rounded-lg" alt="preview">
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showModal = false" />
            <x-button label="{{ $editId ? 'Update Course' : 'Create Course' }}"
                      icon="o-check" class="btn-primary" wire:click="save" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-modal>
</div>
