<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new
#[Layout('components.layouts.app')]
#[Title('Asma ul Husna')]
class extends Component {
    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    public array   $names = [];
    public array   $meta  = [];
    public ?string $error = null;

    public function mount(): void { $this->load(); }

    public function load(): void
    {
        $this->error = null;
        try {
            $r = Http::timeout(15)
                ->withHeaders(['X-API-Key' => self::KEY, 'Accept' => 'application/json'])
                ->get(self::BASE . '/api/asma-ul-husna');
            if ($r->successful() && $r->json('success')) {
                $d = $r->json('data') ?? [];
                $this->names = $d['names']  ?? [];
                $this->meta  = array_diff_key($d, ['names' => null]);
            } else {
                $this->error = "HTTP {$r->status()}";
            }
        } catch (\Exception $e) { $this->error = $e->getMessage(); }
    }
}; ?>

<div>
    <x-header title="Asma ul Husna" subtitle="The 99 Beautiful Names of Allah" separator>
        <x-slot:actions>
            @if(!empty($meta['arabic_title']))
                <span class="font-arabic text-2xl text-primary font-bold" dir="rtl">{{ $meta['arabic_title'] }}</span>
            @endif
            <x-badge :value="count($names) . ' names'" class="badge-primary" />
            <x-button label="Refresh" icon="o-arrow-path" wire:click="load" spinner="load" class="btn-ghost btn-sm" />
        </x-slot:actions>
    </x-header>

    @if($error)
        <x-alert title="API Error" :description="$error" icon="o-exclamation-triangle" class="alert-error mb-4" />
    @endif

    @if(!empty($meta['description']))
        <x-card shadow class="mb-4 bg-primary/5">
            <p class="text-sm text-base-content/70">{{ $meta['description'] }}</p>
            @if(!empty($meta['hadith']))
                <p class="text-xs text-base-content/50 mt-2 italic">— {{ $meta['hadith'] }}</p>
            @endif
        </x-card>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
        @foreach($names as $name)
            <x-card shadow class="hover:shadow-lg hover:border-primary/30 border transition-all cursor-default text-center p-3">
                <div class="text-xs text-base-content/30 mb-1">#{{ $name['number'] ?? '' }}</div>
                <div class="font-arabic text-2xl font-bold text-primary mb-1" dir="rtl">{{ $name['arabic'] ?? '' }}</div>
                <div class="text-xs font-semibold text-base-content/80 mb-1">{{ $name['transliteration'] ?? '' }}</div>
                <div class="text-xs text-base-content/50 leading-tight">{{ $name['meaning'] ?? $name['english'] ?? '' }}</div>
            </x-card>
        @endforeach
    </div>

    @if(empty($names) && !$error)
        <div class="text-center py-16 text-base-content/40">Loading...</div>
    @endif
</div>
