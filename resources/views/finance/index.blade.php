@extends('layouts.app')

@section('title', 'Laporan Keuangan - Aplikasi Kasir Toko')
@section('page-header', 'Laporan Keuangan')

@section('styles')
<style>
    .report-filter-bar {
        background: var(--bg-sidebar);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 20px;
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-end;
        box-shadow: var(--shadow-sm);
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
        padding: 8px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.6);
        border-radius: var(--radius-sm);
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
        border-color: var(--primary);
    }
    
    .stats-card-flow {
        position: relative;
        overflow: hidden;
    }
    
    .stats-card-flow::after {
        content: '';
        position: absolute;
        bottom: -20px;
        right: -20px;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        opacity: 0.05;
        background: currentColor;
    }

    .report-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }

    @media (max-width: 992px) {
        .report-grid {
            grid-template-columns: 1fr;
        }
    }

    .table-ledger th, .table-ledger td {
        padding: 12px 16px;
        font-size: 0.85rem;
    }

    .ledger-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        font-weight: 700;
        border-radius: var(--radius-sm);
        text-transform: uppercase;
    }

    .ledger-badge.penjualan { background: rgba(37, 99, 235, 0.1); color: var(--info); }
    .ledger-badge.dp { background: rgba(16, 185, 129, 0.1); color: var(--success); }
    .ledger-badge.cicilan { background: rgba(234, 88, 12, 0.1); color: var(--warning); }

    /* Printable statement container, hidden on screen */
    #print-report-area {
        display: none;
    }

    @media print {
        body, html {
            background: white !important;
            color: black !important;
        }
        .app-container, .no-print, button, .btn, header, aside, .report-filter-bar, .stats-grid, .glass-card, .table-container, .report-grid, #ledger-panel {
            display: none !important;
            visibility: hidden !important;
        }
        #print-report-area {
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
            padding: 10mm !important;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif !important;
            color: #000 !important;
            background: #fff !important;
        }
        #print-report-area table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        #print-report-area th, #print-report-area td {
            border: 1px solid #000 !important;
            padding: 6px 10px !important;
            font-size: 10pt !important;
            text-align: left;
        }
        #print-report-area th {
            background-color: #f2f2f2 !important;
            -webkit-print-color-adjust: exact;
            font-weight: bold;
        }
        .print-header {
            text-align: center;
            border-bottom: 2px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .print-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11pt;
        }
        .print-title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .print-meta {
            font-size: 10pt;
            color: #555;
        }
        .print-section-title {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
        }
    }
</style>
@endsection

