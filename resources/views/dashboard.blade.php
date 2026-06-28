@extends('layouts.app')

@section('title', 'Beranda Utama')
@section('page-header', 'Ringkasan Toko')

@section('content')
<!-- Core Stats Grid -->
<div class="stats-grid">
    <!-- Stat 1: Total Products -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Total Jenis Barang</span>
            <div class="stat-icon indigo">
                <i class="fas fa-box"></i>
            </div>
        </div>
        <div class="stat-value">{{ number_format($totalProducts) }}</div>
        <div class="stat-desc">Varian barang yang terdaftar</div>
    </div>
    
    <!-- Stat 2: Asset Value -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Nilai Aset Modal</span>
            <div class="stat-icon emerald">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="stat-value">Rp. {{ number_format($assetValue, 0, ',', '.') }}</div>
        <div class="stat-desc">Total modal barang di toko</div>
    </div>

    <!-- Stat 3: Potential Profit -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Potensi Keuntungan</span>
            <div class="stat-icon purple">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-value">Rp. {{ number_format($potentialProfit, 0, ',', '.') }}</div>
        <div class="stat-desc">Potensi untung jika semua barang habis</div>
    </div>
    
    <!-- Stat 4: Active Receivables -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Total Piutang Aktif (Bon)</span>
            <div class="stat-icon amber">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #fbbf24;">Rp. {{ number_format($totalReceivable, 0, ',', '.') }}</div>
        <div class="stat-desc">Utang pelanggan belum lunas</div>
    </div>
    
    <!-- Stat 5: Today's Transactions -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Transaksi Hari Ini</span>
            <div class="stat-icon indigo">
                <i class="fas fa-shopping-basket"></i>
            </div>
        </div>
        <div class="stat-value">{{ number_format($todaySalesCount) }}</div>
        <div class="stat-desc">Volume transaksi masuk hari ini</div>
    </div>
    
    <!-- Stat 6: Today's Revenue -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Omzet Hari Ini</span>
            <div class="stat-icon emerald">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #34d399;">Rp. {{ number_format($todaySalesAmount, 0, ',', '.') }}</div>
        <div class="stat-desc">Jumlah pemasukan kasir hari ini</div>
    </div>
</div>

<!-- Alert Notifications (Expired, Stock, Overdue Debts) -->
@if($expiredProducts->count() > 0 || $nearExpiryProducts->count() > 0 || $overdueDebts->count() > 0)
<div style="margin-bottom: 32px; display:flex; flex-direction:column; gap:16px;">
    <!-- Overdue Debts Alert -->
    @if($overdueDebts->count() > 0)
        <div class="alert-strip danger" style="margin-bottom: 0;">
            <i class="fas fa-file-invoice-dollar"></i>
            <div class="alert-strip-content">
                <strong>Peringatan Utang Jatuh Tempo:</strong> Ada <strong>{{ $overdueDebts->count() }}</strong> catatan utang pelanggan yang telah melewati batas jatuh tempo!
                <ul style="margin-top: 6px; padding-left: 20px; font-size: 0.85rem;">
                    @foreach($overdueDebts->take(3) as $debt)
                        <li>{{ $debt->customer_name }} - Sisa Utang: Rp. {{ number_format($debt->remaining_amount, 0, ',', '.') }} (Jatuh tempo: {{ $debt->due_date->format('d-m-Y') }})</li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ route('debts.index') }}" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem;">Kelola Utang</a>
        </div>
    @endif

    <!-- Expired Products Alert -->
    @if($expiredProducts->count() > 0)
        <div class="alert-strip danger" style="margin-bottom: 0;">
            <i class="fas fa-calendar-times"></i>
            <div class="alert-strip-content">
                <strong>Barang Kedaluwarsa (Expired):</strong> Ada <strong>{{ $expiredProducts->count() }}</strong> produk yang sudah kedaluwarsa! Segera pisahkan dari rak penjualan.
                <ul style="margin-top: 6px; padding-left: 20px; font-size: 0.85rem;">
                    @foreach($expiredProducts->take(3) as $prod)
                        <li>{{ $prod->name }} (Kedaluwarsa pada: {{ $prod->expiry_date->format('d-m-Y') }})</li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem;">Kelola Barang</a>
        </div>
    @endif

    <!-- Near Expiry Products Alert -->
    @if($nearExpiryProducts->count() > 0)
        <div class="alert-strip warning" style="margin-bottom: 0;">
            <i class="fas fa-hourglass-half"></i>
            <div class="alert-strip-content">
                <strong>Peringatan Barang Segera Kedaluwarsa:</strong> Ada <strong>{{ $nearExpiryProducts->count() }}</strong> produk yang akan kedaluwarsa dalam waktu kurang dari 30 hari!
                <ul style="margin-top: 6px; padding-left: 20px; font-size: 0.85rem;">
                    @foreach($nearExpiryProducts->take(3) as $prod)
                        <li>{{ $prod->name }} - {{ $prod->expiry_date->diffInDays(\Carbon\Carbon::today(), true) }} hari lagi ({{ $prod->expiry_date->format('d-m-Y') }})</li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem;">Kelola Barang</a>
        </div>
    @endif
