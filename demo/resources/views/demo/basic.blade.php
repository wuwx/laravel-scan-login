@extends('demo.layouts.app')

@section('title', 'Basic Demo - Laravel Scan Login')

@section('content')
<div class="demo-header">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('demo.index') }}">Demo Home</a></li>
                        <li class="breadcrumb-item active">Basic Implementation</li>
                    </ol>
                </nav>
                <h1 class="display-4 fw-bold mb-3">Basic Scan Login Demo</h1>
                <p class="lead">Experience the standard scan login flow with default styling and behavior.</p>
            </div>
        </div>
    </div>
</div>

<div class="demo-content py-5">
    <div class="container">
        <div class="row">
            <!-- Desktop Login Section -->
            <div class="col-lg-8">
                <div class="login-section">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-desktop me-2"></i>Desktop Login
                            </h3>
                        </div>
                        <div class="card-body">
                            @auth
                                <div class="alert alert-success">
                                    <h4><i class="fas fa-check-circle me-2"></i>Login Successful!</h4>
                                    <p class="mb-3">Welcome back, {{ auth()->user()->name }}!</p>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('demo.dashboard') }}" class="btn btn-success">
                                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                        </a>
                                        <a href="{{ route('demo.logout') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="row">
                                    <!-- Traditional Login -->
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Traditional Login</h5>
                                        <form method="POST" action="{{ route('login') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="demo@example.com" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input type="password" class="form-control" id="password" name="password" 
                                                       value="password" required>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                                <label class="form-check-label" for="remember">Remember me</label>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-sign-in-alt me-2"></i>Login
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Scan Login -->
                                    <div class="col-md-6">
                                        <div class="scan-login-section">
                                            <h5 class="mb-3">Scan to Login</h5>
                                            <p class="text-muted mb-3">
                                                Scan the QR code with your mobile device to login quickly and securely.
                                            </p>
                                            
                                            <div class="qr-code-container">
                                                <div class="qr-code-loading">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Generating QR code...</span>
                                                    </div>
                                                    <p class="mt-2">Generating QR code...</p>
                                                </div>
                                            </div>
                                            
                                            <div class="qr-code-instructions mt-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Use test credentials: <strong>demo@example.com</strong> / <strong>password</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Demo Information Sidebar -->
            <div class="col-lg-4">
                <div class="demo-info">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Demo Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>How to Test:</h6>
                            <ol class="demo-steps">
                                <li>Wait for the QR code to generate</li>
                                <li>Scan the QR code with your mobile device</li>
                                <li>Enter the test credentials on mobile:
                                    <ul>
                                        <li><strong>Email:</strong> demo@example.com</li>
                                        <li><strong>Password:</strong> password</li>
                                    </ul>
                                </li>
                                <li>Watch this page automatically log you in!</li>
                            </ol>
                            
                            <hr>
                            
                            <h6>Features Demonstrated:</h6>
                            <ul class="feature-list">
                                <li><i class="fas fa-check text-success me-2"></i>QR code generation</li>
                                <li><i class="fas fa-check text-success me-2"></i>Real-time status polling</li>
                                <li><i class="fas fa-check text-success me-2"></i>Mobile login interface</li>
                                <li><i class="fas fa-check text-success me-2"></i>Automatic desktop login</li>
                                <li><i class="fas fa-check text-success me-2"></i>Token expiration handling</li>
                            </ul>
                            
                            <hr>
                            
                            <div class="demo-status">
                                <h6>Current Status:</h6>
                                <div class="status-indicator" id="demo-status">
                                    <span class="badge bg-secondary">Initializing...</span>
                                </div>
                                <div class="polling-info mt-2">
                                    <small class="text-muted">
                                        Polling count: <span id="polling-count">0</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i>Code Example
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">Basic Blade template integration:</p>
                            <pre><code class="language-blade">{{-- Include QR code component --}}
@include('scan-login::qr-code')