@section('content')
<!-- Filter bar -->
<div class="report-filter-bar no-print">
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">Pilih Periode Laporan</label>
        <div class="period-tabs">
            <a href="{{ route('finance.index', ['period' => 'today']) }}" class="period-tab-btn {{ $period === 'today' ? 'active' : '' }}">
                <i class="fas fa-calendar-day"></i> Hari Ini
            </a>
            <a href="{{ route('finance.index', ['period' => 'week']) }}" class="period-tab-btn {{ $period === 'week' ? 'active' : '' }}">
                <i class="fas fa-calendar-week"></i> Minggu Ini
            </a>
            <a href="{{ route('finance.index', ['period' => 'month']) }}" class="period-tab-btn {{ $period === 'month' ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i> Bulan Ini
            </a>
            <a href="{{ route('finance.index', ['period' => '3months']) }}" class="period-tab-btn {{ $period === '3months' ? 'active' : '' }}">
                <i class="fas fa-history"></i> 3 Bulan
            </a>
            <a href="#" onclick="toggleCustomDates(event)" class="period-tab-btn {{ $period === 'custom' ? 'active' : '' }}" id="custom-tab-btn">
                <i class="fas fa-sliders-h"></i> Kustom
            </a>
        </div>
    </div>
    
    <form action="{{ route('finance.index') }}" method="GET" style="display: contents;" id="filter-form">
        <input type="hidden" name="period" id="period-input" value="{{ $period }}">
        
        <!-- Custom Dates (Conditional view) -->
        <div id="custom-date-inputs" style="display: {{ $period === 'custom' ? 'flex' : 'none' }}; gap: 10px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="start_date" style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;">Dari Tanggal</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate ? $startDate->format('Y-m-d') : date('Y-m-d') }}" style="background-color:#ffffff; border-color:var(--border-color); font-size: 0.85rem; padding: 8px 12px;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="end_date" style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;">Sampai Tanggal</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate ? $endDate->format('Y-m-d') : date('Y-m-d') }}" style="background-color:#ffffff; border-color:var(--border-color); font-size: 0.85rem; padding: 8px 12px;">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 38px; border-radius: var(--radius-sm); font-size: 0.85rem; padding: 0 16px;">
                <i class="fas fa-filter"></i> Terapkan
            </button>
        </div>
    </form>

    <div style="margin-left: auto;">
        <button type="button" onclick="window.print()" class="btn btn-secondary" style="border-radius: var(--radius-sm); border-color: var(--primary); color: var(--primary); gap: 6px; font-weight: bold; height: 42px;">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="stats-grid no-print">
    <div class="glass-card stats-card-flow" style="border-left: 4px solid var(--info); color: var(--info);">
        <div class="stat-header">
            <span class="stat-title">Omzet Penjualan (Kotor)</span>
            <div class="stat-icon" style="background: rgba(37,99,235,0.08); color: var(--info);"><i class="fas fa-chart-line"></i></div>
        </div>
        <div class="stat-value" style="color: var(--text-primary);">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</div>
        <div class="stat-desc">Nilai total harga jual seluruh transaksi</div>
    </div>
    
    <div class="glass-card stats-card-flow" style="border-left: 4px solid var(--warning); color: var(--warning);">
        <div class="stat-header">
            <span class="stat-title">Harga Pokok (HPP)</span>
            <div class="stat-icon" style="background: rgba(234,88,12,0.08); color: var(--warning);"><i class="fas fa-shopping-bag"></i></div>
        </div>
        <div class="stat-value" style="color: var(--text-primary);">Rp {{ number_format($totalHpp, 0, ',', '.') }}</div>
        <div class="stat-desc">Biaya pembelian barang yang terjual</div>
    </div>
    
    <div class="glass-card stats-card-flow" style="border-left: 4px solid var(--success); color: var(--success);">
        <div class="stat-header">
            <span class="stat-title">Laba Penjualan (Kotor)</span>
            <div class="stat-icon" style="background: rgba(22,163,74,0.08); color: var(--success);"><i class="fas fa-wallet"></i></div>
        </div>
        <div class="stat-value" style="color: var(--success);">Rp {{ number_format($totalLabaKotor, 0, ',', '.') }}</div>
        <div class="stat-desc">Selisih harga jual dikurangi modal beli</div>
    </div>

    <div class="glass-card stats-card-flow" style="border-left: 4px solid #10b981; color: #10b981;">
        <div class="stat-header">
            <span class="stat-title">Kas Masuk Bersih</span>
            <div class="stat-icon" style="background: rgba(16,185,129,0.08); color: #10b981;"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
        <div class="stat-value" style="color: #10b981;">Rp {{ number_format($totalCashInflow, 0, ',', '.') }}</div>
        <div class="stat-desc">Total uang riil masuk (Penjualan lunas + DP + cicilan)</div>
    </div>
</div>

