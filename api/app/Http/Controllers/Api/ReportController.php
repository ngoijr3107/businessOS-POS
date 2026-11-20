<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function company(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    public function overview(Request $request)
    {
        $companyId = $this->company($request);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $sales = Sale::where('company_id', $companyId)->where('status', 'completed')->whereBetween('sale_date', [$from, $to]);
        $expenses = Expense::where('company_id', $companyId)->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        $payments = Payment::where('company_id', $companyId)->whereBetween('payment_date', [$from, $to]);

        $revenue = (float) $sales->sum('total');
        $expenseTotal = (float) $expenses->sum('amount');
        $receivables = (float) Invoice::where('company_id', $companyId)->where('balance', '>', 0)->where('status', '!=', 'cancelled')->sum('balance');
        $overdue = (float) Invoice::where('company_id', $companyId)->where('balance', '>', 0)->where('status', '!=', 'cancelled')->whereNotNull('due_date')->whereDate('due_date', '<', today())->sum('balance');
        $grossProfit = (float) DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sales.company_id', $companyId)->where('sales.status', 'completed')->whereBetween('sales.sale_date', [$from, $to])->selectRaw('COALESCE(SUM(sale_items.total - (sale_items.quantity * sale_items.cost_price)), 0) as value')->value('value');

        $topClients = Client::where('company_id', $companyId)->withSum(['sales as sales_total' => fn ($q) => $q->where('status', 'completed')->whereBetween('sale_date', [$from, $to])], 'total')->orderByDesc('sales_total')->limit(5)->get(['id', 'name']);
        $topProducts = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->join('products', 'products.id', '=', 'sale_items.product_id')->where('sales.company_id', $companyId)->where('sales.status', 'completed')->whereBetween('sales.sale_date', [$from, $to])->groupBy('products.id', 'products.name')->select('products.id', 'products.name', DB::raw('SUM(sale_items.quantity) quantity'), DB::raw('SUM(sale_items.total) revenue'))->orderByDesc('revenue')->limit(5)->get();

        $daily = Sale::where('company_id', $companyId)->where('status', 'completed')->whereBetween('sale_date', [$from, $to])->selectRaw('DATE(sale_date) day, SUM(total) total')->groupBy('day')->orderBy('day')->get();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'cards' => [
                'revenue' => $revenue,
                'payments' => (float) $payments->sum('amount'),
                'expenses' => $expenseTotal,
                'gross_profit' => $grossProfit,
                'net_profit' => $grossProfit - $expenseTotal,
                'receivables' => $receivables,
                'overdue' => $overdue,
                'open_invoices' => Invoice::where('company_id', $companyId)->where('balance', '>', 0)->where('status', '!=', 'cancelled')->count(),
                'low_stock' => Product::where('company_id', $companyId)->where('type', 'product')->where('track_stock', true)->whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            ],
            'daily_sales' => $daily,
            'top_clients' => $topClients,
            'top_products' => $topProducts,
            'insights' => $this->insights($companyId, $revenue, $grossProfit, $expenseTotal, $receivables, $overdue),
        ];
    }

    private function insights(int $companyId, float $revenue, float $grossProfit, float $expenses, float $receivables, float $overdue): array
    {
        $signals=[];
        if ($overdue>0) $signals[]=['level'=>'risk','title'=>'Collection pressure','message'=>number_format($overdue,2).' TSh is overdue. Prioritise the oldest invoices and high-value customers.'];
        if ($receivables>0 && $revenue>0 && $receivables>$revenue*.5) $signals[]=['level'=>'warn','title'=>'Receivables are elevated','message'=>'Outstanding customer debt is more than half of period revenue. Tighten credit follow-up.'];
        if ($grossProfit<0 || $grossProfit<$expenses) $signals[]=['level'=>'risk','title'=>'Margin pressure','message'=>'Operating expenses are exceeding gross profit for the selected period.'];
        $low=Product::where('company_id',$companyId)->where('type','product')->where('track_stock',true)->whereColumn('stock_quantity','<=','minimum_stock')->count();
        if($low) $signals[]=['level'=>'warn','title'=>'Stock replenishment','message'=>$low.' product(s) are at or below minimum stock.'];
        if(!$signals) $signals[]=['level'=>'good','title'=>'Healthy operating signal','message'=>'No major financial or stock risks were detected for the selected period.'];
        return $signals;
    }

    public function sales(Request $request)
    {
        $companyId = $this->company($request);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $sales = Sale::where('company_id', $companyId)->where('status', 'completed')->whereBetween('sale_date', [$from, $to]);

        return [
            'total' => (float) $sales->sum('total'),
            'count' => $sales->count(),
            'cash_or_paid' => (float) (clone $sales)->where('payment_status', 'paid')->sum('total'),
            'credit' => (float) (clone $sales)->whereIn('payment_status', ['partial', 'credit'])->sum('total'),
            'discounts' => (float) $sales->sum('discount'),
            'tax' => (float) $sales->sum('tax'),
            'daily' => (clone $sales)->selectRaw('DATE(sale_date) day, SUM(total) total')->groupBy('day')->orderBy('day')->get(),
        ];
    }

    public function expenses(Request $request)
    {
        $companyId = $this->company($request);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->toDateString();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->toDateString();
        $query = Expense::where('company_id', $companyId)->whereBetween('expense_date', [$from, $to]);
        return ['total' => (float) $query->sum('amount'), 'by_category' => (clone $query)->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')->groupBy('expense_categories.id', 'expense_categories.name')->select('expense_categories.name', DB::raw('SUM(expenses.amount) total'))->orderByDesc('total')->get()];
    }

    public function inventory(Request $request)
    {
        $companyId = $this->company($request);
        $products = Product::where('company_id', $companyId)->where('type', 'product')->where('track_stock', true)->get(['id', 'name', 'sku', 'stock_quantity', 'minimum_stock', 'cost_price', 'selling_price']);
        return ['stock_value' => (float) $products->sum(fn ($p) => (float) $p->stock_quantity * (float) $p->cost_price), 'retail_value' => (float) $products->sum(fn ($p) => (float) $p->stock_quantity * (float) $p->selling_price), 'low_stock' => $products->filter(fn ($p) => (float) $p->stock_quantity <= (float) $p->minimum_stock)->values(), 'products' => $products];
    }

    public function receivables(Request $request)
    {
        $companyId = $this->company($request);
        $invoices = Invoice::where('company_id', $companyId)->where('balance', '>', 0)->where('status', '!=', 'cancelled')->with('client')->orderBy('due_date')->get();
        $aging = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
        foreach ($invoices as $invoice) {
            $days = $invoice->due_date ? today()->diffInDays(Carbon::parse($invoice->due_date), false) * -1 : 0;
            $key = $days <= 0 ? 'current' : ($days <= 30 ? '1_30' : ($days <= 60 ? '31_60' : ($days <= 90 ? '61_90' : '90_plus')));
            $aging[$key] += (float) $invoice->balance;
        }
        return ['total' => (float) $invoices->sum('balance'), 'overdue' => (float) $invoices->filter(fn ($i) => $i->due_date && Carbon::parse($i->due_date)->isBefore(today()))->sum('balance'), 'count' => $invoices->count(), 'aging' => $aging, 'invoices' => $invoices];
    }
}
