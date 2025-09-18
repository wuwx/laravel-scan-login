@extends('demo.layouts.app')

@section('title', 'Laravel Scan Login Demo')

@section('content')
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold text-primary mb-4">
                    Laravel Scan Login
                </h1>
                <p class="lead mb-4">
                    Experience seamless QR code-based authentication. Scan with your mobile device, 
                    login on mobile, and automatically authenticate on desktop.
                </p>
                <div class="d-flex gap-3 mb-4">
                    <a href="{{ route('demo.basic') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-qrcode me-2"></i>Try Demo
                    </a>
                    <a href="https://github.com/wuwx/laravel-scan-login" class="btn btn-outline-secondary btn-lg">
                        <i class="fab fa-github me-2"></i>View on GitHub
                    </a>
                </div>
                <div class="demo-stats">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="stat-number" id="total-demos">0</div>
                                <div class="stat-label">Demos Run</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="stat-number" id="successful-logins">0</div>
                                <div class="stat-label">Successful Logins</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="stat-number" id="qr-generated">0</div>
                                <div class="stat-label">QR Codes Generated</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="demo-preview">
                    <div class="device-mockup">
                        <div class="desktop-mockup">
                            <div class="screen">
                                <div class="qr-placeholder">
                                    <i class="fas fa-qrcode"></i>
                                    <p>QR Code appears here</p>
                                </div>
                            </div>
                        </div>
                        <div class="mobile-mockup">
                            <div class="screen">
                                <div class="login-form-preview">
                                    <div class="form-field"></div>
                                    <div class="form-field"></div>
                                    <div class="form-button"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="features-section py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">How It Works</h2>
                <p class="lead text-muted">Simple, secure, and seamless authentication in three steps</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="feature-card text-center">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h4>1. Generate QR Code</h4>
                    <p>A unique QR code is generated on your desktop browser with a secure, time-limited token.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="feature-card text-center">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4>2. Scan & Login</h4>
                    <p>Scan the QR code with your mobile device and enter your credentials on the mobile interface.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="feature-card text-center">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>3. Auto Login</h4>
                    <p>Your desktop browser automatically detects the successful mobile login and logs you in.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="demo-options-section py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Demo Scenarios</h2>
                <p class="lead text-muted">Explore different implementations and use cases</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="demo-card">
                    <div class="demo-card-header">
                        <i class="fas fa-play-circle"></i>
                        <h4>Basic Implementation</h4>
                    </div>
                    <div class="demo-card-body">
                        <p>Experience the standard scan login flow with default styling and behavior.</p>
                        <ul class="demo-features">
                            <li>Standard QR code generation</li>
                            <li>Real-time status polling</li>
                            <li>Automatic desktop login</li>
                            <li>Error handling</li>
                        </ul>
                        <a href="{{ route('demo.basic') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Try Basic Demo
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="demo-card">
                    <div class="demo-card-header">
                        <i class="fas fa-palette"></i>
                        <h4>Custom Styling</h4>
                    </div>
                    <div class="demo-card-body">
                        <p>See how to customize the appearance and behavior to match your brand.</p>
                        <ul class="demo-features">
                            <li>Custom branded interface</li>
                            <li>Animated transitions</li>
                            <li>Custom success messages</li>
                            <li>Responsive design</li>
                        </ul>
                        <a href="{{ route('demo.custom') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Try Custom Demo
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="demo-card">
                    <div class="demo-card-header">
                        <i class="fas fa-code"></i>
                        <h4>API Integration</h4>
                    </div>
                    <div class="demo-card-body">
                        <p>Learn how to integrate scan login using the REST API endpoints.</p>
                        <ul class="demo-features">
                            <li>Direct API calls</li>
                            <li>JavaScript SDK usage</li>
                            <li>Custom polling logic</li>
                            <li>Error handling</li>
                        </ul>
                        <a href="{{ route('demo.api') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Try API Demo
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="demo-card">
                    <div class="demo-card-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Error Scenarios</h4>
                    </div>
                    <div class="demo-card-body">
                        <p>Test various error conditions and see how the system handles them.</p>
                        <ul class="demo-features">
                            <li>Token expiration</li>
                            <li>Network errors</li>
                            <li>Invalid credentials</li>
                            <li>Rate limiting</li>
                        </ul>
                        <a href="{{ route('demo.errors') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Try Error Demo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="security-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold mb-4">Built for Security</h2>
                <p class="lead mb-4">
                    Laravel Scan Login implements multiple security layers to protect your users and applications.
                </p>
                <div class="security-features">
                    <div class="security-feature">
                        <i class="fas fa-shield-alt text-success"></i>
                        <div>
                            <h5>Secure Token Generation</h5>
                            <p>Cryptographically secure random tokens with configurable expiration times.</p>
                        </div>
                    </div>
                    <div class="security-feature">
                        <i class="fas fa-lock text-success"></i>
                        <div>
                            <h5>HTTPS Enforcement</h5>
                            <p>All communication is encrypted and HTTPS is enforced in production environments.</p>
                        </div>
                    </div>
                    <div class="security-feature">
                        <i class="fas fa-tachometer-alt text-success"></i>
                        <div>
                            <h5>Rate Limiting</h5>
                            <p>Built-in rate limiting prevents abuse and brute force attacks.</p>
                        </div>
                    </div>
                    <div class="security-feature">
                        <i class="fas fa-eye text-success"></i>
                        <div>
                            <h5>Comprehensive Logging</h5>
                            <p>All security events are logged for monitoring and audit purposes.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="security-diagram">
                    <div class="security-flow">
                        <div class="flow-step">
                            <div class="step-icon"><i class="fas fa-desktop"></i></div>
                            <div class="step-label">Desktop Browser</div>
                        </div>
                        <div class="flow-arrow">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="flow-step">
                            <div class="step-icon"><i class="fas fa-server"></i></div>
                            <div class="step-label">Secure Server</div>
                        </div>
                        <div class="flow-arrow">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="flow-step">
                            <div class="step-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="step-label">Mobile Device</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cta-section py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-3">Ready to Get Started?</h2>
        <p class="lead mb-4">
            Install Laravel Scan Login in your application and provide your users with a seamless login experience.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('demo.basic') }}" class="btn btn-light btn-lg">
                <i class="fas fa-play me-2"></i>Try Demo Now
            </a>
            <a href="https://github.com/wuwx/laravel-scan-login" class="btn btn-outline-light btn-lg">
                <i class="fas fa-download me-2"></i>Install Package
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 100px 0;
}

