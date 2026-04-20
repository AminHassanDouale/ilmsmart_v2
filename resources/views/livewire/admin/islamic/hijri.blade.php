<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Islamic Calendar')]
class extends Component {
    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public string  $mode         = 'today'; // today | months | events
    public array   $hijriData    = [];
    public array   $monthsData   = [];
    public array   $eventsData   = [];
    public ?string $error        = null;

    public function mount(): void { $this->load(); }

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

    public function load(): void
    {
        $this->error = null;
        $d = $this->api('/api/hijri-date');
        $this->hijriData = $d ?? [];
    }

    public function loadMonths(): void
    {
        $this->error = null;
        $this->mode  = 'months';
        if (empty($this->monthsData)) {
            $d = $this->api('/api/islamic-months');
            $this->monthsData = $d ?? [];
        }
    }

    public function loadEvents(): void
    {
        $this->error = null;
        $this->mode  = 'events';
        if (empty($this->eventsData)) {
            $d = $this->api('/api/islamic-events');
            $this->eventsData = $d ?? [];
        }
    }

    public function setMode(string $m): void
    {
        $this->mode  = $m;
        $this->error = null;
        if ($m === 'today') $this->load();
        elseif ($m === 'months') $this->loadMonths();
        else $this->loadEvents();
    }
}; ?>