{{-- Include required assets --}}
@push('scripts')
&lt;script src="{{ asset('vendor/scan-login/js/qr-code-component.js') }}"&gt;&lt;/script&gt;
@endpush</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demo Controls -->
<div class="demo-controls">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Demo Controls</h6>
                                <small class="text-muted">Test different scenarios</small>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary" onclick="refreshQrCode()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh QR
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="simulateError('token_expired')">
                                    <i class="fas fa-clock me-1"></i>Simulate Expiry
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="simulateError('network_error')">
                                    <i class="fas fa-wifi me-1"></i>Simulate Error
                                </button>
                                <a href="{{ route('demo.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Demos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendor/scan-login/css/qr-code-component.css') }}" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
<style>
.demo-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0 40px;
}

.demo-header .breadcrumb {
    background: none;
    padding: 0;
    margin-bottom: 20px;
}

.demo-header .breadcrumb-item a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
}

.demo-header .breadcrumb-item.active {
    color: white;
}

.scan-login-section {
    text-align: center;
    padding: 20px;
    border-left: 1px solid #dee2e6;
}

.qr-code-container {
    min-height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
}

.qr-code-loading {
    text-align: center;
    color: #6c757d;
}

.demo-steps {
    padding-left: 20px;
}

.demo-steps li {
    margin-bottom: 10px;
}

.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    margin-bottom: 8px;
}

.demo-controls {
    background: #f8f9fa;
    padding: 20px 0;
    border-top: 1px solid #dee2e6;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.polling-info {
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .scan-login-section {
        border-left: none;
        border-top: 1px solid #dee2e6;
        margin-top: 30px;
        padding-top: 30px;
    }
    
    .demo-controls .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .demo-controls .btn-group .btn {
        margin-bottom: 5px;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/scan-login/js/qr-code-component.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/autoloader/prism-autoloader.min.js"></script>
<script>
let scanLogin = null;
let pollingCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    @guest
        initializeScanLogin();
    @endguest
});

function initializeScanLogin() {
    updateStatus('Generating QR code...', 'secondary');
    
    scanLogin = new QrCodeComponent({
        container: '.qr-code-container',
        generateUrl: '/demo/generate-qr',
        statusUrl: '/demo/status',
        pollingInterval: 3000,
        onQrGenerated: function(data) {
            updateStatus('Waiting for mobile scan...', 'primary');
            console.log('QR code generated:', data);
        },
        onPolling: function() {
            pollingCount++;
            document.getElementById('polling-count').textContent = pollingCount;
        },
        onLoginSuccess: function(data) {
            updateStatus('Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        },
        onTokenExpired: function() {
            updateStatus('QR code expired, refreshing...', 'warning');
            pollingCount = 0;
        },
        onError: function(error) {
            updateStatus('Error: ' + error.message, 'danger');
            console.error('Scan login error:', error);
        }
    });
}

function updateStatus(message, type) {
    const statusElement = document.getElementById('demo-status');
    if (statusElement) {
        statusElement.innerHTML = `<span class="badge bg-${type}">${message}</span>`;
    }
}

function refreshQrCode() {
    if (scanLogin) {
        pollingCount = 0;
        document.getElementById('polling-count').textContent = pollingCount;
        updateStatus('Refreshing QR code...', 'secondary');
        scanLogin.refresh();
    }
}

async function simulateError(errorType) {
    try {
        updateStatus(`Simulating ${errorType.replace('_', ' ')}...`, 'warning');
        
        const response = await fetch('/demo/simulate-error', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ type: errorType })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            updateStatus(`Simulated Error: ${data.error.message}`, 'danger');
            
            // Auto-recover after 3 seconds
            setTimeout(() => {
                updateStatus('Recovering from simulated error...', 'info');
                refreshQrCode();
            }, 3000);
        }
    } catch (error) {
        console.error('Failed to simulate error:', error);
        updateStatus('Failed to simulate error', 'danger');
    }
}
</script>
@endpush