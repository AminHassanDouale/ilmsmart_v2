<?php

use App\Models\SubscriptionHistory;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
class extends Component {
    use WithPagination;

    public function with(): array
    {
        $histories = SubscriptionHistory::with('plan','subscription')
            ->where('user_id', auth()->id())
            ->latest('occurred_at')
            ->paginate(15);

        $totalSpent = SubscriptionHistory::where('user_id', auth()->id())
            ->whereIn('event', ['created','renewed','upgraded'])
            ->sum('amount');

        return compact('histories', 'totalSpent');
    }
};
?>

<div>
    <x-header title="Subscription History" subtitle="All your subscription activity">
        <x-slot:actions>
            <a href="{{ route('individual.subscriptions') }}" wire:navigate class="btn btn-ghost btn-sm">
                <x-icon name="o-arrow-left" class="w-4 h-4" /> Back to Plans
            </a>
        </x-slot:actions>
    </x-header>

    {{-- Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-6">
        <x-stat title="Total Events" :value="$histories->total()" icon="o-clipboard-document-list" />
        <x-stat title="Total Spent" :value="number_format($totalSpent, 0) . ' DZD'" icon="o-banknotes" color="text-success" />
        <x-stat title="Active Plan" :value="auth()->user()->activeSubscription?->plan?->name ?? 'None'" icon="o-star" color="text-info" />
    </div>

    @if($histories->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-clock" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No subscription history yet.</p>
            <a href="{{ route('individual.subscriptions') }}" wire:navigate class="btn btn-primary mt-4">View Plans</a>
        </x-card>
    @else
        {{-- Timeline --}}
        <div class="relative pl-6 sm:pl-8 space-y-4">
            {{-- Vertical line --}}
            <div class="absolute left-2 sm:left-3 top-2 bottom-2 w-0.5 bg-base-300"></div>

            @foreach($histories as $h)
                <div class="relative">
                    {{-- Timeline dot --}}
                    <div @class([
                        'absolute -left-6 sm:-left-8 top-3 w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-base-100 shadow',
                        'bg-success' => in_array($h->event, ['created','reactivated','renewed']),
                        'bg-warning' => in_array($h->event, ['upgraded','downgraded']),
                        'bg-error'   => in_array($h->event, ['cancelled','expired','refunded']),
                    ])></div>

                    <x-card class="!p-3 sm:!p-4">
                        <div class="flex items-start gap-3 flex-wrap sm:flex-nowrap">
                            <div @class([
                                'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
                                'bg-success/10 text-success' => in_array($h->event, ['created','reactivated','renewed']),
                                'bg-warning/10 text-warning' => in_array($h->event, ['upgraded','downgraded']),
                                'bg-error/10 text-error'     => in_array($h->event, ['cancelled','expired','refunded']),
                            ])>
                                <x-icon name="{{ $h->event_icon }}" class="w-5 h-5" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 flex-wrap">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm sm:text-base capitalize">
                                            {{ $h->event_label }} @if($h->plan) — {{ $h->plan->name }} @endif
                                        </p>
                                        @if($h->notes)
                                            <p class="text-xs text-base-content/60 mt-0.5">{{ $h->notes }}</p>
                                        @endif
                                    </div>
                                    @if($h->amount > 0)
                                        <p @class([
                                            'font-bold text-sm shrink-0',
                                            'text-success' => in_array($h->event, ['created','renewed','upgraded']),
                                            'text-error'   => $h->event === 'refunded',
                                        ])>
                                            {{ $h->event === 'refunded' ? '-' : '+' }}{{ number_format($h->amount, 0) }} {{ $h->currency }}
                                        </p>
                                    @endif
                                </div>
                                <p class="text-xs text-base-content/40 mt-1">
                                    {{ $h->occurred_at?->format('M d, Y · H:i') }}
                                    ({{ $h->occurred_at?->diffForHumans() }})
                                </p>
                            </div>
                        </div>
                    </x-card>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $histories->links() }}</div>
    @endif
</div>
