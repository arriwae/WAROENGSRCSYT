<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        // 1. Determine period date range (timezone Asia/Jakarta is configured in app.php)
        $period = $request->input('period', 'month');
        $startDate = null;
        $endDate = Carbon::now()->endOfDay();

        switch ($period) {
            case 'today':
                $startDate = Carbon::today()->startOfDay();
                break;
            case 'week':
                $startDate = Carbon::today()->subDays(6)->startOfDay();
                break;
            case 'month':
                $startDate = Carbon::today()->subDays(29)->startOfDay();
                break;
            case '3months':
                $startDate = Carbon::today()->subDays(89)->startOfDay();
                break;
            case 'custom':
                $startInput = $request->input('start_date');
                $endInput = $request->input('end_date');
                if ($startInput) {
                    $startDate = Carbon::parse($startInput)->startOfDay();
                } else {
                    $startDate = Carbon::today()->subDays(29)->startOfDay();
                }
                if ($endInput) {
                    $endDate = Carbon::parse($endInput)->endOfDay();
                }
                break;
            default:
                $period = 'month';
                $startDate = Carbon::today()->subDays(29)->startOfDay();
                break;
        }

        // 2. Calculate Total Omzet (Gross Sales)
        $totalOmzet = Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total_price');

        // 3. Calculate HPP (Cost of Goods Sold / Cost of Sales)
        $totalHpp = SaleDetail::whereHas('sale', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->sum(DB::raw('quantity * purchase_price'));

        // 4. Calculate Laba Kotor (Gross Profit)
        $totalLabaKotor = $totalOmzet - $totalHpp;

        // 5. Calculate Cash Inflow Breakdown
        // Direct cash sales
        $salesCash = Sale::where('payment_method', 'cash')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_price');

        // Direct bank transfer sales
        $salesTransfer = Sale::where('payment_method', 'transfer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_price');

        // Direct QRIS sales
        $salesQris = Sale::where('payment_method', 'qris')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_price');

        // Debt payments collected in this period (includes Down Payments and cicilan)
        $debtPaymentsCollected = DebtPayment::whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('payment_amount');

        // Real cash inflows = direct cash/transfer/qris + debt collections
        $totalCashInflow = $salesCash + $salesTransfer + $salesQris + $debtPaymentsCollected;

        // 6. Calculate Debt Summary (Piutang)
        // Total credit sales created in this period
        $newDebtsValue = Sale::where('payment_method', 'debt')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_price');

        // Outstanding receivables remaining in total (all time unpaid balance)
        $outstandingReceivables = Debt::sum('remaining_amount');

        // Calculate total discounts from vouchers in this period
        $totalDiscounts = Sale::whereBetween('created_at', [$startDate, $endDate])->sum('discount_amount');

        // 7. Compile Financial Cash Flow Ledger (Ledger Log)
        $ledger = [];

        // Add sales receipts to ledger
        $salesList = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($salesList as $sale) {
            if ($sale->payment_method !== 'debt') {
                // Direct payment
                $ledger[] = [
                    'date' => $sale->created_at,
                    'type' => 'Penjualan',
                    'reference' => $sale->invoice_number,
                    'customer' => $sale->customer_name ?? 'Pelanggan Umum',
                    'method' => strtoupper($sale->payment_method),
                    'inflow' => (float) $sale->total_price,
                    'notes' => 'Lunas'
                ];
            } else {
                // Debt sale - check if DP was paid (Down Payment is stored as DebtPayment)
                // We will handle Down Payment in the DebtPayment loop to avoid double-counting
            }
        }

        // Add debt payments (DP and installments) to ledger
        $debtPaymentsList = DebtPayment::with('debt.sale')
            ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        foreach ($debtPaymentsList as $payment) {
            $saleRef = $payment->debt->sale->invoice_number ?? 'UTANG-#' . $payment->debt_id;
            $customerName = $payment->debt->customer_name ?? 'Pelanggan';
            $isDp = str_contains(strtolower($payment->notes), 'uang muka') || str_contains(strtolower($payment->notes), 'dp');
            
            $ledger[] = [
                'date' => Carbon::parse($payment->created_at), // use created_at for precise time sorting if available
                'type' => $isDp ? 'Uang Muka (DP)' : 'Cicilan Utang',
                'reference' => $saleRef,
                'customer' => $customerName,
                'method' => 'TUNAI', // Payments are cash by default
                'inflow' => (float) $payment->payment_amount,
                'notes' => $payment->notes ?? 'Pembayaran Piutang'
            ];
        }

        // Sort ledger descending by date
        usort($ledger, function ($a, $b) {
            return $b['date']->timestamp <=> $a['date']->timestamp;
        });

        // Paginate ledger manually (limit to top 100 for safety, showing in page)
        $ledgerPage = array_slice($ledger, 0, 100);

        return view('finance.index', [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalOmzet' => $totalOmzet,
            'totalHpp' => $totalHpp,
            'totalLabaKotor' => $totalLabaKotor,
            'salesCash' => $salesCash,
            'salesTransfer' => $salesTransfer,
            'salesQris' => $salesQris,
            'debtPaymentsCollected' => $debtPaymentsCollected,
            'totalCashInflow' => $totalCashInflow,
            'newDebtsValue' => $newDebtsValue,
            'outstandingReceivables' => $outstandingReceivables,
            'ledger' => $ledgerPage,
            'totalDiscounts' => $totalDiscounts
        ]);
    }

    public function checkState()
    {
        $lastSaleId = Sale::max('id') ?? 0;
        $lastPaymentId = DebtPayment::max('id') ?? 0;

        return response()->json([
            'last_sale_id' => $lastSaleId,
            'last_payment_id' => $lastPaymentId,
        ]);
    }
}
