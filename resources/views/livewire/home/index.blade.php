<?php

use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Course;

new
#[Layout('components.layouts.public')]
#[Title('Bienvenue — EduPlatform')]
class extends Component
{
    public int    $asmaPage      = 1;
    public int    $selectedSurah = 1;
    public array  $currentSurah  = [];
    public string $quranTab      = 'verse';  // verse | browser | search
    public string $searchQuery   = '';
    public array  $searchResults = [];

    private const API_KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    private const API_BASE = 'https://ummahapi.com/api';

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect('/dashboard', navigate: false);
            return;
        }
    }

    /* ── Load Surah ──────────────────────────── */
    public function loadSurah(int $n): void
    {
        $this->selectedSurah = $n;
        $this->currentSurah  = Cache::remember("surah_{$n}", 86400, function () use ($n) {
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(10)
                      ->get(self::API_BASE . "/quran/surah/{$n}");
            return $r->successful() ? ($r->json()['data'] ?? []) : [];
        });
        $this->quranTab = 'browser';
    }

    /* ── Search Quran ────────────────────────── */
    public function searchQuran(): void
    {
        if (strlen(trim($this->searchQuery)) < 3) return;

        $q = trim($this->searchQuery);
        $this->searchResults = Cache::remember("quran_search_" . md5($q), 3600, function () use ($q) {
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(10)
                      ->get(self::API_BASE . "/quran/search", ['q' => $q, 'limit' => 8]);
            return $r->successful() ? ($r->json()['data']['results'] ?? $r->json()['data'] ?? []) : [];
        });
        $this->quranTab = 'search';
    }

    /* ── Asma Pagination ─────────────────────── */
    public function nextAsma(): void { if ($this->asmaPage < 11) $this->asmaPage++; }
    public function prevAsma(): void { if ($this->asmaPage > 1)  $this->asmaPage--; }

    /* ── with() ──────────────────────────────── */
    public function with(): array
    {
        $today = now()->format('Y-m-d');
        $doy   = now()->dayOfYear;

        // ── Verse of the Day
        $verseOfDay = Cache::remember("verse_of_day_{$today}", 86400, function () use ($doy) {
            $surahNum = ($doy % 10) + 1;   // cycle through first 10 surahs
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(10)
                      ->get(self::API_BASE . "/quran/surah/{$surahNum}");
            if (!$r->successful()) return [];
            $data   = $r->json()['data'];
            $verses = $data['verses'] ?? [];
            $surah  = $data['surah']  ?? [];
            $idx    = $doy % max(1, count($verses));
            return ['verse' => $verses[$idx] ?? null, 'surah' => $surah];
        });

        // ── Hadith of the Day
        $hadithOfDay = Cache::remember("hadith_of_day_{$today}", 86400, function () use ($doy) {
            $page = (int) (($doy * 3) % 152) + 1;
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(10)
                      ->get(self::API_BASE . "/hadith/bukhari", ['limit' => 1, 'page' => $page]);
            if (!$r->successful()) return [];
            return $r->json()['data']['hadiths'][0] ?? [];
        });

        // ── Hijri Date
        $hijriDate = Cache::remember("hijri_{$today}", 86400, function () {
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(5)
                      ->get(self::API_BASE . "/hijri-date");
            return $r->successful() ? ($r->json()['data'] ?? []) : [];
        });

        // ── Asma ul Husna (all 99)
        $asmaAll = Cache::remember('asma_ul_husna', 86400, function () {
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(10)
                      ->get(self::API_BASE . "/asma-ul-husna");
            return $r->successful() ? ($r->json()['data']['names'] ?? []) : [];
        });

        // ── Daily Dua
        $dailyDua = Cache::remember("dua_{$today}", 86400, function () use ($doy) {
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(10)
                      ->get(self::API_BASE . "/duas");
            if (!$r->successful()) return [];
            $duas = $r->json()['data']['duas'] ?? [];
            return $duas[$doy % max(1, count($duas))] ?? [];
        });

        // ── Surah List (names from first 114 surahs info cache)
        $surahNames = Cache::remember('surah_names', 86400, function () {
            $names = [];
            $r = Http::withHeaders(['X-API-Key' => self::API_KEY])->timeout(10)
                      ->get(self::API_BASE . "/quran/surah/1");
            // We only have individual surah endpoints; build a partial list statically
            return $names;
        });

        // ── Public Courses
        $courses = Course::where('status', 'published')
            ->with(['teacher.user', 'subject'])
            ->withCount('enrollments')
            ->latest()
            ->take(6)
            ->get();

        // Asma page slice (9 per page)
        $asmaSlice = array_slice($asmaAll, ($this->asmaPage - 1) * 9, 9);

        // Current surah data (load on demand via loadSurah())
        if (empty($this->currentSurah) && $this->quranTab === 'browser') {
            $this->loadSurah($this->selectedSurah);
        }

        return compact(
            'verseOfDay', 'hadithOfDay', 'hijriDate',
            'asmaSlice', 'dailyDua', 'courses'
        );
    }
}; ?>

