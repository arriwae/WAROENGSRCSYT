@extends('layouts.app')

@section('title', 'Kelola Barang')
@section('page-header', 'Kelola Stok & Harga Barang')

@section('content')
<div class="glass-card" style="padding: 24px;">
    <!-- Top toolbar: Search & Add button -->
    <div class="toolbar-container">
        <form action="{{ route('products.index') }}" method="GET" class="toolbar-form" style="max-width: 400px;">
            <div style="display: flex; gap: 12px; width: 100%;">
                <input type="text" name="search" class="form-control" placeholder="Cari nama barang atau barcode..." value="{{ request('search') }}" style="border-radius: var(--radius-sm); flex-grow: 1;">
                <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-sm); min-width: 80px;">
                    <i class="fas fa-search"></i> Cari
                </button>
                @if(request('search'))
                    <a href="{{ route('products.index') }}" class="btn btn-secondary" style="border-radius: var(--radius-sm);">Reset</a>
                @endif
            </div>
        </form>
        
        <button onclick="openAddModal()" class="btn btn-success" style="border-radius: var(--radius-sm);">
            <i class="fas fa-plus"></i> Tambah Barang Baru
        </button>
    </div>

    <!-- Products Table (Desktop) -->
    <div class="table-container desktop-view">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Kode Barcode / SKU</th>
                    <th>Nama Barang</th>
                    <th style="text-align: center;">Satuan</th>
                    <th style="text-align: right;">Harga Beli (Modal)</th>
                    <th style="text-align: right;">Harga Jual</th>
                    <th style="text-align: center;">Stok</th>
                    <th>Tanggal Kedaluwarsa</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $item)
                    <tr>
                        <td>
                            <img src="{{ $item->image_url }}" class="product-thumb" alt="{{ $item->name }}">
                        </td>
                        <td style="font-family: monospace; font-weight: bold;">
                            {{ $item->sku ?? '-' }}
                        </td>
                        <td style="font-weight: 600; font-size: 0.95rem;">
                            {{ $item->name }}
                        </td>
                        <td style="text-align: center;">
                            <span class="badge info" style="text-transform: uppercase; font-size: 0.8rem; background: rgba(37,99,235,0.1); color: var(--info); border: 1px solid rgba(37,99,235,0.2);">
                                {{ $item->unit ?? 'pcs' }}
                            </span>
                        </td>
                        <td style="text-align: right; color: var(--text-secondary);">
                            Rp. {{ number_format($item->purchase_price, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--primary);">
                            Rp. {{ number_format($item->selling_price, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            <span class="badge {{ $item->isLowStock() ? 'danger' : 'success' }}" style="font-size: 0.85rem; padding: 4px 12px;">
                                {{ $item->stock }}
                            </span>
                        </td>
                        <td>
                            {{ $item->expiry_date ? $item->expiry_date->format('d-m-Y') : '-' }}
                        </td>
                        <td>
                            @if($item->isExpired())
                                <span class="badge danger"><i class="fas fa-calendar-times" style="margin-right: 4px;"></i> Kedaluwarsa</span>
                            @elseif($item->isNearExpiry())
                                <span class="badge warning"><i class="fas fa-hourglass-half" style="margin-right: 4px;"></i> Segera Kadal.</span>
                            @elseif($item->isLowStock())
                                <span class="badge danger"><i class="fas fa-exclamation-triangle" style="margin-right: 4px;"></i> Stok Hampir Habis</span>
                            @else
                                <span class="badge success">Kondisi Baik</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 8px;">
                                <button onclick="openEditModal('{{ json_encode($item) }}')" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: var(--radius-sm); border-color: rgba(99,102,241,0.3); color: #818cf8;" title="Ubah">
                                    <i class="fas fa-edit"></i> Ubah
                                </button>
                                <form action="{{ route('products.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; border-radius: var(--radius-sm);" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            <i class="fas fa-box-open" style="font-size: 3rem; opacity: 0.2; display: block; margin-bottom: 16px;"></i>
                            Tidak ada data barang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Products Mobile View (Card List) -->
    <div class="mobile-view">
        @forelse($products as $item)
            <div class="mobile-list-card" style="flex-direction: row; gap: 12px; align-items: flex-start; @if($item->isExpired()) border-left: 4px solid var(--danger); @elseif($item->isLowStock()) border-left: 4px solid var(--danger); @else border-left: 4px solid var(--success); @endif">
                <img src="{{ $item->image_url }}" style="width: 70px; height: 70px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0; background: rgba(0,0,0,0.1);">
                <div style="flex-grow: 1; display: flex; flex-direction: column; gap: 4px; min-width: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 6px;">
                        <span class="mobile-card-title" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->name }}</span>
                        <span class="badge info" style="text-transform: uppercase; font-size: 0.7rem; background: rgba(37,99,235,0.1); color: var(--info); border: 1px solid rgba(37,99,235,0.2); padding: 2px 6px; flex-shrink: 0;">
                            {{ $item->unit ?? 'pcs' }}
                        </span>
                    </div>
                    
                    <span style="font-family: monospace; font-size: 0.75rem; color: var(--text-secondary); font-weight: bold;">
                        SKU: {{ $item->sku ?? '-' }}
                    </span>
                    
                    <div class="mobile-card-row" style="margin-top: 2px;">
                        <span class="mobile-card-subtitle">Harga Jual / Beli:</span>
                        <span>
                            <strong style="color: var(--primary); font-size: 0.85rem;">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</strong>
                            <span style="font-size: 0.75rem; color: var(--text-secondary);">/ Rp {{ number_format($item->purchase_price, 0, ',', '.') }}</span>
                        </span>
                    </div>
                    
                    <div class="mobile-card-row">
                        <span class="mobile-card-subtitle">Stok / Kadaluwarsa:</span>
                        <span>
                            <span class="badge {{ $item->isLowStock() ? 'danger' : 'success' }}" style="font-size: 0.75rem; padding: 2px 8px;">
                                {{ $item->stock }}
                            </span>
                            <span style="font-size: 0.75rem; color: var(--text-secondary); margin-left: 6px;">
                                {{ $item->expiry_date ? $item->expiry_date->format('d-m-Y') : '-' }}
                            </span>
                        </span>
                    </div>
                    
                    <div class="mobile-card-actions">
                        <button onclick="openEditModal('{{ json_encode($item) }}')" class="btn btn-secondary" style="border-color: rgba(99,102,241,0.3); color: #818cf8;">
                            <i class="fas fa-edit"></i> Ubah
                        </button>
                        <form action="{{ route('products.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" style="display: contents;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="color: #ef4444; border-color: rgba(239,68,68,0.2);">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-card" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                <i class="fas fa-box-open" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 12px;"></i>
                Tidak ada data barang ditemukan.
            </div>
        @endforelse
    </div>

    <!-- Pagination Links -->
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $products->links() }}
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal-overlay" id="product-modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-action-title">Tambah Barang Baru</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form action="" method="POST" id="product-form" enctype="multipart/form-data">
            @csrf
            <div id="form-method"></div>
            <input type="hidden" name="id" id="product-id">
            
            <div class="modal-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Column 1 -->
                <div>
                    <div class="form-group">
                        <label for="prod-name">Nama Barang <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="name" id="prod-name" class="form-control" placeholder="Contoh: Indomie Goreng" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prod-sku">Kode Barcode / SKU</label>
                        <input type="text" name="sku" id="prod-sku" class="form-control" placeholder="Contoh: 8998866200225">
                    </div>
                    
                    <div class="form-group">
                        <label for="prod-stock">Stok Awal <span style="color:var(--danger)">*</span></label>
                        <input type="number" name="stock" id="prod-stock" class="form-control" min="0" placeholder="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prod-unit">Satuan <span style="color:var(--danger)">*</span></label>
                        <select name="unit" id="prod-unit" class="form-control" required style="background-color:#ffffff; color:var(--text-primary);">
                            <option value="pcs">Pcs</option>
                            <option value="renteng">Renteng</option>
                            <option value="karton">Karton</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="prod-expiry-date">Tanggal Kedaluwarsa</label>
                        <input type="date" name="expiry_date" id="prod-expiry-date" class="form-control">
                    </div>
                </div>

                <!-- Column 2 -->
                <div>
                    <div class="form-group">
                        <label for="prod-purchase-price">Harga Beli (Modal) <span style="color:var(--danger)">*</span></label>
                        <input type="number" name="purchase_price" id="prod-purchase-price" class="form-control" min="0" placeholder="Rp. 0" required>
                    </div>

                    <div class="form-group">
                        <label for="prod-selling-price">Harga Jual <span style="color:var(--danger)">*</span></label>
                        <input type="number" name="selling_price" id="prod-selling-price" class="form-control" min="0" placeholder="Rp. 0" required>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:var(--text-secondary);">Gambar Produk</label>
                        
                        <!-- Choice Switcher Tabs -->
                        <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                            <button type="button" id="tab-gallery" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: var(--radius-sm); border: 1px solid var(--primary); background-color: rgba(99, 102, 241, 0.1); color: white;" onclick="switchImageSource('gallery')">
                                <i class="fas fa-images"></i> Pilih Galeri
                            </button>
                            <button type="button" id="tab-camera" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: var(--radius-sm);" onclick="switchImageSource('camera')">
                                <i class="fas fa-camera"></i> Ambil Foto (Kamera)
                            </button>
                        </div>
                        
                        <!-- Gallery Upload Input -->
                        <div id="gallery-input-container">
                            <input type="file" name="image" id="prod-image" class="form-control" accept="image/*" onchange="previewImage(this)">
                        </div>
                        
                        <!-- Camera Upload Input -->
                        <div id="camera-input-container" style="display: none; flex-direction: column; gap: 8px;">
                            <button type="button" id="btn-start-camera" class="btn btn-secondary" style="width: 100%; border-radius: var(--radius-sm);" onclick="startCamera()">
                                <i class="fas fa-video"></i> Aktifkan Kamera
                            </button>
                            
                            <div id="camera-feed-container" style="display: none; flex-direction: column; gap: 8px; align-items: center; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <video id="camera-video" autoplay playsinline style="width: 100%; max-height: 200px; border-radius: 6px; background-color: black;"></video>
                                <button type="button" id="btn-capture-photo" class="btn btn-success" style="width: 100%; border-radius: var(--radius-sm);" onclick="capturePhoto()">
                                    <i class="fas fa-circle"></i> Jepret Foto
                                </button>
                            </div>
                            
                            <input type="hidden" name="image_base64" id="prod-image-base64">
                            <canvas id="camera-canvas" style="display: none;"></canvas>
                        </div>
                        
                        <!-- Image Preview Container -->
                        <div id="preview-container" class="image-upload-preview" style="display: none; width: 120px; height: 120px; margin-top: 10px;">
                            <img src="#" id="preview-img" alt="Pratinjau Gambar">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('product-modal');
    let cameraStream = null;
    
    function closeModal() {
        stopCamera();
        modal.classList.remove('active');
    }
    
    function openAddModal() {
        document.getElementById('modal-action-title').textContent = 'Tambah Barang Baru';
        document.getElementById('product-form').action = "{{ route('products.store') }}";
        document.getElementById('form-method').innerHTML = '';
        
        // Reset inputs
        document.getElementById('product-id').value = '';
        document.getElementById('prod-sku').value = '';
        document.getElementById('prod-name').value = '';
        document.getElementById('prod-purchase-price').value = '';
        document.getElementById('prod-selling-price').value = '';
        document.getElementById('prod-stock').value = '0';
        document.getElementById('prod-expiry-date').value = '';
        document.getElementById('prod-unit').value = 'pcs';
        
        // Reset image source state
        document.getElementById('prod-image').value = '';
        document.getElementById('prod-image-base64').value = '';
        document.getElementById('btn-start-camera').innerHTML = '<i class="fas fa-video"></i> Aktifkan Kamera';
        
        switchImageSource('gallery');
        
        // Reset preview
        document.getElementById('preview-img').src = '#';
        document.getElementById('preview-container').style.display = 'none';
        
        modal.classList.add('active');
    }
    
    function openEditModal(productJson) {
        const product = JSON.parse(productJson);
        document.getElementById('modal-action-title').textContent = 'Ubah Data Barang';
        document.getElementById('product-form').action = `/products/${product.id}`;
        document.getElementById('form-method').innerHTML = '@method("PUT")';
        
        // Set values
        document.getElementById('product-id').value = product.id;
        document.getElementById('prod-sku').value = product.sku || '';
        document.getElementById('prod-name').value = product.name;
        document.getElementById('prod-purchase-price').value = Math.round(product.purchase_price);
        document.getElementById('prod-selling-price').value = Math.round(product.selling_price);
        document.getElementById('prod-stock').value = product.stock;
        document.getElementById('prod-unit').value = product.unit || 'pcs';
        
        if (product.expiry_date) {
            const expDate = new Date(product.expiry_date).toISOString().split('T')[0];
            document.getElementById('prod-expiry-date').value = expDate;
        } else {
            document.getElementById('prod-expiry-date').value = '';
        }
        
        // Reset image inputs
        document.getElementById('prod-image').value = '';
        document.getElementById('prod-image-base64').value = '';
        document.getElementById('btn-start-camera').innerHTML = '<i class="fas fa-video"></i> Aktifkan Kamera';
        
        switchImageSource('gallery');
        
        // Set preview
        if (product.image) {
            document.getElementById('preview-img').src = `/storage/products/${product.image}`;
            document.getElementById('preview-container').style.display = 'flex';
        } else {
            document.getElementById('preview-img').src = '#';
            document.getElementById('preview-container').style.display = 'none';
        }
        
        modal.classList.add('active');
    }
    
    function previewImage(input) {
        const previewContainer = document.getElementById('preview-container');
        const previewImg = document.getElementById('preview-img');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.style.display = 'flex';
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            // Keep current model image if updating, otherwise hide
            const productId = document.getElementById('product-id').value;
            const hasBase64 = document.getElementById('prod-image-base64').value;
            
            if (hasBase64) {
                previewImg.src = hasBase64;
                previewContainer.style.display = 'flex';
            } else if (!productId) {
                previewImg.src = '#';
                previewContainer.style.display = 'none';
            }
        }
    }

    // Switch between Gallery and Camera source
    function switchImageSource(source) {
        const tabGallery = document.getElementById('tab-gallery');
        const tabCamera = document.getElementById('tab-camera');
        const galleryContainer = document.getElementById('gallery-input-container');
        const cameraContainer = document.getElementById('camera-input-container');
        
        stopCamera();
        
        if (source === 'gallery') {
            tabGallery.style.backgroundColor = 'rgba(99, 102, 241, 0.1)';
            tabGallery.style.borderColor = 'var(--primary)';
            tabGallery.style.color = 'white';
            
            tabCamera.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
            tabCamera.style.borderColor = 'var(--border-color)';
            tabCamera.style.color = 'var(--text-secondary)';
            
            galleryContainer.style.display = 'block';
            cameraContainer.style.display = 'none';
            
            // Clear base64 data
            document.getElementById('prod-image-base64').value = '';
            
            // Refresh preview
            previewImage(document.getElementById('prod-image'));
        } else {
            tabCamera.style.backgroundColor = 'rgba(99, 102, 241, 0.1)';
            tabCamera.style.borderColor = 'var(--primary)';
            tabCamera.style.color = 'white';
            
            tabGallery.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
            tabGallery.style.borderColor = 'var(--border-color)';
            tabGallery.style.color = 'var(--text-secondary)';
            
            galleryContainer.style.display = 'none';
            cameraContainer.style.display = 'flex';
            
            // Clear gallery file
            document.getElementById('prod-image').value = '';
            
            // Show preview if base64 is already captured, otherwise hide
            const base64Value = document.getElementById('prod-image-base64').value;
            if (base64Value) {
                document.getElementById('preview-img').src = base64Value;
                document.getElementById('preview-container').style.display = 'flex';
            } else {
                // If editing and has dynamic product image, it was set in openEditModal
                const productId = document.getElementById('product-id').value;
                if (!productId) {
                    document.getElementById('preview-img').src = '#';
                    document.getElementById('preview-container').style.display = 'none';
                }
            }
        }
    }

    // Start video camera stream
    async function startCamera() {
        const video = document.getElementById('camera-video');
        const feedContainer = document.getElementById('camera-feed-container');
        const startBtn = document.getElementById('btn-start-camera');
        
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }, // Back camera on phones
                audio: false
            });
            video.srcObject = cameraStream;
            feedContainer.style.display = 'flex';
            startBtn.style.display = 'none';
        } catch (err) {
            console.error("Camera access failed:", err);
            alert("Gagal mengakses kamera. Silakan periksa izin akses kamera di browser Anda.");
        }
    }

    // Stop video camera stream
    function stopCamera() {
        const video = document.getElementById('camera-video');
        const feedContainer = document.getElementById('camera-feed-container');
        const startBtn = document.getElementById('btn-start-camera');
        
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        if (video) video.srcObject = null;
        if (feedContainer) feedContainer.style.display = 'none';
        if (startBtn) startBtn.style.display = 'block';
    }

    // Capture photo from video stream
    function capturePhoto() {
        const video = document.getElementById('camera-video');
        const canvas = document.getElementById('camera-canvas');
        const base64Input = document.getElementById('prod-image-base64');
        const previewImg = document.getElementById('preview-img');
        const previewContainer = document.getElementById('preview-container');
        
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Get JPEG Base64
            const base64Data = canvas.toDataURL('image/jpeg', 0.85); // Compress quality slightly
            base64Input.value = base64Data;
            
            // Show preview
            previewImg.src = base64Data;
            previewContainer.style.display = 'flex';
            
            // Stop stream
            stopCamera();
            document.getElementById('btn-start-camera').innerHTML = '<i class="fas fa-sync"></i> Ambil Foto Ulang';
        }
    }
    
    // Close modal when clicking outside content area
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
</script>
@endsection
