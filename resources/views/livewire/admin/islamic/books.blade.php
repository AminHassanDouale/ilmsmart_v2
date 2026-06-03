<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\IslamicBook;
use Illuminate\Support\Facades\Storage;

new
#[Layout('components.layouts.app')]
#[Title('Islamic Books')]
class extends Component {
    use WithFileUploads;

    // ── List state ────────────────────────────────────────────────────────────
    public string  $filterCategory = 'all';
    public string  $search         = '';

    // ── Modal state ───────────────────────────────────────────────────────────
    public bool    $showModal  = false;
    public bool    $showDelete = false;
    public ?int    $editId     = null;
    public ?int    $deleteId   = null;

    // ── Form fields ───────────────────────────────────────────────────────────
    public string  $title          = '';
    public string  $author         = '';
    public string  $category       = 'quran';
    public string  $description    = '';
    public string  $year           = '';
    public string  $language       = '';
    public string  $pages          = '';
    public string  $external_url   = '';
    public bool    $is_published   = true;
    public         $coverFile      = null;  // Livewire temp upload
    public         $bookFile       = null;  // Livewire temp upload (new file)
    public string  $existingFile   = '';    // selected from public/files/

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** List PDF files already placed in public/files/ */
    public function publicFiles(): array
    {
        $dir = public_path('files');
        if (!is_dir($dir)) return [];
        return array_values(array_filter(
            scandir($dir),
            fn($f) => $f !== '.' && $f !== '..' && is_file($dir . DIRECTORY_SEPARATOR . $f)
        ));
    }

    public function books()
    {
        $q = IslamicBook::query()->orderBy('sort_order')->orderByDesc('created_at');

        if ($this->filterCategory !== 'all') {
            $q->where('category', $this->filterCategory);
        }

        if (trim($this->search) !== '') {
            $q->where(function ($sq) {
                $sq->where('title',  'like', '%' . $this->search . '%')
                   ->orWhere('author', 'like', '%' . $this->search . '%');
            });
        }

        return $q->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editId    = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $book = IslamicBook::findOrFail($id);
        $this->editId       = $id;
        $this->title        = $book->title;
        $this->author       = $book->author       ?? '';
        $this->category     = $book->category;
        $this->description  = $book->description  ?? '';
        $this->year         = $book->year          ?? '';
        $this->language     = $book->language      ?? '';
        $this->pages        = $book->pages         ?? '';
        $this->external_url = $book->external_url  ?? '';
        $this->is_published = $book->is_published;
        $this->coverFile    = null;
        $this->bookFile     = null;
        // Pre-select existing public file if applicable
        $this->existingFile = str_starts_with($book->file_path ?? '', 'public-files:')
            ? substr($book->file_path, 13)
            : '';
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'        => 'required|string|max:255',
            'author'       => 'nullable|string|max:255',
            'category'     => 'required|in:' . implode(',', array_keys(IslamicBook::$categories)),
            'description'  => 'nullable|string|max:2000',
            'year'         => 'nullable|string|max:50',
            'language'     => 'nullable|string|max:100',
            'pages'        => 'nullable|string|max:50',
            'external_url' => 'nullable|url|max:500',
            'coverFile'    => 'nullable|image|max:2048',
            'bookFile'     => 'nullable|mimes:pdf,epub,doc,docx|max:102400',
        ]);

        $data = [
            'title'        => $this->title,
            'author'       => $this->author       ?: null,
            'category'     => $this->category,
            'description'  => $this->description  ?: null,
            'year'         => $this->year          ?: null,
            'language'     => $this->language      ?: null,
            'pages'        => $this->pages         ?: null,
            'external_url' => $this->external_url  ?: null,
            'is_published' => $this->is_published,
        ];

        $book = $this->editId ? IslamicBook::findOrFail($this->editId) : new IslamicBook();

        // Handle cover image upload
        if ($this->coverFile) {
            if ($book->cover_path) Storage::disk('public')->delete($book->cover_path);
            $data['cover_path'] = $this->coverFile->store('islamic-books/covers', 'public');
        }

        // Handle book file: uploaded file takes priority, then existing public file selection
        if ($this->bookFile) {
            // Delete old stored file if it was a storage upload (not public-files)
            if ($book->file_path && !str_starts_with($book->file_path, 'public-files:')) {
                Storage::disk('public')->delete($book->file_path);
            }
            $data['file_path'] = $this->bookFile->store('islamic-books/files', 'public');
        } elseif ($this->existingFile !== '') {
            // Point to a file already in public/files/
            $data['file_path'] = 'public-files:' . $this->existingFile;
        }

        $book->fill($data)->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId   = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        if (!$this->deleteId) return;
        $book = IslamicBook::findOrFail($this->deleteId);
        if ($book->cover_path) Storage::disk('public')->delete($book->cover_path);
        // Only delete stored uploads, not public/files/ references
        if ($book->file_path && !str_starts_with($book->file_path, 'public-files:')) {
            Storage::disk('public')->delete($book->file_path);
        }
        $book->delete();
        $this->showDelete = false;
        $this->deleteId   = null;
    }

    public function togglePublish(int $id): void
    {
        $book = IslamicBook::findOrFail($id);
        $book->update(['is_published' => !$book->is_published]);
    }

    private function resetForm(): void
    {
        $this->title        = '';
        $this->author       = '';
        $this->category     = 'quran';
        $this->description  = '';
        $this->year         = '';
        $this->language     = '';
        $this->pages        = '';
        $this->external_url = '';
        $this->is_published = true;
        $this->coverFile    = null;
        $this->bookFile     = null;
        $this->existingFile = '';
    }
}; ?>

