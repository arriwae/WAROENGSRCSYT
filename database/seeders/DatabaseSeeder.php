<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Debt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Admin
        User::create([
            'name' => 'SRC Suyanto',
            'username' => 'SrcSuyanto345',
            'password' => bcrypt('srcsuyanto1928'),
        ]);

        // 2. Create Initial Products
        $today = Carbon::today();
        
        $p1 = Product::create([
            'sku' => '8998866200225',
            'name' => 'Indomie Goreng Spesial',
            'purchase_price' => 2500,
            'selling_price' => 3500,
            'stock' => 50,
            'expiry_date' => $today->copy()->addMonths(8),
        ]);

        $p2 = Product::create([
            'sku' => '8991002100511',
            'name' => 'Kopi Kapal Api 20g',
            'purchase_price' => 1200,
            'selling_price' => 1500,
            'stock' => 120,
            'expiry_date' => $today->copy()->addMonths(12),
        ]);

        $p3 = Product::create([
            'sku' => '8992753011302',
            'name' => 'Susu Kaleng Frisian Flag',
            'purchase_price' => 11000,
            'selling_price' => 13500,
            'stock' => 8, // Low Stock!
            'expiry_date' => $today->copy()->addMonths(4),
        ]);

        $p4 = Product::create([
            'sku' => '8992696500122',
            'name' => 'Minyak Goreng Bimoli 1L',
            'purchase_price' => 14000,
            'selling_price' => 16500,
            'stock' => 25,
            'expiry_date' => $today->copy()->addDays(15), // Near Expiry!
        ]);

        $p5 = Product::create([
            'sku' => '8993189000109',
            'name' => 'Roti Tawar Sari Roti',
            'purchase_price' => 12000,
            'selling_price' => 15000,
            'stock' => 5,
            'expiry_date' => $today->copy()->subDays(3), // Expired!
        ]);

        $p6 = Product::create([
            'sku' => '8996001300011',
            'name' => 'Aqua Gelas 220ml',
            'purchase_price' => 500,
            'selling_price' => 800,
            'stock' => 150,
            'expiry_date' => $today->copy()->addMonths(10),
        ]);

        $p7 = Product::create([
            'sku' => '8999999052028',
            'name' => 'Sabun Lifebuoy Merah',
            'purchase_price' => 3000,
            'selling_price' => 4000,
            'stock' => 4, // Low stock!
            'expiry_date' => null, // No expiry
        ]);

        $p8 = Product::create([
            'sku' => '8992696404390',
            'name' => 'Teh Kotak Sosro 300ml',
            'purchase_price' => 3000,
            'selling_price' => 4000,
            'stock' => 45,
            'expiry_date' => $today->copy()->addMonths(6),
        ]);

        // 3. Create historical sales transactions
        
        // Transaction 1: Cash sale
        $s1 = Sale::create([
            'invoice_number' => 'INV-' . $today->copy()->subDays(5)->format('Ymd') . '-0001',
            'total_price' => 54000, // 10 Indomie (35000) + 24 Aqua Gelas (19200) -> let's make it match
            'payment_amount' => 100000,
            'change_amount' => 46000,
            'payment_method' => 'cash',
            'created_at' => $today->copy()->subDays(5)->setTime(10, 30, 0),
        ]);
        SaleDetail::create([
            'sale_id' => $s1->id,
            'product_id' => $p1->id, // Indomie
            'quantity' => 10,
            'purchase_price' => 2500,
            'selling_price' => 3500,
            'subtotal' => 35000,
            'created_at' => $today->copy()->subDays(5)->setTime(10, 30, 0),
        ]);
        SaleDetail::create([
            'sale_id' => $s1->id,
            'product_id' => $p6->id, // Aqua
            'quantity' => 24,
            'purchase_price' => 500,
            'selling_price' => 800,
            'subtotal' => 19200,
            'created_at' => $today->copy()->subDays(5)->setTime(10, 30, 0),
        ]);
        $s1->update(['total_price' => 54200, 'change_amount' => 45800]); // Sync exact math

        // Transaction 2: Cash sale
        $s2 = Sale::create([
            'invoice_number' => 'INV-' . $today->copy()->subDays(3)->format('Ymd') . '-0002',
            'total_price' => 60000, // 20 Kopi Kapal Api (30000) + 2 Roti Tawar (30000)
            'payment_amount' => 60000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'created_at' => $today->copy()->subDays(3)->setTime(14, 15, 0),
        ]);
        SaleDetail::create([
            'sale_id' => $s2->id,
            'product_id' => $p2->id, // Kopi
            'quantity' => 20,
            'purchase_price' => 1200,
            'selling_price' => 1500,
            'subtotal' => 30000,
            'created_at' => $today->copy()->subDays(3)->setTime(14, 15, 0),
        ]);
        SaleDetail::create([
            'sale_id' => $s2->id,
            'product_id' => $p5->id, // Roti Tawar
            'quantity' => 2,
            'purchase_price' => 12000,
            'selling_price' => 15000,
            'subtotal' => 30000,
            'created_at' => $today->copy()->subDays(3)->setTime(14, 15, 0),
        ]);

        // Transaction 3: Active Debt (Pak Ahmad)
        $s3 = Sale::create([
            'invoice_number' => 'INV-' . $today->copy()->subDays(2)->format('Ymd') . '-0003',
            'total_price' => 82500, // 5 Minyak Bimoli (82500)
            'payment_amount' => 0, // No down payment
            'change_amount' => 0,
            'payment_method' => 'debt',
            'created_at' => $today->copy()->subDays(2)->setTime(9, 0, 0),
        ]);
        SaleDetail::create([
            'sale_id' => $s3->id,
            'product_id' => $p4->id, // Minyak Bimoli
            'quantity' => 5,
            'purchase_price' => 14000,
            'selling_price' => 16500,
            'subtotal' => 82500,
            'created_at' => $today->copy()->subDays(2)->setTime(9, 0, 0),
        ]);
        Debt::create([
            'sale_id' => $s3->id,
            'customer_name' => 'Pak Ahmad',
            'total_amount' => 82500,
            'remaining_amount' => 82500,
            'status' => 'unpaid',
            'due_date' => $today->copy()->addDays(5),
            'created_at' => $today->copy()->subDays(2)->setTime(9, 0, 0),
        ]);

        // Transaction 4: Overdue Debt (Ibu Fatimah)
        $s4 = Sale::create([
            'invoice_number' => 'INV-' . $today->copy()->subDays(10)->format('Ymd') . '-0004',
            'total_price' => 54000, // 4 Susu Kaleng (54000)
            'payment_amount' => 20000, // Paid 20000 DP
            'change_amount' => 0,
            'payment_method' => 'debt',
            'created_at' => $today->copy()->subDays(10)->setTime(16, 45, 0),
        ]);
        SaleDetail::create([
            'sale_id' => $s4->id,
            'product_id' => $p3->id, // Susu Kaleng
            'quantity' => 4,
            'purchase_price' => 11000,
            'selling_price' => 13500,
            'subtotal' => 54000,
            'created_at' => $today->copy()->subDays(10)->setTime(16, 45, 0),
        ]);
        $d4 = Debt::create([
            'sale_id' => $s4->id,
            'customer_name' => 'Ibu Fatimah',
            'total_amount' => 54000,
            'remaining_amount' => 34000, // 54000 - 20000
            'status' => 'partially_paid',
            'due_date' => $today->copy()->subDays(3), // Overdue!
            'created_at' => $today->copy()->subDays(10)->setTime(16, 45, 0),
        ]);
        // Record down payment in payments history
        $d4->payments()->create([
            'payment_amount' => 20000,
            'payment_date' => $today->copy()->subDays(10)->toDateString(),
            'notes' => 'Uang muka (pembayaran awal) saat transaksi kasir.',
            'created_at' => $today->copy()->subDays(10)->setTime(16, 45, 0),
        ]);

        // 4. Create default vouchers
        $v1 = \App\Models\Voucher::create([
            'code' => 'HEMAT5K',
            'type' => 'fixed',
            'value' => 5000,
            'min_spend' => 20000,
            'stock' => 49, // One used by transaction 5 below
            'is_active' => true,
            'expiry_date' => $today->copy()->addMonths(3),
        ]);

        $v2 = \App\Models\Voucher::create([
            'code' => 'DISKON10',
            'type' => 'percent',
            'value' => 10,
            'min_spend' => 30000,
            'max_discount' => 15000,
            'stock' => 30,
            'is_active' => true,
            'expiry_date' => $today->copy()->addMonths(2),
        ]);

        \App\Models\Voucher::create([
            'code' => 'KASIRKEREN',
            'type' => 'fixed',
            'value' => 10000,
            'min_spend' => 50000,
            'stock' => 100,
            'is_active' => true,
            'expiry_date' => $today->copy()->addMonths(1),
        ]);

        // Transaction 5: Sale today using HEMAT5K voucher
        // Items: 5 Indomie (17,500) + 2 Susu Kaleng (27,000) = 44,500 subtotal. Discount = 5,000. Total = 39,500.
        $s5 = Sale::create([
            'invoice_number' => 'INV-' . $today->format('Ymd') . '-0005',
            'voucher_id' => $v1->id,
            'total_price' => 39500,
            'discount_amount' => 5000,
            'payment_amount' => 50000,
            'change_amount' => 10500,
            'payment_method' => 'cash',
            'created_at' => $today->copy()->setTime(11, 0, 0),
        ]);
        
        SaleDetail::create([
            'sale_id' => $s5->id,
            'product_id' => $p1->id, // Indomie
            'quantity' => 5,
            'purchase_price' => 2500,
            'selling_price' => 3500,
            'subtotal' => 17500,
            'created_at' => $today->copy()->setTime(11, 0, 0),
        ]);
        
        SaleDetail::create([
            'sale_id' => $s5->id,
            'product_id' => $p3->id, // Susu Kaleng
            'quantity' => 2,
            'purchase_price' => 11000,
            'selling_price' => 13500,
            'subtotal' => 27000,
            'created_at' => $today->copy()->setTime(11, 0, 0),
        ]);
    }
}
