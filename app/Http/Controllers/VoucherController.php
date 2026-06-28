<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $query = Voucher::orderBy('created_at', 'desc');

        if ($search) {
            $query->where('code', 'like', "%{$search}%");
        }

        $vouchers = $query->paginate(15);
        return view('vouchers.index', compact('vouchers', 'search'));
    }

    /**
     * Store a newly created voucher in database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_spend' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after_or_equal:today',
        ], [
            'code.unique' => 'Kode voucher ini sudah terdaftar.',
            'expiry_date.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh di masa lalu.',
        ]);

        // Convert code to uppercase for consistency
        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = true;

        Voucher::create($validated);

        return redirect()->route('vouchers.index')->with('success', 'Voucher baru berhasil ditambahkan.');
    }

    /**
     * Update the specified voucher.
     */
    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_spend' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'required|boolean',
        ], [
            'code.unique' => 'Kode voucher ini sudah terdaftar.',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $voucher->update($validated);

        return redirect()->route('vouchers.index')->with('success', 'Data voucher berhasil diubah.');
    }

    /**
     * Delete/Destroy the specified voucher.
     */
    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->route('vouchers.index')->with('success', 'Voucher berhasil dihapus.');
    }

    /**
     * Validate voucher code via AJAX API at POS checkout.
     */
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $code = strtoupper(trim($request->input('code')));
        $totalAmount = (float) $request->input('total_amount');

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak valid atau tidak terdaftar.'
            ]);
        }

        $check = $voucher->checkValidity($totalAmount);

        if (!$check['valid']) {
            return response()->json([
                'success' => false,
                'message' => $check['message']
            ]);
        }

        $discountAmount = $voucher->getDiscountValue($totalAmount);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dipasang!',
            'data' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'type' => $voucher->type,
                'value' => (float) $voucher->value,
                'min_spend' => (float) $voucher->min_spend,
                'max_discount' => $voucher->max_discount ? (float) $voucher->max_discount : null,
                'discount_amount' => $discountAmount,
            ]
        ]);
    }
}