</div>
@endif

<!-- Analytics Tables (Most vs Least Popular) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px;">
    <!-- Most Popular Products -->
    <div class="glass-card" style="padding: 20px 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-fire" style="color: #fb7185;"></i> 5 Barang Paling Laris
        </h3>
        
        <div class="table-container">
            <table class="custom-table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="padding: 10px 12px; font-size: 0.75rem;">Gambar</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem;">Nama Barang</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem; text-align: right;">Stok</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem; text-align: right;">Harga Jual</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem; text-align: right; color: #fb7185;">Terjual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($popularProducts as $item)
                        <tr>
                            <td style="padding: 10px 12px;">
                                <img src="{{ $item->image_url }}" class="product-thumb" style="width: 36px; height: 36px;" alt="{{ $item->name }}">
                            </td>
                            <td style="padding: 10px 12px; font-weight: 600;">{{ $item->name }}</td>
                            <td style="padding: 10px 12px; text-align: right;">
                                <span class="badge {{ $item->isLowStock() ? 'danger' : 'success' }}">{{ $item->stock }}</span>
                            </td>
                            <td style="padding: 10px 12px; text-align: right;">Rp. {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                            <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #fb7185; font-size: 1rem;">
                                {{ $item->total_sold }} pcs
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 24px;">Belum ada data penjualan barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Least Popular Products -->
    <div class="glass-card" style="padding: 20px 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-snowflake" style="color: #38bdf8;"></i> 5 Barang Kurang Laris / Stok Mengendap (Mati)
        </h3>
        
        <div class="table-container">
            <table class="custom-table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="padding: 10px 12px; font-size: 0.75rem;">Gambar</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem;">Nama Barang</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem; text-align: right;">Stok</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem; text-align: right;">Harga Jual</th>
                        <th style="padding: 10px 12px; font-size: 0.75rem; text-align: right; color: #38bdf8;">Terjual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unpopularProducts as $item)
                        <tr>
                            <td style="padding: 10px 12px;">
                                <img src="{{ $item->image_url }}" class="product-thumb" style="width: 36px; height: 36px;" alt="{{ $item->name }}">
                            </td>
                            <td style="padding: 10px 12px; font-weight: 600;">{{ $item->name }}</td>
                            <td style="padding: 10px 12px; text-align: right;">
                                <span class="badge {{ $item->isLowStock() ? 'danger' : 'success' }}">{{ $item->stock }}</span>
                            </td>
                            <td style="padding: 10px 12px; text-align: right;">Rp. {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                            <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #38bdf8; font-size: 1rem;">
                                {{ $item->total_sold }} pcs
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 24px;">Belum ada data barang di katalog.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
