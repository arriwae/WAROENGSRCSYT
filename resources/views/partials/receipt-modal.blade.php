<!-- Receipt Printing Modal Overlay -->
<div class="modal-overlay" id="receipt-modal">
    <div class="modal-content" style="max-width: 420px; border-color: var(--primary);">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-receipt" style="color:var(--primary); margin-right:6px;"></i> Nota Transaksi</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('receipt-modal').classList.remove('active')">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Struk print area mockup -->
            <div id="modal-receipt-content" style="background-color: rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:16px; font-family: monospace;">
                <!-- Filled via JS -->
            </div>
        </div>
        
        <div class="modal-footer" style="flex-direction:column; gap:10px;">
            <!-- Actions Row 1 -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; width:100%;">
                <button type="button" id="print-browser-btn" class="btn btn-secondary" style="border-radius: var(--radius-sm); font-size: 0.85rem; padding: 10px;">
                    <i class="fas fa-print"></i> Cetak Struk
                </button>
                <button type="button" id="print-bluetooth-btn" class="btn btn-primary" style="border-radius: var(--radius-sm); font-size: 0.85rem; padding: 10px; background:var(--grad-purple); box-shadow:0 4px 10px rgba(168,85,247,0.3);">
                    <i class="fab fa-bluetooth"></i> Cetak Bluetooth
                </button>
            </div>
            <!-- Actions Row 2 -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; width:100%;">
                <button type="button" id="save-receipt-btn" class="btn btn-success" style="border-radius: var(--radius-sm); font-size: 0.85rem; padding: 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-download"></i> Simpan Nota
                </button>
                <button type="button" id="share-wa-btn" class="btn" style="border-radius: var(--radius-sm); font-size: 0.85rem; padding: 10px; background-color: #25D366; color: white; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer;">
                    <i class="fab fa-whatsapp"></i> Bagikan WA
                </button>
            </div>
            <button type="button" class="btn btn-secondary" style="width:100%; border-radius: var(--radius-sm); font-size: 0.85rem; padding: 10px;" onclick="document.getElementById('receipt-modal').classList.remove('active')">
                Tutup Nota
            </button>
        </div>
    </div>
</div>
