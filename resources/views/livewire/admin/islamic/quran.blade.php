<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Quran')]
class extends Component {
    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public string  $mode            = 'browse'; // browse | surah | search | random | juz
    public array   $surahs          = [];
    public array   $surahInfo       = [];
    public array   $verses          = [];
    public array   $surahAudio      = [];
    public int     $surahNumber     = 1;
    public int     $selectedReciter = 1;
    public array   $reciters        = [];
    public string  $searchQuery     = '';
    public array   $searchResults   = [];
    public int     $searchCount     = 0;
    public array   $randomVerse     = [];
    public int     $juzNumber       = 1;
    public array   $juzVerses       = [];
    public int     $juzTotal        = 0;
    public string  $tafsirVerse     = '';
    public string  $tafsirText      = '';
    public string  $tafsirName      = '';
    public bool    $showTafsir      = false;
    public ?string $error           = null;

    public function mount(): void
    {
        $this->loadSurahs();
        $this->loadReciters();
    }

    private function api(string $path): ?array
    {
        try {
            $r = Http::timeout(20)
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

    public function loadSurahs(): void
    {
        $this->error = null;
        $d = $this->api('/api/quran/surahs');
        $this->surahs = $d['surahs'] ?? [];
    }

    public function loadReciters(): void
    {
        $d = $this->api('/api/quran/reciters');
        $this->reciters = $d['reciters'] ?? [];
    }

    public function openSurah(int $n): void
    {
        $this->surahNumber = $n;
        $this->mode        = 'surah';
        $this->tafsirVerse = '';
        $this->tafsirText  = '';
        $this->showTafsir  = false;
        $this->loadSurah();
    }

    public function loadSurah(): void
    {
        $this->error = null;
        $d = $this->api("/api/quran/surah/{$this->surahNumber}");
        if ($d) {
            $this->surahInfo  = $d['surah']  ?? [];
            $this->verses     = $d['verses'] ?? [];
            $this->surahAudio = $d['audio']  ?? [];
        }
    }

    public function loadRandom(): void
    {
        $this->error = null;
        $this->mode  = 'random';
        $d = $this->api('/api/quran/random');
        $this->randomVerse = $d ?? [];
    }

    public function search(): void
    {
        if (!trim($this->searchQuery)) return;
        $this->error = null;
        $this->mode  = 'search';
        $q = urlencode(trim($this->searchQuery));
        $d = $this->api("/api/quran/search?q={$q}");
        $this->searchResults = $d['results']       ?? [];
        $this->searchCount   = $d['results_count'] ?? count($this->searchResults);
    }

    public function loadJuz(): void
    {
        $this->error = null;
        $this->mode  = 'juz';
        $d = $this->api("/api/quran/juz/{$this->juzNumber}");
        if ($d) {
            $this->juzVerses = $d['verses']       ?? [];
            $this->juzTotal  = $d['total_verses'] ?? 0;
        }
    }

    public function loadTafsir(int $surah, int $ayah): void
    {
        $this->error       = null;
        $this->tafsirVerse = "{$surah}:{$ayah}";
        $this->tafsirText  = '';
        $this->showTafsir  = true;
        $d = $this->api("/api/tafsir/ibn_kathir/surah/{$surah}/ayah/{$ayah}");
        if ($d) {
            $this->tafsirText = $d['tafsir']['text'] ?? '';
            $this->tafsirName = $d['tafsir']['name'] ?? 'Ibn Kathir';
        }
    }

    public function setMode(string $m): void
    {
        $this->mode  = $m;
        $this->error = null;
    }
}; ?>

<div>
    <x-header title="Quran" subtitle="The Holy Quran — 114 Surahs · 6,236 Verses" separator>
        <x-slot:actions>
            <x-button label="Random Verse" icon="o-sparkles" wire:click="loadRandom" spinner="loadRandom" class="btn-ghost btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- Mode navigation --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <x-button label="Surahs" icon="o-list-bullet"
                  wire:click="setMode('browse')"
                  class="btn-sm {{ $mode === 'browse' ? 'btn-primary' : 'btn-ghost' }}" />

        @if($mode === 'surah' && !empty($surahInfo))
            <x-button :label="($surahInfo['name_english'] ?? 'Surah') . ' (' . ($surahInfo['number'] ?? $surahNumber) . ')'"
                      icon="o-book-open"
                      class="btn-sm btn-primary" />
        @elseif($mode === 'surah')
            <x-button label="Surah" icon="o-book-open" class="btn-sm btn-primary" />
        @endif

        <x-button label="Search" icon="o-magnifying-glass"
                  wire:click="setMode('search')"
                  class="btn-sm {{ $mode === 'search' ? 'btn-primary' : 'btn-ghost' }}" />

        <x-button label="By Juz" icon="o-squares-2x2"
                  wire:click="$set('mode','juz'); $wire.loadJuz()"
                  class="btn-sm {{ $mode === 'juz' ? 'btn-primary' : 'btn-ghost' }}" />

        @if($mode === 'random')
            <x-button label="Random" icon="o-sparkles" class="btn-sm btn-secondary" />
        @endif
    </div>

    @if($error)
        <x-alert title="Error" :description="$error" icon="o-exclamation-triangle" class="alert-error mb-4" />
    @endif

    {{-- Tafsir overlay --}}
    @if($showTafsir)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
             wire:click.self="$set('showTafsir', false)">
            <div class="bg-base-100 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between p-4 border-b border-base-200 shrink-0">
                    <div>
                        <div class="font-semibold">{{ $tafsirName ?: 'Ibn Kathir (Abridged)' }}</div>
                        <div class="text-xs text-base-content/50">Verse {{ $tafsirVerse }}</div>
                    </div>
                    <x-button icon="o-x-mark" wire:click="$set('showTafsir', false)" class="btn-ghost btn-circle btn-sm" />
                </div>
                <div class="overflow-y-auto p-4">
                    @if($tafsirText)
                        <p class="text-sm text-base-content/80 leading-relaxed whitespace-pre-line">{{ $tafsirText }}</p>
                    @else
                        <div class="flex justify-center py-8">
                            <span class="loading loading-spinner loading-lg"></span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- BROWSE: surah grid                                           --}}
    {{-- ============================================================ --}}
    @if($mode === 'browse')
        @if(empty($surahs))
            <div class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2">
                @foreach($surahs as $s)
                    <div wire:click="openSurah({{ $s['number'] }})"
                         class="cursor-pointer p-3 rounded-xl border border-base-200 bg-base-100 hover:border-primary/50 hover:shadow-md transition-all text-center group">
                        <div class="text-xs text-base-content/40 mb-1">#{{ $s['number'] }}</div>
                        <div class="font-arabic text-xl font-bold text-primary leading-tight mb-1" dir="rtl">{{ $s['name_arabic'] }}</div>
                        <div class="text-xs font-semibold group-hover:text-primary transition-colors">{{ $s['name_english'] }}</div>
                        <div class="text-xs text-base-content/50 mb-1">{{ $s['name_translation'] }}</div>
                        <div class="flex justify-center gap-1">
                            <x-badge :value="$s['verses_count'] . 'v'" class="badge-ghost badge-xs" />
                            <x-badge :value="ucfirst($s['revelation_place'])" class="badge-ghost badge-xs" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    {{-- ============================================================ --}}
    {{-- SURAH: verses                                                --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'surah')
        @if(!empty($surahInfo))
            <x-card shadow class="mb-6 bg-primary/5 border border-primary/20">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="font-arabic text-4xl font-bold text-primary mb-1" dir="rtl">{{ $surahInfo['name_arabic'] }}</div>
                        <div class="text-xl font-semibold">{{ $surahInfo['name_english'] }}</div>
                        <div class="text-base-content/60 mb-2">{{ $surahInfo['name_translation'] }}</div>
                        <div class="flex flex-wrap gap-2">
                            <x-badge :value="($surahInfo['verses_count'] ?? 0) . ' verses'" class="badge-primary badge-sm" />
                            <x-badge :value="ucfirst($surahInfo['revelation_place'] ?? '')" class="badge-ghost badge-sm" />
                        </div>
                    </div>
                    @if(!empty($surahAudio))
                        <div class="flex flex-col gap-2 min-w-[16rem]">
                            <label class="text-xs text-base-content/50 uppercase tracking-wider">Reciter</label>
                            <select wire:model="selectedReciter" class="select select-bordered select-sm">
                                @foreach($surahAudio as $a)
                                    <option value="{{ $a['reciter_id'] }}">{{ $a['reciter'] }} ({{ $a['style'] }})</option>
                                @endforeach
                            </select>
                            @php
                                $selAudio = collect($surahAudio)->firstWhere('reciter_id', (int) $selectedReciter);
                            @endphp
                            @if(!empty($selAudio['surah_audio']))
                                <audio controls class="w-full">
                                    <source src="{{ $selAudio['surah_audio'] }}" type="audio/mpeg">
                                </audio>
                            @endif
                        </div>
                    @endif
                </div>
            </x-card>

            <div class="flex justify-between mb-4">
                @if(($surahInfo['number'] ?? 1) > 1)
                    <x-button label="← Previous" wire:click="openSurah({{ ($surahInfo['number'] ?? 1) - 1 }})" spinner class="btn-ghost btn-sm" />
                @else
                    <div></div>
                @endif
                @if(($surahInfo['number'] ?? 114) < 114)
                    <x-button label="Next →" wire:click="openSurah({{ ($surahInfo['number'] ?? 1) + 1 }})" spinner class="btn-ghost btn-sm" />
                @endif
            </div>
        @endif

        <div class="space-y-3">
            @forelse($verses as $v)
                @php [$sn, $an] = explode(':', $v['verse_key']); @endphp
                <x-card shadow>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 text-center">
                            <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-sm font-bold text-primary">
                                {{ $v['ayah'] }}
                            </div>
                            <div class="text-xs text-base-content/30 mt-0.5">{{ $v['verse_key'] }}</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-arabic text-2xl text-right leading-loose border-r-4 border-primary/30 pr-4 mb-2" dir="rtl">
                                {{ $v['arabic'] }}
                            </p>
                            @if(!empty($v['transliteration']))
                                <p class="text-sm italic text-base-content/50 mb-1">{{ $v['transliteration'] }}</p>
                            @endif
                            @if(!empty($v['translations']['sahih_international']))
                                <p class="text-sm text-base-content/80 leading-relaxed">{{ $v['translations']['sahih_international'] }}</p>
                            @endif
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                @if(!empty($v['audio']['ayah_audio']))
                                    <audio controls class="h-7 w-44">
                                        <source src="{{ $v['audio']['ayah_audio'] }}" type="audio/mpeg">
                                    </audio>
                                @endif
                                <x-button label="Tafsir" icon="o-book-open"
                                          wire:click="loadTafsir({{ (int)$sn }}, {{ (int)$an }})"
                                          spinner="loadTafsir"
                                          class="btn-ghost btn-xs" />
                            </div>
                        </div>
                    </div>
                </x-card>
            @empty
                <div class="flex justify-center py-16">
                    <span class="loading loading-spinner loading-lg"></span>
                </div>
            @endforelse
        </div>

    {{-- ============================================================ --}}
    {{-- SEARCH                                                       --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'search')
        <div class="flex gap-2 mb-6">
            <input type="text" wire:model="searchQuery"
                   wire:keydown.enter="search"
                   placeholder="Search the Quran — e.g. mercy, patience, light..."
                   class="input input-bordered flex-1" />
            <x-button label="Search" icon="o-magnifying-glass" wire:click="search" spinner="search" class="btn-primary" />
        </div>

        @if(!empty($searchResults))
            <div class="text-sm text-base-content/50 mb-4">
                {{ $searchCount }} results for "<strong>{{ $searchQuery }}</strong>"
            </div>
            <div class="space-y-3">
                @foreach($searchResults as $r)
                    <x-card shadow>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 text-center min-w-[4.5rem]">
                                <x-badge :value="$r['verse_key']" class="badge-primary badge-sm" />
                                <div class="text-xs text-base-content/50 mt-1">{{ $r['surah_name'] }}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-arabic text-xl text-right leading-loose border-r-4 border-primary/20 pr-3 mb-2" dir="rtl">
                                    {{ $r['arabic'] }}
                                </p>
                                <p class="text-sm text-base-content/80 leading-relaxed">{{ $r['translation'] }}</p>
                                <div class="text-xs text-base-content/40 mt-1">via {{ $r['translation_source'] ?? 'Sahih International' }}</div>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @elseif(trim($searchQuery) === '')
            <div class="text-center py-16 text-base-content/40">Enter a keyword to search the Quran</div>
        @else
            <div class="text-center py-16 text-base-content/40">No results found for "{{ $searchQuery }}"</div>
        @endif

    {{-- ============================================================ --}}
    {{-- JUZ                                                          --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'juz')
        <div class="flex items-center gap-3 mb-6 flex-wrap">
            <span class="font-semibold text-sm">Select Juz:</span>
            <div class="flex flex-wrap gap-1">
                @for($j = 1; $j <= 30; $j++)
                    <button wire:click="$set('juzNumber', {{ $j }}); $wire.loadJuz()"
                            class="btn btn-xs {{ $juzNumber == $j ? 'btn-primary' : 'btn-ghost' }}">{{ $j }}</button>
                @endfor
            </div>
        </div>
        @if($juzTotal)
            <div class="text-sm text-base-content/50 mb-4">{{ number_format($juzTotal) }} verses in Juz {{ $juzNumber }}</div>
        @endif
        <div class="space-y-2">
            @forelse($juzVerses as $v)
                <x-card shadow>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 text-center min-w-[3.5rem]">
                            <x-badge :value="$v['verse_key']" class="badge-ghost badge-xs" />
                            @if(!empty($v['surah_name']))
                                <div class="text-xs text-base-content/40 mt-0.5">{{ $v['surah_name'] }}</div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-arabic text-xl text-right leading-loose border-r-4 border-primary/20 pr-3 mb-1" dir="rtl">
                                {{ $v['arabic'] }}
                            </p>
                            @if(!empty($v['transliteration']))
                                <p class="text-xs italic text-base-content/40 mb-1">{{ $v['transliteration'] }}</p>
                            @endif
                            @if(!empty($v['translations']['sahih_international']))
                                <p class="text-sm text-base-content/70">{{ $v['translations']['sahih_international'] }}</p>
                            @endif
                        </div>
                    </div>
                </x-card>
            @empty
                <div class="flex justify-center py-16">
                    <span class="loading loading-spinner loading-lg"></span>
                </div>
            @endforelse
        </div>

    {{-- ============================================================ --}}
    {{-- RANDOM                                                       --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'random')
        <div class="flex justify-center mb-8">
            <x-button label="Get Another Random Verse" icon="o-arrow-path" wire:click="loadRandom" spinner="loadRandom" class="btn-primary btn-lg" />
        </div>

        @if(!empty($randomVerse['verse']))
            @php
                $rv = $randomVerse['verse'];
                $rs = $randomVerse['surah'];
                $ra = $randomVerse['audio'][0] ?? null;
            @endphp
            <x-card shadow class="max-w-2xl mx-auto">
                <div class="text-center mb-6">
                    <x-badge :value="$rv['verse_key']" class="badge-primary badge-lg" />
                    <div class="text-base text-base-content/60 mt-2">
                        {{ $rs['name_english'] ?? '' }} — {{ $rs['name_translation'] ?? '' }}
                    </div>
                </div>
                <p class="font-arabic text-3xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-4 mb-4" dir="rtl">
                    {{ $rv['arabic'] }}
                </p>
                @if(!empty($rv['transliteration']))
                    <p class="text-sm italic text-base-content/50 mb-3">{{ $rv['transliteration'] }}</p>
                @endif
                @if(!empty($rv['translations']['sahih_international']))
                    <p class="text-base text-base-content/80 leading-relaxed border-l-4 border-base-300 pl-4 mb-3">
                        {{ $rv['translations']['sahih_international'] }}
                    </p>
                @endif
                @if(!empty($rv['translations']['pickthall']))
                    <p class="text-sm text-base-content/50 italic mb-4">— {{ $rv['translations']['pickthall'] }}</p>
                @endif
                @if($ra && !empty($ra['ayah_audio']))
                    <div class="mb-4">
                        <div class="text-xs text-base-content/40 mb-1">{{ $ra['reciter'] ?? '' }}</div>
                        <audio controls class="w-full">
                            <source src="{{ $ra['ayah_audio'] }}" type="audio/mpeg">
                        </audio>
                    </div>
                @endif
                @if(!empty($rs['number']))
                    <div class="flex justify-center">
                        <x-button :label="'Open ' . ($rs['name_english'] ?? 'Surah')"
                                  icon="o-book-open"
                                  wire:click="openSurah({{ (int)$rs['number'] }})"
                                  class="btn-ghost btn-sm" />
                    </div>
                @endif
            </x-card>
        @elseif(empty($error))
            <div class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>
        @endif
    @endif
</div>
