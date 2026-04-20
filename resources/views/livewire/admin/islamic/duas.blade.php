<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Duas')]
class extends Component {
    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public string  $mode          = 'browse'; // browse | search | random
    public array   $duas          = [];
    public array   $categories    = [];
    public string  $activecat     = '';
    public int     $total         = 0;
    public string  $searchQuery   = '';
    public array   $searchResults = [];
    public int     $searchTotal   = 0;
    public array   $randomDua     = [];
    public ?string $error         = null;

    public function mount(): void
    {
        $this->loadCategories();
        $this->load();
    }

    private function api(string $path): ?array
    {
        try {
            $r = Http::timeout(15)
                ->withHeaders(['X-API-Key' => self::KEY, 'Accept' => 'application/json'])
                ->get(self::BASE . $path);
            if ($r->successful() && $r->json('success')) {
                return $r->json('data');
            }
            $this->error = "HTTP {$r->status()}";
            return null;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    public function loadCategories(): void
    {
        $d = $this->api('/api/duas/categories');
        $this->categories = $d['categories'] ?? [];
    }

    public function load(): void
    {
        $this->error = null;
        $d = $this->api('/api/duas');
        if ($d) {
            $this->duas  = $d['duas']  ?? [];
            $this->total = $d['total'] ?? count($this->duas);
            if (empty($this->activecat) && !empty($this->categories)) {
                $this->activecat = $this->categories[0]['id'] ?? '';
            }
        }
    }

    public function setCategory(string $cat): void
    {
        $this->activecat = $cat;
        $this->mode      = 'browse';
    }

    public function loadRandom(): void
    {
        $this->error = null;
        $this->mode  = 'random';
        $d = $this->api('/api/duas/random');
        $this->randomDua = $d ?? [];
    }

    public function search(): void
    {
        if (!trim($this->searchQuery)) return;
        $this->error = null;
        $this->mode  = 'search';
        $q = urlencode(trim($this->searchQuery));
        $d = $this->api("/api/duas/search?q={$q}");
        $this->searchResults = $d['results']       ?? [];
        $this->searchTotal   = $d['results_count'] ?? count($this->searchResults);
    }

    public function setMode(string $m): void
    {
        $this->mode  = $m;
        $this->error = null;
    }

    public function filtered(): array
    {
        if (empty($this->activecat)) return $this->duas;
        return array_values(array_filter($this->duas, fn($d) => ($d['category'] ?? '') === $this->activecat));
    }
}; ?>

<div>
    <x-header title="Duas" subtitle="Daily supplications and prayers" separator>
        <x-slot:actions>
            <x-badge :value="$total . ' duas'" class="badge-primary" />
            <x-button label="Random" icon="o-sparkles" wire:click="loadRandom" spinner="loadRandom" class="btn-ghost btn-sm" />
            <x-button label="Browse" icon="o-list-bullet"
                      wire:click="setMode('browse')"
                      class="btn-ghost btn-sm {{ $mode === 'browse' ? 'btn-active' : '' }}" />
            <x-button label="Refresh" icon="o-arrow-path" wire:click="load" spinner="load" class="btn-ghost btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- Search bar --}}
    <div class="flex gap-2 mb-4">
        <input type="text" wire:model="searchQuery"
               wire:keydown.enter="search"
               placeholder="Search duas — e.g. morning, forgiveness, travel..."
               class="input input-bordered flex-1 input-sm" />
        <x-button label="Search" icon="o-magnifying-glass" wire:click="search" spinner="search" class="btn-primary btn-sm" />
    </div>

    @if($error)
        <x-alert title="API Error" :description="$error" icon="o-exclamation-triangle" class="alert-error mb-4" />
    @endif

    {{-- Category filter (browse mode) --}}
    @if($mode === 'browse' && !empty($categories))
        <div class="flex flex-wrap gap-2 mb-6">
            @foreach($categories as $cat)
                <button wire:click="setCategory('{{ $cat['id'] }}')"
                        class="btn btn-xs {{ $activecat === $cat['id'] ? 'btn-primary' : 'btn-ghost' }}">
                    {{ $cat['name'] }}
                    <span class="opacity-50 ml-1">{{ $cat['count'] }}</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- BROWSE --}}
    @if($mode === 'browse')
        <div class="space-y-4">
            @forelse($this->filtered() as $dua)
                <x-card shadow>
                    <div class="flex justify-between items-start mb-3">
                        <div class="font-semibold">{{ $dua['title'] ?? 'Dua' }}</div>
                        <x-badge :value="ucwords(str_replace('_', ' ', $dua['category'] ?? ''))" class="badge-ghost badge-xs" />
                    </div>
                    @if(!empty($dua['arabic']))
                        <p class="font-arabic text-2xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-4 mb-3" dir="rtl">
                            {{ $dua['arabic'] }}
                        </p>
                    @endif
                    @if(!empty($dua['transliteration']))
                        <p class="text-sm italic text-base-content/50 mb-2">{{ $dua['transliteration'] }}</p>
                    @endif
                    @if(!empty($dua['translation']))
                        <p class="text-sm text-base-content/75 border-l-2 border-base-300 pl-3">{{ $dua['translation'] }}</p>
                    @endif
                    @if(!empty($dua['source']))
                        <div class="text-xs text-base-content/40 mt-2">— {{ $dua['source'] }}</div>
                    @endif
                    @if(!empty($dua['repeat']) && $dua['repeat'] > 1)
                        <x-badge :value="'Repeat × ' . $dua['repeat']" class="badge-ghost badge-xs mt-2" />
                    @endif
                </x-card>
            @empty
                <div class="text-center py-16 text-base-content/40">No duas found</div>
            @endforelse
        </div>

    {{-- SEARCH --}}
    @elseif($mode === 'search')
        @if(!empty($searchResults))
            <div class="text-sm text-base-content/50 mb-4">
                {{ $searchTotal }} results for "<strong>{{ $searchQuery }}</strong>"
            </div>
            <div class="space-y-4">
                @foreach($searchResults as $dua)
                    <x-card shadow>
                        <div class="flex justify-between items-start mb-3">
                            <div class="font-semibold">{{ $dua['title'] ?? 'Dua' }}</div>
                            <x-badge :value="ucwords(str_replace('_', ' ', $dua['category'] ?? ''))" class="badge-ghost badge-xs" />
                        </div>
                        @if(!empty($dua['arabic']))
                            <p class="font-arabic text-2xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-4 mb-3" dir="rtl">
                                {{ $dua['arabic'] }}
                            </p>
                        @endif
                        @if(!empty($dua['transliteration']))
                            <p class="text-sm italic text-base-content/50 mb-2">{{ $dua['transliteration'] }}</p>
                        @endif
                        @if(!empty($dua['translation']))
                            <p class="text-sm text-base-content/75 border-l-2 border-base-300 pl-3">{{ $dua['translation'] }}</p>
                        @endif
                        @if(!empty($dua['source']))
                            <div class="text-xs text-base-content/40 mt-2">— {{ $dua['source'] }}</div>
                        @endif
                    </x-card>
                @endforeach
            </div>
        @elseif(trim($searchQuery) === '')
            <div class="text-center py-16 text-base-content/40">Enter a keyword to search duas</div>
        @else
            <div class="text-center py-16 text-base-content/40">No results for "{{ $searchQuery }}"</div>
        @endif

    {{-- RANDOM --}}
    @elseif($mode === 'random')
        <div class="flex justify-center mb-6">
            <x-button label="Get Another Random Dua" icon="o-arrow-path" wire:click="loadRandom" spinner="loadRandom" class="btn-primary" />
        </div>
        @if(!empty($randomDua))
            <x-card shadow class="max-w-2xl mx-auto">
                <div class="flex justify-between items-start mb-4">
                    <div class="font-semibold text-lg">{{ $randomDua['title'] ?? 'Dua' }}</div>
                    <x-badge :value="$randomDua['category_info']['name'] ?? ucwords(str_replace('_', ' ', $randomDua['category'] ?? ''))"
                             class="badge-primary badge-sm" />
                </div>
                @if(!empty($randomDua['arabic']))
                    <p class="font-arabic text-3xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-4 mb-4" dir="rtl">
                        {{ $randomDua['arabic'] }}
                    </p>
                @endif
                @if(!empty($randomDua['transliteration']))
                    <p class="text-sm italic text-base-content/50 mb-3">{{ $randomDua['transliteration'] }}</p>
                @endif
                @if(!empty($randomDua['translation']))
                    <p class="text-base text-base-content/80 border-l-4 border-base-300 pl-4 mb-3">{{ $randomDua['translation'] }}</p>
                @endif
                @if(!empty($randomDua['source']))
                    <div class="text-xs text-base-content/40">— {{ $randomDua['source'] }}</div>
                @endif
                @if(!empty($randomDua['repeat']) && $randomDua['repeat'] > 1)
                    <x-badge :value="'Repeat × ' . $randomDua['repeat']" class="badge-ghost badge-xs mt-2" />
                @endif
            </x-card>
        @elseif(empty($error))
            <div class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>
        @endif
    @endif
</div>
