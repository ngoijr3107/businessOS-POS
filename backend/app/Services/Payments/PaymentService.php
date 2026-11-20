<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function create(array $data, $user): Payment
    {
        return DB::transaction(function () use ($data, $user) {
            $amount = round((float) $data['amount'], 2);
            $allocations = collect($data['allocations'] ?? [])->filter(fn ($a) => (float) ($a['amount'] ?? 0) > 0);
            if ($allocations->pluck('invoice_id')->duplicates()->isNotEmpty()) { throw ValidationException::withMessages(['allocations' => 'Each invoice may only be allocated once per payment.']); }

            $allocationTotal = round((float) $allocations->sum(fn ($a) => (float) $a['amount']), 2);

            if (abs($allocationTotal - $amount) > 0.01) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must equal the total allocated to invoices.']);
            }

            $payment = Payment::create([
                'company_id' => $user->company_id,
                'branch_id' => $data['branch_id'] ?? $user->branch_id,
                'client_id' => $data['client_id'] ?? null,
                'user_id' => $user->id,
                'payment_number' => $this->number($user->company_id),
                'payment_date' => $data['payment_date'] ?? now(),
                'amount' => $amount,
                'method' => $data['method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($allocations as $allocation) {
                $invoice = Invoice::where('company_id', $user->company_id)->lockForUpdate()->findOrFail($allocation['invoice_id']);
                if ($data['client_id'] && (int) $invoice->client_id !== (int) $data['client_id']) {
                    throw ValidationException::withMessages(['allocations' => 'All invoices must belong to the selected client.']);
                }
                $requested = round((float) $allocation['amount'], 2);
                if ($invoice->status === 'cancelled') { throw ValidationException::withMessages(['allocations' => "Cancelled invoices cannot receive payments."]); }
                $balance = round((float) $invoice->balance, 2);
                if ($requested > $balance + 0.01) {
                    throw ValidationException::withMessages(['allocations' => "Allocation exceeds the balance on {$invoice->invoice_number}."]);
                }

                $payment->allocations()->create(['invoice_id' => $invoice->id, 'amount' => $requested]);
                $paid = round((float) $invoice->amount_paid + $requested, 2);
                $newBalance = max(0, round((float) $invoice->total - $paid, 2));
                $invoice->update(['amount_paid' => $paid, 'balance' => $newBalance, 'status' => $newBalance <= 0 ? 'paid' : 'partial']);
            }

            if (!$payment->client_id) {
                $clientId = $payment->allocations()->first()?->invoice?->client_id;
                $payment->update(['client_id' => $clientId]);
            }

            return $payment->load('allocations.invoice.client', 'client');
        });
    }

    private function number($companyId): string
    {
        return 'REC-'.str_pad((string) (Payment::where('company_id', $companyId)->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
