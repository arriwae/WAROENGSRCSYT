// Report history Chart.js and Receipt Viewer Logic

document.addEventListener('DOMContentLoaded', () => {
    // 1. Chart.js initialization
    const ctx = document.getElementById('revenueTrendChart');
    if (ctx && typeof Chart !== 'undefined') {
        // Create gradients
        const ctx2d = ctx.getContext('2d');
        const gradient = ctx2d.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(52, 211, 153, 0.4)'); // emerald green translucent
        gradient.addColorStop(1, 'rgba(52, 211, 153, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Pemasukan (Omzet)',
                    data: chartValues,
                    borderColor: '#34d399',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#34d399'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#34d399',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 10
                            },
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000) + ' M';
                                } else if (value >= 1000) {
                                    return (value / 1000) + ' K';
                                }
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    // Modal action references
    const printBrowserBtn = document.getElementById('print-browser-btn');
    const saveReceiptBtn = document.getElementById('save-receipt-btn');
    const shareWaBtn = document.getElementById('share-wa-btn');
    
    // Receipt Printing handler
    if (printBrowserBtn) {
        printBrowserBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // Save Receipt Action
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
    if (shareWaBtn) {
        shareWaBtn.addEventListener('click', () => {
            if (!lastSaleData) return;
            shareToWhatsApp(lastSaleData.sale, lastSaleData.debt, lastSaleData.date_formatted);
        });
    }
});

// UI state for the report receipt modal
let lastSaleData = null;

function formatRupiah(number) {
    return 'Rp. ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
}

function formatRupiahRaw(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

// Download receipt as text file
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
        text += `${detail.quantity} x Rp ${formatRupiahRaw(detail.selling_price)}   Rp ${formatRupiahRaw(detail.subtotal)}\n`;
    });
    text += `--------------------------------\n`;
    if (parseFloat(sale.discount_amount) > 0) {
        const originalSubtotal = parseFloat(sale.total_price) + parseFloat(sale.discount_amount);
        text += `Subtotal     : Rp ${formatRupiahRaw(originalSubtotal)}\n`;
        text += `Diskon Voc   : -Rp ${formatRupiahRaw(sale.discount_amount)}\n`;
    }
    text += `TOTAL BAYAR  : Rp ${formatRupiahRaw(sale.total_price)}\n`;
    text += `BAYAR        : Rp ${formatRupiahRaw(sale.payment_amount)}\n`;
    if (sale.payment_method === 'debt' && debt) {
        text += `SISA UTANG   : Rp ${formatRupiahRaw(debt.remaining_amount)}\n`;
    } else {
        text += `KEMBALIAN    : Rp ${formatRupiahRaw(sale.change_amount)}\n`;
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

// Share receipt message via WhatsApp API
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
        msg += `- *${name}*\n  ${detail.quantity} x Rp ${formatRupiahRaw(detail.selling_price)} = *Rp ${formatRupiahRaw(detail.subtotal)}*\n`;
    });
    msg += `-----------------------------------------\n`;
    if (parseFloat(sale.discount_amount) > 0) {
        const originalSubtotal = parseFloat(sale.total_price) + parseFloat(sale.discount_amount);
        msg += `*Subtotal:* Rp ${formatRupiahRaw(originalSubtotal)}\n`;
        msg += `*Diskon Voucher:* -Rp ${formatRupiahRaw(sale.discount_amount)}\n`;
    }
    msg += `*TOTAL BAYAR: Rp ${formatRupiahRaw(sale.total_price)}*\n`;
    msg += `*BAYAR:* Rp ${formatRupiahRaw(sale.payment_amount)}\n`;
    
    if (sale.payment_method === 'debt' && debt) {
        msg += `*SISA UTANG: Rp ${formatRupiahRaw(debt.remaining_amount)}*\n`;
        if (debt.due_date) {
            msg += `*Jatuh Tempo:* ${new Date(debt.due_date).toLocaleDateString('id-ID')}\n`;
        }
    } else {
        msg += `*KEMBALIAN: Rp ${formatRupiahRaw(sale.change_amount)}*\n`;
    }
    msg += `-----------------------------------------\n`;
    msg += `_Terima kasih atas kunjungan Anda!_\n`;
    
    const encodedMsg = encodeURIComponent(msg);
    const url = `https://api.whatsapp.com/send?text=${encodedMsg}`;
    window.open(url, '_blank');
}

// Populate and show the modal receipt for past transactions
window.showPastReceipt = function(saleData) {
    lastSaleData = saleData;
    const sale = saleData.sale;
    const debt = saleData.debt;
    const dateStr = saleData.date_formatted;
    
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
                <span style="color:var(--text-secondary); font-size:0.8rem;">Nama Pelanggan:</span>
                <span style="font-weight:600; font-size:0.8rem;">${debt.customer_name}</span>
            </div>
            <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span style="color:var(--text-secondary); font-size:0.8rem;">Uang Muka (DP):</span>
                <span style="font-weight:600; font-size:0.8rem;">${formatRupiah(sale.payment_amount)}</span>
            </div>
            <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px; font-weight:700; color:var(--warning); font-size:0.8rem;">
                <span>Sisa Utang:</span>
                <span>${formatRupiah(debt.remaining_amount)}</span>
            </div>
            ${debt.due_date ? `
            <div class="receipt-info-row" style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:0.75rem; color:var(--text-secondary);">
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
    
    // Update hidden printout area
    updatePrintArea(sale, debt, dateStr);
    
    // Open modal
    document.getElementById('receipt-modal').classList.add('active');
}

// Update print element
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