<div>

{{-- ═══════════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════════ --}}
<section class="islamic-pattern relative overflow-hidden bg-gradient-to-br from-primary/5 via-base-100 to-secondary/5 pt-16 pb-20">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

            {{-- Left: Text --}}
            <div class="order-2 lg:order-1">
                <div class="inline-flex items-center gap-2 bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full mb-5">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                    Plateforme islamique & académique
                </div>
                <h1 class="text-4xl lg:text-5xl font-bold leading-tight mb-4">
                    Apprenez, Grandissez<br>
                    <span class="text-primary">avec la connaissance</span>
                </h1>
                <p class="text-base-content/60 text-lg mb-8 leading-relaxed">
                    Accédez au Coran, aux Hadiths, et à des centaines de cours académiques.
                    Une plateforme complète pour l'étudiant musulman.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg shadow-lg shadow-primary/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Commencer gratuitement
                    </a>
                    <a href="{{ route('courses') }}" class="btn btn-ghost btn-lg border border-base-300">
                        Voir les cours
                    </a>
                </div>
                <div class="flex items-center gap-6 mt-8 text-sm text-base-content/50">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Coran complet + audio
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Hadiths authentiques
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Cours en ligne
                    </div>
                </div>
            </div>

            {{-- Right: Floating Islamic card --}}
            <div class="order-1 lg:order-2 flex justify-center">
                <div class="relative">
                    <div class="w-72 h-72 rounded-3xl bg-gradient-to-br from-primary to-secondary shadow-2xl shadow-primary/30 flex items-center justify-center p-8">
                        <div class="text-center text-white">
                            <p class="font-amiri text-5xl leading-relaxed mb-3" dir="rtl">بِسْمِ ٱللَّهِ</p>
                            <p class="font-amiri text-3xl leading-relaxed" dir="rtl">ٱلرَّحْمَـٰنِ ٱلرَّحِيمِ</p>
                            <p class="text-white/60 text-xs mt-4">Au nom d'Allah, le Tout Miséricordieux</p>
                        </div>
                    </div>
                    {{-- Floating badges --}}
                    <div class="absolute -top-3 -right-3 bg-warning text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                        🌙 Coran
                    </div>
                    <div class="absolute -bottom-3 -left-3 bg-success text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                        📚 Hadith
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     HIJRI DATE BAR
═══════════════════════════════════════════════════════════════ --}}
@if(!empty($hijriDate))
<div class="bg-primary text-primary-content py-3">
    <div class="container mx-auto px-4 max-w-6xl flex flex-wrap items-center justify-center gap-6 text-sm">
        <div class="flex items-center gap-2">
            <span class="opacity-70">📅 Aujourd'hui:</span>
            <span class="font-semibold">{{ $hijriDate['gregorian']['formatted'] ?? '' }}</span>
        </div>
        <div class="opacity-30">|</div>
        <div class="flex items-center gap-2">
            <span class="opacity-70">🌙 Calendrier hégirien:</span>
            <span class="font-semibold" dir="rtl">{{ $hijriDate['hijri']['formatted'] ?? '' }}</span>
        </div>
        @if(!empty($hijriDate['hijri']['month_name']))
        <div class="opacity-30">|</div>
        <div class="flex items-center gap-2">
            <span class="opacity-70">Mois:</span>
            <span class="font-semibold">{{ $hijriDate['hijri']['month_name'] }}</span>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     QURAN SECTION
