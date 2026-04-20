<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Hadith')]
class extends Component {
    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public string  $mode          = 'browse'; // browse | search | random
    public string  $collection    = 'bukhari';
    public array   $collections   = [];
    public int     $page          = 1;
    public int     $perPage       = 5;
    public int     $total         = 0;
    public int     $totalPages    = 0;
    public array   $hadiths       = [];
    public string  $searchQuery   = '';
    public array   $searchResults = [];
    public int     $searchTotal   = 0;
    public array   $randomHadith  = [];
    public ?string $error         = null;

    public function mount(): void
    {
        $this->loadCollections();
        $this->load();
    }

    private function api(string $path): ?array
    {
        try {
            $r = Http::timeout(30)
                ->withHeaders(['X-API-Key' => self::KEY, 'Accept' => 'application/json'])
                ->get(self::BASE . $path);
            if ($r->successful() && $r->json('success')) {
                return $r->json('data');
            }
            $this->error = "HTTP {$r->status()}";
            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error = 'Request timed out — the server is slow for this collection. Try again or choose a smaller per-page value.';
            return null;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    public function loadCollections(): void
    {
        $d = $this->api('/api/hadith/collections');
        $this->collections = $d['collections'] ?? [];
    }

    public function load(): void
    {
        $this->error = null;
        $d = $this->api("/api/hadith/{$this->collection}?page={$this->page}&per_page={$this->perPage}");
        if ($d) {
            $this->hadiths    = $d['hadiths']     ?? [];
            $this->total      = $d['total']       ?? 0;
            $this->totalPages = $d['total_pages'] ?? 0;
        }
    }

    public function setCollection(string $key): void
    {
        $this->collection = $key;
        $this->page       = 1;
        $this->mode       = 'browse';
        $this->load();
    }

    public function prevPage(): void
    {
        if ($this->page > 1) { $this->page--; $this->load(); }
    }

    public function nextPage(): void
    {
        if ($this->page < $this->totalPages) { $this->page++; $this->load(); }
    }

    public function loadRandom(): void
    {
        $this->error = null;
        $this->mode  = 'random';
        $d = $this->api('/api/hadith/random');
        $this->randomHadith = $d ?? [];
    }

    public function search(): void
    {
        if (!trim($this->searchQuery)) return;
        $this->error = null;
        $this->mode  = 'search';
        $q = urlencode(trim($this->searchQuery));
        $d = $this->api("/api/hadith/search?q={$q}");
        $this->searchResults = $d['hadiths']     ?? [];
        $this->searchTotal   = $d['total_found'] ?? count($this->searchResults);
    }

    public function setMode(string $m): void
    {
        $this->mode  = $m;
        $this->error = null;
    }
}; ?>

<div>
    <x-header title="Hadith" separator>
        <x-slot:subtitle>
            @if($mode === 'browse' && $total)
                {{ number_format($total) }} hadiths · Page {{ $page }} / {{ $totalPages }}
            @elseif($mode === 'search')
                Search results
            @elseif($mode === 'random')
                Random hadith
            @endif
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Random" icon="o-sparkles" wire:click="loadRandom" spinner="loadRandom" class="btn-ghost btn-sm" />
            <x-button label="Browse" icon="o-list-bullet"
                      wire:click="setMode('browse')"
                      class="btn-ghost btn-sm {{ $mode === 'browse' ? 'btn-active' : '' }}" />
        </x-slot:actions>
    </x-header>

    {{-- Collection tabs --}}
    @if(!empty($collections))
        <div class="flex flex-wrap gap-1 mb-4">
            @foreach($collections as $c)
                <button wire:click="setCollection('{{ $c['key'] }}')"
                        class="btn btn-xs {{ $collection === $c['key'] && $mode === 'browse' ? 'btn-primary' : 'btn-ghost' }}">
                    {{ $c['name'] }}
                    <span class="opacity-50 ml-1">{{ number_format($c['total_hadiths']) }}</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Search bar --}}
    <div class="flex gap-2 mb-6">
        <input type="text" wire:model="searchQuery"
               wire:keydown.enter="search"
               placeholder="Search across all collections — e.g. intention, prayer, charity..."
               class="input input-bordered flex-1 input-sm" />
        <x-button label="Search" icon="o-magnifying-glass" wire:click="search" spinner="search" class="btn-primary btn-sm" />
    </div>

    @if($error)
        <div class="alert alert-error mb-4">
            <x-icon name="o-exclamation-triangle" class="w-5 h-5 shrink-0" />
            <div class="flex-1 text-sm">{{ $error }}</div>
            <x-button label="Retry" icon="o-arrow-path" wire:click="load" spinner="load" class="btn-sm btn-ghost" />
        </div>
    @endif

    {{-- BROWSE --}}
    @if($mode === 'browse')
        <div class="flex items-center gap-2 mb-4">
            <select wire:model="perPage" wire:change="load" class="select select-bordered select-sm">
                <option value="5">5 / page</option>
                <option value="10">10 / page</option>
                <option value="20">20 / page</option>
                <option value="50">50 / page</option>
            </select>
            <x-button label="Reload" icon="o-arrow-path" wire:click="load" spinner="load" class="btn-ghost btn-sm" />
        </div>

        <div class="space-y-4">
            @forelse($hadiths as $h)
                <x-card shadow>
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <x-badge :value="'#' . ($h['hadithnumber'] ?? '')" class="badge-warning badge-lg" />
                        <x-badge :value="$h['collection_name'] ?? ucfirst($collection)" class="badge-ghost badge-sm" />
                        @if(!empty($h['grade']))
                            <x-badge :value="$h['grade']" class="badge-success badge-xs" />
                        @endif
                    </div>
                    @if(!empty($h['arabic']))
                        <p class="font-arabic text-xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-4 mb-3" dir="rtl">
                            {{ $h['arabic'] }}
                        </p>
                    @endif
                    @if(!empty($h['english']))
                        <p class="text-sm text-base-content/80 leading-relaxed">{{ $h['english'] }}</p>
                    @endif
                </x-card>
            @empty
                <div class="flex justify-center py-16">
                    <span class="loading loading-spinner loading-lg"></span>
                </div>
            @endforelse
        </div>

        @if($totalPages > 1)
            <div class="flex justify-center items-center gap-3 mt-6">
                <x-button label="← Prev" wire:click="prevPage" :disabled="$page <= 1" class="btn-ghost btn-sm" />
                <div class="flex gap-1">
                    @foreach(range(max(1, $page - 2), min($totalPages, $page + 2)) as $p)
                        <button wire:click="$set('page', {{ $p }}); $wire.load()"
                                class="btn btn-sm {{ $p == $page ? 'btn-primary' : 'btn-ghost' }}">{{ $p }}</button>
                    @endforeach
                </div>
                <x-button label="Next →" wire:click="nextPage" :disabled="$page >= $totalPages" class="btn-ghost btn-sm" />
            </div>
        @endif

    {{-- SEARCH --}}
    @elseif($mode === 'search')
        @if(!empty($searchResults))
            <div class="text-sm text-base-content/50 mb-4">
                {{ $searchTotal }} results for "<strong>{{ $searchQuery }}</strong>"
            </div>
            <div class="space-y-4">
                @foreach($searchResults as $h)
                    <x-card shadow>
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <x-badge :value="'#' . ($h['hadithnumber'] ?? '')" class="badge-warning badge-lg" />
                            <x-badge :value="$h['collection_name'] ?? ''" class="badge-ghost badge-sm" />
                            @if(!empty($h['grade']))
                                <x-badge :value="$h['grade']" class="badge-success badge-xs" />
                            @endif
                        </div>
                        @if(!empty($h['arabic']))
                            <p class="font-arabic text-xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-4 mb-3" dir="rtl">
                                {{ $h['arabic'] }}
                            </p>
                        @endif
                        @if(!empty($h['english']))
                            <p class="text-sm text-base-content/80 leading-relaxed">{{ $h['english'] }}</p>
                        @endif
                    </x-card>
                @endforeach
            </div>
        @elseif(trim($searchQuery) === '')
            <div class="text-center py-16 text-base-content/40">Enter a keyword to search</div>
        @else
            <div class="text-center py-16 text-base-content/40">No results found for "{{ $searchQuery }}"</div>
        @endif

    {{-- RANDOM --}}
    @elseif($mode === 'random')
        <div class="flex justify-center mb-6">
            <x-button label="Get Another Random Hadith" icon="o-arrow-path" wire:click="loadRandom" spinner="loadRandom" class="btn-primary" />
        </div>
        @if(!empty($randomHadith))
            <x-card shadow class="max-w-2xl mx-auto">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <x-badge :value="'#' . ($randomHadith['hadithnumber'] ?? '')" class="badge-warning badge-lg" />
                    <x-badge :value="$randomHadith['collection_name'] ?? ''" class="badge-ghost" />
                    @if(!empty($randomHadith['grade']))
                        <x-badge :value="$randomHadith['grade']" class="badge-success badge-sm" />
                    @endif
                </div>
                @if(!empty($randomHadith['arabic']))
                    <p class="font-arabic text-xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-4 mb-4" dir="rtl">
                        {{ $randomHadith['arabic'] }}
                    </p>
                @endif
                @if(!empty($randomHadith['english']))
                    <p class="text-base text-base-content/80 leading-relaxed">{{ $randomHadith['english'] }}</p>
                @endif
            </x-card>
        @elseif(empty($error))
            <div class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>
        @endif
    @endif
</div>
