<?php

use App\Models\SubscriptionHistory;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search      = '';
    public string $filterEvent = '';
    public ?string $fromDate   = null;
    public ?string $toDate     = null;

    public function with(): array
    {
        $query = SubscriptionHistory::with(['user','plan','subscription'])
            ->when($this->filterEvent, fn($q) => $q->where('event', $this->filterEvent))
            ->when($this->fromDate,    fn($q) => $q->whereDate('occurred_at', '>=', $this->fromDate))
            ->when($this->toDate,      fn($q) => $q->whereDate('occurred_at', '<=', $this->toDate))
            ->when($this->search, fn($q) => $q->whereHas('user', fn($u) =>
                $u->where('name','like',"%{$this->search}%")
                  ->orWhere('email','like',"%{$this->search}%")
            ))
            ->latest('occurred_at');

        $totalRevenue = (clone $query)->whereIn('event', ['created','renewed','upgraded'])->sum('amount');
        $refunds      = (clone $query)->where('event', 'refunded')->sum('amount');

        return [
            'histories'    => $query->paginate(20),
            'totalRevenue' => $totalRevenue,
            'refunds'      => $refunds,
            'totalEvents'  => SubscriptionHistory::count(),
        ];
    }

    public function exportCsv()
    {
        $rows = SubscriptionHistory::with(['user','plan'])
            ->when($this->filterEvent, fn($q) => $q->where('event', $this->filterEvent))
            ->latest('occurred_at')->get();

        $filename = 'subscription-history-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Date','User','Email','Event','Plan','Amount','Notes']);
            foreach ($rows as $r) {
                fputcsv($h, [
                    $r->occurred_at?->format('Y-m-d H:i'),
                    $r->user?->full_name,
                    $r->user?->email,
                    $r->event,
                    $r->plan?->name,
                    $r->amount,
                    $r->notes,
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
};
?>

<div>
    <x-header title="Subscription History" subtitle="Full audit trail of all subscription events">
        <x-slot:actions>
            <x-button label="Export CSV" icon="o-arrow-down-tray" wire:click="exportCsv" class="btn-ghost" responsive />
        </x-slot:actions>
    </x-header>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-6">
        <x-stat title="Total Events" :value="$totalEvents" icon="o-chart-bar" />
        <x-stat title="Total Revenue" :value="number_format($totalRevenue, 0) . ' DZD'" icon="o-banknotes" color="text-success" />
        <x-stat title="Refunded" :value="number_format($refunds, 0) . ' DZD'" icon="o-arrow-uturn-left" color="text-error" />
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <x-input placeholder="Search user..." wire:model.live.debounce="search" icon="o-magnifying-glass" />
        <x-select wire:model.live="filterEvent" placeholder="All events" :options="[
            ['id'=>'created','name'=>'Created'],
            ['id'=>'renewed','name'=>'Renewed'],
            ['id'=>'upgraded','name'=>'Upgraded'],
            ['id'=>'downgraded','name'=>'Downgraded'],
            ['id'=>'cancelled','name'=>'Cancelled'],
            ['id'=>'expired','name'=>'Expired'],
            ['id'=>'reactivated','name'=>'Reactivated'],
            ['id'=>'refunded','name'=>'Refunded'],
        ]" option-value="id" option-label="name" />
        <x-input type="date" wire:model.live="fromDate" placeholder="From" />
        <x-input type="date" wire:model.live="toDate" placeholder="To" />
    </div>

    {{-- Timeline --}}
    @if($histories->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-clipboard-document-list" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No subscription events yet.</p>
            <p class="text-sm text-base-content/40 mt-1">Events will appear here as users subscribe, renew, or cancel.</p>
        </x-card>
    @else
        <div class="space-y-2">
            @foreach($histories as $h)
                <x-card class="!p-3 sm:!p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        {{-- Event icon --}}
                        <div @class([
                            'w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0',
                            'bg-success/10 text-success' => in_array($h->event, ['created','reactivated']),
                            'bg-info/10 text-info'       => $h->event === 'renewed',
                            'bg-primary/10 text-primary' => $h->event === 'upgraded',
                            'bg-warning/10 text-warning' => $h->event === 'downgraded',
                            'bg-error/10 text-error'     => in_array($h->event, ['cancelled','expired','refunded']),
                        ])>
                            <x-icon name="{{ $h->event_icon }}" class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm sm:text-base">
                                        {{ $h->user?->full_name ?? 'Unknown user' }}
                                        <span class="font-normal text-base-content/60">{{ $h->event_label }}</span>
                                        @if($h->plan)
                                            <span class="text-base-content/60">"{{ $h->plan->name }}"</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-base-content/50 mt-0.5">{{ $h->user?->email }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    @if($h->amount > 0)
                                        <p @class([
                                            'font-bold text-sm sm:text-base',
                                            'text-success' => in_array($h->event, ['created','renewed','upgraded']),
                                            'text-error'   => $h->event === 'refunded',
                                        ])>
                                            {{ in_array($h->event, ['refunded']) ? '-' : '+' }}{{ number_format($h->amount, 0) }} {{ $h->currency }}
                                        </p>
                                    @endif
                                    <p class="text-[10px] text-base-content/40">{{ $h->occurred_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                            @if($h->notes)
                                <p class="text-xs text-base-content/60 mt-2 italic">{{ $h->notes }}</p>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <span class="badge {{ $h->event_color }} badge-xs capitalize">{{ $h->event }}</span>
                                <span class="text-[10px] text-base-content/40">{{ $h->occurred_at?->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-6">{{ $histories->links() }}</div>
    @endif
</div>