═══════════════════════════════════════════════════════════════ --}}
<section id="quran" class="py-16 bg-base-100">
    <div class="container mx-auto px-4 max-w-6xl">

        {{-- Section Header --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 text-primary font-semibold text-sm mb-2">
                <span class="w-8 h-px bg-primary"></span>
                القرآن الكريم
                <span class="w-8 h-px bg-primary"></span>
            </div>
            <h2 class="text-3xl font-bold">Le Saint Coran</h2>
            <p class="text-base-content/60 mt-2">Lisez, écoutez et explorez le Coran avec traductions</p>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-2 mb-6 border-b border-base-200 pb-0">
            <button wire:click="$set('quranTab','verse')"
                    class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition-all
                           {{ $quranTab === 'verse' ? 'bg-primary text-white shadow' : 'text-base-content/60 hover:text-primary' }}">
                📖 Verset du Jour
            </button>
            <button wire:click="$set('quranTab','browser')"
                    class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition-all
                           {{ $quranTab === 'browser' ? 'bg-primary text-white shadow' : 'text-base-content/60 hover:text-primary' }}">
                🔍 Parcourir
            </button>
            <button wire:click="$set('quranTab','search')"
                    class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition-all
                           {{ $quranTab === 'search' ? 'bg-primary text-white shadow' : 'text-base-content/60 hover:text-primary' }}">
                🔎 Rechercher
            </button>
        </div>

        {{-- Tab: Verse of the Day --}}
        @if($quranTab === 'verse' && !empty($verseOfDay['verse']))
        @php $verse = $verseOfDay['verse']; $surah = $verseOfDay['surah']; @endphp
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main verse card --}}
            <div class="lg:col-span-2 bg-gradient-to-br from-primary/5 to-secondary/5 rounded-2xl p-8 border border-primary/10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <span class="badge badge-primary badge-soft">Verset du Jour</span>
                        <p class="text-xs text-base-content/50 mt-1">{{ $surah['name_english'] ?? '' }} ({{ $surah['name_arabic'] ?? '' }}) — Verset {{ $verse['ayah'] ?? '' }}</p>
                    </div>
                    @if(!empty($verse['audio']['ayah_audio']))
                    <button onclick="toggleAudio('verse_audio','{{ $verse['audio']['ayah_audio'] }}')"
                            class="btn btn-circle btn-primary btn-sm shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                    @endif
                </div>

                {{-- Arabic Text --}}
                <div class="bg-white/50 rounded-xl p-6 mb-5 text-center">
                    <p class="arabic-text text-3xl leading-loose text-base-content font-amiri" dir="rtl">
                        {{ $verse['arabic'] ?? '' }}
                    </p>
                </div>

                {{-- Transliteration --}}
                @if(!empty($verse['transliteration']))
                <p class="text-base-content/50 text-sm italic text-center mb-4">
                    {{ $verse['transliteration'] }}
                </p>
                @endif

                {{-- Translations --}}
                <div class="space-y-2">
                    @if(!empty($verse['translations']['french']))
                    <div class="bg-white/50 rounded-lg p-3">
                        <span class="text-xs font-semibold text-primary uppercase tracking-wide">🇫🇷 Français</span>
                        <p class="text-sm text-base-content/80 mt-1">{{ $verse['translations']['french'] }}</p>
                    </div>
                    @elseif(!empty($verse['translations']['sahih_international']))
                    <div class="bg-white/50 rounded-lg p-3">
                        <span class="text-xs font-semibold text-primary uppercase tracking-wide">🇬🇧 English</span>
                        <p class="text-sm text-base-content/80 mt-1">{{ $verse['translations']['sahih_international'] }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Surah quick navigation --}}
            <div class="bg-base-100 rounded-2xl border border-base-200 p-5">
                <h3 class="font-semibold text-sm mb-4 text-base-content/70">Parcourir les Sourates</h3>
                <div class="grid grid-cols-4 gap-1.5 max-h-72 overflow-y-auto">
                    @for($i = 1; $i <= 114; $i++)
                    <button wire:click="loadSurah({{ $i }})"
                            class="btn btn-xs {{ $selectedSurah === $i ? 'btn-primary' : 'btn-ghost border border-base-200' }}">
                        {{ $i }}
                    </button>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Tab: Browser --}}
        @elseif($quranTab === 'browser')
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Surah Grid --}}
            <div class="bg-base-100 rounded-2xl border border-base-200 p-4">
                <h3 class="font-semibold text-sm mb-3 text-base-content/60">Choisir une Sourate</h3>
                <div class="grid grid-cols-4 gap-1 max-h-96 overflow-y-auto">
                    @for($i = 1; $i <= 114; $i++)
                    <button wire:click="loadSurah({{ $i }})"
                            class="btn btn-xs {{ $selectedSurah === $i ? 'btn-primary' : 'btn-ghost border border-base-200' }}">
                        {{ $i }}
                    </button>
                    @endfor
                </div>
            </div>

            {{-- Verses --}}
            <div class="lg:col-span-3">
                @if(!empty($currentSurah))
                <div class="flex items-center gap-3 mb-5 p-4 bg-primary/5 rounded-xl">
                    <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">
                        {{ $currentSurah['surah']['number'] ?? $selectedSurah }}
                    </div>
                    <div>
                        <h3 class="font-bold">{{ $currentSurah['surah']['name_english'] ?? '' }}</h3>
                        <p class="text-xs text-base-content/50">
                            {{ $currentSurah['surah']['name_arabic'] ?? '' }} •
                            {{ $currentSurah['total_verses'] ?? count($currentSurah['verses'] ?? []) }} versets •
                            {{ $currentSurah['surah']['revelation_place'] ?? '' }}
                        </p>
                    </div>
                    @if(!empty($currentSurah['audio'][0]['surah_audio']))
                    <button onclick="toggleAudio('surah_audio','{{ $currentSurah['audio'][0]['surah_audio'] }}')"
                            class="btn btn-primary btn-sm ms-auto gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        Écouter
                    </button>
                    @endif
                </div>

                <div class="space-y-3 max-h-[32rem] overflow-y-auto pe-2">
                    @foreach($currentSurah['verses'] ?? [] as $v)
                    <div class="bg-base-100 border border-base-200 rounded-xl p-4 hover:border-primary/30 hover:bg-primary/5 transition-all">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <span class="w-7 h-7 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center shrink-0">{{ $v['ayah'] }}</span>
                            @if(!empty($v['audio']['ayah_audio']))
                            <button onclick="toggleAudio('ayah_{{ $v['ayah'] }}','{{ $v['audio']['ayah_audio'] }}')"
                                    class="btn btn-circle btn-ghost btn-xs opacity-60 hover:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            @endif
                        </div>
                        <p class="arabic-text text-xl leading-loose mb-2" dir="rtl">{{ $v['arabic'] ?? '' }}</p>
                        @if(!empty($v['transliteration']))
                        <p class="text-xs text-base-content/40 italic mb-1">{{ $v['transliteration'] }}</p>
                        @endif
                        @if(!empty($v['translations']['french']))
                        <p class="text-sm text-base-content/70">{{ $v['translations']['french'] }}</p>
                        @elseif(!empty($v['translations']['sahih_international']))
                        <p class="text-sm text-base-content/70">{{ $v['translations']['sahih_international'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <div class="text-5xl mb-3">📖</div>
                    <p>Sélectionnez une sourate pour commencer</p>
                    <button wire:click="loadSurah(1)" class="btn btn-primary btn-sm mt-4">Al-Fatiha (1)</button>
                </div>
                @endif
            </div>
        </div>

        {{-- Tab: Search --}}
        @elseif($quranTab === 'search')
        <div class="max-w-2xl mx-auto">
            <div class="flex gap-3 mb-6">
                <input wire:model.lazy="searchQuery"
                       wire:keydown.enter="searchQuran"
                       type="text"
                       placeholder="Rechercher dans le Coran (ex: miséricorde, lumière...)"
                       class="input input-bordered flex-1" />
                <button wire:click="searchQuran" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>🔍 Rechercher</span>
                    <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                </button>
            </div>

            @if(!empty($searchResults))
            <div class="space-y-4">
                @foreach($searchResults as $result)
                <div class="bg-base-100 border border-base-200 rounded-xl p-4">
                    @if(!empty($result['arabic']))
                    <p class="arabic-text text-xl leading-loose mb-2" dir="rtl">{{ $result['arabic'] }}</p>
                    @endif
                    @if(!empty($result['translations']['sahih_international']))
                    <p class="text-sm text-base-content/70">{{ $result['translations']['sahih_international'] }}</p>
                    @endif
                    @if(!empty($result['verse_key']))
                    <span class="badge badge-soft badge-primary mt-2">{{ $result['verse_key'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            @elseif(!empty($searchQuery))
            <div class="text-center py-12 text-base-content/40">
                <p>Aucun résultat pour « {{ $searchQuery }} »</p>
            </div>
            @else
            <div class="text-center py-12 text-base-content/30">
                <div class="text-6xl mb-3">🔍</div>
                <p>Entrez un mot-clé pour chercher dans le Coran</p>
            </div>
            @endif
        </div>
        @endif

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     HADITH OF THE DAY
═══════════════════════════════════════════════════════════════ --}}
@if(!empty($hadithOfDay))
<section id="hadith" class="py-16 bg-base-200/50">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 text-secondary font-semibold text-sm mb-2">
                <span class="w-8 h-px bg-secondary"></span>
                الحديث الشريف
                <span class="w-8 h-px bg-secondary"></span>
            </div>
            <h2 class="text-3xl font-bold">Hadith du Jour</h2>
        </div>

        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-200 overflow-hidden">
            {{-- Header band --}}
            <div class="bg-gradient-to-r from-secondary to-secondary/80 px-6 py-3 flex items-center justify-between">
                <span class="text-white text-sm font-medium">{{ $hadithOfDay['collection_name'] ?? 'Sahih al-Bukhari' }}</span>
                <span class="badge badge-soft bg-white/20 text-white border-0 text-xs">N° {{ $hadithOfDay['hadithnumber'] ?? '' }}</span>
            </div>

            <div class="p-8">
                {{-- Arabic --}}
                @if(!empty($hadithOfDay['arabic']))
                <div class="bg-secondary/5 rounded-xl p-6 mb-5 border border-secondary/10">
                    <p class="arabic-text text-lg leading-loose" dir="rtl">
                        {{ $hadithOfDay['arabic'] }}
                    </p>
                </div>
                @endif

                {{-- English --}}
                @if(!empty($hadithOfDay['english']))
                <div class="flex gap-4">
                    <div class="w-1 rounded-full bg-secondary shrink-0"></div>
                    <p class="text-base-content/80 leading-relaxed italic">
                        "{{ $hadithOfDay['english'] }}"
                    </p>
                </div>
                @endif

                @if(!empty($hadithOfDay['grade']))
                <div class="mt-4 flex items-center gap-2">
                    <span class="text-xs text-base-content/40">Grade:</span>
                    <span class="badge badge-soft badge-success text-xs">{{ $hadithOfDay['grade'] }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     DAILY DUA
═══════════════════════════════════════════════════════════════ --}}
@if(!empty($dailyDua))
<section id="dua" class="py-16 bg-base-100">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 text-warning font-semibold text-sm mb-2">
                <span class="w-8 h-px bg-warning"></span>
                الدعاء
                <span class="w-8 h-px bg-warning"></span>
            </div>
            <h2 class="text-3xl font-bold">Dua du Jour</h2>
            <p class="text-base-content/60 mt-1">{{ $dailyDua['title'] ?? '' }}</p>
        </div>

        <div class="bg-gradient-to-br from-warning/5 to-orange-50 rounded-2xl border border-warning/20 p-8 text-center">
            @if(!empty($dailyDua['arabic']))
            <p class="arabic-text text-2xl leading-loose text-base-content mb-4" dir="rtl">
                {{ $dailyDua['arabic'] }}
            </p>
            @endif
            @if(!empty($dailyDua['transliteration']))
            <p class="text-base-content/50 text-sm italic mb-4">{{ $dailyDua['transliteration'] }}</p>
            @endif
            @if(!empty($dailyDua['translation']))
            <div class="bg-white/60 rounded-xl p-4 inline-block max-w-2xl">
                <p class="text-base-content/80 leading-relaxed">{{ $dailyDua['translation'] }}</p>
            </div>
            @endif
            @if(!empty($dailyDua['reference']))
            <p class="text-xs text-base-content/40 mt-4">📚 {{ $dailyDua['reference'] }}</p>
            @endif
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     ASMA UL HUSNA (99 Names of Allah)
═══════════════════════════════════════════════════════════════ --}}
@if(!empty($asmaSlice))
<section id="asma" class="py-16 bg-base-200/30">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 text-primary font-semibold text-sm mb-2">
                <span class="w-8 h-px bg-primary"></span>
                أسماء الله الحسنى
                <span class="w-8 h-px bg-primary"></span>
            </div>
            <h2 class="text-3xl font-bold">Les 99 Noms d'Allah</h2>
            <p class="text-base-content/60 mt-1">Asma ul Husna — Page {{ $asmaPage }} / 11</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @foreach($asmaSlice as $name)
            <div class="bg-base-100 rounded-2xl border border-base-200 p-5 hover:border-primary/30 hover:shadow-md transition-all group">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                        {{ $name['number'] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="arabic-text text-2xl font-bold text-primary mb-1" dir="rtl">{{ $name['arabic'] }}</p>
                        <p class="font-semibold text-sm">{{ $name['english'] }}</p>
                        <p class="text-xs text-base-content/50 italic">{{ $name['transliteration'] }}</p>
                    </div>
                </div>
                @if(!empty($name['meaning']))
                <p class="text-xs text-base-content/60 mt-3 leading-relaxed line-clamp-2">{{ $name['meaning'] }}</p>
                @endif
            </div>
            @endforeach
        </div>

        <div class="flex items-center justify-center gap-3">
            <button wire:click="prevAsma" class="btn btn-ghost btn-sm" @if($asmaPage <= 1) disabled @endif>
                ← Précédent
            </button>
            <span class="text-sm text-base-content/50">{{ ($asmaPage - 1) * 9 + 1 }}–{{ min($asmaPage * 9, 99) }} sur 99</span>
            <button wire:click="nextAsma" class="btn btn-ghost btn-sm" @if($asmaPage >= 11) disabled @endif>
                Suivant →
            </button>
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     FEATURED COURSES
═══════════════════════════════════════════════════════════════ --}}
@if($courses->count())
<section class="py-16 bg-base-100">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="flex items-end justify-between mb-8">
            <div>
                <div class="inline-flex items-center gap-2 text-primary font-semibold text-sm mb-2">
                    <span class="w-8 h-px bg-primary"></span>
                    التعلم الأكاديمي
                </div>
                <h2 class="text-3xl font-bold">Nos Cours Populaires</h2>
            </div>
            <a href="{{ route('courses') }}" class="btn btn-ghost btn-sm">Voir tous →</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($courses as $course)
            <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-lg hover:-translate-y-0.5 transition-all overflow-hidden">
                <figure class="h-36 bg-gradient-to-br from-primary/15 to-secondary/15 relative">
                    @if($course->thumbnail)
                        <img src="{{ asset('storage/'.$course->thumbnail) }}" class="w-full h-full object-cover" alt="">
                    @else
                        <div class="flex items-center justify-center w-full h-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 text-primary/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    @endif
                    <div class="absolute top-2 right-2">
                        @if($course->type === 'free')
                            <span class="badge badge-success badge-soft text-xs">Gratuit</span>
                        @else
                            <span class="badge badge-warning badge-soft text-xs">{{ number_format($course->price) }} DA</span>
                        @endif
                    </div>
                </figure>
                <div class="card-body p-4">
                    <h3 class="font-bold text-sm line-clamp-2">{{ $course->translated_title }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-base-content/50">{{ $course->teacher->user->full_name ?? '' }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-2 text-xs text-base-content/50">
                        <span>👥 {{ $course->enrollments_count ?? 0 }} étudiants</span>
                        <span>⏱ {{ $course->duration_hours ?? 0 }}h</span>
                    </div>
                    <div class="card-actions mt-3">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-sm w-full">
                            S'inscrire au cours
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════════════════════════════ --}}
<section class="py-16 bg-gradient-to-br from-primary to-secondary text-white">
    <div class="container mx-auto px-4 max-w-4xl text-center">
        <p class="arabic-text text-3xl font-bold mb-3 opacity-90" dir="rtl">
            وَقُل رَّبِّ زِدْنِي عِلْمًا
        </p>
        <p class="text-white/70 text-sm italic mb-8">« Mon Seigneur, accroît ma science » — Sourate Ta-Ha (20:114)</p>
        <h2 class="text-3xl font-bold mb-4">Commencez votre voyage d'apprentissage</h2>
        <p class="text-white/80 mb-8 max-w-xl mx-auto">
            Rejoignez des milliers d'étudiants qui apprennent le Coran, les sciences islamiques et les matières académiques.
        </p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ route('register') }}" class="btn bg-white text-primary hover:bg-white/90 btn-lg shadow-xl">
                Créer un compte gratuit
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline btn-lg border-white/40 text-white hover:bg-white/10">
                Se connecter
            </a>
        </div>
    </div>
</section>

{{-- Audio Player (hidden) --}}
<audio id="quran_audio_player" style="display:none"></audio>

<script>
let currentAudioSrc = '';
function toggleAudio(id, src) {
    const player = document.getElementById('quran_audio_player');
    if (currentAudioSrc === src && !player.paused) {
        player.pause();
        currentAudioSrc = '';
    } else {
        player.src = src;
        currentAudioSrc = src;
        player.play().catch(() => {});
    }
}
</script>

</div>
