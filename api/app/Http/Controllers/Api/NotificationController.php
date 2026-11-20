<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessNotification;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $this->sync($companyId, $request->user()->id);
        return BusinessNotification::where('company_id', $companyId)->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $request->user()->id))->latest()->limit(30)->get();
    }

    public function unreadCount(Request $request)
    {
        $companyId = $request->user()->company_id;
        $this->sync($companyId, $request->user()->id);
        return ['count' => BusinessNotification::where('company_id', $companyId)->where('is_read', false)->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $request->user()->id))->count()];
    }

    public function read(Request $request, BusinessNotification $notification)
    {
        abort_unless($notification->company_id === $request->user()->company_id, 404);
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return $notification->fresh();
    }

    private function sync(int $companyId, int $userId): void
    {
        $overdue = Invoice::where('company_id', $companyId)->where('balance', '>', 0)->whereNotNull('due_date')->whereDate('due_date', '<', today())->sum('balance');
        if ($overdue > 0) {
            BusinessNotification::updateOrCreate(['company_id' => $companyId, 'user_id' => null, 'type' => 'overdue_receivables'], ['title' => 'Overdue receivables need attention', 'message' => 'Customers currently owe '.number_format((float) $overdue, 0).' TSh on overdue invoices.', 'data' => ['amount' => (float) $overdue], 'is_read' => false]);
        }
        $low = Product::where('company_id', $companyId)->where('type', 'product')->where('track_stock', true)->whereColumn('stock_quantity', '<=', 'minimum_stock')->count();
        if ($low > 0) {
            BusinessNotification::updateOrCreate(['company_id' => $companyId, 'user_id' => null, 'type' => 'low_stock'], ['title' => 'Low stock alert', 'message' => $low.' product(s) are at or below minimum stock.', 'data' => ['count' => $low], 'is_read' => false]);
        }
    }
}
