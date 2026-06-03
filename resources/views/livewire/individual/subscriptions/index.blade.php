<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {

    public function with(): array
    {
        return [
            'plans'             => Plan::where('is_active', true)->orderBy('order')->get(),
            'activeSubscription'=> auth()->user()->activeSubscription,
        ];
    }

    public function subscribe(int $planId): void
    {
        $user = auth()->user();
        $plan = Plan::findOrFail($planId);

        // Cancel current
        if ($current = $user->activeSubscription) {
            $current->update(['status' => 'cancelled']);
            SubscriptionHistory::create([
                'user_id'         => $user->id,
                'subscription_id' => $current->id,
                'plan_id'         => $current->plan_id,
                'event'           => 'cancelled',
                'notes'           => 'Cancelled to subscribe to ' . $plan->name,
                'occurred_at'     => now(),
            ]);
        }

        $endsAt = match($plan->billing_cycle) {
            'monthly'   => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly'    => now()->addYear(),
            default     => null,
        };

        $sub = Subscription::create([
            'user_id'    => $user->id,
            'plan_id'    => $planId,
            'starts_at'  => now(),
            'ends_at'    => $endsAt,
            'auto_renew' => $plan->billing_cycle !== 'once',
            'status'     => 'active',
        ]);

        SubscriptionHistory::create([
            'user_id'         => $user->id,
            'subscription_id' => $sub->id,
            'plan_id'         => $planId,
            'event'           => 'created',
            'amount'          => $plan->price,
            'currency'        => 'DZD',
            'notes'           => 'Subscribed to ' . $plan->name,
            'occurred_at'     => now(),
        ]);

        $this->success('Successfully subscribed to ' . $plan->name);
    }

    public function cancel(): void
    {
        $sub = auth()->user()->activeSubscription;
        if (!$sub) return;

        $sub->update(['status' => 'cancelled', 'auto_renew' => false]);

        SubscriptionHistory::create([
            'user_id'         => auth()->id(),
            'subscription_id' => $sub->id,
            'plan_id'         => $sub->plan_id,
            'event'           => 'cancelled',
            'notes'           => 'Cancelled by user',
            'occurred_at'     => now(),
        ]);

        $this->warning('Your subscription has been cancelled.');
    }
};
?>

<div>
    <x-header title="Subscription Plans" subtitle="Choose the plan that fits your learning goals">
        <x-slot:actions>
            <a href="{{ route('individual.subscriptions.history') }}" wire:navigate class="btn btn-ghost btn-sm">
                <x-icon name="o-clock" class="w-4 h-4" /> History
            </a>
        </x-slot:actions>
    </x-header>

    {{-- Active subscription banner --}}
    @if($activeSubscription)
        <div class="bg-gradient-to-r from-primary to-purple-600 text-white rounded-xl p-4 sm:p-6 mb-8 shadow-lg">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="text-sm opacity-90">Current Plan</p>
                    <h2 class="text-xl sm:text-2xl font-bold">{{ $activeSubscription->plan->name }}</h2>
                    <p class="text-xs sm:text-sm opacity-80 mt-1">
                        Started {{ $activeSubscription->starts_at?->format('M d, Y') }} ·
                        Renews {{ $activeSubscription->ends_at?->format('M d, Y') ?? 'never' }}
                    </p>
                </div>
                <button wire:click="cancel" wire:confirm="Cancel your subscription?"
                        class="btn btn-sm bg-white/20 text-white border-0 hover:bg-white/30">
                    Cancel
                </button>
            </div>
        </div>
    @endif

    {{-- Plans --}}
    @if($plans->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-star" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No plans available at the moment.</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach($plans as $idx => $plan)
                @php $isCurrent = $activeSubscription?->plan_id === $plan->id; @endphp
                <div @class([
                    'card bg-base-100 shadow-sm overflow-hidden relative',
                    'ring-2 ring-primary' => $isCurrent,
                    'scale-105 shadow-xl' => $idx === 1 && !$isCurrent,
                ])>
                    @if($idx === 1 && !$isCurrent)
                        <div class="absolute top-0 right-0 bg-warning text-warning-content text-xs px-3 py-1 font-bold rounded-bl-lg">
                            POPULAR
                        </div>
                    @endif
                    @if($isCurrent)
                        <div class="absolute top-0 right-0 bg-primary text-primary-content text-xs px-3 py-1 font-bold rounded-bl-lg">
                            CURRENT
                        </div>
                    @endif

                    <div class="card-body p-5 sm:p-6">
                        <h3 class="font-bold text-lg sm:text-xl">{{ $plan->name }}</h3>

                        <div class="mt-2">
                            <span class="text-3xl sm:text-4xl font-bold">{{ number_format($plan->price, 0) }}</span>
                            <span class="text-sm text-base-content/60">DZD / {{ $plan->billing_cycle }}</span>
                        </div>

                        @if($plan->description)
                            <p class="text-sm text-base-content/60 mt-2">{{ $plan->description }}</p>
                        @endif

                        <div class="divider my-3"></div>

                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <x-icon name="o-check-circle" class="w-4 h-4 text-success shrink-0 mt-0.5" />
                                <span>
                                    @if($plan->unlimited_courses) Unlimited courses
                                    @else Up to {{ $plan->courses_limit }} courses
                                    @endif
                                </span>
                            </li>
                            <li @class(['flex items-start gap-2', 'opacity-40 line-through' => !$plan->live_classes])>
                                <x-icon name="{{ $plan->live_classes ? 'o-check-circle' : 'o-x-circle' }}"
                                        class="w-4 h-4 shrink-0 mt-0.5 {{ $plan->live_classes ? 'text-success' : 'text-base-content/40' }}" />
                                <span>Live classes & sessions</span>
                            </li>
                            <li @class(['flex items-start gap-2', 'opacity-40 line-through' => !$plan->private_tutoring])>
                                <x-icon name="{{ $plan->private_tutoring ? 'o-check-circle' : 'o-x-circle' }}"
                                        class="w-4 h-4 shrink-0 mt-0.5 {{ $plan->private_tutoring ? 'text-success' : 'text-base-content/40' }}" />
                                <span>Private tutoring</span>
                            </li>
                        </ul>

                        <button wire:click="subscribe({{ $plan->id }})"
                                wire:confirm="Subscribe to {{ $plan->name }}?"
                                @class([
                                    'btn w-full mt-4',
                                    'btn-disabled' => $isCurrent,
                                    'btn-primary'  => !$isCurrent,
                                ])>
                            {{ $isCurrent ? 'Current Plan' : 'Subscribe' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
