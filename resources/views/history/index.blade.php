@extends('layouts.app')

@section('title', 'Riwayat & Omzet')
@section('page-header', 'Riwayat Transaksi & Laporan Omzet')

@section('styles')
<style>
    /* Premium visual aids for report page */
    .report-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 20px;
        margin-bottom: 28px;
        backdrop-filter: blur(10px);
    }
    
    .period-tabs {
        display: flex;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid var(--border-color);
        padding: 4px;
        border-radius: var(--radius-sm);
        gap: 2px;
    }
    
    .period-tab-btn {
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        padding: 8px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .period-tab-btn:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .period-tab-btn.active {
        color: white !important;
        background: var(--primary);
        box-shadow: 0 4px 10px var(--primary-glow);
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        margin-top: 16px;
    }
    
    .search-input-group {
        position: relative;
        flex-grow: 1;
    }
    
    .search-input-group i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }
    
    .search-input-group input {
        padding-left: 36px !important;
    }

    /* Print styles override for details printout */
    #receipt-print-area {
        display: none;
    }
    
    @media print {
        body, html {
            background: white !important;
            color: black !important;
        }
        .app-container, .modal-overlay, button, .btn, .no-print, header, aside, .report-filter-bar, .stats-grid, .glass-card, .table-container, .pagination-container {
            display: none !important;
            visibility: hidden !important;
        }
        #receipt-print-area {
            display: block !important;
            visibility: visible !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 58mm !important;
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 10pt !important;
            line-height: 1.2 !important;
            padding: 2mm !important;
        }
        #receipt-print-area * {
            visibility: visible !important;
            color: black !important;
        }
    }
</style>
@endsection

