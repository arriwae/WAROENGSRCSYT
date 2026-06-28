@extends('layouts.app')

@section('title', 'Halaman Kasir')
@section('page-header', 'Halaman Kasir')

@section('styles')
<style>
    /* Hide in screen mode */
    #receipt-print-area {
        display: none;
    }
    
    /* Print CSS Styles - Optimized for 58mm Thermal Receipt Printer */
    @media print {
        body, html {
            background: white !important;
            color: black !important;
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
        }
        
        /* Hide all layout elements of the screen */
        .app-container, .modal-overlay, button, .btn, .no-print {
            display: none !important;
            visibility: hidden !important;
        }
        
        /* Reveal and style ONLY the receipt print area */
        #receipt-print-area {
            display: block !important;
            visibility: visible !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 58mm !important; /* Standard 58mm paper width */
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 10pt !important;
            line-height: 1.2 !important;
            color: black !important;
            padding: 2mm !important;
        }

        #receipt-print-area * {
            visibility: visible !important;
            color: black !important;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 4mm;
        }
        
        .receipt-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .receipt-divider {
            border-top: 1px dashed black;
            margin: 2mm 0;
        }
        
        .receipt-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
        }
        
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3mm 0;
            font-size: 9pt;
        }
        
        .receipt-table td {
            padding: 1mm 0;
            vertical-align: top;
        }
        
        .receipt-totals {
            width: 100%;
            margin-top: 2mm;
        }
        
        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
            font-weight: bold;
            padding: 1mm 0;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 6mm;
            font-size: 9pt;
        }
    }
</style>
@endsection

@section('content')
<div class="pos-tabs no-print">
    <button type="button" id="pos-tab-catalog" class="pos-tab active" onclick="switchPosTab('catalog')">
        <i class="fas fa-box-open"></i> Katalog Barang
    </button>
    <button type="button" id="pos-tab-cart" class="pos-tab" onclick="switchPosTab('cart')">
        <i class="fas fa-shopping-cart"></i> Keranjang
        <span id="pos-cart-badge" class="pos-cart-badge">0</span>
    </button>
</div>

