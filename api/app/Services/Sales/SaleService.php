<?php

namespace App\Services\Sales;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use RuntimeException;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function create(array $data, $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $paymentStatus = $data['payment_status'] ?? 'paid';
            $clientId = $data['client_id'] ?? null;

            if ($paymentStatus === 'credit' && !$clientId) {
                throw ValidationException::withMessages([
                    'client_id' => 'A credit sale must have a client. The client will receive an invoice.',
                ]);
            }

            $sale = Sale::create([
                'company_id' => $user->company_id,
                'branch_id' => $data['branch_id'] ?? $user->branch_id,
                'client_id' => $clientId,
                'user_id' => $user->id,
                'sale_number' => $this->number($user->company_id),
                'sale_date' => $data['sale_date'] ?? now(),
                'status' => 'completed',
                'payment_status' => $paymentStatus,
                'subtotal' => 0,
                'discount' => $data['discount'] ?? 0,
                'tax' => 0,
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $tax = 0;

            foreach ($data['items'] as $item) {
                $product = Product::where('company_id', $user->company_id)->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];

                if ($product->track_stock && (float) $product->stock_quantity < $qty) {
                    throw new RuntimeException("Insufficient stock for {$product->name}");
                }

                $base = $qty * (float) $item['unit_price'];
                $lineDiscount = (float) ($item['discount'] ?? 0);
                $line = max(0, $base - $lineDiscount);
                $lineTax = $line * ((float) $product->tax_rate / 100);

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $product->cost_price,
                    'discount' => $lineDiscount,
                    'tax' => $lineTax,
                    'total' => $line + $lineTax,
                ]);

                $subtotal += $base;
                $tax += $lineTax;

                if ($product->track_stock) {
                    $product->decrement('stock_quantity', $qty);
                    $product->refresh();
                    $product->stockMovements()->create([
                        'company_id' => $user->company_id,
                        'branch_id' => $sale->branch_id,
                        'user_id' => $user->id,
                        'type' => 'sale',
                        'quantity' => -$qty,
                        'balance_after' => $product->stock_quantity,
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'notes' => 'Sale '.$sale->sale_number,
                    ]);
                }
            }

            $discount = (float) ($data['discount'] ?? 0);
            $total = max(0, $subtotal - $discount + $tax);
            $sale->update(['subtotal' => $subtotal, 'tax' => $tax, 'total' => $total]);

            // Option A: every customer sale is linked to an invoice.
            // Credit sales create an outstanding invoice. Paid sales create an invoice
            // and an immediate payment allocation. Walk-in paid sales remain receipts
            // because the invoice schema requires a client.
            if ($clientId) {
                $invoice = $this->createInvoiceFromSale($sale, $user, $paymentStatus);

                if ($paymentStatus === 'paid' && $total > 0) {
                    app(PaymentService::class)->create([
                        'branch_id' => $sale->branch_id,
                        'client_id' => $clientId,
                        'payment_date' => $sale->sale_date,
                        'amount' => $total,
                        'method' => $data['payment_method'] ?? 'cash',
                        'reference' => $data['payment_reference'] ?? null,
                        'notes' => 'Payment for '.$sale->sale_number,
                        'allocations' => [[
                            'invoice_id' => $invoice->id,
                            'amount' => $total,
                        ]],
                    ], $user);
                }
            }

            return $sale->fresh()->load('items.product', 'client', 'invoice', 'invoice.allocations.payment');
        });
    }

    private function createInvoiceFromSale(Sale $sale, $user, string $paymentStatus): Invoice
    {
        $total = (float) $sale->total;
        $sale->loadMissing('client');
        $invoiceStatus = 'issued';

        $invoice = Invoice::create([
            'company_id' => $sale->company_id,
            'branch_id' => $sale->branch_id,
            'client_id' => $sale->client_id,
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'invoice_number' => $this->invoiceNumber($sale->company_id),
            'issue_date' => Carbon::parse($sale->sale_date)->toDateString(),
            'due_date' => $paymentStatus === 'credit'
                ? Carbon::parse($sale->sale_date)->addDays((int)($sale->client?->credit_days ?? 30))->toDateString()
                : Carbon::parse($sale->sale_date)->toDateString(),
            'status' => $invoiceStatus,
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount,
            'tax' => $sale->tax,
            'total' => $total,
            'amount_paid' => 0,
            'balance' => $total,
            'notes' => $sale->notes,
        ]);

        foreach ($sale->items as $item) {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'description' => $item->product?->name ?? 'Sale item',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'tax' => $item->tax,
                'total' => $item->total,
            ]);
        }

        return $invoice;
    }

    private function number($companyId): string
    {
        return 'SALE-'.str_pad((string) (Sale::where('company_id', $companyId)->count() + 1), 6, '0', STR_PAD_LEFT);
    }

    private function invoiceNumber($companyId): string
    {
        return 'INV-'.str_pad((string) (Invoice::where('company_id', $companyId)->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
