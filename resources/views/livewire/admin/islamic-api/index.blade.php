<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Islamic API Explorer')]
class extends Component {

    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public string $activeTab = 'hijri';

    // Quran
    public int    $surahNumber = 1;
    public array  $surahData   = [];

    // Hadith
    public int    $hadithPage    = 1;
    public int    $hadithPerPage = 5;
    public array  $hadithData    = [];

    // Asma
    public array  $asmaData = [];

    // Duas
    public array  $duasData = [];

    // Prayer / Qibla
    public string $latitude  = '36.7';
    public string $longitude = '3.05';
    public array  $prayerData = [];
    public array  $qiblaData  = [];

    // Hijri
    public array  $hijriData = [];

    // Status
    public ?string $error = null;

    public function mount(): void
    {
        $this->fetchHijri();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->error = null;

        match ($tab) {
            'hijri'  => $this->fetchHijri(),
            'quran'  => $this->fetchQuran(),
            'hadith' => $this->fetchHadith(),
            'asma'   => $this->fetchAsma(),
            'duas'   => $this->fetchDuas(),
            'prayer' => $this->fetchPrayer(),
            'qibla'  => $this->fetchQibla(),
            default  => null,
        };
    }

    public function fetchHijri(): void
    {
        $r = $this->api('/api/hijri-date');
        $this->hijriData = $r['data'] ?? [];
    }

    public function fetchQuran(): void
    {
        $r = $this->api("/api/quran/surah/{$this->surahNumber}");
        $this->surahData = $r['data'] ?? [];
    }

    public function fetchHadith(): void
    {
        $r = $this->api("/api/hadith/bukhari?page={$this->hadithPage}&per_page={$this->hadithPerPage}");
        $this->hadithData = $r['data']['hadiths'] ?? [];
    }

    public function fetchAsma(): void
    {
        $r = $this->api('/api/asma-ul-husna');
        $this->asmaData = $r['data'] ?? [];
    }

    public function fetchDuas(): void
    {
        $r = $this->api('/api/duas');
        $this->duasData = $r['data']['duas'] ?? [];
    }

    public function fetchPrayer(): void
    {
        $r = $this->api("/api/prayer-times?latitude={$this->latitude}&longitude={$this->longitude}");
        $this->prayerData = $r['data'] ?? [];
    }

    public function setCity(string $lat, string $lng): void
    {
        $this->latitude  = $lat;
        $this->longitude = $lng;
    }

    public function prevHadithPage(): void
    {
        if ($this->hadithPage > 1) {
            $this->hadithPage--;
            $this->fetchHadith();
        }
    }

    public function nextHadithPage(): void
    {
        $this->hadithPage++;
        $this->fetchHadith();
    }

    public function fetchQibla(): void
    {
        $r = $this->api("/api/qibla?latitude={$this->latitude}&longitude={$this->longitude}");
        $this->qiblaData = $r['data'] ?? [];
    }

    private function api(string $path): ?array
    {
        try {
            $resp = Http::timeout(15)
                ->withHeaders(['X-API-Key' => self::KEY, 'Accept' => 'application/json'])
                ->get(self::BASE . $path);
            if ($resp->successful() && !empty($resp->json('success'))) {
                return $resp->json();
            }
            $this->error = "HTTP {$resp->status()} — " . ($resp->json('message') ?? 'Unknown error');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        return null;
    }
}; ?>

<div>
    <x-header title="Islamic API Explorer" subtitle="Test live UmmahAPI endpoints" separator>
        <x-slot:actions>
            <x-badge value="umh_bb202…" class="badge-ghost font-mono text-xs" />
            <x-badge value="ummahapi.com" class="badge-success badge-sm" />
        </x-slot:actions>
    </x-header>

