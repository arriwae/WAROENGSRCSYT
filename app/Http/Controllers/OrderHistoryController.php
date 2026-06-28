<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrderHistoryController extends Controller
{
    /**
     * Show the order history and turnover analytics.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'today'); // today, week, month, 3months, custom
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $search = $request->get('search');
        $paymentMethod = $request->get('payment_method');
        
        $today = Carbon::today();
        
        // Base query for filter period (not paginated)
        $query = Sale::query();
        
        // Apply time filters
        if ($period === 'today') {
            $query->whereDate('created_at', $today);
        } elseif ($period === 'week') {
            $query->where('created_at', '>=', $today->copy()->subDays(6)->startOfDay()); // past 7 days including today
        } elseif ($period === 'month') {
            $query->where('created_at', '>=', $today->copy()->subDays(29)->startOfDay()); // past 30 days including today
        } elseif ($period === '3months') {
            $query->where('created_at', '>=', $today->copy()->subDays(89)->startOfDay()); // past 90 days including today
        } elseif ($period === 'custom' && $startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } else {
            // Fallback default is today
            $query->whereDate('created_at', $today);
        }
        
        // Get copy of query for stats (without search & payment method filters so cards show period sums)
        $statsQuery = clone $query;
        
        // Apply search and payment method to the list query specifically
        $listQuery = clone $query;
        if ($search) {
            $listQuery->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('debt', function($dq) use ($search) {
                      $dq->where('customer_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('saleDetails', function($sdq) use ($search) {
                      $sdq->where('custom_name', 'like', "%{$search}%")
                          ->orWhereHas('product', function($pq) use ($search) {
                              $pq->where('name', 'like', "%{$search}%");
                          });
                  });
            });
        }
        if ($paymentMethod) {
            $listQuery->where('payment_method', $paymentMethod);
        }
        
        // Fetch statistics for cards
        // 1. Total Turnover (Omzet) for selected period
        $totalTurnover = $statsQuery->sum('total_price');
        
        // 2. Today's Turnover (always today, for visual confirmation)
        $todayTurnover = Sale::whereDate('created_at', $today)->sum('total_price');
        
        // 3. Total Profit (from sale details) in selected period
        $saleIds = $statsQuery->pluck('id');
        $totalProfit = 0;
        if ($saleIds->isNotEmpty()) {
            $grossProfit = SaleDetail::whereIn('sale_id', $saleIds)
                ->select(DB::raw('SUM(quantity * (selling_price - purchase_price)) as profit'))
                ->first()->profit ?? 0;
            
            $totalDiscounts = Sale::whereIn('id', $saleIds)->sum('discount_amount');
            $totalProfit = $grossProfit - $totalDiscounts;
        }
            
        // 4. Total Transactions in selected period
        $totalTransactions = $statsQuery->count();
        
        // Fetch paginated transactions with details
        $transactions = $listQuery->with(['saleDetails.product', 'debt'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();
            
        // Generate data for revenue history (riwayat omzet chart)
        $chartData = [];
        if ($period === 'today') {
            // Group by Hour for Today
            $salesByHour = $statsQuery->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('SUM(total_price) as total')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
            
            for ($i = 0; $i < 24; $i++) {
                $hourData = $salesByHour->firstWhere('hour', $i);
                $label = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $chartData[$label] = $hourData ? (float)$hourData->total : 0;
            }
        } else {
            // Group by Date for other periods
            $salesByDate = $statsQuery->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_price) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
            $daysCount = $period === 'week' ? 7 : ($period === 'month' ? 30 : 90);
            if ($period === 'custom' && $startDate && $endDate) {
                $daysCount = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                if ($daysCount > 365) $daysCount = 365; // cap to 1 year
            }
            
            $end = ($period === 'custom' && $endDate) ? Carbon::parse($endDate) : $today;
            for ($i = $daysCount - 1; $i >= 0; $i--) {
                $currentDay = $end->copy()->subDays($i);
                $dateStr = $currentDay->format('Y-m-d');
                $dateLabel = $currentDay->format('d M');
                $dateData = $salesByDate->firstWhere('date', $dateStr);
                $chartData[$dateLabel] = $dateData ? (float)$dateData->total : 0;
            }
        }
        
        return view('history.index', compact(
            'transactions',
            'totalTurnover',
            'todayTurnover',
            'totalProfit',
            'totalTransactions',
            'chartData',
            'period',
            'startDate',
            'endDate',
            'search',
            'paymentMethod'
        ));
    }
}