<div>
    <x-header title="Islamic Calendar" subtitle="Hijri date, months, and events" separator>
        <x-slot:actions>
            <x-button label="Refresh" icon="o-arrow-path" wire:click="load" spinner="load" class="btn-ghost btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- View mode --}}
    <div class="flex gap-2 mb-6">
        <x-button label="Today" icon="o-calendar-days"
                  wire:click="setMode('today')"
                  class="btn-sm {{ $mode === 'today' ? 'btn-primary' : 'btn-ghost' }}" />
        <x-button label="Islamic Months" icon="o-moon"
                  wire:click="setMode('months')"
                  spinner="setMode"
                  class="btn-sm {{ $mode === 'months' ? 'btn-primary' : 'btn-ghost' }}" />
        <x-button label="Islamic Events" icon="o-star"
                  wire:click="setMode('events')"
                  spinner="setMode"
                  class="btn-sm {{ $mode === 'events' ? 'btn-primary' : 'btn-ghost' }}" />
    </div>

    @if($error)
        <x-alert title="API Error" :description="$error" icon="o-exclamation-triangle" class="alert-error mb-4" />
    @endif

    {{-- ============================================================ --}}
    {{-- TODAY: Hijri date                                            --}}
    {{-- ============================================================ --}}
    @if($mode === 'today' && !empty($hijriData['hijri']))
        @php
            $h = $hijriData['hijri'];
            $g = $hijriData['gregorian'];
            $info = $hijriData['islamic_info'] ?? [];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            {{-- Hijri card --}}
            <x-card shadow class="bg-primary/5 border border-primary/20">
                <div class="text-xs text-base-content/50 uppercase tracking-widest mb-2">Hijri Date</div>
                <div class="text-4xl font-bold text-primary mb-1">{{ $h['formatted'] ?? '' }}</div>
                <div class="text-lg text-base-content/70 mb-3" dir="rtl">
                    {{ $h['day'] ?? '' }} {{ $h['month_name_arabic'] ?? '' }} {{ $h['year'] ?? '' }} {{ $h['era'] ?? 'AH' }}
                </div>
                <div class="grid grid-cols-3 gap-3 mt-4">
                    <div class="text-center p-3 bg-base-100 rounded-lg">
                        <div class="text-2xl font-bold text-primary">{{ $h['day'] ?? '' }}</div>
                        <div class="text-xs text-base-content/50">Day</div>
                    </div>
                    <div class="text-center p-3 bg-base-100 rounded-lg">
                        <div class="text-sm font-bold text-primary">{{ $h['month_name'] ?? '' }}</div>
                        <div class="text-xs text-base-content/50">Month {{ $h['month'] ?? '' }}</div>
                    </div>
                    <div class="text-center p-3 bg-base-100 rounded-lg">
                        <div class="text-2xl font-bold text-primary">{{ $h['year'] ?? '' }}</div>
                        <div class="text-xs text-base-content/50">Year AH</div>
                    </div>
                </div>
            </x-card>

            {{-- Gregorian card --}}
            <x-card shadow>
                <div class="text-xs text-base-content/50 uppercase tracking-widest mb-2">Gregorian Date</div>
                <div class="text-4xl font-bold mb-1">{{ $g['formatted'] ?? '' }}</div>
                <div class="text-lg text-base-content/70 mb-3">{{ $g['day_of_week'] ?? '' }}</div>
                <div class="grid grid-cols-3 gap-3 mt-4">
                    <div class="text-center p-3 bg-base-100 rounded-lg border border-base-200">
                        <div class="text-2xl font-bold">{{ $g['day'] ?? '' }}</div>
                        <div class="text-xs text-base-content/50">Day</div>
                    </div>
                    <div class="text-center p-3 bg-base-100 rounded-lg border border-base-200">
                        <div class="text-sm font-bold">{{ $g['month_name'] ?? '' }}</div>
                        <div class="text-xs text-base-content/50">Month {{ $g['month'] ?? '' }}</div>
                    </div>
                    <div class="text-center p-3 bg-base-100 rounded-lg border border-base-200">
                        <div class="text-2xl font-bold">{{ $g['year'] ?? '' }}</div>
                        <div class="text-xs text-base-content/50">Year CE</div>
                    </div>
                </div>
            </x-card>
        </div>

        @if(!empty($info))
            <x-card shadow class="bg-base-100">
                <div class="text-xs text-base-content/50 uppercase tracking-widest mb-3">About the Hijri Calendar</div>
                <div class="space-y-2 text-sm text-base-content/70">
                    @foreach($info as $k => $v)
                        @if(is_string($v))
                            <div class="flex gap-2">
                                <span class="font-semibold text-base-content/50 capitalize shrink-0">{{ ucwords(str_replace('_', ' ', $k)) }}:</span>
                                <span>{{ $v }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-card>
        @endif

    {{-- ============================================================ --}}
    {{-- ISLAMIC MONTHS                                               --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'months' && !empty($monthsData['months']))
        @if(!empty($monthsData['calendar_info']))
            @php $ci = $monthsData['calendar_info']; @endphp
            <div class="flex flex-wrap gap-3 mb-4">
                @foreach($ci as $k => $v)
                    @if(is_string($v))
                        <x-badge :value="ucwords(str_replace('_',' ',$k)) . ': ' . $v" class="badge-ghost" />
                    @endif
                @endforeach
            </div>
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($monthsData['months'] as $m)
                <x-card shadow class="hover:border-primary/30 border transition-all">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-sm font-bold text-primary">
                            {{ $m['number'] }}
                        </div>
                        <div>
                            <div class="font-semibold">{{ $m['name_english'] }}</div>
                            <div class="font-arabic text-lg text-primary" dir="rtl">{{ $m['name_arabic'] }}</div>
                        </div>
                    </div>
                    <p class="text-sm text-base-content/60 leading-relaxed">{{ $m['significance'] }}</p>
                </x-card>
            @endforeach
        </div>

    {{-- ============================================================ --}}
    {{-- ISLAMIC EVENTS                                               --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'events' && !empty($eventsData['events']))
        {{-- Next event highlight --}}
        @if(!empty($eventsData['next_event']))
            @php $ne = $eventsData['next_event']; @endphp
            <div class="alert alert-info mb-6">
                <x-icon name="o-star" class="w-5 h-5" />
                <div>
                    <div class="font-bold">Next Event: {{ $ne['name'] ?? '' }}</div>
                    <div class="text-sm opacity-80">{{ $ne['hijri_date'] ?? '' }}</div>
                </div>
            </div>
        @endif

        <div class="space-y-3">
            @foreach($eventsData['events'] as $ev)
                <x-card shadow>
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 text-center min-w-[4rem]">
                            <div class="text-2xl font-bold text-primary">{{ $ev['day'] }}</div>
                            <div class="text-xs text-base-content/50">Month {{ $ev['month'] }}</div>
                        </div>
                        <div>
                            <div class="font-semibold">{{ $ev['name'] ?? '' }}</div>
                            @if(!empty($ev['description']))
                                <div class="text-sm text-base-content/60 mt-0.5">{{ $ev['description'] }}</div>
                            @endif
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        @if(!empty($eventsData['note']))
            <div class="mt-4 text-xs text-base-content/40 text-center">{{ $eventsData['note'] }}</div>
        @endif

    @elseif(empty($error))
        <div class="flex justify-center py-16">
            <span class="loading loading-spinner loading-lg"></span>
        </div>
    @endif
</div>