<!-- Analytical Panels -->
<div class="report-grid no-print">
    <!-- Left panel: Cash Inflow Breakdown -->
    <div class="glass-card" style="display: flex; flex-direction: column; gap: 16px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-coins" style="color: var(--success);"></i> Rincian Aliran Kas Masuk
        </h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Penjualan Tunai (Cash)</span>
                <span style="font-size: 0.95rem; font-weight: 700;">Rp {{ number_format($salesCash, 0, ',', '.') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Penjualan Bank Transfer</span>
                <span style="font-size: 0.95rem; font-weight: 700;">Rp {{ number_format($salesTransfer, 0, ',', '.') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Penjualan QRIS</span>
                <span style="font-size: 0.95rem; font-weight: 700;">Rp {{ number_format($salesQris, 0, ',', '.') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Penagihan Piutang (DP & Cicilan)</span>
                <span style="font-size: 0.95rem; font-weight: 700; color: var(--warning);">Rp {{ number_format($debtPaymentsCollected, 0, ',', '.') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding-top: 10px; margin-top: 10px;">
                <span style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary);">Total Aliran Kas Masuk</span>
                <span style="font-size: 1.05rem; font-weight: 800; color: #10b981;">Rp {{ number_format($totalCashInflow, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Right panel: Debt & Receivables statement -->
    <div class="glass-card" style="display: flex; flex-direction: column; gap: 16px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-handshake" style="color: var(--warning);"></i> Laporan Piutang Pelanggan
        </h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Piutang Baru Terbentuk (Periode Ini)</span>
                <span style="font-size: 0.95rem; font-weight: 700; color: var(--danger);">Rp {{ number_format($newDebtsValue, 0, ',', '.') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Piutang Berhasil Ditagih (DP/Cicilan)</span>
                <span style="font-size: 0.95rem; font-weight: 700; color: var(--success);">Rp {{ number_format($debtPaymentsCollected, 0, ',', '.') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">Total Diskon Voucher (Periode Ini)</span>
                <span style="font-size: 0.95rem; font-weight: 700; color: #f43f5e;">Rp {{ number_format($totalDiscounts, 0, ',', '.') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding-top: 10px; margin-top: 10px; border-top: 1px solid var(--border-color);">
                <span style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary);">Total Sisa Piutang Berjalan (Semua Periode)</span>
                <span style="font-size: 1.05rem; font-weight: 800; color: var(--danger);">Rp {{ number_format($outstandingReceivables, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Ledger Section (Log Kas Masuk) -->
<div class="glass-card no-print" id="ledger-panel" style="margin-bottom: 32px;">
    <h3 style="font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-book" style="color: var(--info);"></i> Buku Besar Arus Kas Masuk (Ledger)
    </h3>
    
    <!-- Ledger Table (Desktop) -->
    <div class="table-container desktop-view">
        <table class="custom-table table-ledger">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Jenis</th>
                    <th>Referensi</th>
                    <th>Pelanggan</th>
                    <th>Metode</th>
                    <th>Kas Masuk</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledger as $item)
                    <tr>
                        <td>{{ $item['date']->format('d-m-Y H:i') }}</td>
                        <td>
                            <span class="ledger-badge {{ str_contains(strtolower($item['type']), 'uang muka') ? 'dp' : (str_contains(strtolower($item['type']), 'cicilan') ? 'cicilan' : 'penjualan') }}">
                                {{ $item['type'] }}
                            </span>
                        </td>
                        <td><strong>{{ $item['reference'] }}</strong></td>
                        <td>{{ $item['customer'] }}</td>
                        <td><span class="badge info" style="font-size: 0.75rem;">{{ $item['method'] }}</span></td>
                        <td style="font-weight: 700; color: var(--success);">Rp {{ number_format($item['inflow'], 0, ',', '.') }}</td>
                        <td><span style="font-size: 0.85rem; color: var(--text-secondary);">{{ $item['notes'] }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                            Tidak ada transaksi arus kas masuk terdaftar untuk filter periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Ledger Mobile View (Card List) -->
    <div class="mobile-view">
        @forelse($ledger as $item)
            <div class="mobile-list-card" style="border-left: 4px solid var(--success);">
                <div class="mobile-card-row">
                    <span class="mobile-card-title">{{ $item['reference'] }}</span>
                    <span class="ledger-badge {{ str_contains(strtolower($item['type']), 'uang muka') ? 'dp' : (str_contains(strtolower($item['type']), 'cicilan') ? 'cicilan' : 'penjualan') }}" style="font-size: 0.75rem;">
                        {{ $item['type'] }}
                    </span>
                </div>
                
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Pelanggan / Waktu:</span>
                    <span style="font-size: 0.8rem;">
                        {{ $item['customer'] }} <span style="color: var(--text-secondary); margin-left: 4px;">{{ $item['date']->format('d-m-Y H:i') }}</span>
                    </span>
                </div>
                
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Metode / Keterangan:</span>
                    <span>
                        <span class="badge info" style="font-size: 0.7rem; padding: 2px 6px;">{{ $item['method'] }}</span>
                        <span style="font-size: 0.75rem; color: var(--text-secondary); margin-left: 6px;">{{ $item['notes'] }}</span>
                    </span>
                </div>

                <div class="mobile-card-row" style="margin-top: 4px; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                    <span class="mobile-card-subtitle">Kas Masuk:</span>
                    <strong style="color: var(--success); font-size: 0.95rem;">Rp {{ number_format($item['inflow'], 0, ',', '.') }}</strong>
                </div>
            </div>
        @empty
            <div class="glass-card" style="text-align: center; color: var(--text-secondary); padding: 20px;">
                Tidak ada transaksi arus kas masuk terdaftar untuk filter periode ini.
            </div>
        @endforelse
    </div>
</div>@endsection

@section('print-area')
<!-- ==========================================================================
   PRINTABLE financial statement (only renders on window.print())
   ========================================================================== -->
<div id="print-report-area">
    <div class="print-header">
        <span class="print-title">LAPORAN KEUANGAN</span><br>
        <span style="font-size: 14pt; font-weight: bold;">SRC SUYANTO</span><br>
        <span class="print-meta">Periode: {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}</span>
    </div>

    <div class="print-section-title">Ringkasan Kinerja Keuangan</div>
    <div class="print-row">
        <span>Total Omzet Penjualan (Kotor)</span>
        <strong>Rp {{ number_format($totalOmzet, 2, ',', '.') }}</strong>
    </div>
    <div class="print-row">
        <span>Harga Pokok Penjualan (Total HPP)</span>
        <strong>Rp {{ number_format($totalHpp, 2, ',', '.') }}</strong>
    </div>
    <div style="border-top: 1px dashed #000; margin: 6px 0;"></div>
    <div class="print-row" style="font-size: 12pt; font-weight: bold;">
        <span>LABA PENJUALAN (KOTOR)</span>
        <span>Rp {{ number_format($totalLabaKotor, 2, ',', '.') }}</span>
    </div>

    <div class="print-section-title">Rincian Arus Kas Masuk (Realized Cash Inflow)</div>
    <div class="print-row">
        <span>Penjualan Tunai (Cash)</span>
        <span>Rp {{ number_format($salesCash, 2, ',', '.') }}</span>
    </div>
    <div class="print-row">
        <span>Penjualan Bank Transfer</span>
        <span>Rp {{ number_format($salesTransfer, 2, ',', '.') }}</span>
    </div>
    <div class="print-row">
        <span>Penjualan QRIS</span>
        <span>Rp {{ number_format($salesQris, 2, ',', '.') }}</span>
    </div>
    <div class="print-row">
        <span>Penerimaan Cicilan & Uang Muka Piutang</span>
        <span>Rp {{ number_format($debtPaymentsCollected, 2, ',', '.') }}</span>
    </div>
    <div style="border-top: 1px dashed #000; margin: 6px 0;"></div>
    <div class="print-row" style="font-size: 12pt; font-weight: bold; color: #000;">
        <span>TOTAL ALIRAN KAS MASUK</span>
        <span>Rp {{ number_format($totalCashInflow, 2, ',', '.') }}</span>
    </div>

    <div class="print-section-title">Laporan Piutang & Kredit</div>
    <div class="print-row">
        <span>Piutang Baru Terbentuk (Periode Ini)</span>
        <span>Rp {{ number_format($newDebtsValue, 2, ',', '.') }}</span>
    </div>
    <div class="print-row">
        <span>Piutang Tertagih/Terbayar (DP & Cicilan)</span>
        <span>Rp {{ number_format($debtPaymentsCollected, 2, ',', '.') }}</span>
    </div>
    <div style="border-top: 1px dashed #000; margin: 6px 0;"></div>
    <div class="print-row" style="font-size: 11pt; font-weight: bold;">
        <span>TOTAL OUTSTANDING PIUTANG (Semua Periode)</span>
        <span>Rp {{ number_format($outstandingReceivables, 2, ',', '.') }}</span>
    </div>

    <div class="print-section-title">Ledger Arus Kas Masuk (Histori Rinci)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Tanggal</th>
                <th style="width: 20%;">Tipe</th>
                <th style="width: 15%;">Referensi</th>
                <th style="width: 25%;">Pelanggan</th>
                <th style="width: 20%;">Jumlah Uang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger as $item)
                <tr>
                    <td>{{ $item['date']->format('d-m-Y H:i') }}</td>
                    <td>{{ $item['type'] }}</td>
                    <td>{{ $item['reference'] }}</td>
                    <td>{{ $item['customer'] }}</td>
                    <td>Rp {{ number_format($item['inflow'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Tidak ada data arus kas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
        <div style="text-align: center; width: 200px;">
            <span>Jakarta, {{ date('d-m-Y') }}</span><br>
            <span>Pemilik Toko</span><br><br><br><br>
            <span style="text-decoration: underline; font-weight: bold;">( {{ Auth::user()->name ?? 'SrcSuyanto345' }} )</span>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleCustomDates(e) {
        if (e) e.preventDefault();
        const inputs = document.getElementById('custom-date-inputs');
        const customTab = document.getElementById('custom-tab-btn');
        const periodInput = document.getElementById('period-input');
        
        // Remove active class from all tabs
        document.querySelectorAll('.period-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        customTab.classList.add('active');
        periodInput.value = 'custom';
        inputs.style.display = 'flex';
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
                        console.log("New transaction detected! Refreshing finance data...");
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
@endsection
