<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard index with statistics.
     */
    public function index()
    {
        $today = Carbon::today();

        // 1. Core counters
        $totalProducts = Product::count();
        
        $assetValue = Product::select(DB::raw('SUM(stock * purchase_price) as total'))->first()->total ?? 0;
        $potentialProfit = Product::select(DB::raw('SUM(stock * (selling_price - purchase_price)) as total'))->first()->total ?? 0;

        $totalReceivable = Debt::where('status', '!=', 'paid')->sum('remaining_amount');
        
        $todaySalesCount = Sale::whereDate('created_at', $today)->count();
        $todaySalesAmount = Sale::whereDate('created_at', $today)->sum('total_price');

        // 2. Alert notifications
        $lowStockProducts = Product::where('stock', '<', 10)->orderBy('stock', 'asc')->get();
        
        // Products that are already expired
        $expiredProducts = Product::whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->orderBy('expiry_date', 'asc')
            ->get();
            
        // Products expiring within 30 days (but not expired yet)
        $nearExpiryProducts = Product::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $today->copy()->addDays(30))
            ->orderBy('expiry_date', 'asc')
            ->get();

        // 3. Sales Analytics (Most Popular and Least Popular)
        // Most Popular (Paling Laris)
        $popularProducts = Product::select('products.*', 'sales_summary.total_sold')
            ->joinSub(
                DB::table('sale_details')
                    ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
                    ->groupBy('product_id'),
                'sales_summary',
                'products.id',
                '=',
                'sales_summary.product_id'
            )
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // Least Popular (Tidak Laris)
        $unpopularProducts = Product::select('products.*', DB::raw('COALESCE(sales_summary.total_sold, 0) as total_sold'))
            ->leftJoinSub(
                DB::table('sale_details')
                    ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
                    ->groupBy('product_id'),
                'sales_summary',
                'products.id',
                '=',
                'sales_summary.product_id'
            )
            ->orderBy('total_sold', 'asc')
            ->limit(5)
            ->get();

        // 4. Debts Overdue (Utang Jatuh Tempo)
        $overdueDebts = Debt::where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->orderBy('due_date', 'asc')
            ->get();

        return view('dashboard', compact(
            'totalProducts',
            'assetValue',
            'potentialProfit',
            'totalReceivable',
            'todaySalesCount',
            'todaySalesAmount',
            'lowStockProducts',
            'expiredProducts',
            'nearExpiryProducts',
            'popularProducts',
            'unpopularProducts',
            'overdueDebts'
        ));
    }
}
