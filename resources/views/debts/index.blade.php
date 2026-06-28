@extends('layouts.app')

@section('title', 'Kelola Utang')
@section('page-header', 'Catatan Utang Pelanggan')

@section('content')
<div class="glass-card" style="padding: 24px;">
    <!-- Filters Toolbar -->
    <div class="toolbar-container">
        <form action="{{ route('debts.index') }}" method="GET" class="toolbar-form" style="max-width: 700px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
                <input type="text" name="search" class="form-control" placeholder="Cari nama pelanggan atau nomor nota..." value="{{ request('search') }}" style="border-radius: var(--radius-sm); flex: 1 1 200px; max-width: none;">
                
                <select name="status" class="form-control" style="border-radius: var(--radius-sm); flex: 1 1 150px; max-width: none;">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="unpaid" {{ request('status', 'unpaid') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>Masih Dicicil</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Sudah Lunas</option>
                </select>
                
                <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-sm); padding: 0 20px;">
                    <i class="fas fa-filter"></i> Saring
                </button>
                
                @if(request('search') || request('status'))
                    <a href="{{ route('debts.index') }}" class="btn btn-secondary" style="border-radius: var(--radius-sm);">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Debts Table (Desktop) -->
    <div class="table-container desktop-view">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>Nomor Nota Asal</th>
                    <th style="text-align: right;">Total Belanja</th>
                    <th style="text-align: right;">Sisa Utang</th>
                    <th style="text-align: center;">Status</th>
                    <th>Tanggal Belanja</th>
                    <th>Batas Jatuh Tempo</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($debts as $item)
                    @php
                        $isOverdue = $item->isOverdue();
                    @endphp
                    <tr style="@if($isOverdue) background-color: rgba(239, 68, 68, 0.02); @endif">
                        <td style="font-weight: 700; font-size: 0.95rem;">
                            {{ $item->customer_name }}
                        </td>
                        <td>
                            @if($item->sale)
                                <span style="font-family: monospace; font-weight: 600; color: #818cf8;">
                                    {{ $item->sale->invoice_number }}
                                </span>
                            @else
                                <span style="color:var(--text-secondary)">Bukan transaksi kasir</span>
                            @endif
                        </td>
                        <td style="text-align: right; color: var(--text-secondary);">
                            Rp. {{ number_format($item->total_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight: 800; color: @if($item->status === 'unpaid') #f87171 @else #fbbf24 @endif; font-size: 0.95rem;">
                            Rp. {{ number_format($item->remaining_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            @if($item->status === 'paid')
                                <span class="badge success">Sudah Lunas</span>
                            @elseif($isOverdue)
                                <span class="badge danger"><i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i> Jatuh Tempo</span>
                            @elseif($item->status === 'partially_paid')
                                <span class="badge warning">Masih Dicicil</span>
                            @else
                                <span class="badge danger">Belum Dibayar</span>
                            @endif
                        </td>
                        <td>
                            {{ $item->created_at->format('d-m-Y H:i') }}
                        </td>
                        <td>
                            <span style="@if($isOverdue) color: #ef4444; font-weight: bold; @endif">
                                {{ $item->due_date ? $item->due_date->format('d-m-Y') : '-' }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('debts.show', $item->id) }}" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem; border-radius: var(--radius-sm); @if($item->status === 'paid') background:var(--btn-secondary); color:var(--text-secondary); box-shadow:none; @endif">
                                <i class="fas fa-eye"></i> Lihat & Cicil
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            <i class="fas fa-hand-holding-usd" style="font-size: 3rem; opacity: 0.2; display: block; margin-bottom: 16px;"></i>
                            Tidak ada catatan utang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Debts Mobile View (Card List) -->
    <div class="mobile-view">
        @forelse($debts as $item)
            @php
                $isOverdue = $item->isOverdue();
            @endphp
            <div class="mobile-list-card" style="@if($isOverdue) border-left: 4px solid var(--danger); @elseif($item->status === 'paid') border-left: 4px solid var(--success); @else border-left: 4px solid var(--warning); @endif">
                <div class="mobile-card-row">
                    <span class="mobile-card-title">{{ $item->customer_name }}</span>
                    @if($item->status === 'paid')
                        <span class="badge success">Lunas</span>
                    @elseif($isOverdue)
                        <span class="badge danger"><i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i> Jatuh Tempo</span>
                    @elseif($item->status === 'partially_paid')
                        <span class="badge warning">Cicilan</span>
                    @else
                        <span class="badge danger">Belum Bayar</span>
                    @endif
                </div>
                
                <div class="mobile-card-row" style="margin-top: 2px;">
                    <span class="mobile-card-subtitle">Nota:</span>
                    <span style="font-family: monospace; font-weight: 600; color: #818cf8;">
                        {{ $item->sale ? $item->sale->invoice_number : 'Manual' }}
                    </span>
                </div>
                
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Sisa Utang / Total:</span>
                    <span>
                        <strong style="color: @if($item->status === 'paid') var(--success) @else var(--warning) @endif;">Rp {{ number_format($item->remaining_amount, 0, ',', '.') }}</strong>
                        <span style="font-size: 0.75rem; color: var(--text-secondary);">/ Rp {{ number_format($item->total_amount, 0, ',', '.') }}</span>
                    </span>
                </div>

                <div class="mobile-card-row" style="font-size: 0.8rem; color: var(--text-secondary);">
                    <span>Jatuh Tempo:</span>
                    <span style="@if($isOverdue) color: var(--danger); font-weight: bold; @endif">
                        {{ $item->due_date ? $item->due_date->format('d-m-Y') : '-' }}
                    </span>
                </div>

                <div class="mobile-card-actions">
                    <a href="{{ route('debts.show', $item->id) }}" class="btn btn-primary">
                        <i class="fas fa-eye"></i> Detail & Cicil
                    </a>
                </div>
            </div>
        @empty
            <div class="glass-card" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                <i class="fas fa-hand-holding-usd" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 12px;"></i>
                Tidak ada catatan utang ditemukan.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $debts->links() }}
    </div>
</div>
@endsection