<div>
    <x-header title="Islamic Books" subtitle="Manage your Islamic book library" separator>
        <x-slot:actions>
            <x-button label="Add Book" icon="o-plus" wire:click="openCreate" class="btn-primary btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 sm:items-center">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Search title or author..."
               class="input input-bordered input-sm w-full sm:w-56" />
        <div class="flex gap-1 overflow-x-auto pb-1 -mx-1 px-1">
            <button wire:click="$set('filterCategory','all')"
                    class="btn btn-xs shrink-0 {{ $filterCategory === 'all' ? 'btn-primary' : 'btn-ghost' }}">All</button>
            @foreach(App\Models\IslamicBook::$categories as $key => $label)
                <button wire:click="$set('filterCategory','{{ $key }}')"
                        class="btn btn-xs shrink-0 {{ $filterCategory === $key ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    {{-- Books grid --}}
    @php $books = $this->books(); @endphp

    @if($books->isEmpty())
        <div class="text-center py-16 text-base-content/40">
            No books yet.
            <button wire:click="openCreate" class="link link-primary ml-1">Add the first one</button>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($books as $book)
                <x-card shadow class="flex flex-col hover:shadow-lg transition-all {{ !$book->is_published ? 'opacity-60' : '' }}">
                    {{-- Cover --}}
                    <div class="flex justify-center mb-3 relative">
                        @if($book->cover_path)
                            <img src="{{ Storage::url($book->cover_path) }}"
                                 alt="{{ $book->title }}"
                                 class="h-36 object-contain rounded shadow" />
                        @else
                            <div class="h-36 w-24 bg-primary/10 rounded flex items-center justify-center text-primary/40 text-5xl">📕</div>
                        @endif
                        @if(!$book->is_published)
                            <span class="absolute top-1 right-1 badge badge-warning badge-xs">Draft</span>
                        @endif
                    </div>

                    <x-badge :value="$book->category_label" class="badge-ghost badge-xs mb-2 self-start" />

                    <div class="font-semibold text-sm leading-tight mb-1">{{ $book->title }}</div>
                    @if($book->author)
                        <div class="text-xs text-primary font-medium mb-1">{{ $book->author }}</div>
                    @endif
                    @if($book->year)
                        <div class="text-xs text-base-content/40 mb-2">{{ $book->year }}</div>
                    @endif
                    @if($book->description)
                        <p class="text-xs text-base-content/60 leading-relaxed flex-1 mb-3 line-clamp-3">{{ $book->description }}</p>
                    @endif

                    <div class="flex gap-1 flex-wrap mb-3">
                        @if($book->pages)
                            <x-badge :value="$book->pages . ' pp'" class="badge-ghost badge-xs" />
                        @endif
                        @if($book->language)
                            <x-badge :value="$book->language" class="badge-ghost badge-xs" />
                        @endif
                        @if($book->file_path)
                            <x-badge :value="$book->file_name" class="badge-success badge-xs" />
                        @endif
                    </div>

                    <div class="flex gap-1 mb-2 flex-wrap">
                        @if($book->file_path)
                            <a href="{{ $book->file_url }}" target="_blank"
                               class="btn btn-xs btn-primary flex-1">
                                <x-icon name="o-arrow-down-tray" class="w-3 h-3" /> Download
                            </a>
                        @endif
                        @if($book->external_url)
                            <a href="{{ $book->external_url }}" target="_blank" rel="noopener noreferrer"
                               class="btn btn-xs btn-ghost border border-base-300 flex-1">
                                <x-icon name="o-arrow-top-right-on-square" class="w-3 h-3" /> Link
                            </a>
                        @endif
                    </div>

                    <div class="flex gap-1 mt-auto">
                        <x-button icon="o-pencil-square" wire:click="openEdit({{ $book->id }})"
                                  class="btn-ghost btn-xs flex-1" tooltip="Edit" />
                        <x-button :icon="$book->is_published ? 'o-eye-slash' : 'o-eye'"
                                  wire:click="togglePublish({{ $book->id }})"
                                  class="btn-ghost btn-xs flex-1"
                                  :tooltip="$book->is_published ? 'Unpublish' : 'Publish'" />
                        <x-button icon="o-trash" wire:click="confirmDelete({{ $book->id }})"
                                  class="btn-ghost btn-xs text-error flex-1" tooltip="Delete" />
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- Add / Edit Modal                                                  --}}
    {{-- ================================================================ --}}
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto"
             wire:click.self="$set('showModal', false)">
            <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-2xl my-4">
                <div class="flex items-center justify-between p-5 border-b border-base-200">
                    <h3 class="font-bold text-lg">{{ $editId ? 'Edit Book' : 'Add New Book' }}</h3>
                    <x-button icon="o-x-mark" wire:click="$set('showModal', false)" class="btn-ghost btn-circle btn-sm" />
                </div>

                <div class="p-5 space-y-4 overflow-y-auto max-h-[75vh]">
                    {{-- Title --}}
                    <div class="form-control">
                        <label class="label label-text font-semibold">Title <span class="text-error">*</span></label>
                        <input type="text" wire:model="title" class="input input-bordered" placeholder="e.g. القاعدة النورانية" />
                        @error('title') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Author + Category --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label label-text font-semibold">Author</label>
                            <input type="text" wire:model="author" class="input input-bordered" placeholder="e.g. Imam al-Bukhari" />
                        </div>
                        <div class="form-control">
                            <label class="label label-text font-semibold">Category <span class="text-error">*</span></label>
                            <select wire:model="category" class="select select-bordered">
                                @foreach(App\Models\IslamicBook::$categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="form-control">
                        <label class="label label-text font-semibold">Description</label>
                        <textarea wire:model="description" class="textarea textarea-bordered h-24"
                                  placeholder="Brief description..."></textarea>
                        @error('description') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Year + Language + Pages --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-control">
                            <label class="label label-text font-semibold">Year</label>
                            <input type="text" wire:model="year" class="input input-bordered" placeholder="e.g. 846 CE" />
                        </div>
                        <div class="form-control">
                            <label class="label label-text font-semibold">Language</label>
                            <input type="text" wire:model="language" class="input input-bordered" placeholder="Arabic / English" />
                        </div>
                        <div class="form-control">
                            <label class="label label-text font-semibold">Pages</label>
                            <input type="text" wire:model="pages" class="input input-bordered" placeholder="e.g. 580" />
                        </div>
                    </div>

                    {{-- External URL --}}
                    <div class="form-control">
                        <label class="label label-text font-semibold">External Link <span class="text-xs font-normal text-base-content/40">(optional)</span></label>
                        <input type="url" wire:model="external_url" class="input input-bordered"
                               placeholder="https://archive.org/..." />
                        @error('external_url') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Cover image --}}
                    <div class="form-control">
                        <label class="label label-text font-semibold">Cover Image</label>
                        <input type="file" wire:model="coverFile" accept="image/*" class="file-input file-input-bordered w-full" />
                        @error('coverFile') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        @if($coverFile)
                            <img src="{{ $coverFile->temporaryUrl() }}" class="mt-2 h-24 object-contain rounded" />
                        @elseif($editId && ($eb = App\Models\IslamicBook::find($editId)) && $eb?->cover_path)
                            <div class="mt-2 flex items-center gap-2">
                                <img src="{{ Storage::url($eb->cover_path) }}" class="h-16 object-contain rounded" />
                                <span class="text-xs text-base-content/50">Current cover</span>
                            </div>
                        @endif
                    </div>

                    {{-- Book file section --}}
                    <div class="form-control">
                        <label class="label label-text font-semibold">Book File</label>

                        {{-- Select from public/files/ --}}
                        @php $pubFiles = $this->publicFiles(); @endphp
                        @if(!empty($pubFiles))
                            <div class="mb-2">
                                <label class="text-xs text-base-content/50 mb-1 block">
                                    Select from <code class="bg-base-200 px-1 rounded">public/files/</code>
                                    ({{ count($pubFiles) }} file{{ count($pubFiles) > 1 ? 's' : '' }} available)
                                </label>
                                <select wire:model="existingFile" class="select select-bordered select-sm w-full">
                                    <option value="">— none —</option>
                                    @foreach($pubFiles as $f)
                                        <option value="{{ $f }}">{{ $f }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="divider text-xs my-1">OR upload a new file</div>
                        @endif

                        <input type="file" wire:model="bookFile" accept=".pdf,.epub,.doc,.docx"
                               class="file-input file-input-bordered w-full" />
                        <span class="text-xs text-base-content/40 mt-1">PDF, EPUB, DOC — max 100 MB. Uploading a new file overrides the selection above.</span>
                        @error('bookFile') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror

                        {{-- Show currently linked file when editing --}}
                        @if($editId && ($eb2 = App\Models\IslamicBook::find($editId)) && $eb2?->file_path)
                            <div class="mt-1 flex items-center gap-2 text-xs text-base-content/50">
                                <x-icon name="o-document" class="w-4 h-4 text-success" />
                                Current: <strong>{{ $eb2->file_name }}</strong>
                            </div>
                        @endif
                    </div>

                    {{-- Published toggle --}}
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="is_published" class="toggle toggle-primary" id="pub-toggle" />
                        <label for="pub-toggle" class="text-sm font-semibold">Published</label>
                    </div>
                </div>

                <div class="p-5 border-t border-base-200 flex justify-end gap-2">
                    <x-button label="Cancel" wire:click="$set('showModal', false)" class="btn-ghost" />
                    <x-button :label="$editId ? 'Save Changes' : 'Add Book'"
                              icon="o-check"
                              wire:click="save"
                              spinner="save"
                              class="btn-primary" />
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirmation --}}
    @if($showDelete)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
             wire:click.self="$set('showDelete', false)">
            <div class="bg-base-100 rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
                <x-icon name="o-exclamation-triangle" class="w-12 h-12 text-error mx-auto mb-3" />
                <h3 class="font-bold text-lg mb-2">Delete Book?</h3>
                <p class="text-sm text-base-content/60 mb-6">This will permanently remove the book record. Uploaded files will be deleted; files in <code>public/files/</code> will NOT be deleted.</p>
                <div class="flex gap-3 justify-center">
                    <x-button label="Cancel" wire:click="$set('showDelete', false)" class="btn-ghost" />
                    <x-button label="Delete" icon="o-trash" wire:click="delete" spinner="delete" class="btn-error" />
                </div>
            </div>
        </div>
    @endif
</div>
