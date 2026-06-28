@extends('layouts.app')

@section('title', 'Rincian Utang - ' . $debt->customer_name)
@section('page-header', 'Rincian Utang Pelanggan')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('debts.index') }}" class="btn btn-secondary" style="border-radius: var(--radius-sm);">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Utang
    </a>
</div>

<div class="debts-detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <!-- Left Column: Summary & Payment Form -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Summary Card -->
        <div class="glass-card">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fas fa-user-tag" style="color:var(--primary); margin-right:8px;"></i> Informasi Utang</span>
                @if($debt->status === 'paid')
                    <span class="badge success">Sudah Lunas</span>
                @elseif($debt->isOverdue())
                    <span class="badge danger"><i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i> Jatuh Tempo</span>
                @elseif($debt->status === 'partially_paid')
                    <span class="badge warning">Masih Dicicil</span>
                @else
                    <span class="badge danger">Belum Dibayar</span>
                @endif
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem;">
                <div class="info-row" style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 8px;">
                    <span style="color: var(--text-secondary);">Nama Pelanggan:</span>
                    <strong style="font-size: 1.1rem;">{{ $debt->customer_name }}</strong>
                </div>
                <div class="info-row" style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 8px;">
                    <span style="color: var(--text-secondary);">Nomor Nota Asal:</span>
                    <strong style="font-family: monospace; color: #818cf8;">{{ $debt->sale ? $debt->sale->invoice_number : '-' }}</strong>
                </div>
                <div class="info-row" style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 8px;">
                    <span style="color: var(--text-secondary);">Total Utang Awal:</span>
                    <strong>Rp. {{ number_format($debt->total_amount, 0, ',', '.') }}</strong>
                </div>
                <div class="info-row" style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 8px; font-size: 1.15rem;">
                    <span style="color: var(--text-secondary);">Sisa Utang Yang Harus Dibayar:</span>
                    <strong style="color: #fbbf24;">Rp. {{ number_format($debt->remaining_amount, 0, ',', '.') }}</strong>
                </div>
                <div class="info-row" style="display: flex; justify-content: space-between; padding-bottom: 4px;">
                    <span style="color: var(--text-secondary);">Batas Tanggal Jatuh Tempo:</span>
                    <span style="@if($debt->isOverdue()) color: #ef4444; font-weight: bold; @endif">
                        {{ $debt->due_date ? $debt->due_date->format('d-m-Y') : 'Tidak ada batas waktu' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Payment Form (if not paid yet) -->
        @if($debt->status !== 'paid')
        <div class="glass-card">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 20px;">
                <i class="fas fa-hand-holding-usd" style="color:var(--success); margin-right:8px;"></i> Catat Cicilan / Pelunasan
            </h3>
            
            <form action="{{ route('debts.store_payment', $debt->id) }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="payment_amount">Jumlah Uang Yang Dibayarkan (Sisa: Rp. {{ number_format($debt->remaining_amount, 0, ',', '.') }}) <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="payment_amount" id="payment_amount" class="form-control" min="1" max="{{ $debt->remaining_amount }}" placeholder="Contoh: 50000" required style="font-size: 1.15rem; font-weight: 800; color: #34d399;">
                    <div style="margin-top: 6px; display:flex; gap: 8px;">
                        <button type="button" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem; border-radius: var(--radius-sm);" onclick="document.getElementById('payment_amount').value = {{ round($debt->remaining_amount) }}">Bayar Lunas</button>
                        @if($debt->remaining_amount > 50000)
                            <button type="button" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem; border-radius: var(--radius-sm);" onclick="document.getElementById('payment_amount').value = 50000">Rp. 50.000</button>
                        @endif
                        @if($debt->remaining_amount > 100000)
                            <button type="button" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem; border-radius: var(--radius-sm);" onclick="document.getElementById('payment_amount').value = 100000">Rp. 100.000</button>
                        @endif
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="payment_date">Tanggal Pembayaran <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                
                <div class="form-group">
                    <label for="notes">Catatan Tambahan (Misal: Ditransfer / Titip Anak)</label>
                    <input type="text" name="notes" id="notes" class="form-control" placeholder="Contoh: Dibayar cash dititipkan di kasir">
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%; border-radius: var(--radius-sm); padding: 12px 20px; font-size: 0.95rem;">
                    <i class="fas fa-save"></i> SIMPAN PEMBAYARAN
                </button>
            </form>
        </div>
        @endif

        <!-- Linked Invoice Breakdown -->
        @if($debt->sale)
        <div class="glass-card">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px;">
                <i class="fas fa-shopping-basket" style="color:var(--info); margin-right:8px;"></i> Daftar Barang Yang Belanja
            </h3>
            
            <div class="table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th style="padding: 8px 12px;">Nama Barang</th>
                            <th style="padding: 8px 12px; text-align: center;">Jumlah</th>
                            <th style="padding: 8px 12px; text-align: right;">Harga Satuan</th>
                            <th style="padding: 8px 12px; text-align: right;">Total Sementara</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($debt->sale->saleDetails as $detail)
                            <tr>
                                <td style="padding: 8px 12px; font-weight: 600;">
                                    {{ $detail->custom_name ?? ($detail->product ? $detail->product->name : 'Barang Dihapus') }}
                                </td>
                                <td style="padding: 8px 12px; text-align: center;">{{ $detail->quantity }} pcs</td>
                                <td style="padding: 8px 12px; text-align: right;">Rp. {{ number_format($detail->selling_price, 0, ',', '.') }}</td>
                                <td style="padding: 8px 12px; text-align: right; font-weight: bold;">Rp. {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 1px dashed var(--border-color); font-weight: bold; font-size: 0.9rem;">
                            <td colspan="3" style="padding: 12px 12px; text-align: right;">TOTAL BELANJA:</td>
                            <td style="padding: 12px 12px; text-align: right; color: white;">Rp. {{ number_format($debt->sale->total_price, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column: Installment Payment History -->
    <div class="glass-card">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 20px;">
            <i class="fas fa-history" style="color:var(--primary); margin-right:8px;"></i> Riwayat Pembayaran Cicilan
        </h3>
        
        <div class="table-container">
            <table class="custom-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Tanggal Bayar</th>
                        <th style="text-align: right;">Jumlah Dibayar</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debt->payments as $payment)
                        <tr>
                            <td>
                                {{ $payment->payment_date->format('d-m-Y') }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--success);">
                                Rp. {{ number_format($payment->payment_amount, 0, ',', '.') }}
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                {{ $payment->notes }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                <i class="fas fa-history" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 12px;"></i>
                                Belum ada riwayat pembayaran untuk utang ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