    {{-- Tabs --}}
    <div class="tabs tabs-boxed mb-6 flex-wrap gap-1 bg-base-200 p-1">
        @foreach([
            ['hijri',  'o-calendar-days',         'Hijri Date'],
            ['quran',  'o-book-open',              'Quran'],
            ['hadith', 'o-chat-bubble-left-right', 'Hadith'],
            ['asma',   'o-star',                   'Asma ul Husna'],
            ['duas',   'o-hand-raised',            'Duas'],
            ['prayer', 'o-clock',                  'Prayer Times'],
            ['qibla',  'o-map-pin',                'Qibla'],
        ] as [$key, $icon, $label])
            <button wire:click="setTab('{{ $key }}')"
                    class="tab gap-2 {{ $activeTab === $key ? 'tab-active' : '' }}">
                <x-icon :name="$icon" class="w-4 h-4" />
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Error --}}
    @if($error)
        <x-alert title="API Error" :description="$error" icon="o-exclamation-triangle" class="alert-error mb-4" />
    @endif

    {{-- ── HIJRI DATE ── --}}
    @if($activeTab === 'hijri')
        <x-card shadow>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-icon name="o-calendar-days" class="w-5 h-5 text-primary" />
                    Hijri Date — Today
                </div>
            </x-slot:title>
            @if(!empty($hijriData['hijri']))
                @php $h = $hijriData['hijri']; $g = $hijriData['gregorian'] ?? []; @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Hijri --}}
                    <div class="bg-primary/10 rounded-xl p-5">
                        <div class="text-xs font-semibold uppercase text-primary/60 mb-2">Islamic (Hijri)</div>
                        <div class="text-3xl font-bold text-primary mb-1">{{ $h['date'] ?? '—' }}</div>
                        <div class="text-lg font-semibold">{{ $h['formatted'] ?? '—' }}</div>
                        <div class="font-arabic text-xl mt-2 text-primary/80" dir="rtl">{{ $h['month_name_arabic'] ?? '' }}</div>
                        <div class="grid grid-cols-3 gap-2 mt-3 text-center text-sm">
                            <div class="bg-white/50 rounded p-2"><div class="text-xs text-base-content/50">Day</div><div class="font-bold">{{ $h['day'] ?? '—' }}</div></div>
                            <div class="bg-white/50 rounded p-2"><div class="text-xs text-base-content/50">Month</div><div class="font-bold">{{ $h['month'] ?? '—' }}</div></div>
                            <div class="bg-white/50 rounded p-2"><div class="text-xs text-base-content/50">Year</div><div class="font-bold">{{ $h['year'] ?? '—' }} AH</div></div>
                        </div>
                        <div class="text-xs text-base-content/40 mt-2">{{ $h['era'] ?? '' }}</div>
                    </div>
                    {{-- Gregorian --}}
                    <div class="bg-base-200 rounded-xl p-5">
                        <div class="text-xs font-semibold uppercase text-base-content/50 mb-2">Gregorian</div>
                        <div class="text-3xl font-bold mb-1">{{ $g['date'] ?? '—' }}</div>
                        <div class="text-lg font-semibold">{{ $g['formatted'] ?? '—' }}</div>
                        <div class="grid grid-cols-3 gap-2 mt-3 text-center text-sm">
                            <div class="bg-white/50 rounded p-2"><div class="text-xs text-base-content/50">Day</div><div class="font-bold">{{ $g['day'] ?? '—' }}</div></div>
                            <div class="bg-white/50 rounded p-2"><div class="text-xs text-base-content/50">Month</div><div class="font-bold">{{ $g['month_name'] ?? '—' }}</div></div>
                            <div class="bg-white/50 rounded p-2"><div class="text-xs text-base-content/50">Year</div><div class="font-bold">{{ $g['year'] ?? '—' }}</div></div>
                        </div>
                    </div>
                </div>
                @if(!empty($hijriData['islamic_info']['note']))
                    <div class="mt-3 text-xs text-base-content/50 italic">
                        ℹ {{ $hijriData['islamic_info']['note'] }}
                    </div>
                @endif
            @else
                <div class="text-base-content/40 text-center py-8">No data</div>
            @endif
            <x-slot:actions>
                <x-button label="Refresh" icon="o-arrow-path" wire:click="fetchHijri" spinner="fetchHijri" class="btn-ghost btn-sm" />
            </x-slot:actions>
        </x-card>
    @endif

    {{-- ── QURAN ── --}}
    @if($activeTab === 'quran')
        <div class="flex items-end gap-3 mb-4">
            <div class="form-control">
                <label class="label"><span class="label-text">Surah Number (1–114)</span></label>
                <input type="number" wire:model="surahNumber" min="1" max="114"
                       class="input input-bordered input-sm w-32" />
            </div>
            <x-button label="Load Surah" icon="o-magnifying-glass" wire:click="fetchQuran" spinner="fetchQuran" class="btn-primary btn-sm" />
        </div>

        @if(!empty($surahData['surah']))
            @php $surah = $surahData['surah']; $verses = $surahData['verses'] ?? []; @endphp
            <x-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-3">
                        <x-badge value="Surah #{{ $surah['number'] ?? '' }}" class="badge-primary" />
                        <span>{{ $surah['name_english'] ?? '' }}</span>
                        <span class="font-arabic text-xl text-primary" dir="rtl">{{ $surah['name_arabic'] ?? '' }}</span>
                        <x-badge :value="($surah['revelation_type'] ?? '') . ' — ' . ($surah['verses_count'] ?? count($verses)) . ' verses'" class="badge-ghost badge-sm" />
                    </div>
                </x-slot:title>

                <div class="space-y-4 mt-4">
                    @foreach($verses as $verse)
                        <div class="border border-base-300 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <x-badge :value="$verse['verse_key'] ?? ''" class="badge-outline badge-sm" />
                                @php $audioUrl = $verse['audio']['ayah_audio'] ?? (is_string($verse['audio'] ?? null) ? $verse['audio'] : null); @endphp
                                @if($audioUrl)
                                    <audio controls class="h-8">
                                        <source src="{{ $audioUrl }}" type="audio/mpeg">
                                    </audio>
                                @endif
                            </div>
                            <p class="font-arabic text-2xl text-right leading-loose mb-3 text-primary" dir="rtl">
                                {{ $verse['arabic'] ?? '' }}
                            </p>
                            @php $translation = $verse['translations']['french'] ?? ($verse['translations']['sahih_international'] ?? ($verse['translations']['yusuf_ali'] ?? '')); @endphp
                            @if($translation)
                                <p class="text-sm text-base-content/70 italic">{{ $translation }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if(!empty($surahData['total_verses']) && $surahData['total_verses'] > count($verses))
                    <div class="text-center text-sm text-base-content/50 mt-4">
                        Showing {{ count($verses) }} of {{ $surahData['total_verses'] }} verses
                    </div>
                @endif
            </x-card>
        @elseif($activeTab === 'quran')
            <div class="text-center py-12 text-base-content/40">Select a surah and click Load</div>
        @endif
    @endif

    {{-- ── HADITH ── --}}
    @if($activeTab === 'hadith')
        <div class="flex items-end gap-3 mb-4">
            <div class="form-control">
                <label class="label"><span class="label-text">Page</span></label>
                <input type="number" wire:model="hadithPage" min="1"
                       class="input input-bordered input-sm w-24" />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Per page</span></label>
                <select wire:model="hadithPerPage" class="select select-bordered select-sm">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <x-button label="Load" icon="o-magnifying-glass" wire:click="fetchHadith" spinner="fetchHadith" class="btn-primary btn-sm" />
        </div>

        @if(!empty($hadithData))
            <div class="space-y-4">
                @foreach($hadithData as $h)
                    <x-card shadow>
                        <div class="flex items-center gap-2 mb-3">
                            <x-badge value="Hadith #{{ $h['hadithnumber'] ?? '' }}" class="badge-warning" />
                            <x-badge :value="$h['collection'] ?? 'Bukhari'" class="badge-ghost badge-sm" />
                            @if(!empty($h['grade']))
                                <x-badge :value="$h['grade']" class="badge-success badge-xs" />
                            @endif
                        </div>
                        @if(!empty($h['arabic']))
                            <p class="font-arabic text-lg text-right leading-loose mb-3 text-primary border-r-4 border-primary/30 pr-4" dir="rtl">
                                {{ $h['arabic'] }}
                            </p>
                        @endif
                        @if(!empty($h['english']))
                            <p class="text-sm text-base-content/80">{{ $h['english'] }}</p>
                        @endif
                    </x-card>
                @endforeach
            </div>

            <div class="flex justify-center gap-2 mt-4">
                @if($hadithPage > 1)
                    <x-button label="← Prev" wire:click="prevHadithPage" class="btn-ghost btn-sm" />
                @endif
                <x-badge :value="'Page ' . $hadithPage" class="badge-neutral" />
                <x-button label="Next →" wire:click="nextHadithPage" class="btn-ghost btn-sm" />
            </div>
        @else
            <div class="text-center py-12 text-base-content/40">Click Load to fetch hadiths</div>
        @endif
    @endif

    {{-- ── ASMA UL HUSNA ── --}}
    @if($activeTab === 'asma')
        <x-button label="Load 99 Names" icon="o-arrow-down-tray" wire:click="fetchAsma" spinner="fetchAsma" class="btn-primary btn-sm mb-4" />

        @if(!empty($asmaData['names']))
            <div class="mb-3 flex gap-2 items-center">
                <x-badge :value="($asmaData['total_count'] ?? count($asmaData['names'])) . ' names'" class="badge-primary" />
                @if(!empty($asmaData['arabic_title']))
                    <span class="font-arabic text-lg text-primary" dir="rtl">{{ $asmaData['arabic_title'] }}</span>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($asmaData['names'] as $name)
                    <div class="bg-base-200 rounded-xl p-3 text-center hover:bg-primary/10 transition-colors">
                        <div class="text-xs text-base-content/40 mb-1">#{{ $name['number'] ?? '' }}</div>
                        <div class="font-arabic text-xl font-bold text-primary mb-1" dir="rtl">{{ $name['arabic'] ?? '' }}</div>
                        <div class="text-xs font-semibold">{{ $name['transliteration'] ?? '' }}</div>
                        <div class="text-xs text-base-content/60 mt-1">{{ $name['meaning'] ?? $name['english'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @elseif($activeTab === 'asma')
            <div class="text-center py-12 text-base-content/40">Click Load to fetch the 99 Names</div>
        @endif
    @endif

    {{-- ── DUAS ── --}}
    @if($activeTab === 'duas')
        <x-button label="Load Duas" icon="o-arrow-down-tray" wire:click="fetchDuas" spinner="fetchDuas" class="btn-primary btn-sm mb-4" />

        @if(!empty($duasData))
            @php
                $grouped = [];
                foreach ((array)$duasData as $dua) {
                    $cat = $dua['category'] ?? $dua['occasion'] ?? 'Général';
                    $grouped[$cat][] = $dua;
                }
            @endphp
            <div class="space-y-6">
                @foreach($grouped as $category => $catDuas)
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <x-icon name="o-tag" class="w-4 h-4 text-primary" />
                            <span class="font-semibold text-primary">{{ $category }}</span>
                            <x-badge :value="count($catDuas) . ' duas'" class="badge-ghost badge-xs" />
                        </div>
                        <div class="space-y-3">
                            @foreach($catDuas as $dua)
                                <x-card shadow class="bg-base-200/50">
                                    <div class="font-semibold mb-2">{{ $dua['title'] ?? $dua['name'] ?? 'Dua' }}</div>
                                    @if(!empty($dua['arabic']))
                                        <p class="font-arabic text-xl text-right leading-loose text-primary border-r-4 border-primary/30 pr-3 mb-2" dir="rtl">
                                            {{ $dua['arabic'] }}
                                        </p>
                                    @endif
                                    @if(!empty($dua['transliteration']))
                                        <p class="text-sm italic text-base-content/60 mb-1">{{ $dua['transliteration'] }}</p>
                                    @endif
                                    @if(!empty($dua['translation'] ?? $dua['english'] ?? null))
                                        <p class="text-sm text-base-content/80">{{ $dua['translation'] ?? $dua['english'] }}</p>
                                    @endif
                                    @if(!empty($dua['source'] ?? $dua['reference'] ?? null))
                                        <div class="mt-2 text-xs text-base-content/40">— {{ $dua['source'] ?? $dua['reference'] }}</div>
                                    @endif
                                </x-card>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-base-content/40">Click Load to fetch duas</div>
        @endif
    @endif

    {{-- ── PRAYER TIMES ── --}}
    @if($activeTab === 'prayer')
        <div class="flex items-end gap-3 mb-4 flex-wrap">
            <div class="form-control">
                <label class="label"><span class="label-text">Latitude</span></label>
                <input type="text" wire:model="latitude" class="input input-bordered input-sm w-32" placeholder="36.7" />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Longitude</span></label>
                <input type="text" wire:model="longitude" class="input input-bordered input-sm w-32" placeholder="3.05" />
            </div>
            <x-button label="Algiers" wire:click="setCity('36.7','3.05')" class="btn-ghost btn-xs" />
            <x-button label="Mecca"   wire:click="setCity('21.39','39.85')" class="btn-ghost btn-xs" />
            <x-button label="Paris"   wire:click="setCity('48.85','2.35')" class="btn-ghost btn-xs" />
            <x-button label="Load" icon="o-clock" wire:click="fetchPrayer" spinner="fetchPrayer" class="btn-primary btn-sm" />
        </div>

        @if(!empty($prayerData['prayer_times']))
            @php $pt = $prayerData['prayer_times']; @endphp
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                @foreach([
                    ['Fajr',    'الفجر',   $pt['fajr']    ?? '--:--', 'text-blue-500',   'o-moon'],
                    ['Dhuhr',   'الظهر',   $pt['dhuhr']   ?? '--:--', 'text-yellow-500', 'o-sun'],
                    ['Asr',     'العصر',   $pt['asr']     ?? '--:--', 'text-orange-400', 'o-sun'],
                    ['Maghrib', 'المغرب',  $pt['maghrib'] ?? '--:--', 'text-red-400',    'o-sun'],
                    ['Isha',    'العشاء',  $pt['isha']    ?? '--:--', 'text-indigo-500', 'o-moon'],
                ] as [$name, $nameAr, $time, $color, $icon])
                    <div class="bg-base-200 rounded-xl p-4 text-center">
                        <x-icon :name="$icon" class="w-6 h-6 mx-auto mb-2 {{ $color }}" />
                        <div class="font-semibold">{{ $name }}</div>
                        <div class="font-arabic text-sm text-base-content/60" dir="rtl">{{ $nameAr }}</div>
                        <div class="text-2xl font-bold {{ $color }} mt-1">{{ $time }}</div>
                    </div>
                @endforeach
            </div>

            @if(!empty($prayerData['date']))
                <div class="text-sm text-base-content/50 mb-2">Date: {{ $prayerData['date'] }}</div>
            @endif
            @if(!empty($prayerData['current_status']['current_prayer']))
                @php $cs = $prayerData['current_status']; @endphp
                <div class="flex gap-2 flex-wrap mt-2">
                    <x-badge :value="'Now: ' . ucfirst($cs['current_prayer'])" class="badge-primary" />
                    <x-badge :value="'Next: ' . ucfirst($cs['next_prayer'] ?? '') . ' in ' . ($cs['time_until_next'] ?? '')" class="badge-ghost badge-sm" />
                </div>
            @endif
        @else
            <div class="text-center py-12 text-base-content/40">Enter coordinates and click Load</div>
        @endif
    @endif

    {{-- ── QIBLA ── --}}
    @if($activeTab === 'qibla')
        <div class="flex items-end gap-3 mb-4 flex-wrap">
            <div class="form-control">
                <label class="label"><span class="label-text">Latitude</span></label>
                <input type="text" wire:model="latitude" class="input input-bordered input-sm w-32" placeholder="36.7" />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Longitude</span></label>
                <input type="text" wire:model="longitude" class="input input-bordered input-sm w-32" placeholder="3.05" />
            </div>
            <x-button label="Algiers" wire:click="setCity('36.7','3.05')" class="btn-ghost btn-xs" />
            <x-button label="Mecca"   wire:click="setCity('21.39','39.85')" class="btn-ghost btn-xs" />
            <x-button label="Paris"   wire:click="setCity('48.85','2.35')" class="btn-ghost btn-xs" />
            <x-button label="Load" icon="o-map-pin" wire:click="fetchQibla" spinner="fetchQibla" class="btn-primary btn-sm" />
        </div>

        @if(!empty($qiblaData['direction']))
            <div class="flex flex-col items-center gap-6 py-8">
                {{-- Compass --}}
                <div class="relative w-48 h-48">
                    <div class="w-48 h-48 rounded-full border-4 border-base-300 bg-base-200 flex items-center justify-center">
                        <div class="text-4xl font-bold text-primary">
                            {{ round($qiblaData['direction'], 1) }}°
                        </div>
                    </div>
                    {{-- Needle --}}
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none"
                         style="transform: rotate({{ round($qiblaData['direction'], 1) }}deg)">
                        <div class="w-1 h-20 bg-primary rounded-full origin-bottom -translate-y-4"></div>
                    </div>
                    {{-- Cardinal points --}}
                    <div class="absolute top-2 left-1/2 -translate-x-1/2 text-xs font-bold">N</div>
                    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 text-xs font-bold">S</div>
                    <div class="absolute left-2 top-1/2 -translate-y-1/2 text-xs font-bold">W</div>
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-bold">E</div>
                </div>

                <div class="text-center">
                    <div class="text-3xl font-bold text-primary">{{ round($qiblaData['direction'], 2) }}°</div>
                    @if(!empty($qiblaData['compass_direction']))
                        <div class="text-lg text-base-content/70">{{ $qiblaData['compass_direction'] }}</div>
                    @endif
                    <div class="text-sm text-base-content/50 mt-1">from {{ $latitude }}, {{ $longitude }}</div>
                    @if(!empty($qiblaData['distance_to_mecca_km']))
                        <div class="text-sm text-base-content/50">Distance to Mecca: {{ number_format($qiblaData['distance_to_mecca_km']) }} km</div>
                    @endif
                </div>
            </div>
        @else
            <div class="text-center py-12 text-base-content/40">Enter coordinates and click Load</div>
        @endif
    @endif

</div>