@section('content')
<!-- Filter and Search Bar -->
<div class="report-filter-bar">
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">Pilih Periode Laporan</label>
        <div class="period-tabs">
            <a href="{{ route('history.index', ['period' => 'today', 'search' => $search, 'payment_method' => $paymentMethod]) }}" class="period-tab-btn {{ $period === 'today' ? 'active' : '' }}">
                <i class="fas fa-calendar-day"></i> Hari Ini
            </a>
            <a href="{{ route('history.index', ['period' => 'week', 'search' => $search, 'payment_method' => $paymentMethod]) }}" class="period-tab-btn {{ $period === 'week' ? 'active' : '' }}">
                <i class="fas fa-calendar-week"></i> Minggu Ini
            </a>
            <a href="{{ route('history.index', ['period' => 'month', 'search' => $search, 'payment_method' => $paymentMethod]) }}" class="period-tab-btn {{ $period === 'month' ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i> Bulan Ini
            </a>
            <a href="{{ route('history.index', ['period' => '3months', 'search' => $search, 'payment_method' => $paymentMethod]) }}" class="period-tab-btn {{ $period === '3months' ? 'active' : '' }}">
                <i class="fas fa-history"></i> 3 Bulan
            </a>
            <a href="#" onclick="toggleCustomDates(event)" class="period-tab-btn {{ $period === 'custom' ? 'active' : '' }}" id="custom-tab-btn">
                <i class="fas fa-sliders-h"></i> Kustom
            </a>
        </div>
    </div>
    
    <form action="{{ route('history.index') }}" method="GET" style="display: contents;" id="filter-form">
        <input type="hidden" name="period" id="period-input" value="{{ $period }}">
        
        <!-- Custom Dates (Conditional view) -->
        <div id="custom-date-inputs" style="display: {{ $period === 'custom' ? 'flex' : 'none' }}; gap: 10px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="start_date" style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;">Dari Tanggal</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate ?? date('Y-m-d') }}" style="background-color:rgba(0,0,0,0.3); border-color:var(--border-color); font-size: 0.85rem; padding: 8px 12px;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="end_date" style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;">Sampai Tanggal</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate ?? date('Y-m-d') }}" style="background-color:rgba(0,0,0,0.3); border-color:var(--border-color); font-size: 0.85rem; padding: 8px 12px;">
            </div>
        </div>

        <div class="search-input-group">
            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 8px;">Cari Transaksi</label>
            <div style="position: relative;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="form-control" placeholder="No. Nota / Nama Pelanggan / Barang..." value="{{ $search }}" style="background-color:rgba(0,0,0,0.3); border-color:var(--border-color); font-size: 0.85rem; padding: 8px 12px;">
            </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 8px; width: 160px;">
            <label for="payment_method" style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">Cara Bayar</label>
            <select name="payment_method" id="payment_method_filter" class="form-control" style="background-color:rgba(0,0,0,0.3); border-color:var(--border-color); font-size: 0.85rem; padding: 8px 12px;">
                <option value="">Semua Cara</option>
                <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                <option value="transfer" {{ $paymentMethod === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                <option value="qris" {{ $paymentMethod === 'qris' ? 'selected' : '' }}>QRIS (Digital)</option>
                <option value="debt" {{ $paymentMethod === 'debt' ? 'selected' : '' }}>Utang (Bon)</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 16px; border-radius: var(--radius-sm); font-size: 0.85rem;">
                <i class="fas fa-filter"></i> Terapkan
            </button>
            <a href="{{ route('history.index') }}" class="btn btn-secondary" style="padding: 10px 16px; border-radius: var(--radius-sm); font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center;" title="Reset Filter">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Metrics Stats Grid -->
<div class="stats-grid" style="margin-bottom: 28px;">
    <!-- Stat 1: Today's Revenue -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Omzet Hari Ini</span>
            <div class="stat-icon emerald">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #34d399;">Rp. {{ number_format($todayTurnover, 0, ',', '.') }}</div>
        <div class="stat-desc">Pemasukan penjualan hari ini saja</div>
    </div>
    
    <!-- Stat 2: Selected Period Revenue -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Omzet Periode Ini</span>
            <div class="stat-icon indigo">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #60a5fa;">Rp. {{ number_format($totalTurnover, 0, ',', '.') }}</div>
        <div class="stat-desc">Total pemasukan untuk jangka waktu terpilih</div>
    </div>

    <!-- Stat 3: Selected Period Profit -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Keuntungan Bersih</span>
            <div class="stat-icon purple">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #c084fc;">Rp. {{ number_format($totalProfit, 0, ',', '.') }}</div>
        <div class="stat-desc">Selisih harga jual - beli dari barang terjual</div>
    </div>
    
    <!-- Stat 4: Transaction Count -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Jumlah Transaksi</span>
            <div class="stat-icon amber">
                <i class="fas fa-shopping-basket"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #fbbf24;">{{ number_format($totalTransactions) }}</div>
        <div class="stat-desc">Jumlah nota diterbitkan di periode ini</div>
    </div>
</div>

<!-- Graph Chart Card -->
<div class="glass-card" style="padding: 24px; margin-bottom: 28px;">
    <h3 style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
        <i class="fas fa-chart-area" style="color:var(--primary);"></i> Grafik Riwayat Omzet
    </h3>
    
    <div class="chart-container">
        <canvas id="revenueTrendChart"></canvas>
    </div>
</div>

<!-- Transactions Table List -->
<div class="glass-card" style="padding: 20px 24px;">
    <h3 style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
        <i class="fas fa-list-ul" style="color: var(--primary);"></i> Daftar Nota Penjualan
    </h3>
    
    <!-- Transactions Table List (Desktop) -->
    <div class="table-container desktop-view">
        <table class="custom-table" style="font-size: 0.85rem;">
            <thead>
                <tr>
                    <th style="padding: 12px 14px;">No. Nota</th>
                    <th style="padding: 12px 14px;">Tanggal & Waktu</th>
                    <th style="padding: 12px 14px;">Metode Bayar</th>
                    <th style="padding: 12px 14px; text-align: right;">Total Item</th>
                    <th style="padding: 12px 14px; text-align: right;">Total Belanja</th>
                    <th style="padding: 12px 14px; text-align: center; width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $sale)
                    @php
                        $totalQty = $sale->saleDetails->sum('quantity');
                        
                        // Prep json data for easy local modal display
                        $saleData = [
                            'sale' => $sale,
                            'debt' => $sale->debt,
                            'date_formatted' => $sale->created_at->format('d-m-Y H:i:s')
                        ];
                    @endphp
                    <tr>
                        <td style="padding: 12px 14px; font-weight: 700; color: var(--text-primary);">{{ $sale->invoice_number }}</td>
                        <td style="padding: 12px 14px;">{{ $sale->created_at->format('d/m/Y H:i:s') }}</td>
                        <td style="padding: 12px 14px;">
                            @if($sale->payment_method === 'cash')
                                <span class="badge success" style="font-size: 0.75rem; padding: 2px 8px;">Tunai (Cash)</span>
                            @elseif($sale->payment_method === 'transfer')
                                <span class="badge info" style="font-size: 0.75rem; padding: 2px 8px; background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #60a5fa;">Transfer</span>
                            @elseif($sale->payment_method === 'qris')
                                <span class="badge" style="font-size: 0.75rem; padding: 2px 8px; background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.3); color: #c084fc;">QRIS (Digital)</span>
                            @elseif($sale->payment_method === 'debt')
                                <span class="badge warning" style="font-size: 0.75rem; padding: 2px 8px;">Utang ({{ $sale->debt->customer_name ?? 'Pelanggan' }})</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 600;">{{ $totalQty }} pcs</td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: #34d399;">Rp. {{ number_format($sale->total_price, 0, ',', '.') }}</td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick='showPastReceipt({{ json_encode($saleData) }})' style="padding: 6px 12px; font-size: 0.8rem; border-radius: var(--radius-sm); border-color: var(--primary); color: #60a5fa; background: rgba(59,130,246,0.05);">
                                <i class="fas fa-receipt"></i> Detail Nota
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            <i class="fas fa-receipt" style="font-size: 3rem; opacity: 0.15; display: block; margin-bottom: 12px;"></i>
                            Tidak ada transaksi terdaftar di database untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Transactions Mobile View (Card List) -->
    <div class="mobile-view">
        @forelse($transactions as $sale)
            @php
                $totalQty = $sale->saleDetails->sum('quantity');
                $saleData = [
                    'sale' => $sale,
                    'debt' => $sale->debt,
                    'date_formatted' => $sale->created_at->format('d-m-Y H:i:s')
                ];
            @endphp
            <div class="mobile-list-card" style="border-left: 4px solid var(--primary);">
                <div class="mobile-card-row">
                    <span class="mobile-card-title">{{ $sale->invoice_number }}</span>
                    @if($sale->payment_method === 'cash')
                        <span class="badge success" style="font-size: 0.7rem; padding: 2px 6px;">Tunai</span>
                    @elseif($sale->payment_method === 'transfer')
                        <span class="badge info" style="font-size: 0.7rem; padding: 2px 6px; background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #60a5fa;">Transfer</span>
                    @elseif($sale->payment_method === 'qris')
                        <span class="badge" style="font-size: 0.7rem; padding: 2px 6px; background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.3); color: #c084fc;">QRIS</span>
                    @elseif($sale->payment_method === 'debt')
                        <span class="badge warning" style="font-size: 0.7rem; padding: 2px 6px;">Kasbon</span>
                    @endif
                </div>
                
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Waktu:</span>
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $sale->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
                
                @if($sale->payment_method === 'debt' && $sale->debt)
                    <div class="mobile-card-row">
                        <span class="mobile-card-subtitle">Pelanggan Utang:</span>
                        <span style="font-weight: bold; color: var(--warning);">{{ $sale->debt->customer_name }}</span>
                    </div>
                @endif
                
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Jumlah Barang:</span>
                    <span style="font-weight: 600;">{{ $totalQty }} pcs</span>
                </div>

                <div class="mobile-card-row" style="margin-top: 4px; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                    <span class="mobile-card-subtitle">Total Belanja:</span>
                    <strong style="color: #34d399; font-size: 0.95rem;">Rp {{ number_format($sale->total_price, 0, ',', '.') }}</strong>
                </div>

                <div class="mobile-card-actions">
                    <button type="button" class="btn btn-secondary" onclick='showPastReceipt({{ json_encode($saleData) }})'>
                        <i class="fas fa-receipt"></i> Detail & Struk
                    </button>
                </div>
            </div>
        @empty
            <div class="glass-card" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                <i class="fas fa-receipt" style="font-size: 2.5rem; opacity: 0.15; display: block; margin-bottom: 12px;"></i>
                Tidak ada transaksi terdaftar di database untuk filter ini.
            </div>
        @endforelse
    </div>

    <!-- Pagination links -->
    @if($transactions->hasPages())
        <div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

<!-- Incorporate shared receipt layout -->
@include('partials.receipt-modal')
@endsection

@section('print-area')
<!-- Print area specifically populated for printing window.print() -->
<div id="receipt-print-area"></div>
@endsection

@section('scripts')
<script>
    // Pass chart trend data to JavaScript
    const chartLabels = {!! json_encode(array_keys($chartData)) !!};
    const chartValues = {!! json_encode(array_values($chartData)) !!};
    
    // Toggle custom date range view
    function toggleCustomDates(e) {
        if (e) e.preventDefault();
        const customContainer = document.getElementById('custom-date-inputs');
        const periodInput = document.getElementById('period-input');
        
        // Remove active class from other buttons
        document.querySelectorAll('.period-tab-btn').forEach(btn => btn.classList.remove('active'));
        
        // Add active to custom button
        document.getElementById('custom-tab-btn').classList.add('active');
        
        customContainer.style.display = 'flex';
        periodInput.value = 'custom';
    }

    // Auto-sync polling logic (real-time screen updates)
    let initialSaleId = null;
    let initialPaymentId = null;

    async function initializeSyncState() {
        try {
            const response = await fetch("{{ route('api.check_state') }}");
            if (response.ok) {
                const data = await response.json();
                initialSaleId = data.last_sale_id;
                initialPaymentId = data.last_payment_id;
                
                // Start polling every 8 seconds
                setInterval(checkForUpdates, 8000);
            }
        } catch (err) {
            console.error("Failed to initialize sync state:", err);
        }
    }

    async function checkForUpdates() {
        try {
            const response = await fetch("{{ route('api.check_state') }}");
            if (response.ok) {
                const data = await response.json();
                if (initialSaleId !== null && initialPaymentId !== null) {
                    if (data.last_sale_id > initialSaleId || data.last_payment_id > initialPaymentId) {
                        console.log("New transaction detected! Refreshing history data...");
                        window.location.reload();
                    }
                }
            }
        } catch (err) {
            console.warn("Auto-sync connection warning:", err);
        }
    }

    // Run initialization on load
    document.addEventListener("DOMContentLoaded", initializeSyncState);
</script>
<script src="{{ asset('js/history.js') }}"></script>
@endsection
