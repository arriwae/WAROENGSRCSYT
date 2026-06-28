@extends('layouts.app')

@section('title', 'Daftar Kulakan')
@section('page-header', 'Daftar Kulakan (Belanja Toko)')

@section('styles')
<style>
    /* Print container hidden by default on screen */
    #print-restock-area {
        display: none;
    }

    @media print {
        .app-container, .no-print, button, .btn, header, aside, .stats-grid, .glass-card, .table-container, .action-buttons-row, .mobile-view, .print-header {
            display: none !important;
            visibility: hidden !important;
        }
        #print-restock-area {
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
            color: #000 !important;
            background: #fff !important;
            position: absolute;
            left: 0;
            top: 0;
            margin: 0;
            padding: 10mm;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
        #print-restock-area table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-top: 20px;
        }
        #print-restock-area th, #print-restock-area td {
            border: 1px solid #000 !important;
            padding: 8px 10px !important;
            font-size: 10pt !important;
            color: #000 !important;
        }
        #print-restock-area th {
            background-color: #f2f2f2 !important;
            font-weight: bold !important;
        }
    }

    /* Tampilan dropdown autocomplete */
    .search-dropdown-results {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 100;
        max-height: 250px;
        overflow-y: auto;
        background: #1e1b4b; /* Dark indigo */
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    }
    .search-result-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        border-bottom: 1px solid rgba(255,255,255,0.03);
    }
    .search-result-item:hover {
        background-color: rgba(99, 102, 241, 0.2);
    }
    .search-result-item img {
        width: 30px;
        height: 30px;
        border-radius: 4px;
        object-fit: cover;
    }
    .search-result-info {
        display: flex;
        flex-direction: column;
    }
    .search-result-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: white;
    }
    .search-result-sku {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    /* Toast Notification */
    .toast-notif {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: var(--success);
        color: white;
        padding: 12px 24px;
        border-radius: var(--radius-md);
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        z-index: 9999;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .toast-notif.show {
        transform: translateY(0);
        opacity: 1;
    }
</style>
@endsection

@section('content')
<!-- Header khusus untuk hasil cetak (disembunyikan di layar biasa) -->
<div class="print-header" style="display: none;">
    <h2>DAFTAR BELANJA KULAKAN - SRC SUYANTO</h2>
    <p>Tanggal Cetak: {{ \Carbon\Carbon::now()->format('d-m-Y H:i') }} | Total Kebutuhan Modal: <span id="print-total-modal">Rp. 0</span></p>
</div>

<!-- Widgets Summary -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <!-- Stat 1: Low Stock Count -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Barang Hampir Habis</span>
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        <div class="stat-value" id="count-low-stock">{{ count($lowStockProducts) }}</div>
        <div class="stat-desc">Jumlah barang dengan stok &lt; 10</div>
    </div>
    
    <!-- Stat 2: Active Items in Shopping List -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Barang di Daftar Belanja</span>
            <div class="stat-icon indigo">
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>
        <div class="stat-value" id="count-list-items">0</div>
        <div class="stat-desc">Varian barang yang akan dibeli</div>
    </div>

    <!-- Stat 3: Total Estimated Cost -->
    <div class="glass-card">
        <div class="stat-header">
            <span class="stat-title">Estimasi Modal Kulakan</span>
            <div class="stat-icon emerald">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #34d399;" id="total-cost-display">Rp. 0</div>
        <div class="stat-desc">Total dana yang perlu disiapkan</div>
    </div>
</div>

<div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
    <!-- Filter/Toolbar Area -->
    <div class="filter-toolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <div style="position: relative; width: 100%; max-width: 450px;">
            <label style="display:block; margin-bottom:6px; font-weight:600; color:var(--text-secondary); font-size:0.85rem;">
                <i class="fas fa-search-plus"></i> Tambah barang lain ke daftar kulakan:
            </label>
            <input type="text" id="product-search-input" class="form-control" placeholder="Ketik nama barang atau barcode di sini..." onfocus="showSearchDropdown()" oninput="filterSearchDropdown()">
            
            <!-- Autocomplete Dropdown -->
            <div id="product-search-results" class="search-dropdown-results">
                <!-- Dropdown items will be populated by JS -->
            </div>
        </div>
        
        <div class="toolbar-actions-group" style="display: flex; gap: 12px; margin-top: 22px;">
            <button onclick="addAllLowStock()" class="btn btn-secondary" style="border-radius: var(--radius-sm); border-color: rgba(239,68,68,0.3); color: #f87171;" title="Kembalikan semua barang stok kritis">
                <i class="fas fa-sync-alt"></i> Muat Ulang Stok Tipis
            </button>
            <button onclick="clearList()" class="btn btn-danger" style="border-radius: var(--radius-sm);">
                <i class="fas fa-trash-alt"></i> Kosongkan Daftar
            </button>
        </div>
    </div>

    <!-- Active Restock List Table (Desktop) -->
    <div class="table-container desktop-view">
        <table class="custom-table" id="restock-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Gambar</th>
                    <th>Kode Barcode / SKU</th>
                    <th>Nama Barang</th>
                    <th style="text-align: center; width: 120px;">Stok Saat Ini</th>
                    <th style="text-align: right; width: 150px;">Harga Beli (Modal)</th>
                    <th style="text-align: center; width: 130px;">Jumlah Beli</th>
                    <th style="text-align: right; width: 160px;">Estimasi Subtotal</th>
                    <th style="text-align: center; width: 80px;" class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody id="restock-list-body">
                <!-- Shopping list items populated via JS -->
            </tbody>
        </table>
    </div>

    <!-- Active Restock List Cards (Mobile) -->
    <div class="mobile-view" id="restock-list-mobile">
        <!-- Shopping list cards populated via JS -->
    </div>

    <!-- Actions Row -->
    <div class="action-buttons-row" style="display: flex; justify-content: flex-end; gap: 16px; margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
        <button onclick="copyToWhatsApp()" class="btn btn-primary" style="background: #25d366; border: none; box-shadow: 0 4px 10px rgba(37,211,102,0.3); border-radius: var(--radius-sm); padding: 12px 24px; font-weight: bold;">
            <i class="fab fa-whatsapp" style="font-size: 1.2rem; margin-right: 6px; vertical-align: middle;"></i> Salin Daftar ke WhatsApp
        </button>
        
        <button onclick="window.print()" class="btn btn-primary" style="background: var(--grad-purple); border: none; box-shadow: 0 4px 10px rgba(168,85,247,0.3); border-radius: var(--radius-sm); padding: 12px 24px; font-weight: bold;">
            <i class="fas fa-print" style="font-size: 1.1rem; margin-right: 6px; vertical-align: middle;"></i> Cetak Daftar Belanja
        </button>
    </div>
</div>

<!-- Toast element -->
<div id="toast" class="toast-notif">
    <i class="fas fa-check-circle"></i>
    <span id="toast-message">Daftar kulakan berhasil disalin!</span>
</div>
@endsection

@section('scripts')
<script>
    // Raw database products list converted to JS array for instant client-side autocomplete
    const databaseProducts = @json($allProducts);
    
    // Active shopping list state
    let shoppingList = [];

    // Format Rupiah
    function formatRupiah(number) {
        return 'Rp. ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
    }

    // Initialize list with products having stock < 10
    document.addEventListener('DOMContentLoaded', () => {
        const initialLowStock = @json($lowStockProducts);
        initialLowStock.forEach(product => {
            // Calculate a smart default order qty (for example, target stock to 10 or at least order 10)
            const defaultOrderQty = Math.max(10 - parseInt(product.stock), 10);
            
            shoppingList.push({
                id: product.id,
                name: product.name,
                sku: product.sku || '-',
                stock: parseInt(product.stock),
                purchase_price: parseFloat(product.purchase_price),
                image_url: product.image_url,
                qty: defaultOrderQty,
                unit: 'pcs'
            });
        });
        
        renderList();

        // Close search dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const searchContainer = document.getElementById('product-search-input');
            const dropdown = document.getElementById('product-search-results');
            if (e.target !== searchContainer && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    });

    // Render table rows & update summary widgets
    function renderList() {
        const tbody = document.getElementById('restock-list-body');
        const mobileContainer = document.getElementById('restock-list-mobile');
        const printTbody = document.getElementById('print-restock-tbody');
        
        tbody.innerHTML = '';
        mobileContainer.innerHTML = '';
        if (printTbody) printTbody.innerHTML = '';

        if (shoppingList.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 50px;">
                        <i class="fas fa-clipboard-list" style="font-size: 3rem; opacity: 0.2; display: block; margin-bottom: 16px;"></i>
                        Belum ada barang di daftar kulakan. Silakan tambah barang di atas.
                    </td>
                </tr>
            `;
            mobileContainer.innerHTML = `
                <div class="glass-card" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                    <i class="fas fa-clipboard-list" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 12px;"></i>
                    Belum ada barang di daftar kulakan. Silakan tambah barang di atas.
                </div>
            `;
            if (printTbody) {
                printTbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">Belum ada barang di daftar belanja.</td>
                    </tr>
                `;
            }
            document.getElementById('count-list-items').innerText = '0';
            document.getElementById('total-cost-display').innerText = formatRupiah(0);
            document.getElementById('print-total-modal').innerText = formatRupiah(0);
            
            const printTotalVal = document.getElementById('print-total-val');
            if (printTotalVal) printTotalVal.innerText = formatRupiah(0);
            return;
        }

        let totalCost = 0;
        
        shoppingList.forEach((item, index) => {
            const multiplier = item.unit === 'karton' ? 40 : (item.unit === 'pak' ? 12 : 1);
            const subtotal = item.purchase_price * item.qty * multiplier;
            totalCost += subtotal;

            // Desktop Row
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <img src="${item.image_url}" class="product-thumb" alt="${item.name}">
                </td>
                <td style="font-family: monospace; font-weight: bold;">${item.sku}</td>
                <td style="font-weight: 600; font-size: 0.95rem;">${item.name}</td>
                <td style="text-align: center;">
                    <span class="badge ${item.stock < 10 ? 'danger' : 'success'}" style="font-size: 0.85rem; padding: 4px 12px;">
                        ${item.stock}
                    </span>
                </td>
                <td style="text-align: right; color: var(--text-secondary);">
                    ${formatRupiah(item.purchase_price)}
                </td>
                <td style="text-align: center;">
                    <div class="qty-input-container" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <input type="number" class="form-control" min="1" value="${item.qty}" 
                               style="width: 65px; text-align: center; background: rgba(0,0,0,0.2); border-color: var(--border-color); font-weight: bold; color: white; padding: 8px 4px;"
                               onchange="updateItemQty(${item.id}, this.value)">
                        <select class="form-control" style="width: 125px; background: rgba(0,0,0,0.3); border-color: var(--border-color); color: white; padding: 6px; font-size: 0.85rem;"
                                onchange="updateItemUnit(${item.id}, this.value)">
                            <option value="pcs" ${item.unit === 'pcs' ? 'selected' : ''}>Pcs (Eceran)</option>
                            <option value="pak" ${item.unit === 'pak' ? 'selected' : ''}>Pak (12 pcs)</option>
                            <option value="karton" ${item.unit === 'karton' ? 'selected' : ''}>Karton (40 pcs)</option>
                        </select>
                    </div>
                </td>
                <td style="text-align: right; font-weight: 700; color: #34d399;">
                    ${formatRupiah(subtotal)}
                </td>
                <td style="text-align: center;" class="col-aksi">
                    <button onclick="removeItem(${item.id})" class="btn btn-danger btn-remove-item" style="padding: 6px 12px; font-size: 0.8rem; border-radius: var(--radius-sm);" title="Hapus">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            // Mobile Card
            const mobileCard = document.createElement('div');
            mobileCard.className = 'mobile-list-card';
            mobileCard.style.borderLeft = '4px solid var(--primary)';
            mobileCard.innerHTML = `
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <img src="${item.image_url}" style="width: 60px; height: 60px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0; background: rgba(0,0,0,0.1);">
                    <div style="flex-grow: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px;">
                        <span class="mobile-card-title" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.name}</span>
                        <span style="font-family: monospace; font-size: 0.75rem; color: var(--text-secondary); font-weight: bold; display: block;">SKU: ${item.sku}</span>
                        
                        <div class="mobile-card-row" style="margin-top: 2px;">
                            <span class="mobile-card-subtitle">Stok Toko:</span>
                            <span class="badge ${item.stock < 10 ? 'danger' : 'success'}" style="font-size: 0.75rem; padding: 2px 8px;">${item.stock}</span>
                        </div>
                        
                        <div class="mobile-card-row">
                            <span class="mobile-card-subtitle">Harga Modal:</span>
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">${formatRupiah(item.purchase_price)}</span>
                        </div>
                        
                        <div class="mobile-card-row" style="margin-top: 4px; flex-wrap: wrap; gap: 6px;">
                            <span class="mobile-card-subtitle" style="flex-basis: 100%;">Jumlah Beli:</span>
                            <div style="display: flex; gap: 8px; width: 100%;">
                                <input type="number" class="form-control" min="1" value="${item.qty}" 
                                       style="width: 70px; text-align: center; background: rgba(0,0,0,0.2); border-color: var(--border-color); font-weight: bold; color: white; padding: 6px 4px; font-size: 0.85rem;"
                                       onchange="updateItemQty(${item.id}, this.value)">
                                <select class="form-control" style="flex-grow: 1; background: rgba(0,0,0,0.3); border-color: var(--border-color); color: white; padding: 6px; font-size: 0.85rem;"
                                        onchange="updateItemUnit(${item.id}, this.value)">
                                    <option value="pcs" ${item.unit === 'pcs' ? 'selected' : ''}>Pcs (Eceran)</option>
                                    <option value="pak" ${item.unit === 'pak' ? 'selected' : ''}>Pak (12 pcs)</option>
                                    <option value="karton" ${item.unit === 'karton' ? 'selected' : ''}>Karton (40 pcs)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mobile-card-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                            <span class="mobile-card-subtitle">Subtotal:</span>
                            <strong style="color: #34d399; font-size: 0.95rem;">${formatRupiah(subtotal)}</strong>
                        </div>
                    </div>
                </div>
                <div class="mobile-card-actions">
                    <button onclick="removeItem(${item.id})" class="btn btn-danger btn-remove-item" style="color: #ef4444; border-color: rgba(239,68,68,0.2);">
                        <i class="fas fa-trash-alt"></i> Hapus
                    </button>
                </div>
            `;
            mobileContainer.appendChild(mobileCard);

            // Add row to print table
            if (printTbody) {
                const printTr = document.createElement('tr');
                printTr.innerHTML = `
                    <td style="font-family: monospace; font-weight: bold;">${item.sku}</td>
                    <td>${item.name}</td>
                    <td style="text-align: center;">${item.stock} pcs</td>
                    <td style="text-align: right;">${formatRupiah(item.purchase_price)}</td>
                    <td style="text-align: center; font-weight: bold;">
                        ${item.qty} ${item.unit === 'karton' ? 'Karton' : (item.unit === 'pak' ? 'Pak' : 'Pcs')}
                    </td>
                    <td style="text-align: right; font-weight: bold;">${formatRupiah(subtotal)}</td>
                `;
                printTbody.appendChild(printTr);
            }
        });

        document.getElementById('count-list-items').innerText = shoppingList.length;
        document.getElementById('total-cost-display').innerText = formatRupiah(totalCost);
        document.getElementById('print-total-modal').innerText = formatRupiah(totalCost);
        
        const printTotalVal = document.getElementById('print-total-val');
        if (printTotalVal) printTotalVal.innerText = formatRupiah(totalCost);
        
        const printDateVal = document.getElementById('print-date-val');
        if (printDateVal) {
            const now = new Date();
            const dateStr = now.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).replace(/\//g, '-') + ' ' + now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            }).replace(/\./g, ':');
            printDateVal.innerText = dateStr;
        }
    }

    // Update qty for specific item
    window.updateItemQty = function(id, val) {
        const qty = parseInt(val) || 1;
        const item = shoppingList.find(i => i.id === id);
        if (item) {
            item.qty = qty < 1 ? 1 : qty;
            renderList();
        }
    };

    // Remove item from shopping list
    window.removeItem = function(id) {
        shoppingList = shoppingList.filter(item => item.id !== id);
        renderList();
    };

    // Clear entire shopping list
    window.clearList = function() {
        if (confirm("Apakah Anda yakin ingin mengosongkan seluruh daftar belanja?")) {
            shoppingList = [];
            renderList();
        }
    };

    // Re-load all low stock products
    window.addAllLowStock = function() {
        const initialLowStock = @json($lowStockProducts);
        
        initialLowStock.forEach(product => {
            const exists = shoppingList.some(item => item.id === product.id);
            if (!exists) {
                const defaultOrderQty = Math.max(10 - parseInt(product.stock), 10);
                shoppingList.push({
                    id: product.id,
                    name: product.name,
                    sku: product.sku || '-',
                    stock: parseInt(product.stock),
                    purchase_price: parseFloat(product.purchase_price),
                    image_url: product.image_url,
                    qty: defaultOrderQty,
                    unit: 'pcs'
                });
            }
        });
        
        renderList();
        showToast("Daftar barang stok tipis berhasil dimuat ulang.");
    };

    // Show/Filter Autocomplete Search Dropdown
    window.showSearchDropdown = function() {
        const dropdown = document.getElementById('product-search-results');
        dropdown.style.display = 'block';
        filterSearchDropdown();
    };

    window.filterSearchDropdown = function() {
        const input = document.getElementById('product-search-input').value.toLowerCase().trim();
        const dropdown = document.getElementById('product-search-results');
        dropdown.innerHTML = '';

        // Filter products that are NOT already in the shoppingList
        const filtered = databaseProducts.filter(prod => {
            const alreadyInList = shoppingList.some(item => item.id === prod.id);
            const matchesQuery = prod.name.toLowerCase().includes(input) || (prod.sku && prod.sku.toLowerCase().includes(input));
            return !alreadyInList && matchesQuery;
        }).slice(0, 8); // show max 8 items for cleaner layout

        if (filtered.length === 0) {
            dropdown.innerHTML = `<div style="padding: 12px; color: var(--text-secondary); text-align: center; font-size: 0.85rem;">Tidak ada barang lain untuk ditambahkan</div>`;
            return;
        }

        filtered.forEach(prod => {
            const div = document.createElement('div');
            div.className = 'search-result-item';
            div.onclick = () => addItemFromSearch(prod);
            
            div.innerHTML = `
                <img src="${prod.image_url}" alt="${prod.name}">
                <div class="search-result-info">
                    <span class="search-result-name">${prod.name}</span>
                    <span class="search-result-sku">Barcode: ${prod.sku || '-'} | Stok: ${prod.stock}</span>
                </div>
            `;
            dropdown.appendChild(div);
        });
    };

    // Add item selected from dropdown search
    function addItemFromSearch(product) {
        // Calculate smart default order
        const defaultOrderQty = Math.max(10 - parseInt(product.stock), 10);
        
        shoppingList.push({
            id: product.id,
            name: product.name,
            sku: product.sku || '-',
            stock: parseInt(product.stock),
            purchase_price: parseFloat(product.purchase_price),
            image_url: product.image_url,
            qty: defaultOrderQty,
            unit: 'pcs'
        });

        // Reset input and hide dropdown
        document.getElementById('product-search-input').value = '';
        document.getElementById('product-search-results').style.display = 'none';

        renderList();
        showToast(`'${product.name}' ditambahkan ke daftar kulakan.`);
    }

    // Helper to hide dropdown
    window.hideSearchDropdown = function() {
        // Delayed to allow click events to register on result items
        setTimeout(() => {
            document.getElementById('product-search-results').style.display = 'none';
        }, 200);
    };

    // Format and Copy shopping list to clipboard for WhatsApp sending
    window.copyToWhatsApp = function() {
        if (shoppingList.length === 0) {
            alert("Daftar belanja masih kosong.");
            return;
        }

        const dateStr = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        
        let text = `*DAFTAR BELANJA KULAKAN - SRC SUYANTO*\n`;
        text += `Tanggal: ${dateStr}\n`;
        text += `------------------------------------------\n\n`;

        let grandTotal = 0;
        
        shoppingList.forEach((item, index) => {
            const multiplier = item.unit === 'karton' ? 40 : (item.unit === 'pak' ? 12 : 1);
            const subtotal = item.purchase_price * item.qty * multiplier;
            grandTotal += subtotal;
            
            let qtyDisplay = '';
            if (item.unit === 'karton') {
                qtyDisplay = `*${item.qty} Karton (Dus)* (40 pcs/dus = total ${item.qty * 40} pcs)`;
            } else if (item.unit === 'pak') {
                qtyDisplay = `*${item.qty} Pak* (12 pcs/pak = total ${item.qty * 12} pcs)`;
            } else {
                qtyDisplay = `*${item.qty} Pcs*`;
            }
            
            text += `${index + 1}. *${item.name}*\n`;
            text += `   - Barcode : ${item.sku}\n`;
            text += `   - Stok Kini: ${item.stock} unit\n`;
            text += `   - Rencana Beli: ${qtyDisplay} x ${formatRupiah(item.purchase_price)} / pcs\n`;
            text += `   - Estimasi Subtotal: ${formatRupiah(subtotal)}\n\n`;
        });
        
        text += `------------------------------------------\n`;
        text += `*TOTAL ESTIMASI MODAL: ${formatRupiah(grandTotal)}*\n\n`;
        text += `_Dibuat otomatis dari Sistem Kasir SRC SUYANTO_`;

        // Copy using Clipboard API
        navigator.clipboard.writeText(text).then(() => {
            showToast("Daftar kulakan berhasil disalin! Silakan paste (tempel) di WhatsApp.");
        }).catch(err => {
            console.error("Gagal menyalin teks: ", err);
            alert("Gagal menyalin teks otomatis. Silakan coba cetak halaman.");
        });
    };

    // Helper to update item unit
    window.updateItemUnit = function(id, val) {
        const item = shoppingList.find(i => i.id === id);
        if (item) {
            item.unit = val;
            renderList();
        }
    };

    // Toast notification trigger
    function showToast(message) {
        const toast = document.getElementById('toast');
        document.getElementById('toast-message').innerText = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
</script>
@endsection

@section('print-area')
<div id="print-restock-area">
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="font-size: 1.6rem; font-weight: bold; margin: 0; text-transform: uppercase;">DAFTAR BELANJA KULAKAN</h2>
        <h3 style="font-size: 1.25rem; font-weight: bold; margin: 5px 0 0 0; letter-spacing: 0.5px;">SRC SUYANTO</h3>
        <p style="font-size: 0.9rem; margin: 6px 0 0 0; color: #333;">
            Tanggal Cetak: <span id="print-date-val"></span> | 
            Total Estimasi Modal: <span id="print-total-val" style="font-weight: bold;"></span>
        </p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 150px;">Kode Barcode / SKU</th>
                <th>Nama Barang</th>
                <th style="text-align: center; width: 100px;">Stok Toko</th>
                <th style="text-align: right; width: 120px;">Harga Beli</th>
                <th style="text-align: center; width: 120px;">Jumlah Beli</th>
                <th style="text-align: right; width: 150px;">Subtotal</th>
            </tr>
        </thead>
        <tbody id="print-restock-tbody">
            <!-- Dynamic rows populated via JS -->
        </tbody>
    </table>
</div>
@endsection
