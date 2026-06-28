<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CashierController extends Controller
{
    /**
     * Show the cashier POS screen.
     */
    public function index()
    {
        // Load initial products (excluding expired items to be safe, but including them with warning if they want to sell)
        // Let's load all products sorted by name so they are searchable client-side immediately
        $products = Product::orderBy('name', 'asc')->get();
        return view('cashier.index', compact('products'));
    }

    /**
     * Search products via AJAX (useful for barcode scanner or dynamic dropdowns).
     */
    public function search(Request $request)
    {
        $query = $request->get('query');
        $products = Product::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('sku', 'like', "%{$query}%");
        })->limit(20)->get();

        return response()->json($products);
    }

    /**
     * Process checkout transaction.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required', // Relaxed to allow string IDs for custom/digital items
            'cart.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,debt,transfer,qris',
            'payment_amount' => 'required|numeric|min:0',
            'voucher_code' => 'nullable|string',
            // Debt inputs (required if payment_method is debt)
            'customer_name' => 'required_if:payment_method,debt|nullable|string|max:255',
            'due_date' => 'nullable|date|after_or_equal:today',
        ], [
            'customer_name.required_if' => 'Nama pelanggan wajib diisi jika menggunakan metode pembayaran Utang.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo tidak boleh di masa lalu.',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $cart = $request->input('cart');
                $paymentMethod = $request->input('payment_method');
                $paymentAmount = (float) $request->input('payment_amount');
                
                // 1. Calculate original subtotal first
                $cartSubtotal = 0;
                foreach ($cart as $item) {
                    $quantity = (int) $item['quantity'];
                    $isCustom = isset($item['is_custom']) && $item['is_custom'];
                    if ($isCustom) {
                        $sellingPrice = (float) ($item['price'] ?? 0);
                        $cartSubtotal += $sellingPrice * $quantity;
                    } else {
                        $product = Product::find($item['id']);
                        if (!$product) {
                            throw new \Exception("Produk dengan ID {$item['id']} tidak ditemukan.");
                        }
                        $cartSubtotal += $product->selling_price * $quantity;
                    }
                }

                // 2. Validate voucher code if supplied
                $discountAmount = 0;
                $voucherId = null;
                $voucherCode = $request->input('voucher_code');
                if ($voucherCode) {
                    $voucher = \App\Models\Voucher::where('code', strtoupper(trim($voucherCode)))->lockForUpdate()->first();
                    if (!$voucher) {
                        throw new \Exception("Voucher dengan kode '" . strtoupper($voucherCode) . "' tidak ditemukan.");
                    }
                    $check = $voucher->checkValidity($cartSubtotal);
                    if (!$check['valid']) {
                        throw new \Exception($check['message']);
                    }
                    $discountAmount = $voucher->getDiscountValue($cartSubtotal);
                    $voucherId = $voucher->id;

                    // Decrement voucher stock
                    $voucher->decrement('stock', 1);
                }

                $finalPrice = $cartSubtotal - $discountAmount;

                // 3. Generate unique invoice number: INV-YYYYMMDD-XXXX
                $dateStr = Carbon::now()->format('Ymd');
                $randomDigits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $invoiceNumber = 'INV-' . $dateStr . '-' . $randomDigits;

                // Ensure unique
                while (Sale::where('invoice_number', $invoiceNumber)->exists()) {
                    $randomDigits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $invoiceNumber = 'INV-' . $dateStr . '-' . $randomDigits;
                }

                // 4. Create initial sale record
                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'voucher_id' => $voucherId,
                    'total_price' => $finalPrice,
                    'discount_amount' => $discountAmount,
                    'payment_amount' => $paymentAmount,
                    'change_amount' => 0,
                    'payment_method' => $paymentMethod,
                ]);

                // 5. Process each item in cart and deduct stocks
                foreach ($cart as $item) {
                    $quantity = (int) $item['quantity'];
                    $isCustom = isset($item['is_custom']) && $item['is_custom'];

                    if ($isCustom) {
                        // Digital Top Up or Bank Transfer
                        $customName = $item['name'];
                        $purchasePrice = (float) ($item['purchase_price'] ?? 0);
                        $sellingPrice = (float) ($item['price'] ?? 0);
                        $subtotal = $sellingPrice * $quantity;

                        SaleDetail::create([
                            'sale_id' => $sale->id,
                            'product_id' => null,
                            'custom_name' => $customName,
                            'quantity' => $quantity,
                            'purchase_price' => $purchasePrice,
                            'selling_price' => $sellingPrice,
                            'subtotal' => $subtotal,
                        ]);
                    } else {
                        // Physical product from catalog
                        $product = Product::lockForUpdate()->find($item['id']);
                        if (!$product) {
                            throw new \Exception("Produk dengan ID {$item['id']} tidak ditemukan.");
                        }

                        // Check stock
                        if ($product->stock < $quantity) {
                            throw new \Exception("Stok produk '{$product->name}' tidak mencukupi. Tersedia: {$product->stock}, Diminta: {$quantity}.");
                        }

                        // Deduct stock
                        $product->decrement('stock', $quantity);

                        // Calculate subtotal
                        $subtotal = $product->selling_price * $quantity;

                        // Create sale detail record
                        SaleDetail::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'custom_name' => $product->name,
                            'quantity' => $quantity,
                            'purchase_price' => $product->purchase_price,
                            'selling_price' => $product->selling_price,
                            'subtotal' => $subtotal,
                        ]);
                    }
                }

                // 6. Finalize sale price and change calculations
                $changeAmount = 0;
                if (in_array($paymentMethod, ['cash', 'transfer', 'qris'])) {
                    if ($paymentAmount < $finalPrice) {
                        throw new \Exception("Uang pembayaran (" . number_format($paymentAmount, 0, ',', '.') . ") kurang dari total belanja setelah diskon (" . number_format($finalPrice, 0, ',', '.') . ").");
                    }
                    $changeAmount = $paymentAmount - $finalPrice;
                }

                $sale->update([
                    'change_amount' => $changeAmount,
                ]);

                // 7. If debt, create debt record
                $debt = null;
                if ($paymentMethod === 'debt') {
                    $remainingAmount = $finalPrice - $paymentAmount;
                    if ($remainingAmount <= 0) {
                        throw new \Exception("Jika metode pembayaran adalah utang, total belanja setelah diskon harus lebih besar dari jumlah uang muka (pembayaran awal).");
                    }

                    $status = $paymentAmount > 0 ? 'partially_paid' : 'unpaid';

                    $debt = Debt::create([
                        'sale_id' => $sale->id,
                        'customer_name' => $request->input('customer_name'),
                        'total_amount' => $finalPrice,
                        'remaining_amount' => $remainingAmount,
                        'status' => $status,
                        'due_date' => $request->input('due_date'),
                    ]);

                    // If they paid an initial down payment, record it in debt_payments as well
                    if ($paymentAmount > 0) {
                        $debt->payments()->create([
                            'payment_amount' => $paymentAmount,
                            'payment_date' => Carbon::now()->toDateString(),
                            'notes' => 'Uang muka (pembayaran awal) saat transaksi kasir.',
                        ]);
                    }
                }

                // Load relationships for printing receipt in frontend
                $sale->load('saleDetails.product');

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil diselesaikan.',
                    'data' => [
                        'sale' => $sale,
                        'debt' => $debt,
                        'date_formatted' => $sale->created_at->format('d-m-Y H:i:s'),
                    ]
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
