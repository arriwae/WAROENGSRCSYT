<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DebtController extends Controller
{
    /**
     * Display a listing of the debts.
     */
    public function index(Request $request)
    {
        $query = Debt::with('sale');

        // Filter by search (customer name or invoice number)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', '%' . $search . '%')
                  ->orWhereHas('sale', function ($sq) use ($search) {
                      $sq->where('invoice_number', 'like', '%' . $search . '%');
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        } else {
            // By default show unpaid & partially paid first
            $query->whereIn('status', ['unpaid', 'partially_paid']);
        }

        $debts = $query->orderBy('due_date', 'asc')->paginate(10);
        return view('debts.index', compact('debts'));
    }

    /**
     * Display the specified debt details and payment history.
     */
    public function show(Debt $debt)
    {
        $debt->load(['sale.saleDetails.product', 'payments']);
        return view('debts.show', compact('debt'));
    }

    /**
     * Store a new installment/payment for the specified debt.
     */
    public function storePayment(Request $request, Debt $debt)
    {
        $maxAmount = $debt->remaining_amount;

        $request->validate([
            'payment_amount' => 'required|numeric|min:1|max:' . $maxAmount,
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ], [
            'payment_amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa utang yaitu Rp. ' . number_format($maxAmount, 0, ',', '.'),
        ]);

        try {
            DB::transaction(function () use ($request, $debt) {
                $amount = (float) $request->input('payment_amount');
                $date = $request->input('payment_date');
                $notes = $request->input('notes');

                // Create the payment record
                $debt->payments()->create([
                    'payment_amount' => $amount,
                    'payment_date' => $date,
                    'notes' => $notes ?? 'Pembayaran cicilan utang.',
                ]);

                // Update remaining amount and status
                $newRemaining = $debt->remaining_amount - $amount;
                $newStatus = $newRemaining <= 0 ? 'paid' : 'partially_paid';

                $debt->update([
                    'remaining_amount' => $newRemaining,
                    'status' => $newStatus,
                ]);

                // Also update the associated sale payment amount if we want to reflect total paid
                // (though sale payment_amount is typically snapshot of checkout cash paid, it's optional)
                if ($debt->sale) {
                    $debt->sale->increment('payment_amount', $amount);
                }
            });

            return redirect()->route('debts.show', $debt->id)->with('success', 'Pembayaran cicilan berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mencatat pembayaran: ' . $e->getMessage()]);
        }
    }
}