.demo-preview {
    position: relative;
}

.device-mockup {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
}

.desktop-mockup, .mobile-mockup {
    background: #333;
    border-radius: 10px;
    padding: 20px;
}

.desktop-mockup {
    width: 200px;
    height: 150px;
}

.mobile-mockup {
    width: 120px;
    height: 200px;
}

.screen {
    background: white;
    border-radius: 5px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qr-placeholder {
    text-align: center;
    color: #666;
}

.qr-placeholder i {
    font-size: 2rem;
    margin-bottom: 10px;
}

.login-form-preview {
    width: 80%;
}

.form-field {
    height: 20px;
    background: #f0f0f0;
    border-radius: 3px;
    margin-bottom: 10px;
}

.form-button {
    height: 25px;
    background: #007bff;
    border-radius: 3px;
}

.demo-stats {
    margin-top: 40px;
}

.stat-item {
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #fff;
}

.stat-label {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
}

.feature-card {
    padding: 40px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 2rem;
}

.demo-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
    height: 100%;
}

.demo-card:hover {
    transform: translateY(-5px);
}

.demo-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 20px;
    text-align: center;
}

.demo-card-header i {
    font-size: 2rem;
    margin-bottom: 10px;
}

.demo-card-body {
    padding: 30px 20px;
}

.demo-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
}

.demo-features li {
    padding: 5px 0;
    position: relative;
    padding-left: 20px;
}

.demo-features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #28a745;
    font-weight: bold;
}

.security-features {
    margin-top: 30px;
}

.security-feature {
    display: flex;
    align-items: flex-start;
    margin-bottom: 30px;
}

.security-feature i {
    font-size: 1.5rem;
    margin-right: 15px;
    margin-top: 5px;
}

.security-feature h5 {
    margin-bottom: 5px;
}

.security-diagram {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 400px;
}

.security-flow {
    text-align: center;
}

.flow-step {
    margin-bottom: 30px;
}

.step-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    color: white;
    font-size: 1.5rem;
}

.step-label {
    font-weight: 600;
    color: #333;
}

.flow-arrow {
    color: #667eea;
    font-size: 1.5rem;
    margin: 10px 0;
}

@media (max-width: 768px) {
    .device-mockup {
        flex-direction: column;
        gap: 20px;
    }
    
    .desktop-mockup, .mobile-mockup {
        width: 150px;
    }
    
    .mobile-mockup {
        height: 150px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load demo statistics
    loadDemoStats();
    
    // Update stats every 30 seconds
    setInterval(loadDemoStats, 30000);
});

async function loadDemoStats() {
    try {
        const response = await fetch('/demo/statistics');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-demos').textContent = data.data.total_demos_run;
            document.getElementById('successful-logins').textContent = data.data.successful_logins;
            document.getElementById('qr-generated').textContent = data.data.qr_codes_generated;
        }
    } catch (error) {
        console.error('Failed to load demo statistics:', error);
    }
}
</script>
@endpush