<?php

namespace App\Services;

use App\Models\{Invoice, Payment, Subscription, Plan, User};

class PaymentService
{
    /**
     * Create a payment and generate an invoice.
     */
    public function createPayment(array $data): Payment
    {
        $payment = Payment::create([
            'user_id'         => $data['user_id'],
            'amount'          => $data['amount'],
            'currency'        => $data['currency'] ?? 'DZD',
            'method'          => $data['method'] ?? 'cash',
            'status'          => 'completed',
            'paid_at'         => now(),
            'notes'           => $data['notes'] ?? null,
            'course_id'       => $data['course_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'received_by'     => $data['received_by'] ?? auth()->id(),
        ]);

        $this->generateInvoice($payment);

        return $payment;
    }

    /**
     * Subscribe a user to a plan.
     */
    public function subscribe(User $user, Plan $plan, array $paymentData = []): Subscription
    {
        $endsAt = match($plan->billing_cycle) {
            'monthly'   => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly'    => now()->addYear(),
            default     => null,
        };

        $subscription = Subscription::create([
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'starts_at'  => now(),
            'ends_at'    => $endsAt,
            'auto_renew' => $paymentData['auto_renew'] ?? false,
            'status'     => 'active',
        ]);

        if ($plan->price > 0) {
            $this->createPayment([
                'user_id'         => $user->id,
                'amount'          => $plan->price,
                'method'          => $paymentData['method'] ?? 'cash',
                'subscription_id' => $subscription->id,
            ]);
        }

        return $subscription;
    }

    private function generateInvoice(Payment $payment): Invoice
    {
        return Invoice::create([
            'payment_id'     => $payment->id,
            'user_id'        => $payment->user_id,
            'amount'         => $payment->amount,
            'tax'            => 0,
            'total'          => $payment->amount,
            'currency'       => $payment->currency,
            'status'         => 'paid',
            'issue_date'     => today(),
            'due_date'       => today(),
        ]);
    }
}
