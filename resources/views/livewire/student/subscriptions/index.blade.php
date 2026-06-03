<?php

use App\Models\SubscriptionHistory;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public function with(): array
    {
        return [
            'activeSubscription'  => auth()->user()->activeSubscription,
            'history'             => SubscriptionHistory::with('plan')
                                        ->where('user_id', auth()->id())
                                        ->latest('occurred_at')->take(20)->get(),
            'totalSpent'          => SubscriptionHistory::where('user_id', auth()->id())
                                        ->whereIn('event',['created','renewed','upgraded'])->sum('amount'),
        ];
    }
};
?>

<div>
    <x-header title="My Subscription" subtitle="Plan details and activity" />

    @if($activeSubscription)
        <div class="bg-gradient-to-r from-primary to-purple-600 text-white rounded-xl p-4 sm:p-6 mb-6 shadow-lg">
            <p class="text-sm opacity-90">Current Plan</p>
            <h2 class="text-xl sm:text-2xl font-bold">{{ $activeSubscription->plan->name }}</h2>
            <p class="text-xs opacity-80 mt-1">
                Active since {{ $activeSubscription->starts_at?->format('M d, Y') }} ·
                Renews {{ $activeSubscription->ends_at?->format('M d, Y') ?? 'never' }}
            </p>
        </div>
    @else
        <x-card class="text-center py-10 mb-6">
            <x-icon name="o-star" class="w-14 h-14 mx-auto text-base-content/20 mb-3" />
            <p class="text-base-content/60">No active subscription.</p>
        </x-card>
    @endif

    <h3 class="font-bold mb-3">Recent Activity</h3>
    @if($history->isEmpty())
        <p class="text-sm text-base-content/50">No history yet.</p>
    @else
        <div class="space-y-2">
            @foreach($history as $h)
                <x-card class="!p-3">
                    <div class="flex items-center gap-3">
                        <x-icon name="{{ $h->event_icon }}" class="w-5 h-5 text-primary" />
                        <div class="flex-1">
                            <p class="text-sm font-medium capitalize">{{ $h->event_label }} {{ $h->plan?->name }}</p>
                            <p class="text-xs text-base-content/50">{{ $h->occurred_at?->format('M d, Y · H:i') }}</p>
                        </div>
                        @if($h->amount > 0)
                            <span class="font-bold text-sm">{{ number_format($h->amount, 0) }} DZD</span>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>
