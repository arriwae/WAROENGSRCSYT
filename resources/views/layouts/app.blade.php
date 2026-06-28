<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SRC SUYANTO') - Aplikasi Kasir Toko</title>
    
    <!-- PWA Manifest & Theme Color -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#dc2626">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <!-- Chart.js & html2canvas library CDNs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    @yield('styles')
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="brand-section">
                <div class="brand-logo">
                    <i class="fas fa-cash-register"></i>
                </div>
                <span class="brand-name">SRC SUYANTO</span>
            </div>
            
            <ul class="nav-links">
                <li class="nav-item {{ Request::routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-chart-line"></i>
                        <span>Beranda Utama</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::routeIs('cashier.*') ? 'active' : '' }}">
                    <a href="{{ route('cashier.index') }}">
                        <i class="fas fa-calculator"></i>
                        <span>Mesin Kasir</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::routeIs('history.*') ? 'active' : '' }}">
                    <a href="{{ route('history.index') }}">
                        <i class="fas fa-history"></i>
                        <span>Riwayat & Omzet</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::routeIs('finance.*') ? 'active' : '' }}">
                    <a href="{{ route('finance.index') }}">
                        <i class="fas fa-balance-scale"></i>
                        <span>Laporan Keuangan</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::routeIs('products.index') ? 'active' : '' }}">
                    <a href="{{ route('products.index') }}">
                        <i class="fas fa-box-open"></i>
                        <span>Kelola Barang</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::routeIs('products.restock') ? 'active' : '' }}">
                    <a href="{{ route('products.restock') }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Daftar Kulakan</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::routeIs('debts.*') ? 'active' : '' }}">
                    <a href="{{ route('debts.index') }}">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Catatan Utang</span>
                    </a>
                </li>
                <li class="nav-item {{ Request::routeIs('vouchers.*') ? 'active' : '' }}">
                    <a href="{{ route('vouchers.index') }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Kelola Voucher</span>
                    </a>
                </li>
            </ul>
            
            <div class="user-profile-section">
                <div class="user-info">
                    <div class="user-avatar">
                        {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <span class="user-name">{{ Auth::user()->name ?? 'Administrator' }}</span>
                        <span class="user-role">Pemilik / Kasir</span>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" id="logout-form" style="display: none;">
                    @csrf
                </form>
                <button class="logout-btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" title="Keluar">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Topbar Header -->
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <button type="button" class="sidebar-toggle no-print" onclick="toggleSidebar(event)">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        <h1>@yield('page-header', 'Beranda')</h1>
                    </div>
                </div>
                
                <div class="topbar-actions">
                    <!-- Dynamic Indicators -->
                    @php
                        $lowStockCount = \App\Models\Product::where('stock', '<', 10)->count();
                        $expiredCount = \App\Models\Product::whereNotNull('expiry_date')->where('expiry_date', '<', \Carbon\Carbon::today())->count();
                        $activeDebtCount = \App\Models\Debt::whereIn('status', ['unpaid', 'partially_paid'])->count();
                    @endphp
                    
                    @if($expiredCount > 0)
                        <a href="{{ route('dashboard') }}" class="badge danger" title="Ada {{ $expiredCount }} produk kedaluwarsa!" style="text-decoration:none;">
                            <i class="fas fa-calendar-times" style="margin-right: 5px;"></i> {{ $expiredCount }} Kedaluwarsa
                        </a>
                    @endif

                    @if($lowStockCount > 0)
                        <a href="{{ route('products.index') }}" class="badge warning" title="Ada {{ $lowStockCount }} produk stok menipis!" style="text-decoration:none;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i> {{ $lowStockCount }} Stok Hampir Habis
                        </a>
                    @endif
                    
                    @if($activeDebtCount > 0)
                        <a href="{{ route('debts.index') }}" class="badge info" title="Ada {{ $activeDebtCount }} catatan utang aktif!" style="text-decoration:none;">
                            <i class="fas fa-file-invoice-dollar" style="margin-right: 5px;"></i> {{ $activeDebtCount }} Utang Aktif
                        </a>
                    @endif
                    
                    <span style="font-size: 0.85rem; color: var(--text-secondary); border-left: 1px solid var(--border-color); padding-left: 16px;">
                        <i class="far fa-clock" style="margin-right: 5px;"></i> {{ \Carbon\Carbon::now()->format('d M Y') }}
                    </span>
                </div>
            </header>

            <!-- Main Yield Content -->
            <main class="content-body">
                @if(session('success'))
                    <div class="alert-strip" style="background:rgba(16,185,129,0.05); border-color:rgba(16,185,129,0.2); margin-bottom:24px;">
                        <i class="fas fa-check-circle" style="color:var(--success);"></i>
                        <div class="alert-strip-content" style="color:var(--success); font-weight:600;">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if($errors->has('error'))
                    <div class="alert-strip" style="background:rgba(239,68,68,0.05); border-color:rgba(239,68,68,0.2); margin-bottom:24px;">
                        <i class="fas fa-times-circle" style="color:var(--danger);"></i>
                        <div class="alert-strip-content" style="color:var(--danger); font-weight:600;">
                            {{ $errors->first('error') }}
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    
    @yield('print-area')
    
    <script>
        function toggleSidebar(e) {
            if (e) e.stopPropagation();
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
            
            // Add or remove backdrop
            let backdrop = document.querySelector('.sidebar-backdrop');
            if (sidebar.classList.contains('active')) {
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'sidebar-backdrop no-print';
                    backdrop.style.cssText = 'position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:90;';
                    backdrop.onclick = () => {
                        sidebar.classList.remove('active');
                        backdrop.remove();
                    };
                    document.body.appendChild(backdrop);
                }
            } else {
                if (backdrop) backdrop.remove();
            }
        }
    </script>
    
    <script>
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered successfully!', reg.scope))
                    .catch(err => console.log('Service Worker registration failed:', err));
            });
        }
    </script>
    
    @yield('scripts')
</body>
</html>
