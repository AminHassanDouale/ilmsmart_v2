<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Prayer Times')]
class extends Component {
    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public string  $mode       = 'today'; // today | month | ramadan
    public string  $latitude   = '36.7';
    public string  $longitude  = '3.05';
    public string  $cityName   = 'Algiers';
    public string  $method     = 'MuslimWorldLeague';
    public array   $methods    = [];
    public array   $data       = [];
    public array   $monthData  = [];
    public array   $ramadanData = [];
    public int     $ramadanYear = 2026;
    public ?string $error      = null;

    public function mount(): void
    {
        $this->loadMethods();
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

    public function loadMethods(): void
    {
        $d = $this->api('/api/prayer-methods');
        $this->methods = $d['methods'] ?? [];
    }

    public function load(): void
    {
        $this->error = null;
        $d = $this->api("/api/prayer-times?lat={$this->latitude}&lng={$this->longitude}&method={$this->method}");
        $this->data = $d ?? [];
    }

    public function loadMonth(): void
    {
        $this->error = null;
        $this->mode  = 'month';
        $d = $this->api("/api/prayer-times/month?lat={$this->latitude}&lng={$this->longitude}&method={$this->method}");
        $this->monthData = $d ?? [];
    }

    public function loadRamadan(): void
    {
        $this->error = null;
        $this->mode  = 'ramadan';
        $d = $this->api("/api/ramadan/{$this->ramadanYear}?lat={$this->latitude}&lng={$this->longitude}&method={$this->method}");
        $this->ramadanData = $d ?? [];
    }

    public function setCity(string $lat, string $lng, string $name): void
    {
        $this->latitude  = $lat;
        $this->longitude = $lng;
        $this->cityName  = $name;
        if ($this->mode === 'today') $this->load();
        elseif ($this->mode === 'month') $this->loadMonth();
        else $this->loadRamadan();
    }

    public function setMode(string $m): void
    {
        $this->mode  = $m;
        $this->error = null;
        if ($m === 'today') $this->load();
        elseif ($m === 'month') $this->loadMonth();
        else $this->loadRamadan();
    }
}; ?>

<div>
    <x-header title="Prayer Times" :subtitle="'Times for ' . $cityName" separator>
        <x-slot:actions>
            <x-button label="Refresh" icon="o-arrow-path" wire:click="load" spinner="load" class="btn-ghost btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- City presets --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach([
            ['36.7',  '3.05',   'Algiers'],
            ['21.39', '39.85',  'Mecca'],
            ['24.47', '39.61',  'Medina'],
            ['51.51', '-0.13',  'London'],
            ['48.85', '2.35',   'Paris'],
            ['40.71', '-74.01', 'New York'],
            ['1.35',  '103.82', 'Singapore'],
            ['24.86', '67.01',  'Karachi'],
        ] as [$lat, $lng, $name])
            <x-button :label="$name"
                      wire:click="setCity('{{ $lat }}', '{{ $lng }}', '{{ $name }}')"
                      spinner="setCity"
                      class="btn-ghost btn-sm {{ $cityName === $name ? 'btn-active btn-primary' : '' }}" />
        @endforeach
    </div>

    {{-- Custom coords + method --}}
    <div class="flex items-end gap-3 mb-4 flex-wrap">
        <div class="form-control">
            <label class="label label-text text-xs">Latitude</label>
            <input type="text" wire:model="latitude" class="input input-bordered input-sm w-28" />
        </div>
        <div class="form-control">
            <label class="label label-text text-xs">Longitude</label>
            <input type="text" wire:model="longitude" class="input input-bordered input-sm w-28" />
        </div>
        @if(!empty($methods))
            <div class="form-control">
                <label class="label label-text text-xs">Calculation Method</label>
                <select wire:model="method" class="select select-bordered select-sm">
                    @foreach($methods as $key => $m)
                        <option value="{{ $key }}">{{ $m['name'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <x-button label="Load" icon="o-magnifying-glass" wire:click="load" spinner="load" class="btn-primary btn-sm" />
    </div>

    {{-- View mode tabs --}}
    <div class="flex gap-2 mb-6">
        <x-button label="Today" icon="o-clock"
                  wire:click="setMode('today')"
                  class="btn-sm {{ $mode === 'today' ? 'btn-primary' : 'btn-ghost' }}" />
        <x-button label="Monthly" icon="o-calendar"
                  wire:click="setMode('month')"
                  spinner="setMode"
                  class="btn-sm {{ $mode === 'month' ? 'btn-primary' : 'btn-ghost' }}" />
        <x-button label="Ramadan" icon="o-moon"
                  wire:click="setMode('ramadan')"
                  spinner="setMode"
                  class="btn-sm {{ $mode === 'ramadan' ? 'btn-primary' : 'btn-ghost' }}" />
    </div>

    @if($error)
        <x-alert title="API Error" :description="$error" icon="o-exclamation-triangle" class="alert-error mb-4" />
    @endif

    {{-- ============================================================ --}}
    {{-- TODAY                                                        --}}
    {{-- ============================================================ --}}
    @if($mode === 'today' && !empty($data['prayer_times']))
        @php
            $pt = $data['prayer_times'];
            $cs = $data['current_status'] ?? [];
            $prayers = [
                'imsak'   => ['Imsak',   'o-moon'],
                'fajr'    => ['Fajr',    'o-sun'],
                'sunrise' => ['Sunrise', 'o-arrow-trending-up'],
                'dhuhr'   => ['Dhuhr',   'o-sun'],
                'asr'     => ['Asr',     'o-cloud'],
                'maghrib' => ['Maghrib', 'o-moon'],
                'isha'    => ['Isha',    'o-star'],
            ];
        @endphp

        {{-- Current prayer banner --}}
        @if(!empty($cs['current_prayer']))
            <div class="alert alert-info mb-6 flex flex-wrap gap-4">
                <div>
                    <div class="font-bold text-lg">{{ ucfirst($cs['current_prayer']) }} — Now</div>
                    <div class="text-sm opacity-80">
                        Next: <strong>{{ ucfirst($cs['next_prayer'] ?? '') }}</strong>
                        in {{ $cs['time_until_next'] ?? '' }}
                    </div>
                </div>
                <div class="text-sm opacity-70">
                    {{ $data['date'] ?? '' }} · {{ $data['timezone'] ?? '' }}
                </div>
            </div>
        @endif

        {{-- Prayer grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
            @foreach($prayers as $key => [$label, $icon])
                @if(isset($pt[$key]))
                    @php $isActive = ($cs['current_prayer'] ?? '') === $key; @endphp
                    <div class="p-4 rounded-xl text-center border transition-all
                                {{ $isActive ? 'bg-primary text-primary-content border-primary shadow-lg scale-105' : 'bg-base-100 border-base-200' }}">
                        <x-icon :name="$icon" class="w-6 h-6 mx-auto mb-2 {{ $isActive ? 'text-primary-content' : 'text-primary' }}" />
                        <div class="text-xs font-semibold mb-1 {{ $isActive ? '' : 'text-base-content/60' }}">{{ $label }}</div>
                        <div class="text-lg font-bold">{{ $pt[$key] }}</div>
                    </div>
                @endif
            @endforeach
        </div>

        @if(!empty($data['calculation_method']))
            <div class="mt-4 text-xs text-base-content/40 text-center">
                Method: {{ $data['calculation_method'] }} · Madhab: {{ $data['madhab'] ?? '' }}
            </div>
        @endif

    {{-- ============================================================ --}}
    {{-- MONTHLY TIMETABLE                                            --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'month' && !empty($monthData['days']))
        <div class="mb-3 text-sm text-base-content/50">
            {{ $monthData['month_name'] ?? '' }} {{ $monthData['year'] ?? '' }} · {{ $monthData['total_days'] ?? '' }} days
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm table-zebra w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Fajr</th>
                        <th>Sunrise</th>
                        <th>Dhuhr</th>
                        <th>Asr</th>
                        <th>Maghrib</th>
                        <th>Isha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthData['days'] as $day)
                        @php $pt = $day['prayer_times']; @endphp
                        <tr>
                            <td class="font-mono text-sm">{{ $day['day'] }}</td>
                            <td class="text-xs text-base-content/50">{{ substr($day['day_name'] ?? '', 0, 3) }}</td>
                            <td>{{ $pt['fajr'] ?? '' }}</td>
                            <td class="text-base-content/50">{{ $pt['sunrise'] ?? '' }}</td>
                            <td>{{ $pt['dhuhr'] ?? '' }}</td>
                            <td>{{ $pt['asr'] ?? '' }}</td>
                            <td>{{ $pt['maghrib'] ?? '' }}</td>
                            <td>{{ $pt['isha'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if(!empty($monthData['calculation_method']))
            <div class="mt-2 text-xs text-base-content/40">Method: {{ $monthData['calculation_method'] }}</div>
        @endif

    {{-- ============================================================ --}}
    {{-- RAMADAN                                                      --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'ramadan')
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <span class="text-sm font-semibold">Year:</span>
            @foreach([2025, 2026, 2027] as $yr)
                <button wire:click="$set('ramadanYear', {{ $yr }}); $wire.loadRamadan()"
                        class="btn btn-xs {{ $ramadanYear == $yr ? 'btn-primary' : 'btn-ghost' }}">{{ $yr }}</button>
            @endforeach
        </div>

        @if(!empty($ramadanData['days']))
            <div class="mb-3">
                <x-badge :value="'Ramadan ' . ($ramadanData['hijri_year'] ?? '') . ' — ' . ($ramadanData['ramadan_days'] ?? '') . ' days'"
                         class="badge-primary badge-lg" />
                <span class="text-sm text-base-content/50 ml-2">
                    {{ $ramadanData['ramadan_start'] ?? '' }} → {{ $ramadanData['ramadan_end'] ?? '' }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Date</th>
                            <th>Hijri</th>
                            <th>Third</th>
                            <th>Suhoor Ends</th>
                            <th>Fajr</th>
                            <th>Dhuhr</th>
                            <th>Asr</th>
                            <th>Iftar</th>
                            <th>Isha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ramadanData['days'] as $day)
                            <tr>
                                <td class="font-bold text-primary">{{ $day['day'] }}</td>
                                <td class="text-sm">{{ $day['date'] ?? '' }}</td>
                                <td class="text-xs text-base-content/50">{{ $day['hijri_date'] ?? '' }}</td>
                                <td class="text-xs">{{ $day['third'] ?? '' }}</td>
                                <td class="text-warning font-semibold">{{ $day['suhoor_ends'] ?? '' }}</td>
                                <td>{{ $day['fajr'] ?? '' }}</td>
                                <td>{{ $day['dhuhr'] ?? '' }}</td>
                                <td>{{ $day['asr'] ?? '' }}</td>
                                <td class="text-success font-semibold">{{ $day['iftar'] ?? '' }}</td>
                                <td>{{ $day['isha'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($mode === 'ramadan' && empty($error))
            <div class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>
        @endif

    @elseif(empty($error))
        <div class="flex justify-center py-16">
            <span class="loading loading-spinner loading-lg"></span>
        </div>
    @endif
</div>
