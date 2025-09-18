@extends('demo.layouts.app')

@section('title', 'Mobile Login - Laravel Scan Login Demo')

@section('content')
<div class="mobile-login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="mobile-login-card">
                    <div class="login-header text-center">
                        <div class="logo-container">
                            <i class="fas fa-qrcode fa-3x"></i>
                        </div>
                        <h2 class="login-title">Mobile Login</h2>
                        <p class="login-subtitle">Complete your login on this device</p>
                    </div>
                    
                    <div class="login-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                {{ $errors->first() }}
                            </div>
                        @endif
                        
                        @if(session('error'))
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                {{ session('error') }}
                            </div>
                        @endif
                        
                        @if(session('success'))
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('demo.mobile.login') }}" id="mobile-login-form">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token ?? '' }}">
                            
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', 'demo@example.com') }}" 
                                       required 
                                       autocomplete="email" 
                                       autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="password-input-container">
                                    <input type="password" 
                                           class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           value="password"
                                           required 
                                           autocomplete="current-password">
                                    <button type="button" class="password-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me on this device
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="login-button">
                                <span class="button-text">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </span>
                                <span class="button-spinner d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Logging in...
                                </span>
                            </button>
                        </form>
                        
                        <div class="login-help text-center">
                            <p class="text-muted mb-2">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Demo credentials are pre-filled for testing
                                </small>
                            </p>
                            <div class="demo-credentials">
                                <small class="text-muted">
                                    <strong>Email:</strong> demo@example.com<br>
                                    <strong>Password:</strong> password
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="login-footer">
                        <div class="security-info text-center">
                            <div class="security-badges">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-shield-alt me-1"></i>Secure
                                </span>
                                <span class="badge bg-info me-2">
                                    <i class="fas fa-lock me-1"></i>Encrypted
                                </span>
                                <span class="badge bg-primary">
                                    <i class="fas fa-mobile-alt me-1"></i>Mobile Optimized
                                </span>
                            </div>
                            <p class="security-text mt-2">
                                <small class="text-muted">
                                    Your login is secured with industry-standard encryption
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Token Information -->
                @if(isset($token))
                <div class="token-info-card mt-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-key me-2"></i>Token Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="token-details">
                                <div class="token-item">
                                    <strong>Token:</strong>
                                    <code class="token-value">{{ Str::limit($token, 20) }}...</code>
                                </div>
                                <div class="token-item">
                                    <strong>Status:</strong>
                                    <span class="badge bg-warning" id="token-status">Pending</span>
                                </div>
                                <div class="token-item">
                                    <strong>Expires:</strong>
                                    <span id="token-expiry">5 minutes</span>
                                </div>
                            </div>
                            <div class="token-progress mt-3">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 100%" id="token-progress"></div>
                                </div>
                                <small class="text-muted mt-1 d-block">Time remaining</small>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Demo Instructions -->
                <div class="demo-instructions mt-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>How This Works
                            </h6>
                        </div>
                        <div class="card-body">
                            <ol class="instruction-list">
                                <li>You scanned a QR code from a desktop browser</li>
                                <li>Enter your credentials on this mobile device</li>
                                <li>The desktop browser will automatically log you in</li>
                                <li>No need to type credentials on desktop!</li>
                            </ol>
                            
                            <div class="demo-flow mt-3">
                                <div class="flow-step">
                                    <i class="fas fa-desktop"></i>
                                    <span>Desktop</span>
                                </div>
                                <div class="flow-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="flow-step">
                                    <i class="fas fa-qrcode"></i>
                                    <span>QR Code</span>
                                </div>
                                <div class="flow-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="flow-step active">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>Mobile</span>
                                </div>
                                <div class="flow-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="flow-step">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Success</span>
                                </div>
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
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.mobile-login-container {
    padding: 20px 0;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.mobile-login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px 30px;
}

.logo-container {
    margin-bottom: 20px;
}

