// Cashier Cart & Bluetooth Printer JS

document.addEventListener('DOMContentLoaded', () => {
    // Cart state
    let cart = [];
    
    // UI elements
    const productsSearch = document.getElementById('products-search');
    const paymentMethodSelect = document.getElementById('payment-method');
    const paymentAmountInput = document.getElementById('payment-amount');
    const changeAmountDisplay = document.getElementById('change-amount');
    const totalDisplay = document.getElementById('cart-total');
    const subtotalDisplay = document.getElementById('cart-subtotal');
    const itemsCountDisplay = document.getElementById('cart-items-count');
    const checkoutBtn = document.getElementById('checkout-btn');
    const cartItemsContainer = document.getElementById('cart-items');
    
    // Debt fields
    const debtFields = document.getElementById('debt-fields');
    const customerNameInput = document.getElementById('customer-name');
    const dueDateInput = document.getElementById('due-date');
    
    // Print Modal elements
    const receiptModal = document.getElementById('receipt-modal');
    const modalClose = receiptModal ? receiptModal.querySelector('.modal-close') : null;
    const printBrowserBtn = document.getElementById('print-browser-btn');
    const printBluetoothBtn = document.getElementById('print-bluetooth-btn');
    
    // Printer State
    let bluetoothDevice = null;
    let printerCharacteristic = null;
    const PRINTER_SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
    const PRINTER_CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';
    
    // Last checkout data
    let lastSaleData = null;
    let activeVoucher = null;

    // Helper: format rupiah
    function formatRupiah(number) {
        return 'Rp. ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
    }
    
    function formatRupiahRaw(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    // Add to cart click handler
    window.addToCart = function(productJson) {
        let product = productJson;
        if (typeof productJson === 'string') {
            product = JSON.parse(productJson);
        }
        
        // Check if product is already in cart
        const existingItem = cart.find(item => item.id === product.id);
        
        if (existingItem) {
            if (existingItem.qty >= product.stock) {
                alert(`Stok untuk '${product.name}' terbatas.`);
                return;
            }
            existingItem.qty += 1;
        } else {
            if (product.stock < 1) {
                alert(`Stok untuk '${product.name}' habis.`);
                return;
            }
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.selling_price),
                qty: 1,
                stock: parseInt(product.stock)
            });
        }
        
        renderCart();
    };

    // Update quantity
    window.updateQty = function(productId, delta) {
        const item = cart.find(item => item.id === productId);
        if (item) {
            const newQty = item.qty + delta;
            if (newQty <= 0) {
                removeFromCart(productId);
            } else if (newQty > item.stock) {
                alert(`Stok untuk '${item.name}' hanya tersedia ${item.stock} unit.`);
            } else {
                item.qty = newQty;
                renderCart();
            }
        }
    };

    // Remove from cart
    window.removeFromCart = function(productId) {
        cart = cart.filter(item => item.id !== productId);
        renderCart();
    };

    // Render cart items & summary
    function renderCart() {
        if (!cartItemsContainer) return;
        
        cartItemsContainer.innerHTML = '';
        
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `
                <div style="text-align: center; color: var(--text-secondary); margin-top: 40px;">
                    <i class="fas fa-shopping-cart" style="font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.3;"></i>
                    Keranjang Belanja Kosong
                </div>
            `;
            
            subtotalDisplay.innerText = formatRupiah(0);
            totalDisplay.innerText = formatRupiah(0);
            document.getElementById('voucher-discount-row').style.display = 'none';
            itemsCountDisplay.innerText = '0';
            paymentAmountInput.value = 0;
            changeAmountDisplay.innerText = formatRupiah(0);
            checkoutBtn.disabled = true;
            activeVoucher = null;
            document.getElementById('voucher-code-input').value = '';
            document.getElementById('voucher-msg').style.display = 'none';
            return;
        }
        
        let total = 0;
        let count = 0;
        
        cart.forEach(item => {
            const itemTotal = item.price * item.qty;
            total += itemTotal;
            count += item.qty;
            
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';
            itemElement.innerHTML = `
                <div class="cart-item-details">
                    <span class="cart-item-name">${item.name}</span>
                    <span class="cart-item-price">${formatRupiah(item.price)}</span>
                </div>
                <div class="cart-qty-ctrl">
                    <button class="cart-qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                    <span class="cart-qty-val">${item.qty}</span>
                    <button class="cart-qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
                </div>
                <div style="font-weight:700; font-size:0.85rem; width:80px; text-align:right;">
                    ${formatRupiah(itemTotal)}
                </div>
                <button class="cart-item-remove" onclick="removeFromCart(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            cartItemsContainer.appendChild(itemElement);
        });
        
        // Handle voucher validity on cart updates
        if (activeVoucher && total < parseFloat(activeVoucher.min_spend)) {
            activeVoucher = null;
            document.getElementById('voucher-code-input').value = '';
            const msgDiv = document.getElementById('voucher-msg');
            msgDiv.style.color = 'var(--danger)';
            msgDiv.innerText = 'Voucher dilepas karena total belanja kurang dari minimal belanja.';
            msgDiv.style.display = 'block';
        }

        let discount = 0;
        if (activeVoucher) {
            if (activeVoucher.type === 'fixed') {
                discount = parseFloat(activeVoucher.value);
            } else if (activeVoucher.type === 'percent') {
                discount = total * (parseFloat(activeVoucher.value) / 100);
                if (activeVoucher.max_discount && discount > parseFloat(activeVoucher.max_discount)) {
                    discount = parseFloat(activeVoucher.max_discount);
                }
            }
            discount = Math.min(discount, total);
        }

        const grandTotal = total - discount;

        subtotalDisplay.innerText = formatRupiah(total);
        if (discount > 0) {
            document.getElementById('voucher-discount-row').style.display = 'flex';
            document.getElementById('voucher-discount-amount').innerText = '-' + formatRupiah(discount);
        } else {
            document.getElementById('voucher-discount-row').style.display = 'none';
        }
        totalDisplay.innerText = formatRupiah(grandTotal);
        itemsCountDisplay.innerText = count;
        
        // Update mobile cart badge count
        const mobileCartBadge = document.getElementById('pos-cart-badge');
        if (mobileCartBadge) {
            mobileCartBadge.innerText = count;
            if (count > 0) {
                mobileCartBadge.style.display = 'inline-flex';
            } else {
                mobileCartBadge.style.display = 'none';
            }
        }
        
        // Auto fill payment amount if cash and not set yet
        const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
        if (paymentMethodSelect.value === 'cash' && (paymentAmount === 0 || paymentAmount < grandTotal)) {
            paymentAmountInput.value = grandTotal;
        }
        
        calculateChange();
        checkoutBtn.disabled = false;
    }

    // Calculate change
    function calculateChange() {
        if (cart.length === 0) return;
        
        const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        
        let discount = 0;
        if (activeVoucher) {
            if (activeVoucher.type === 'fixed') {
                discount = parseFloat(activeVoucher.value);
            } else if (activeVoucher.type === 'percent') {
                discount = total * (parseFloat(activeVoucher.value) / 100);
                if (activeVoucher.max_discount && discount > parseFloat(activeVoucher.max_discount)) {
                    discount = parseFloat(activeVoucher.max_discount);
                }
            }
            discount = Math.min(discount, total);
        }

        const grandTotal = total - discount;
        const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
        
        if (paymentMethodSelect.value === 'cash') {
            const change = paymentAmount - grandTotal;
            changeAmountDisplay.innerText = formatRupiah(change >= 0 ? change : 0);
            
            if (change < 0) {
                changeAmountDisplay.style.color = 'var(--danger)';
            } else {
                changeAmountDisplay.style.color = 'var(--success)';
            }
        } else {
            // If debt (utang), show the remaining debt amount instead of change
            const remaining = grandTotal - paymentAmount;
            changeAmountDisplay.innerText = formatRupiah(remaining >= 0 ? remaining : 0);
            changeAmountDisplay.style.color = 'var(--warning)';
        }
    }

    // Event listeners for POS cashier
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', () => {
            if (paymentMethodSelect.value === 'debt') {
                debtFields.style.display = 'block';
                paymentAmountInput.value = 0;
                document.getElementById('change-label').innerText = 'Sisa Utang:';
                document.getElementById('payment-label').innerText = 'Bayar Uang Muka (DP)';
            } else {
                debtFields.style.display = 'none';
                const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                let discount = 0;
                if (activeVoucher) {
                    if (activeVoucher.type === 'fixed') {
                        discount = parseFloat(activeVoucher.value);
                    } else if (activeVoucher.type === 'percent') {
                        discount = total * (parseFloat(activeVoucher.value) / 100);
                        if (activeVoucher.max_discount && discount > parseFloat(activeVoucher.max_discount)) {
                            discount = parseFloat(activeVoucher.max_discount);
                        }
                    }
                    discount = Math.min(discount, total);
                }
                const grandTotal = total - discount;
                paymentAmountInput.value = grandTotal;
                document.getElementById('change-label').innerText = 'Uang Kembalian:';
                document.getElementById('payment-label').innerText = 'Jumlah Uang Diterima';
            }
            calculateChange();
        });
    }

    if (paymentAmountInput) {
        paymentAmountInput.addEventListener('input', calculateChange);
    }

    // Search filter client-side (very fast for shop catalog) & Barcode scan handler
    if (productsSearch) {
        // Auto-focus on load
        productsSearch.focus();

        productsSearch.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const productCards = document.querySelectorAll('.products-grid .product-card');
            
            productCards.forEach(card => {
                const name = card.querySelector('.product-card-name').textContent.toLowerCase();
                const sku = card.getAttribute('data-sku') ? card.getAttribute('data-sku').toLowerCase() : '';
                
                if (name.includes(query) || sku.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Keydown listener for Barcode Scanner (types characters and finishes with 'Enter')
        productsSearch.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = e.target.value.trim().toLowerCase();
                if (!query) return;

                let matchedCard = null;
                const productCards = document.querySelectorAll('.products-grid .product-card');

                // 1. Look for exact match by SKU/Barcode
                for (const card of productCards) {
                    const sku = card.getAttribute('data-sku') ? card.getAttribute('data-sku').trim().toLowerCase() : '';
                    if (sku === query) {
                        matchedCard = card;
                        break;
                    }
                }

                // 2. If not found, look for exact match by Product Name
                if (!matchedCard) {
                    for (const card of productCards) {
                        const name = card.querySelector('.product-card-name').textContent.trim().toLowerCase();
                        if (name === query) {
                            matchedCard = card;
                            break;
                        }
                    }
                }

                // 3. If still not found, check if there is only 1 visible card in the filtered grid
                if (!matchedCard) {
                    const visibleCards = Array.from(productCards).filter(card => card.style.display !== 'none');
                    if (visibleCards.length === 1) {
                        matchedCard = visibleCards[0];
                    }
                }

                if (matchedCard) {
                    // Check if disabled/out of stock
                    const isOutOrExp = matchedCard.style.opacity === '0.5' || matchedCard.style.pointerEvents === 'none';
                    if (isOutOrExp) {
                        alert("Produk tidak bisa ditambahkan ke keranjang karena stok habis atau kedaluwarsa.");
                    } else {
                        // Programmatically click to add to cart
                        matchedCard.click();
                        // Clear search input and trigger input event to show all cards
                        e.target.value = '';
                        e.target.dispatchEvent(new Event('input'));
                    }
                } else {
                    alert(`Barang dengan barcode/nama "${e.target.value}" tidak ditemukan.`);
                }
                
                // Refocus search
                e.target.focus();
            }
        });
    }

    // Global shortcut key: F2 to focus and select text in search bar
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            if (productsSearch) {
                productsSearch.focus();
                productsSearch.select();
            }
        }
    });

    // Refocus search when receipt modal is closed
    if (receiptModal) {
        // Find all close elements in receipt modal (both the close x button and any footer/secondary action buttons)
        const closeBtnElements = receiptModal.querySelectorAll('.modal-close, button');
        closeBtnElements.forEach(btn => {
            btn.addEventListener('click', () => {
                // Wait slightly for modal animation to complete or class to be removed
                setTimeout(() => {
                    if (productsSearch && !receiptModal.classList.contains('active')) {
                        productsSearch.focus();
                        productsSearch.select();
                    }
                }, 150);
            });
        });
    }

    // Checkout submission
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', async () => {
            if (cart.length === 0) return;
            
            checkoutBtn.disabled = true;
            checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            
            let discount = 0;
            if (activeVoucher) {
                if (activeVoucher.type === 'fixed') {
                    discount = parseFloat(activeVoucher.value);
                } else if (activeVoucher.type === 'percent') {
                    discount = total * (parseFloat(activeVoucher.value) / 100);
                    if (activeVoucher.max_discount && discount > parseFloat(activeVoucher.max_discount)) {
                        discount = parseFloat(activeVoucher.max_discount);
                    }
                }
                discount = Math.min(discount, total);
            }
            const grandTotal = total - discount;
            const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
            const paymentMethod = paymentMethodSelect.value;
            
            // Validation
            if (['cash', 'transfer', 'qris'].includes(paymentMethod) && paymentAmount < grandTotal) {
                alert(`Uang pembayaran kurang! Total belanja setelah diskon: ${formatRupiah(grandTotal)}`);
                checkoutBtn.disabled = false;
                checkoutBtn.innerText = 'PROSES PEMBAYARAN';
                return;
            }
            
            if (paymentMethod === 'debt') {
                if (!customerNameInput.value.trim()) {
                    alert('Nama pelanggan wajib diisi untuk pencatatan utang.');
                    customerNameInput.focus();
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerText = 'PROSES PEMBAYARAN';
                    return;
                }
                
                if (paymentAmount >= grandTotal) {
                    alert('Jika menggunakan sistem utang/bon, nominal bayar awal (DP) harus lebih kecil dari total belanja setelah diskon.');
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerText = 'PROSES PEMBAYARAN';
                    return;
                }
            }
            
            // Prepare payload
            const payload = {
                cart: cart.map(item => ({
                    id: item.id,
                    quantity: item.qty,
                    is_custom: item.is_custom || false,
                    name: item.name,
                    purchase_price: item.purchase_price || 0,
                    price: item.price || 0
                })),
                payment_method: paymentMethod,
                payment_amount: paymentAmount,
                customer_name: paymentMethod === 'debt' ? customerNameInput.value.trim() : null,
                due_date: paymentMethod === 'debt' ? dueDateInput.value : null,
                voucher_code: activeVoucher ? activeVoucher.code : null
            };
            
            try {
                const response = await fetch(typeof checkoutUrl !== 'undefined' ? checkoutUrl : '/cashier/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    lastSaleData = result.data;
                    
                    // Show receipt modal
                    showReceiptModal(result.data);
                    
                    // Reset POS cart state
                    cart = [];
                    customerNameInput.value = '';
                    dueDateInput.value = '';
                    paymentMethodSelect.value = 'cash';
                    debtFields.style.display = 'none';
                    document.getElementById('change-label').innerText = 'Uang Kembalian:';
                    document.getElementById('payment-label').innerText = 'Jumlah Uang Diterima';
                    
                    // Clear applied voucher in UI
                    activeVoucher = null;
                    document.getElementById('voucher-code-input').value = '';
                    document.getElementById('voucher-msg').style.display = 'none';
                    
                    // Fetch updated products stock (simulate page refresh or reload products in UI)
                    payload.cart.forEach(cartItem => {
                        const card = document.querySelector(`.product-card[data-id="${cartItem.id}"]`);
                        if (card) {
                            const stockSpan = card.querySelector('.product-card-stock-value');
                            if (stockSpan) {
                                const currentStock = parseInt(stockSpan.textContent);
                                const newStock = currentStock - cartItem.quantity;
                                stockSpan.textContent = newStock;
                                
                                // Disable card if stock is now 0
                                if (newStock <= 0) {
                                    card.style.opacity = '0.5';
                                    card.style.pointerEvents = 'none';
                                    const badge = card.querySelector('.product-card-badge-container');
                                    if (badge) {
                                        badge.innerHTML = '<span class="badge danger">Stok Habis</span>';
                                    }
                                } else if (newStock < 10) {
                                    const badge = card.querySelector('.product-card-badge-container');
                                    if (badge) {
                                        badge.innerHTML = '<span class="badge warning">Stok Hampir Habis</span>';
                                    }
                                }
                            }
                        }
                    });
                    
                    renderCart();
                } else {
                    alert('Gagal memproses transaksi: ' + result.message);
                }
            } catch (error) {
                console.error('Checkout error:', error);
                alert('Terjadi kesalahan koneksi saat memproses pembayaran.');
            } finally {
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> PROSES PEMBAYARAN';
            }
        });
    }

    // Modal Close
    if (modalClose) {
        modalClose.addEventListener('click', () => {
            receiptModal.classList.remove('active');
        });
    }

    // Show receipt details in modal
    function showReceiptModal(saleData) {
        const sale = saleData.sale;
        const debt = saleData.debt;
        const dateStr = saleData.date_formatted;
        
        // Render receipt inside modal body
        const receiptContainer = document.getElementById('modal-receipt-content');
        if (!receiptContainer) return;
        
        let detailsHtml = '';
        sale.sale_details.forEach(detail => {
            const name = detail.custom_name || (detail.product ? detail.product.name : 'Barang Dihapus');
            detailsHtml += `
                <tr>
                    <td colspan="2" style="font-weight:600;">${name}</td>
                </tr>
                <tr>
                    <td style="color:var(--text-secondary); font-size:0.8rem; padding-top:2px;">
                        ${detail.quantity} x ${formatRupiah(detail.selling_price)}
                    </td>
                    <td style="text-align:right; font-weight:600;">
                        ${formatRupiah(detail.subtotal)}
                    </td>
                </tr>
            `;
        });
        
        let paymentInfoHtml = '';
        if (['cash', 'transfer', 'qris'].includes(sale.payment_method)) {
            let labelDiterima = 'Uang Diterima:';
            if (sale.payment_method === 'transfer') labelDiterima = 'Transfer Masuk:';
            if (sale.payment_method === 'qris') labelDiterima = 'Bayar QRIS:';
            
            paymentInfoHtml = `
                <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-secondary);">${labelDiterima}</span>
                    <span style="font-weight:600;">${formatRupiah(sale.payment_amount)}</span>
                </div>
                <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px; font-weight:700; color:var(--success);">
                    <span>Uang Kembalian:</span>
                    <span>${formatRupiah(sale.change_amount)}</span>
                </div>
            `;
        } else if (debt) {
            paymentInfoHtml = `
                <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-secondary);">Nama Pelanggan:</span>
                    <span style="font-weight:600;">${debt.customer_name}</span>
                </div>
                <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-secondary);">Uang Muka (DP):</span>
                    <span style="font-weight:600;">${formatRupiah(sale.payment_amount)}</span>
                </div>
                <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px; font-weight:700; color:var(--warning);">
                    <span>Sisa Utang:</span>
                    <span>${formatRupiah(debt.remaining_amount)}</span>
                </div>
                ${debt.due_date ? `
                <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:0.8rem; color:var(--text-secondary);">
                    <span>Jatuh Tempo:</span>
                    <span>${new Date(debt.due_date).toLocaleDateString('id-ID')}</span>
                </div>
                ` : ''}
            `;
        }
        
        let discountHtml = '';
        if (parseFloat(sale.discount_amount) > 0) {
            const originalSubtotal = parseFloat(sale.total_price) + parseFloat(sale.discount_amount);
            discountHtml = `
                <div style="display:flex; justify-content:space-between; margin-bottom:6px; color:var(--text-secondary);">
                    <span>Total Sementara:</span>
                    <span>${formatRupiah(originalSubtotal)}</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:6px; color:var(--danger);">
                    <span>Potongan Voucher:</span>
                    <span>-${formatRupiah(sale.discount_amount)}</span>
                </div>
            `;
        }
        
        receiptContainer.innerHTML = `
            <div style="text-align:center; margin-bottom:16px;">
                <h4 style="font-size:1.1rem; font-weight:800; letter-spacing:-0.5px;">SRC SUYANTO</h4>
                <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:2px;">Aplikasi Kasir POS Toko</p>
                <p style="font-size:0.75rem; color:var(--text-secondary);">somopuro, mutihan, rt 04/ rw02, gantiwarno, klaten</p>
            </div>
            
            <div style="border-top:1px dashed var(--border-color); padding-top:12px; margin-bottom:12px; font-size:0.8rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-secondary);">No. Nota:</span>
                    <span style="font-weight:600;">${sale.invoice_number}</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-secondary);">Tanggal:</span>
                    <span>${dateStr}</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-secondary);">Cara Bayar:</span>
                    <span style="font-weight:600; text-transform:uppercase;">
                        ${sale.payment_method === 'cash' ? 'TUNAI' : (sale.payment_method === 'transfer' ? 'TRANSFER' : (sale.payment_method === 'qris' ? 'QRIS' : 'UTANG/KASBON'))}
                    </span>
                </div>
            </div>
            
            <table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border-color); font-weight:700;">
                        <th style="text-align:left; padding-bottom:6px; color:var(--text-secondary); font-size:0.75rem;">BARANG</th>
                        <th style="text-align:right; padding-bottom:6px; color:var(--text-secondary); font-size:0.75rem;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    ${detailsHtml}
                </tbody>
            </table>
            
            <div style="border-top:1px dashed var(--border-color); padding-top:12px; font-size:0.85rem;">
                ${discountHtml}
                <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:1rem; font-weight:800; color:var(--text-primary);">
                    <span>TOTAL BAYAR:</span>
                    <span>${formatRupiah(sale.total_price)}</span>
                </div>
                ${paymentInfoHtml}
            </div>
            
            <div style="text-align:center; margin-top:20px; border-top:1px dashed var(--border-color); padding-top:12px;">
                <p style="font-size:0.8rem; color:var(--text-secondary);">Terima Kasih Atas Kunjungan Anda</p>
                <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:2px;">Nota Cetak Bluetooth & Kertas</p>
            </div>
        `;
        
        // Also update the hidden #receipt-print-area in the body for browser printing
        updatePrintArea(sale, debt, dateStr);
        
        receiptModal.classList.add('active');
    }

    // Update the hidden print area for window.print()
    function updatePrintArea(sale, debt, dateStr) {
        let printArea = document.getElementById('receipt-print-area');
        if (!printArea) {
            printArea = document.createElement('div');
            printArea.id = 'receipt-print-area';
            document.body.appendChild(printArea);
        }
        
        let itemsHtml = '';
        sale.sale_details.forEach(detail => {
            const name = detail.custom_name || (detail.product ? detail.product.name : 'Barang Dihapus');
            itemsHtml += `
                <tr>
                    <td colspan="2" style="font-weight:bold;">${name}</td>
                </tr>
                <tr>
                    <td>${detail.quantity} x ${formatRupiahRaw(detail.selling_price)}</td>
                    <td style="text-align:right;">${formatRupiahRaw(detail.subtotal)}</td>
                </tr>
            `;
        });
        
        let paymentHtml = '';
        if (['cash', 'transfer', 'qris'].includes(sale.payment_method)) {
            let labelBayar = 'BAYAR TUNAI:';
            if (sale.payment_method === 'transfer') labelBayar = 'TRANSFER BANK:';
            if (sale.payment_method === 'qris') labelBayar = 'BAYAR QRIS:';
            
            paymentHtml = `
                <div class="receipt-info-row">
                    <span>${labelBayar}</span>
                    <span>${formatRupiahRaw(sale.payment_amount)}</span>
                </div>
                <div class="receipt-info-row" style="font-weight:bold;">
                    <span>KEMBALIAN:</span>
                    <span>${formatRupiahRaw(sale.change_amount)}</span>
                </div>
            `;
        } else if (debt) {
            paymentHtml = `
                <div class="receipt-info-row">
                    <span>PELANGGAN:</span>
                    <span>${debt.customer_name.toUpperCase()}</span>
                </div>
                <div class="receipt-info-row">
                    <span>UANG MUKA (DP):</span>
                    <span>${formatRupiahRaw(sale.payment_amount)}</span>
                </div>
                <div class="receipt-info-row" style="font-weight:bold;">
                    <span>SISA UTANG:</span>
                    <span>${formatRupiahRaw(debt.remaining_amount)}</span>
                </div>
                ${debt.due_date ? `
                <div class="receipt-info-row">
                    <span>JATUH TEMPO:</span>
                    <span>${new Date(debt.due_date).toLocaleDateString('id-ID')}</span>
                </div>
                ` : ''}
            `;
        }
        
        let printTotalsHtml = '';
        if (parseFloat(sale.discount_amount) > 0) {
            const originalSubtotal = parseFloat(sale.total_price) + parseFloat(sale.discount_amount);
            printTotalsHtml = `
                <div class="receipt-info-row">
                    <span>SUBTOTAL:</span>
                    <span>${formatRupiahRaw(originalSubtotal)}</span>
                </div>
                <div class="receipt-info-row">
                    <span>DISKON VOUCHER:</span>
                    <span>-${formatRupiahRaw(sale.discount_amount)}</span>
                </div>
                <div class="receipt-total-row">
                    <span>TOTAL:</span>
                    <span>${formatRupiahRaw(sale.total_price)}</span>
                </div>
            `;
        } else {
            printTotalsHtml = `
                <div class="receipt-total-row">
                    <span>TOTAL:</span>
                    <span>${formatRupiahRaw(sale.total_price)}</span>
                </div>
            `;
        }
        
        printArea.innerHTML = `
            <div class="receipt-header">
                <div class="receipt-title">SRC SUYANTO</div>
                <div>Aplikasi Kasir POS Toko</div>
                <div>somopuro, mutihan, rt 04/ rw02, gantiwarno, klaten</div>
            </div>
            
            <div class="receipt-divider"></div>
            
            <div class="receipt-info-row">
                <span>NO NOTA:</span>
                <span>${sale.invoice_number}</span>
            </div>
            <div class="receipt-info-row">
                <span>TANGGAL:</span>
                <span>${dateStr}</span>
            </div>
            <div class="receipt-info-row">
                <span>METODE:</span>
                <span>
                    ${sale.payment_method === 'cash' ? 'TUNAI' : (sale.payment_method === 'transfer' ? 'TRANSFER' : (sale.payment_method === 'qris' ? 'QRIS' : 'UTANG/KASBON'))}
                </span>
            </div>
            
            <div class="receipt-divider"></div>
            
            <table class="receipt-table">
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
            
            <div class="receipt-divider"></div>
            
            <div class="receipt-totals">
                ${printTotalsHtml}
                ${paymentHtml}
            </div>
            
            <div class="receipt-divider"></div>
            
            <div class="receipt-footer">
                <div>TERIMA KASIH</div>
                <div>ATAS KUNJUNGAN ANDA</div>
            </div>
        `;
    }

    // Download raw receipt as a text file if html2canvas is not available
    function downloadReceiptText(sale, debt, dateStr) {
        let text = `SRC SUYANTO\n`;
        text += `Aplikasi Kasir POS Toko\n`;
        text += `somopuro, mutihan, rt 04/ rw02, gantiwarno, klaten\n`;
        text += `--------------------------------\n`;
        text += `No. Nota: ${sale.invoice_number}\n`;
        text += `Tanggal : ${dateStr}\n`;
        text += `Metode  : ${sale.payment_method.toUpperCase() === 'CASH' ? 'TUNAI' : (sale.payment_method.toUpperCase() === 'TRANSFER' ? 'TRANSFER' : (sale.payment_method.toUpperCase() === 'QRIS' ? 'QRIS' : 'UTANG/KASBON'))}\n`;
        if (debt) {
            text += `Pelanggan: ${debt.customer_name.toUpperCase()}\n`;
        }
        text += `--------------------------------\n`;
        sale.sale_details.forEach(detail => {
            const name = detail.custom_name || (detail.product ? detail.product.name : 'Barang');
            text += `${name}\n`;
            text += `${detail.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(detail.selling_price)}   Rp ${new Intl.NumberFormat('id-ID').format(detail.subtotal)}\n`;
        });
        text += `--------------------------------\n`;
        if (parseFloat(sale.discount_amount) > 0) {
            const originalSubtotal = parseFloat(sale.total_price) + parseFloat(sale.discount_amount);
            text += `Subtotal     : Rp ${new Intl.NumberFormat('id-ID').format(originalSubtotal)}\n`;
            text += `Diskon Voc   : -Rp ${new Intl.NumberFormat('id-ID').format(sale.discount_amount)}\n`;
        }
        text += `TOTAL BAYAR  : Rp ${new Intl.NumberFormat('id-ID').format(sale.total_price)}\n`;
        text += `BAYAR        : Rp ${new Intl.NumberFormat('id-ID').format(sale.payment_amount)}\n`;
        if (sale.payment_method === 'debt' && debt) {
            text += `SISA UTANG   : Rp ${new Intl.NumberFormat('id-ID').format(debt.remaining_amount)}\n`;
        } else {
            text += `KEMBALIAN    : Rp ${new Intl.NumberFormat('id-ID').format(sale.change_amount)}\n`;
        }
        text += `--------------------------------\n`;
        text += `TERIMA KASIH\n`;
        text += `ATAS KUNJUNGAN ANDA\n`;
        
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Nota-${sale.invoice_number}.txt`;
        link.click();
    }

    // Share transaction text format directly via WhatsApp Web/App
    function shareToWhatsApp(sale, debt, dateStr) {
        let msg = `*SRC SUYANTO - NOTA TRANSAKSI*\n`;
        msg += `-----------------------------------------\n`;
        msg += `*No. Nota:* ${sale.invoice_number}\n`;
        msg += `*Tanggal:* ${dateStr}\n`;
        msg += `*Metode:* ${sale.payment_method.toUpperCase() === 'CASH' ? 'TUNAI' : (sale.payment_method.toUpperCase() === 'TRANSFER' ? 'TRANSFER' : (sale.payment_method.toUpperCase() === 'QRIS' ? 'QRIS' : 'UTANG/KASBON'))}\n`;
        if (debt) {
            msg += `*Pelanggan:* ${debt.customer_name}\n`;
        }
        msg += `-----------------------------------------\n`;
        
        sale.sale_details.forEach(detail => {
            const name = detail.custom_name || (detail.product ? detail.product.name : 'Barang');
            msg += `- *${name}*\n  ${detail.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(detail.selling_price)} = *Rp ${new Intl.NumberFormat('id-ID').format(detail.subtotal)}*\n`;
        });
        msg += `-----------------------------------------\n`;
        if (parseFloat(sale.discount_amount) > 0) {
            const originalSubtotal = parseFloat(sale.total_price) + parseFloat(sale.discount_amount);
            msg += `*Subtotal:* Rp ${new Intl.NumberFormat('id-ID').format(originalSubtotal)}\n`;
            msg += `*Diskon Voucher:* -Rp ${new Intl.NumberFormat('id-ID').format(sale.discount_amount)}\n`;
        }
        msg += `*TOTAL BAYAR: Rp ${new Intl.NumberFormat('id-ID').format(sale.total_price)}*\n`;
        msg += `*BAYAR:* Rp ${new Intl.NumberFormat('id-ID').format(sale.payment_amount)}\n`;
        
        if (sale.payment_method === 'debt' && debt) {
            msg += `*SISA UTANG: Rp ${new Intl.NumberFormat('id-ID').format(debt.remaining_amount)}*\n`;
            if (debt.due_date) {
                msg += `*Jatuh Tempo:* ${new Date(debt.due_date).toLocaleDateString('id-ID')}\n`;
            }
        } else {
            msg += `*KEMBALIAN: Rp ${new Intl.NumberFormat('id-ID').format(sale.change_amount)}*\n`;
        }
        msg += `-----------------------------------------\n`;
        msg += `_Terima kasih atas kunjungan Anda!_\n`;
        
        const encodedMsg = encodeURIComponent(msg);
        const url = `https://api.whatsapp.com/send?text=${encodedMsg}`;
        window.open(url, '_blank');
    }

    // Trigger standard browser print dialog
    if (printBrowserBtn) {
        printBrowserBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // Save Receipt Action
    const saveReceiptBtn = document.getElementById('save-receipt-btn');
    if (saveReceiptBtn) {
        saveReceiptBtn.addEventListener('click', () => {
            if (!lastSaleData) return;
            const sale = lastSaleData.sale;
            const debt = lastSaleData.debt;
            const dateStr = lastSaleData.date_formatted;
            
            if (window.html2canvas) {
                const receipt = document.getElementById('modal-receipt-content');
                html2canvas(receipt, {
                    backgroundColor: '#1b2535',
                    scale: 2
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = `Nota-${sale.invoice_number}.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                });
            } else {
                downloadReceiptText(sale, debt, dateStr);
            }
        });
    }

    // Share via WhatsApp Action
    const shareWaBtn = document.getElementById('share-wa-btn');
    if (shareWaBtn) {
        shareWaBtn.addEventListener('click', () => {
            if (!lastSaleData) return;
            shareToWhatsApp(lastSaleData.sale, lastSaleData.debt, lastSaleData.date_formatted);
        });
    }

    // Web Bluetooth API Direct ESC/POS Print Action
    if (printBluetoothBtn) {
        printBluetoothBtn.addEventListener('click', async () => {
            if (!lastSaleData) return;
            
            printBluetoothBtn.disabled = true;
            printBluetoothBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghubungkan...';
            
            try {
                if (!navigator.bluetooth) {
                    throw new Error("Web Bluetooth tidak didukung pada browser/koneksi ini. Web Bluetooth hanya aktif jika menggunakan localhost atau koneksi aman (HTTPS).");
                }

                if (!printerCharacteristic) {
                    alert("Silakan pilih printer Bluetooth thermal pada dialog sistem berikut.");
                    
                    bluetoothDevice = await navigator.bluetooth.requestDevice({
                        filters: [
                            { services: [PRINTER_SERVICE_UUID] },
                            { namePrefix: 'Printer' },
                            { namePrefix: 'PRINTER' },
                            { namePrefix: 'MPT' },
                            { namePrefix: 'Thermal' }
                        ],
                        optionalServices: [PRINTER_SERVICE_UUID]
                    });
                    
                    const server = await bluetoothDevice.gatt.connect();
                    const service = await server.getPrimaryService(PRINTER_SERVICE_UUID);
                    printerCharacteristic = await service.getCharacteristic(PRINTER_CHARACTERISTIC_UUID);
                    
                    bluetoothDevice.addEventListener('gattserverdisconnected', onDisconnected);
                }
                
                printBluetoothBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencetak...';
                
                // Format transaction details into raw ESC/POS bytes
                const bytes = formatReceiptESC(lastSaleData.sale, lastSaleData.debt, lastSaleData.date_formatted);
                
                // Write bytes to printer characteristic
                await writeInChunks(printerCharacteristic, bytes);
                
                alert("Nota berhasil dikirim ke printer Bluetooth.");
            } catch (error) {
                console.error("Bluetooth Printing Error:", error);
                alert("Gagal mencetak via Bluetooth: " + error.message + "\n\nCatatan: Pastikan Bluetooth printer menyala, dipasangkan ke PC, dan menggunakan browser Chrome/Edge.");
                
                bluetoothDevice = null;
                printerCharacteristic = null;
            } finally {
                printBluetoothBtn.disabled = false;
                printBluetoothBtn.innerHTML = '<i class="fab fa-bluetooth"></i> Cetak Bluetooth';
            }
        });
    }

    // Reset printer state on disconnect
    function onDisconnected() {
        console.log("Bluetooth Printer Disconnected");
        bluetoothDevice = null;
        printerCharacteristic = null;
    }

    // Helper: write bytes in 20-byte chunks to avoid BLE GATT buffer overflow
    async function writeInChunks(characteristic, bytes) {
        const chunkSize = 20;
        for (let i = 0; i < bytes.length; i += chunkSize) {
            const chunk = bytes.slice(i, i + chunkSize);
            await characteristic.writeValue(chunk);
            await new Promise(resolve => setTimeout(resolve, 50));
        }
    }

    // Format transaction details into ESC/POS binary codes (for 58mm / 32 columns)
    function formatReceiptESC(sale, debt, dateStr) {
        const esc = [];
        
        const addBytes = (arr) => esc.push(...arr);
        const addText = (str) => {
            const encoder = new TextEncoder();
            esc.push(...encoder.encode(str));
        };
        
        // 1. Initialize
        addBytes([0x1B, 0x40]);
        
        // 2. Title (Centered, Bold, Double Size)
        addBytes([0x1B, 0x61, 0x01]); // center alignment
        addBytes([0x1B, 0x45, 0x01]); // bold on
        addBytes([0x1D, 0x21, 0x10]); // double height
        addText("SRC SUYANTO\n");
        
        // 3. Subtitle (Normal Size, Bold Off)
        addBytes([0x1D, 0x21, 0x00]); // normal size
        addBytes([0x1B, 0x45, 0x00]); // bold off
        addText("Aplikasi Kasir POS Toko\n");
        addText("somopuro, mutihan, rt 04/ rw02, gantiwarno, klaten\n");
        addText("--------------------------------\n"); // 32 chars wide
        
        // 4. Metadata
        addBytes([0x1B, 0x61, 0x00]); // left alignment
        addText(`No. Nota: ${sale.invoice_number}\n`);
        addText(`Tanggal : ${dateStr}\n`);
        let methodLabel = 'TUNAI';
        if (sale.payment_method === 'transfer') methodLabel = 'TRANSFER';
        if (sale.payment_method === 'qris') methodLabel = 'QRIS';
        if (sale.payment_method === 'debt') methodLabel = 'UTANG/KASBON';
        addText(`Metode  : ${methodLabel}\n`);
        if (sale.payment_method === 'debt' && debt) {
            addText(`Plg     : ${debt.customer_name.toUpperCase()}\n`);
            if (debt.due_date) {
                const due = new Date(debt.due_date).toLocaleDateString('id-ID');
                addText(`Jt Tempo: ${due}\n`);
            }
        }
        addText("--------------------------------\n");
        
        // 5. Items
        sale.sale_details.forEach(detail => {
            const prodName = detail.custom_name || (detail.product ? detail.product.name : 'Barang Dihapus');
            addText(`${prodName}\n`);
            
            const qtyPrice = `${detail.quantity} x ${formatRupiahRaw(parseFloat(detail.selling_price))}`;
            const subtotal = formatRupiahRaw(parseFloat(detail.subtotal));
            
            const spacesCount = 32 - qtyPrice.length - subtotal.length;
            const spaces = spacesCount > 0 ? ' '.repeat(spacesCount) : ' ';
            
            addText(`${qtyPrice}${spaces}${subtotal}\n`);
        });
        
        addText("--------------------------------\n");
        
        // 6. Totals
        if (parseFloat(sale.discount_amount) > 0) {
            const originalSubtotal = parseFloat(sale.total_price) + parseFloat(sale.discount_amount);
            const subLabel = "SUBTOTAL:";
            const subVal = formatRupiahRaw(originalSubtotal);
            const subSpaces = 32 - subLabel.length - subVal.length;
            addText(`${subLabel}${' '.repeat(subSpaces > 0 ? subSpaces : 1)}${subVal}\n`);

            const discLabel = "DISKON VOC:";
            const discVal = "-" + formatRupiahRaw(parseFloat(sale.discount_amount));
            const discSpaces = 32 - discLabel.length - discVal.length;
            addText(`${discLabel}${' '.repeat(discSpaces > 0 ? discSpaces : 1)}${discVal}\n`);
        }

        const totalLabel = "TOTAL BELANJA:";
        const totalVal = formatRupiahRaw(parseFloat(sale.total_price));
        const totalSpaces = 32 - totalLabel.length - totalVal.length;
        addBytes([0x1B, 0x45, 0x01]); // bold on
        addText(`${totalLabel}${' '.repeat(totalSpaces > 0 ? totalSpaces : 1)}${totalVal}\n`);
        addBytes([0x1B, 0x45, 0x00]); // bold off
        
        const payLabel = "BAYAR:";
        const payVal = formatRupiahRaw(parseFloat(sale.payment_amount));
        const paySpaces = 32 - payLabel.length - payVal.length;
        addText(`${payLabel}${' '.repeat(paySpaces > 0 ? paySpaces : 1)}${payVal}\n`);
        
        if (['cash', 'transfer', 'qris'].includes(sale.payment_method)) {
            const changeLabel = "KEMBALIAN:";
            const changeVal = formatRupiahRaw(parseFloat(sale.change_amount));
            const changeSpaces = 32 - changeLabel.length - changeVal.length;
            addBytes([0x1B, 0x45, 0x01]); // bold on
            addText(`${changeLabel}${' '.repeat(changeSpaces > 0 ? changeSpaces : 1)}${changeVal}\n`);
            addBytes([0x1B, 0x45, 0x00]); // bold off
        } else if (debt) {
            const remainingLabel = "SISA UTANG:";
            const remainingVal = formatRupiahRaw(parseFloat(debt.remaining_amount));
            const remainingSpaces = 32 - remainingLabel.length - remainingVal.length;
            addBytes([0x1B, 0x45, 0x01]); // bold on
            addText(`${remainingLabel}${' '.repeat(remainingSpaces > 0 ? remainingSpaces : 1)}${remainingVal}\n`);
            addBytes([0x1B, 0x45, 0x00]); // bold off
        }
        
        addText("--------------------------------\n");
        
        // 7. Footer
        addBytes([0x1B, 0x61, 0x01]); // center alignment
        addText("TERIMA KASIH\n");
        addText("ATAS KUNJUNGAN ANDA\n\n\n\n\n");
        
        // 8. Cut paper command
        addBytes([0x1D, 0x56, 0x41, 0x03]);
        
        return new Uint8Array(esc);
    }

    // Digital Transaction Modal & Cart Handlers
    const digitalModal = document.getElementById('digital-modal');
    
    window.openDigitalModal = function() {
        if (digitalModal) {
            digitalModal.classList.add('active');
            document.getElementById('digital-form').reset();
            updateDigitalPlaceholder();
        }
    };
    
    window.closeDigitalModal = function() {
        if (digitalModal) {
            digitalModal.classList.remove('active');
        }
    };
    
    window.updateDigitalPlaceholder = function() {
        const typeSelect = document.getElementById('digital-type');
        const targetLabel = document.getElementById('digital-target-label');
        const targetInput = document.getElementById('digital-target');
        const bankContainer = document.getElementById('digital-bank-name-container');
        const bankInput = document.getElementById('digital-bank-name');
        
        const type = typeSelect.value;
        
        if (type === 'bank') {
            bankContainer.style.display = 'block';
            bankInput.required = true;
            targetLabel.innerText = "Nomor Rekening Tujuan";
            targetInput.placeholder = "Contoh: 123456789012345 (BRI/Mandiri/dll.)";
        } else if (type === 'other') {
            bankContainer.style.display = 'block';
            bankInput.required = true;
            targetLabel.innerText = "Nomor Tujuan / ID Pelanggan";
            targetInput.placeholder = "Contoh: 32019283749 (Token/ID Pelanggan)";
        } else {
            bankContainer.style.display = 'none';
            bankInput.required = false;
            bankInput.value = '';
            targetLabel.innerText = "Nomor Handphone (HP) Tujuan";
            targetInput.placeholder = "Contoh: 081234567890";
        }
    };
    
    window.addDigitalToCart = function(e) {
        e.preventDefault();
        
        const typeSelect = document.getElementById('digital-type');
        const bankInput = document.getElementById('digital-bank-name');
        const targetInput = document.getElementById('digital-target');
        const amountInput = document.getElementById('digital-amount');
        const feeInput = document.getElementById('digital-fee');
        
        const typeVal = typeSelect.value;
        const targetVal = targetInput.value.trim();
        const nominal = parseFloat(amountInput.value) || 0;
        const adminFee = parseFloat(feeInput.value) || 0;
        
        if (nominal <= 0) {
            alert("Nominal top up/transfer harus lebih dari 0.");
            return;
        }
        
        // Build descriptive item name
        let serviceName = '';
        if (typeVal === 'bank') {
            const bankName = bankInput.value.trim() || 'Bank';
            serviceName = `[Trf ${bankName}] ${targetVal}`;
        } else if (typeVal === 'other') {
            const providerName = bankInput.value.trim() || 'Digital';
            serviceName = `[${providerName}] ${targetVal}`;
        } else {
            const walletName = typeSelect.options[typeSelect.selectedIndex].text.split(': ')[1] || 'E-Wallet';
            serviceName = `[TopUp ${walletName}] ${targetVal}`;
        }
        
        const itemSellingPrice = nominal + adminFee;
        
        // Add to cart as a custom digital item
        const tempId = 'digital_' + Date.now();
        cart.push({
            id: tempId,
            name: serviceName,
            price: itemSellingPrice,
            purchase_price: nominal,
            qty: 1,
            stock: 999999, // infinite stock for services
            is_custom: true
        });
        
        closeDigitalModal();
        renderCart();
    };

    window.applyVoucher = async function() {
        const input = document.getElementById('voucher-code-input');
        const msgDiv = document.getElementById('voucher-msg');
        
        if (!input || !msgDiv) return;
        
        const code = input.value.trim();
        if (!code) {
            msgDiv.style.color = 'var(--danger)';
            msgDiv.innerText = 'Silakan masukkan kode voucher.';
            msgDiv.style.display = 'block';
            return;
        }
        
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        if (subtotal === 0) {
            msgDiv.style.color = 'var(--danger)';
            msgDiv.innerText = 'Keranjang belanja masih kosong.';
            msgDiv.style.display = 'block';
            return;
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        try {
            const response = await fetch('/api/vouchers/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    code: code,
                    total_amount: subtotal
                })
            });
            
            const result = await response.json();
            if (result.success) {
                activeVoucher = result.data;
                msgDiv.style.color = 'var(--success)';
                msgDiv.innerText = result.message;
                msgDiv.style.display = 'block';
                renderCart();
            } else {
                activeVoucher = null;
                msgDiv.style.color = 'var(--danger)';
                msgDiv.innerText = result.message;
                msgDiv.style.display = 'block';
                renderCart();
            }
        } catch (error) {
            console.error('Validation error:', error);
            msgDiv.style.color = 'var(--danger)';
            msgDiv.innerText = 'Terjadi kesalahan sistem saat memvalidasi voucher.';
            msgDiv.style.display = 'block';
        }
    };
});
