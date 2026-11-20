<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $companyId = $request->user()->company_id;
        $start = now()->startOfMonth();
        $sales = Sale::where('company_id', $companyId)->where('status', 'completed')->whereBetween('sale_date', [$start, now()]);
        $receivables = Invoice::where('company_id', $companyId)->where('balance', '>', 0)->where('status', '!=', 'cancelled');
        $overdue = (clone $receivables)->whereNotNull('due_date')->whereDate('due_date', '<', today());
        $daily = Sale::where('company_id', $companyId)->where('status', 'completed')->whereBetween('sale_date', [now()->subDays(6)->startOfDay(), now()->endOfDay()])->selectRaw('DATE(sale_date) day, SUM(total) total')->groupBy('day')->orderBy('day')->get();
        $topClients = Client::where('company_id', $companyId)->withSum(['sales as sales_total' => fn ($q) => $q->where('status', 'completed')->whereBetween('sale_date', [$start, now()])], 'total')->orderByDesc('sales_total')->limit(5)->get(['id', 'name']);
        $lowStock = Product::where('company_id', $companyId)->where('type', 'product')->where('track_stock', true)->whereColumn('stock_quantity', '<=', 'minimum_stock')->orderBy('stock_quantity')->limit(8)->get(['id', 'name', 'sku', 'stock_quantity', 'minimum_stock']);
        return ['cards' => [
            'sales_month' => (float) $sales->sum('total'),
            'payments_month' => (float) Payment::where('company_id', $companyId)->whereBetween('payment_date', [$start, now()])->sum('amount'),
            'receivables' => (float) $receivables->sum('balance'),
            'clients' => Client::where('company_id', $companyId)->where('is_active', true)->count(),
            'overdue' => (float) $overdue->sum('balance'),
            'open_invoices' => $receivables->count(),
            'low_stock' => $lowStock->count(),
        ], 'daily_sales' => $daily, 'top_clients' => $topClients, 'low_stock_items' => $lowStock, 'recent_sales' => Sale::where('company_id', $companyId)->with('client')->latest('sale_date')->limit(8)->get()];
    }
}