.login-title {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.login-subtitle {
    opacity: 0.9;
    margin-bottom: 0;
}

.login-body {
    padding: 30px;
}

.form-control-lg {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 15px 20px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control-lg:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.password-input-container {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #495057;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 12px;
    padding: 15px 30px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.button-spinner {
    display: none;
}

.login-help {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.demo-credentials {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    margin-top: 10px;
}

.login-footer {
    background: #f8f9fa;
    padding: 20px 30px;
    border-top: 1px solid #e9ecef;
}

.security-badges .badge {
    font-size: 0.75rem;
    padding: 6px 10px;
}

.security-text {
    margin-bottom: 0;
}

.token-info-card .card {
    border: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.token-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.token-item:last-child {
    margin-bottom: 0;
}

.token-value {
    font-size: 0.85rem;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
}

.token-progress .progress {
    height: 6px;
    border-radius: 3px;
}

.demo-instructions .card {
    border: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.instruction-list {
    padding-left: 20px;
    margin-bottom: 0;
}

.instruction-list li {
    margin-bottom: 8px;
    color: #495057;
}

.demo-flow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.flow-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
    min-width: 60px;
    transition: all 0.3s ease;
}

.flow-step.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.flow-step i {
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.flow-step span {
    font-size: 0.75rem;
    font-weight: 600;
}

.flow-arrow {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Mobile optimizations */
@media (max-width: 576px) {
    .mobile-login-container {
        padding: 10px 0;
    }
    
    .login-header {
        padding: 30px 20px 20px;
    }
    
    .login-body {
        padding: 20px;
    }
    
    .login-footer {
        padding: 15px 20px;
    }
    
    .demo-flow {
        gap: 5px;
    }
    
    .flow-step {
        min-width: 50px;
        padding: 8px;
    }
    
    .flow-arrow {
        font-size: 0.8rem;
    }
}

/* Loading state */
.form-loading .button-text {
    display: none;
}

.form-loading .button-spinner {
    display: inline-block;
}

.form-loading .form-control {
    pointer-events: none;
    opacity: 0.6;
}

/* Success animation */
.login-success {
    animation: successPulse 0.6s ease-out;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
@endpush

@push('scripts')
<script>
let tokenExpiryTime = 5 * 60 * 1000; // 5 minutes in milliseconds
let tokenStartTime = Date.now();

document.addEventListener('DOMContentLoaded', function() {
    // Initialize token countdown if token exists
    @if(isset($token))
        startTokenCountdown();
    @endif
    
    // Handle form submission
    document.getElementById('mobile-login-form').addEventListener('submit', handleFormSubmission);
    
    // Auto-focus email field on mobile
    if (window.innerWidth <= 768) {
        setTimeout(() => {
            document.getElementById('email').focus();
        }, 500);
    }
});

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('password-toggle-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function handleFormSubmission(event) {
    const form = event.target;
    const submitButton = document.getElementById('login-button');
    
    // Add loading state
    form.classList.add('form-loading');
    submitButton.disabled = true;
    
    // Show loading spinner
    const buttonText = submitButton.querySelector('.button-text');
    const buttonSpinner = submitButton.querySelector('.button-spinner');
    
    buttonText.classList.add('d-none');
    buttonSpinner.classList.remove('d-none');
    
    // Update token status
    updateTokenStatus('Processing...', 'info');
    
    // The form will submit normally, but we've added visual feedback
}

function startTokenCountdown() {
    const progressBar = document.getElementById('token-progress');
    const expiryElement = document.getElementById('token-expiry');
    const statusElement = document.getElementById('token-status');
    
    function updateCountdown() {
        const elapsed = Date.now() - tokenStartTime;
        const remaining = Math.max(0, tokenExpiryTime - elapsed);
        const percentage = (remaining / tokenExpiryTime) * 100;
        
        // Update progress bar
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            
            // Change color based on remaining time
            if (percentage > 50) {
                progressBar.className = 'progress-bar bg-success';
            } else if (percentage > 25) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-danger';
            }
        }
        
        // Update expiry text
        if (expiryElement) {
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            expiryElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Update status
        if (remaining <= 0) {
            updateTokenStatus('Expired', 'danger');
            showToast('Token has expired. Please scan a new QR code.', 'warning');
            
            // Disable form
            const form = document.getElementById('mobile-login-form');
            const inputs = form.querySelectorAll('input, button');
            inputs.forEach(input => input.disabled = true);
            
            return; // Stop countdown
        }
        
        // Continue countdown
        setTimeout(updateCountdown, 1000);
    }
    
    updateCountdown();
}

function updateTokenStatus(status, type) {
    const statusElement = document.getElementById('token-status');
    if (statusElement) {
        statusElement.className = `badge bg-${type}`;
        statusElement.textContent = status;
    }
}

// Handle successful login (if redirected back with success)
@if(session('success'))
    document.addEventListener('DOMContentLoaded', function() {
        const card = document.querySelector('.mobile-login-card');
        if (card) {
            card.classList.add('login-success');
        }
        
        showToast('{{ session('success') }}', 'success');
        
        // Auto-redirect after success message
        setTimeout(() => {
            window.location.href = '{{ route('demo.dashboard') }}';
        }, 2000);
    });
@endif

// Handle form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('mobile-login-form');
    const inputs = form.querySelectorAll('input[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearValidation);
    });
    
    function validateField(event) {
        const field = event.target;
        const value = field.value.trim();
        
        if (!value) {
            field.classList.add('is-invalid');
            showFieldError(field, 'This field is required');
        } else if (field.type === 'email' && !isValidEmail(value)) {
            field.classList.add('is-invalid');
            showFieldError(field, 'Please enter a valid email address');
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            clearFieldError(field);
        }
    }
    
    function clearValidation(event) {
        const field = event.target;
        field.classList.remove('is-invalid', 'is-valid');
        clearFieldError(field);
    }
    
    function showFieldError(field, message) {
        clearFieldError(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    function clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});

// Add haptic feedback for mobile devices
function addHapticFeedback() {
    if ('vibrate' in navigator) {
        navigator.vibrate(50);
    }
}

// Add haptic feedback to button clicks
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('button, .btn');
    buttons.forEach(button => {
        button.addEventListener('click', addHapticFeedback);
    });
});
</script>
@endpush