@extends('layouts.app')

@section('title', 'Kelola Voucher')
@section('page-header', 'Kelola Voucher Belanja')

@section('styles')
<style>
    /* Styling for printable physical vouchers */
    @media screen {
        #print-voucher-area {
            display: none;
        }
    }
    @media print {
        body, html {
            background: white !important;
            color: black !important;
        }
        .app-container, .no-print, header, aside, .content-header, .btn, form, table, .mobile-view, .desktop-view, .topbar {
            display: none !important;
            visibility: hidden !important;
        }
        #print-voucher-area {
            display: flex !important;
            visibility: visible !important;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100vw;
            background: #fff !important;
            position: absolute;
            left: 0;
            top: 0;
            margin: 0;
            padding: 0;
        }
        .voucher-coupon-card {
            visibility: visible !important;
            width: 90mm;
            height: 55mm;
            border: 3px dashed #d92424 !important;
            border-radius: 12px;
            padding: 10px;
            box-sizing: border-box;
            background: #ffffff !important;
            color: #000000 !important;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .voucher-coupon-card * {
            visibility: visible !important;
        }
        .coupon-header h3 {
            font-size: 8pt;
            margin: 0;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .coupon-header h2 {
            font-size: 13pt;
            margin: 1px 0 0 0;
            color: #d92424 !important;
            font-weight: 800;
        }
        .coupon-value {
            font-size: 20pt;
            font-weight: 900;
            color: #d92424 !important;
            margin: 2px 0;
        }
        .coupon-code-box {
            border: 2px solid #000;
            padding: 2px 10px;
            display: inline-block;
            margin: 2px auto;
            border-radius: 4px;
            background: #fff;
        }
        .code-label {
            font-size: 6pt;
            display: block;
            color: #555;
            font-weight: bold;
        }
        .code-text {
            font-size: 11pt;
            font-weight: 800;
            letter-spacing: 1px;
            color: #000;
        }
        .coupon-footer {
            font-size: 7.5pt;
            color: #333;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }
        .coupon-footer p {
            margin: 1px 0;
            font-weight: bold;
        }
        .coupon-footer small {
            display: block;
            margin-top: 2px;
            font-size: 6pt;
            color: #555;
        }
    }
</style>
@endsection

@section('content')
<div class="no-print">
    <!-- Action Header -->
    <div class="toolbar-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px;">
        <form action="{{ route('vouchers.index') }}" method="GET" class="toolbar-form" style="display: flex; gap: 8px; flex-grow: 1; max-width: 400px;">
            <input type="text" name="search" class="form-control" placeholder="Cari kode voucher..." value="{{ $search }}" style="background-color: rgba(255,255,255,0.03);">
            <button type="submit" class="btn btn-secondary" style="border-radius: var(--radius-sm);"><i class="fas fa-search"></i></button>
        </form>
        
        <button onclick="openAddModal()" class="btn btn-primary" style="border-radius: var(--radius-sm); padding: 10px 20px; font-weight: bold;">
            <i class="fas fa-plus"></i> Tambah Voucher
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom: 24px;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Desktop View (Table Layout) -->
    <div class="desktop-view table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Kode Voucher</th>
                    <th>Tipe Diskon</th>
                    <th>Nilai Potongan</th>
                    <th>Min. Belanja</th>
                    <th>Maks. Diskon</th>
                    <th>Ketersediaan (Kuota)</th>
                    <th>Status</th>
                    <th>Kedaluwarsa</th>
                    <th style="text-align: center; width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $item)
                    <tr>
                        <td style="font-weight: 800; font-family: monospace; font-size: 1.05rem; color: var(--primary);">
                            {{ $item->code }}
                        </td>
                        <td>
                            @if($item->type === 'fixed')
                                <span class="badge info">Nominal Tetap</span>
                            @else
                                <span class="badge warning">Persentase</span>
                            @endif
                        </td>
                        <td style="font-weight: bold;">
                            @if($item->type === 'fixed')
                                Rp {{ number_format($item->value, 0, ',', '.') }}
                            @else
                                {{ floatval($item->value) }}%
                            @endif
                        </td>
                        <td>Rp {{ number_format($item->min_spend, 0, ',', '.') }}</td>
                        <td>
                            @if($item->type === 'percent' && $item->max_discount)
                                Rp {{ number_format($item->max_discount, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="font-weight: bold;">{{ $item->stock }} kali</td>
                        <td>
                            @if($item->is_active && ($item->expiry_date === null || !$item->expiry_date->isPast()))
                                <span class="badge success">Aktif</span>
                            @else
                                <span class="badge danger">Nonaktif / Expired</span>
                            @endif
                        </td>
                        <td>
                            {{ $item->expiry_date ? $item->expiry_date->format('d-m-Y') : 'Selamanya' }}
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 4px; justify-content: center;">
                                <button onclick="printVoucher('{{ $item->code }}', '{{ $item->type }}', {{ $item->value }}, {{ $item->min_spend }}, '{{ $item->expiry_date ? $item->expiry_date->format('d-m-Y') : '' }}')" class="btn btn-secondary" style="color: #34d399; border-color: rgba(52, 211, 153, 0.2); padding: 6px 10px;" title="Cetak Fisik Voucher">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                                <button onclick="openEditModal('{{ json_encode($item) }}')" class="btn btn-secondary" style="color: #818cf8; border-color: rgba(129, 140, 248, 0.2); padding: 6px 10px;">
                                    <i class="fas fa-edit"></i> Ubah
                                </button>
                                <form action="{{ route('vouchers.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus voucher ini?');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2); padding: 6px 10px;">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            <i class="fas fa-ticket-alt" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 12px;"></i>
                            Belum ada voucher yang terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View (Card List) -->
    <div class="mobile-view">
        @forelse($vouchers as $item)
            <div class="mobile-list-card" style="border-left: 4px solid @if($item->is_active && ($item->expiry_date === null || !$item->expiry_date->isPast())) var(--success) @else var(--danger) @endif;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                    <span class="mobile-card-title" style="font-family: monospace; font-size: 1.1rem; color: var(--primary);">{{ $item->code }}</span>
                    @if($item->type === 'fixed')
                        <span class="badge info">Nominal Tetap</span>
                    @else
                        <span class="badge warning">Persentase</span>
                    @endif
                </div>
                
                <div class="mobile-card-row" style="margin-top: 4px;">
                    <span class="mobile-card-subtitle">Potongan Diskon:</span>
                    <strong style="color: white;">
                        @if($item->type === 'fixed')
                            Rp {{ number_format($item->value, 0, ',', '.') }}
                        @else
                            {{ floatval($item->value) }}%
                        @endif
                    </strong>
                </div>
                
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Min. Belanja:</span>
                    <span>Rp {{ number_format($item->min_spend, 0, ',', '.') }}</span>
                </div>

                @if($item->type === 'percent' && $item->max_discount)
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Maks. Diskon:</span>
                    <span>Rp {{ number_format($item->max_discount, 0, ',', '.') }}</span>
                </div>
                @endif
                
                <div class="mobile-card-row">
                    <span class="mobile-card-subtitle">Kuota Sisa / Expired:</span>
                    <span>
                        <span style="font-weight: bold; color: #fbbf24;">{{ $item->stock }} kali</span>
                        <span style="font-size: 0.75rem; color: var(--text-secondary); margin-left: 6px;">
                            ({{ $item->expiry_date ? $item->expiry_date->format('d-m-Y') : 'Selamanya' }})
                        </span>
                    </span>
                </div>

                <div class="mobile-card-actions">
                    <button onclick="printVoucher('{{ $item->code }}', '{{ $item->type }}', {{ $item->value }}, {{ $item->min_spend }}, '{{ $item->expiry_date ? $item->expiry_date->format('d-m-Y') : '' }}')" class="btn btn-secondary" style="color: #34d399; border-color: rgba(52, 211, 153, 0.2);">
                        <i class="fas fa-print"></i> Cetak Fisik
                    </button>
                    <button onclick="openEditModal('{{ json_encode($item) }}')" class="btn btn-secondary" style="color: #818cf8; border-color: rgba(129, 140, 248, 0.2);">
                        <i class="fas fa-edit"></i> Ubah
                    </button>
                    <form action="{{ route('vouchers.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus voucher ini?');" style="display: contents;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="glass-card" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                <i class="fas fa-ticket-alt" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 12px;"></i>
                Tidak ada data voucher ditemukan.
            </div>
        @endforelse
    </div>

    <!-- Pagination Links -->
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $vouchers->links() }}
    </div>
</div>

<!-- Add/Edit Voucher Modal -->
<div class="modal-overlay" id="voucher-modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-action-title">Tambah Voucher Baru</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form action="" method="POST" id="voucher-form">
            @csrf
            <div id="form-method"></div>
            
            <div class="modal-body" style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div class="form-group">
                    <label for="v-code">Kode Voucher <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="code" id="v-code" class="form-control" placeholder="Contoh: HEMAT5K" required style="text-transform: uppercase;">
                </div>

                <div class="form-group">
                    <label for="v-type">Tipe Diskon <span style="color:var(--danger)">*</span></label>
                    <select name="type" id="v-type" class="form-control" required style="background-color:#ffffff; color:var(--text-primary);" onchange="togglePercentInputs()">
                        <option value="fixed">Nominal Tetap (Rp)</option>
                        <option value="percent">Persentase (%)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="v-value">Nilai Potongan <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="value" id="v-value" class="form-control" min="0" placeholder="Contoh: 5000 atau 10" required>
                </div>

                <div class="form-group">
                    <label for="v-min-spend">Minimal Belanja (Rp) <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="min_spend" id="v-min-spend" class="form-control" min="0" placeholder="0" required>
                </div>

                <div class="form-group" id="max-discount-group" style="display: none;">
                    <label for="v-max-discount">Maksimal Diskon (Rp)</label>
                    <input type="number" name="max_discount" id="v-max-discount" class="form-control" min="0" placeholder="Kosongkan jika tidak ada batas">
                </div>

                <div class="form-group">
                    <label for="v-stock">Ketersediaan (Kuota) <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="stock" id="v-stock" class="form-control" min="0" placeholder="Contoh: 100" required>
                </div>

                <div class="form-group">
                    <label for="v-expiry">Tanggal Kedaluwarsa</label>
                    <input type="date" name="expiry_date" id="v-expiry" class="form-control">
                </div>

                <div class="form-group" id="status-group" style="display: none;">
                    <label for="v-status">Status Voucher</label>
                    <select name="is_active" id="v-status" class="form-control" style="background-color:#ffffff; color:var(--text-primary);">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Voucher</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('print-area')
<!-- Printable Voucher Component (hidden on screen) -->
<div id="print-voucher-area">
    <div class="voucher-coupon-card">
        <div class="coupon-header">
            <h3>VOUCHER BELANJA</h3>
            <h2>SRC SUYANTO</h2>
        </div>
        <div class="coupon-body">
            <div class="coupon-value" id="print-val">Rp 5.000</div>
            <div class="coupon-code-box">
                <span class="code-label">KODE VOUCHER</span>
                <div class="code-text" id="print-code">HEMAT5K</div>
            </div>
        </div>
        <div class="coupon-footer">
            <p id="print-min-spend">Minimal Belanja: Rp 20.000</p>
            <p id="print-expiry">Berlaku sampai: 31-12-2026</p>
            <small>somopuro, mutihan, rt 04/ rw02, gantiwarno, klaten</small>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('voucher-modal');
    const form = document.getElementById('voucher-form');
    const title = document.getElementById('modal-action-title');
    const methodInput = document.getElementById('form-method');
    const statusGroup = document.getElementById('status-group');
    const maxDiscountGroup = document.getElementById('max-discount-group');

    function togglePercentInputs() {
        const typeSelect = document.getElementById('v-type');
        if (typeSelect.value === 'percent') {
            maxDiscountGroup.style.display = 'block';
        } else {
            maxDiscountGroup.style.display = 'none';
            document.getElementById('v-max-discount').value = '';
        }
    }

    function openAddModal() {
        title.innerText = 'Tambah Voucher Baru';
        form.action = "{{ route('vouchers.store') }}";
        methodInput.innerHTML = '';
        statusGroup.style.display = 'none';
        
        document.getElementById('v-code').value = '';
        document.getElementById('v-type').value = 'fixed';
        document.getElementById('v-value').value = '';
        document.getElementById('v-min-spend').value = '0';
        document.getElementById('v-max-discount').value = '';
        document.getElementById('v-stock').value = '999';
        document.getElementById('v-expiry').value = '';
        
        togglePercentInputs();
        modal.classList.add('active');
    }

    function openEditModal(voucherJson) {
        const item = JSON.parse(voucherJson);
        
        title.innerText = 'Ubah Data Voucher';
        form.action = `/vouchers/${item.id}`;
        methodInput.innerHTML = '@method("PUT")';
        statusGroup.style.display = 'block';
        
        document.getElementById('v-code').value = item.code;
        document.getElementById('v-type').value = item.type;
        document.getElementById('v-value').value = parseFloat(item.value);
        document.getElementById('v-min-spend').value = parseFloat(item.min_spend);
        document.getElementById('v-max-discount').value = item.max_discount ? parseFloat(item.max_discount) : '';
        document.getElementById('v-stock').value = item.stock;
        
        if (item.expiry_date) {
            // expiry_date is standard YYYY-MM-DD from casts/date
            const dateObj = new Date(item.expiry_date);
            const yyyy = dateObj.getFullYear();
            const mm = String(dateObj.getMonth() + 1).padStart(2, '0');
            const dd = String(dateObj.getDate()).padStart(2, '0');
            document.getElementById('v-expiry').value = `${yyyy}-${mm}-${dd}`;
        } else {
            document.getElementById('v-expiry').value = '';
        }
        
        document.getElementById('v-status').value = item.is_active ? '1' : '0';
        
        togglePercentInputs();
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    function printVoucher(code, type, value, minSpend, expiryDate) {
        let valText = "";
        if (type === "fixed") {
            valText = "Rp " + parseFloat(value).toLocaleString("id-ID");
        } else {
            valText = "Potongan " + parseFloat(value) + "%";
        }
        
        let minSpendText = "Minimal Belanja: Rp " + parseFloat(minSpend).toLocaleString("id-ID");
        let expiryText = expiryDate ? "Berlaku sampai: " + expiryDate : "Berlaku selamanya";
        
        // Populate printable markup
        document.getElementById('print-code').innerText = code;
        document.getElementById('print-val').innerText = valText;
        document.getElementById('print-min-spend').innerText = minSpendText;
        document.getElementById('print-expiry').innerText = expiryText;
        
        // Print
        window.print();
    }
</script>
@endsection
