<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SRC SUYANTO</title>
    
    <!-- PWA Manifest & Theme Color -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#dc2626">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <div class="brand-logo" style="width: 50px; height: 50px; border-radius: 12px; font-size: 1.5rem;">
                <i class="fas fa-cash-register"></i>
            </div>
        </div>
        
        <div class="login-header">
            <h2>SRC SUYANTO</h2>
            <p>Silakan login untuk mengelola kasir & barang</p>
        </div>

        @if($errors->any())
            <div class="alert-strip" style="margin-bottom: 20px; padding: 12px 16px;">
                <i class="fas fa-exclamation-circle" style="color: var(--danger); font-size: 1.1rem;"></i>
                <div class="alert-strip-content" style="color: #fca5a5; font-size: 0.8rem; font-weight: 500;">
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        <form action="{{ url('/login') }}" id="login-form" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user" style="margin-right: 6px;"></i> Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" value="{{ old('username') }}" required autofocus autocomplete="username">
            </div>
            
            <div class="form-group" style="margin-bottom: 28px;">
                <label for="password"><i class="fas fa-lock" style="margin-right: 6px;"></i> Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            </div>
            
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="remember" id="remember" style="accent-color: var(--primary); cursor: pointer;">
                <label for="remember" style="font-size: 0.8rem; color: var(--text-secondary); cursor: pointer; user-select: none;">Ingat Sesi Saya</label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px 20px; border-radius: var(--radius-md);">
                MASUK KASIR <i class="fas fa-arrow-right" style="margin-left: 6px;"></i>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usernameInput = document.getElementById('username');
            const rememberCheckbox = document.getElementById('remember');
            const loginForm = document.getElementById('login-form');
            
            // 1. Check if there is a remembered username in localStorage
            const rememberedUsername = localStorage.getItem('remembered_username');
            if (rememberedUsername) {
                usernameInput.value = rememberedUsername;
                rememberCheckbox.checked = true;
            }
            
            // 2. Handle form submit to save or clear remembered username
            if (loginForm) {
                loginForm.addEventListener('submit', () => {
                    if (rememberCheckbox.checked) {
                        localStorage.setItem('remembered_username', usernameInput.value.trim());
                    } else {
                        localStorage.removeItem('remembered_username');
                    }
                });
            }
        });
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
</body>
</html>