<div class="pos-layout">
    <!-- Left Panel: Products List -->
    <div class="products-panel active-tab">
        <div class="search-bar-container" style="display: flex; gap: 12px; width: 100%;">
            <input type="text" id="products-search" class="form-control" placeholder="Cari nama barang atau scan barcode..." style="border-radius: var(--radius-sm); font-size: 1rem; padding: 12px 16px; flex-grow: 1;">
            <button type="button" onclick="openDigitalModal()" class="btn btn-primary" style="border-radius: var(--radius-sm); white-space: nowrap; background: var(--grad-purple); box-shadow: 0 4px 10px rgba(168,85,247,0.3); font-weight: bold; gap: 6px;">
                <i class="fas fa-mobile-alt"></i> Top Up / Transfer
            </button>
        </div>
        
        <div class="products-grid" id="products-grid">
            @forelse($products as $item)
                @php
                    $isOut = $item->stock < 1;
                    $isLow = $item->isLowStock();
                    $isExp = $item->isExpired();
                @endphp
                <div class="product-card" 
                     data-id="{{ $item->id }}" 
                     data-sku="{{ $item->sku }}"
                     @if(!$isOut && !$isExp) onclick="addToCart({{ json_encode($item) }})" @endif
                     style="@if($isOut || $isExp) opacity: 0.5; pointer-events: none; @endif">
                    
                    <div style="position: absolute; top: 8px; right: 8px; z-index: 1;" class="product-card-badge-container">
                        @if($isExp)
                            <span class="badge danger">Kedaluwarsa</span>
                        @elseif($isOut)
                            <span class="badge danger">Stok Habis</span>
                        @elseif($isLow)
                            <span class="badge warning">Stok Hampir Habis</span>
                        @endif
                    </div>

                    <img src="{{ $item->image_url }}" class="product-card-img" alt="{{ $item->name }}">
                    
                    <div class="product-card-info">
                        <span class="product-card-name" title="{{ $item->name }}">{{ $item->name }}</span>
                        <span class="product-card-price">{{ 'Rp. ' . number_format($item->selling_price, 0, ',', '.') }}</span>
                        
                        <div class="product-card-stock" style="margin-top: 4px;">
                            <span>Stok: <strong class="product-card-stock-value">{{ $item->stock }}</strong></span>
                            @if($item->expiry_date)
                                <span style="font-size: 0.65rem; color: var(--text-secondary);">
                                    Exp: {{ $item->expiry_date->format('d/m/y') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: span 10; text-align: center; color: var(--text-secondary); padding: 60px;">
                    <i class="fas fa-box-open" style="font-size: 4rem; opacity: 0.2; display: block; margin-bottom: 16px;"></i>
                    Belum ada barang terdaftar di database.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Right Panel: Shopping Cart -->
    <div class="cart-panel">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart" style="color:var(--primary); margin-right:8px;"></i> Keranjang Belanja</h3>
            <span class="badge info" id="cart-items-count" style="font-size: 0.85rem; padding: 4px 10px;">0</span>
        </div>
        
        <!-- Cart Items Scroll Area -->
        <div class="cart-items" id="cart-items">
            <div style="text-align: center; color: var(--text-secondary); margin-top: 40px;">
                <i class="fas fa-shopping-cart" style="font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.3;"></i>
                Keranjang Belanja Kosong
            </div>
        </div>
        
        <!-- Cart Summary & Checkout -->
        <div class="cart-summary">
            <div class="summary-row">
                <span style="color:var(--text-secondary);">Total Sementara:</span>
                <span id="cart-subtotal" style="font-weight:600;">Rp. 0</span>
            </div>
            
            <div class="summary-row" id="voucher-discount-row" style="display: none; border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 4px;">
                <span style="color: var(--danger);">Diskon Voucher:</span>
                <span id="voucher-discount-amount" style="color: var(--danger); font-weight: 700;">-Rp. 0</span>
            </div>
            
            <div class="summary-row total">
                <span>TOTAL BAYAR:</span>
                <span id="cart-total">Rp. 0</span>
            </div>
            
            <!-- Payment Form -->
            <div style="margin-top: 14px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                <!-- Voucher Input -->
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="voucher-code-input">Voucher Belanja</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="voucher-code-input" class="form-control" placeholder="Contoh: HEMAT5K" style="text-transform: uppercase; background-color:rgba(0,0,0,0.3); border-color:var(--border-color); flex-grow: 1;">
                        <button type="button" id="apply-voucher-btn" class="btn btn-secondary" onclick="applyVoucher()" style="border-color: var(--primary); color: var(--primary); padding: 0 16px; border-radius: var(--radius-sm); font-weight: bold; white-space: nowrap;">Pasang</button>
                    </div>
                    <div id="voucher-msg" style="margin-top: 4px; font-size: 0.8rem; display: none;"></div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="payment-method">Cara Pembayaran</label>
                    <select id="payment-method" class="form-control" style="background-color:rgba(0,0,0,0.3); border-color:var(--border-color);">
                        <option value="cash">Uang Tunai (Cash)</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS (Cashless)</option>
                        <option value="debt">Utang / Kasbon (Bayar Nanti)</option>
                    </select>
                </div>
                
                <!-- Debt specific fields (hidden by default) -->
                <div id="debt-fields" style="display: none; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.15); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 12px;">
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label for="customer-name" style="color: #fbbf24;">Nama Pelanggan <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="customer-name" class="form-control" placeholder="Contoh: Budi Susanto" style="background-color:rgba(0,0,0,0.3); border-color:rgba(245,158,11,0.2);">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="due-date" style="color: #fbbf24;">Batas Jatuh Tempo</label>
                        <input type="date" id="due-date" class="form-control" style="background-color:rgba(0,0,0,0.3); border-color:rgba(245,158,11,0.2);" min="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="payment-amount" id="payment-label">Jumlah Uang Diterima / DP</label>
                    <input type="number" id="payment-amount" class="form-control" min="0" value="0" style="font-size: 1.15rem; font-weight:800; text-align:right; color: #34d399; background-color:rgba(0,0,0,0.3);">
                </div>

                <div class="summary-row" style="margin-bottom: 12px; font-weight: 700;">
                    <span id="change-label" style="color:var(--text-secondary);">Uang Kembalian:</span>
                    <span id="change-amount" style="color:var(--success); font-size:1.1rem;">Rp. 0</span>
                </div>
                
                <button type="button" id="checkout-btn" class="btn btn-primary" style="width: 100%; padding: 12px 20px; font-size:1rem; border-radius:var(--radius-md);" disabled>
                    <i class="fas fa-check-circle"></i> PROSES PEMBAYARAN
                </button>
            </div>
        </div>
    </div>
</div>

@include('partials.receipt-modal')

<!-- Digital Transaction (Top Up / Transfer) Modal -->
<div class="modal-overlay" id="digital-modal">
    <div class="modal-content" style="max-width: 450px; border-color: var(--primary);">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-mobile-alt" style="color:var(--primary); margin-right:6px;"></i> Transaksi Digital / Top Up / Transfer</h3>
            <button type="button" class="modal-close" onclick="closeDigitalModal()">&times;</button>
        </div>
        
        <form id="digital-form" onsubmit="addDigitalToCart(event)">
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="digital-type">Jenis Layanan</label>
                    <select id="digital-type" class="form-control" style="background-color:rgba(0,0,0,0.3); border-color:var(--border-color);" onchange="updateDigitalPlaceholder()">
                        <option value="dana">Top Up E-Wallet: DANA</option>
                        <option value="gopay">Top Up E-Wallet: GoPay</option>
                        <option value="ovo">Top Up E-Wallet: OVO</option>
                        <option value="shopeepay">Top Up E-Wallet: ShopeePay</option>
                        <option value="linkaja">Top Up E-Wallet: LinkAja</option>
                        <option value="bank">Transfer Bank (BRI, Mandiri, BCA, dll.)</option>
                        <option value="other">Layanan Digital Lainnya (Token PLN, Pulsa, dll.)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0; display: none;" id="digital-bank-name-container">
                    <label for="digital-bank-name">Nama Bank / Provider</label>
                    <input type="text" id="digital-bank-name" class="form-control" placeholder="Contoh: Bank BRI / Token Listrik" style="background-color:rgba(0,0,0,0.3);">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="digital-target" id="digital-target-label">Nomor Handphone (HP) Tujuan</label>
                    <input type="text" id="digital-target" class="form-control" placeholder="Contoh: 081234567890" style="background-color:rgba(0,0,0,0.3);" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="digital-amount">Nominal Kirim / Top Up (Rp)</label>
                    <input type="number" id="digital-amount" class="form-control" min="1000" placeholder="Contoh: 50000" style="font-size: 1.15rem; font-weight:800; color: #34d399; background-color:rgba(0,0,0,0.3);" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="digital-fee">Biaya Admin / Jasa Kasir (Rp)</label>
                    <input type="number" id="digital-fee" class="form-control" min="0" value="2000" style="font-size: 1.15rem; font-weight:800; color: #fbbf24; background-color:rgba(0,0,0,0.3);" required>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDigitalModal()">Batal</button>
                <button type="submit" class="btn btn-success">Tambahkan ke Keranjang</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('print-area')
<!-- Hidden element specifically populated for printing window.print() -->
<div id="receipt-print-area"></div>
@endsection

@section('scripts')
<script>
    const checkoutUrl = "{{ route('cashier.checkout') }}";
    
    // Tab switching for mobile POS layout
    window.switchPosTab = function(tabName) {
        const catalogBtn = document.getElementById('pos-tab-catalog');
        const cartBtn = document.getElementById('pos-tab-cart');
        const catalogPanel = document.querySelector('.products-panel');
        const cartPanel = document.querySelector('.cart-panel');
        
        if (!catalogBtn || !cartBtn || !catalogPanel || !cartPanel) return;
        
        if (tabName === 'catalog') {
            catalogBtn.classList.add('active');
            cartBtn.classList.remove('active');
            catalogPanel.classList.add('active-tab');
            cartPanel.classList.remove('active-tab');
        } else {
            catalogBtn.classList.remove('active');
            cartBtn.classList.add('active');
            catalogPanel.classList.remove('active-tab');
            cartPanel.classList.add('active-tab');
        }
    };
</script>
<!-- Import Cashier JS Logic -->
<script src="{{ asset('js/cashier.js') }}"></script>
@endsection
