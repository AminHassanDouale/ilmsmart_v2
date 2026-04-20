<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Qibla Direction')]
class extends Component {
    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public string  $latitude  = '36.7';
    public string  $longitude = '3.05';
    public string  $cityName  = 'Algiers';
    public array   $data      = [];
    public ?string $error     = null;

    public function mount(): void { $this->load(); }

    public function load(): void
    {
        $this->error = null;
        try {
            $r = Http::timeout(15)
                ->withHeaders(['X-API-Key' => self::KEY, 'Accept' => 'application/json'])
                ->get(self::BASE . "/api/qibla?latitude={$this->latitude}&longitude={$this->longitude}");
            if ($r->successful() && $r->json('success')) {
                $this->data  = $r->json('data') ?? [];
                $this->error = null;
            } else {
                $this->error = "HTTP {$r->status()}";
            }
        } catch (\Exception $e) { $this->error = $e->getMessage(); }
    }

    public function setCity(string $lat, string $lng, string $name): void
    {
        $this->latitude  = $lat;
        $this->longitude = $lng;
        $this->cityName  = $name;
        $this->load();
    }
}; ?>

<div>
    <x-header title="Qibla Direction" :subtitle="'Direction from ' . $cityName" separator>
        <x-slot:actions>
            <x-button label="Refresh" icon="o-arrow-path" wire:click="load" spinner="load" class="btn-ghost btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- City presets --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach([
            ['36.7',  '3.05',   'Algiers'],
            ['21.39', '39.85',  'Mecca'],
            ['51.51', '-0.13',  'London'],
            ['48.85', '2.35',   'Paris'],
            ['40.71', '-74.01', 'New York'],
            ['1.35',  '103.82', 'Singapore'],
            ['-33.87','151.21', 'Sydney'],
        ] as [$lat, $lng, $name])
            <x-button :label="$name"
                      wire:click="setCity('{{ $lat }}', '{{ $lng }}', '{{ $name }}')"
                      spinner="setCity"
                      class="btn-ghost btn-sm {{ $cityName === $name ? 'btn-active btn-primary' : '' }}" />
        @endforeach
    </div>

    {{-- Custom coords --}}
    <div class="flex items-end gap-3 mb-6 flex-wrap">
        <div class="form-control">
            <label class="label label-text text-xs">Latitude</label>
            <input type="text" wire:model="latitude" class="input input-bordered input-sm w-28" />
        </div>
        <div class="form-control">
            <label class="label label-text text-xs">Longitude</label>
            <input type="text" wire:model="longitude" class="input input-bordered input-sm w-28" />
        </div>
        <x-button label="Load" icon="o-magnifying-glass" wire:click="load" spinner="load" class="btn-primary btn-sm" />
    </div>

    @if($error)
        <x-alert title="API Error" :description="$error" icon="o-exclamation-triangle" class="alert-error mb-4" />
    @endif

    @if(!empty($data['direction']))
        @php $deg = round($data['direction'], 2); @endphp
        <div class="flex flex-col lg:flex-row items-center gap-8 py-4">

            {{-- Compass --}}
            <div class="relative w-64 h-64 shrink-0">
                {{-- Outer ring --}}
                <div class="absolute inset-0 rounded-full border-4 border-base-300 bg-base-100 shadow-lg"></div>
                {{-- Degree marks --}}
                @foreach([0, 45, 90, 135, 180, 225, 270, 315] as $angle)
                    <div class="absolute inset-0 flex items-start justify-center"
                         style="transform: rotate({{ $angle }}deg)">
                        <div class="w-0.5 h-4 bg-base-300 mt-1"></div>
                    </div>
                @endforeach
                {{-- Cardinal labels --}}
                <div class="absolute top-3 left-1/2 -translate-x-1/2 text-sm font-bold text-base-content/60">N</div>
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 text-sm font-bold text-base-content/60">S</div>
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-base-content/60">W</div>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold text-base-content/60">E</div>
                {{-- Qibla needle --}}
                <div class="absolute inset-0 flex items-center justify-center"
                     style="transform: rotate({{ $deg }}deg)">
                    <div class="relative w-1 h-24 -translate-y-6">
                        <div class="w-0 h-0 border-l-4 border-r-4 border-b-8 border-transparent border-b-primary mx-auto"></div>
                        <div class="w-1 bg-primary flex-1 h-full mx-auto"></div>
                    </div>
                </div>
                {{-- Center dot --}}
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-4 h-4 rounded-full bg-primary shadow"></div>
                </div>
            </div>

            {{-- Info --}}
            <div class="text-center lg:text-left">
                <div class="text-xs text-base-content/50 uppercase tracking-wider mb-1">Qibla Direction</div>
                <div class="text-6xl font-bold text-primary mb-2">{{ $deg }}°</div>
                @if(!empty($data['compass_direction']))
                    <div class="text-2xl font-semibold text-base-content/70 mb-4">{{ $data['compass_direction'] }}</div>
                @endif
                <div class="text-sm text-base-content/50 mb-2">From {{ $cityName }} ({{ $latitude }}, {{ $longitude }})</div>
                @if(!empty($data['distance_to_mecca_km']))
                    <x-badge :value="'Distance to Mecca: ' . number_format($data['distance_to_mecca_km']) . ' km'" class="badge-ghost" />
                @endif
                <div class="mt-4 p-3 bg-primary/10 rounded-lg text-sm text-primary/80">
                    Face <strong>{{ $deg }}° ({{ $data['compass_direction'] ?? '' }})</strong> to face the Kaaba in Mecca.
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-16 text-base-content/40">Select a city or enter coordinates</div>
    @endif
</div>
