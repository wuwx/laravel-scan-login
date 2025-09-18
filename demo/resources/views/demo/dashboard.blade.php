@extends('demo.layouts.app')

@section('title', 'Dashboard - Laravel Scan Login Demo')

@section('content')
<div class="dashboard-header">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-tachometer-alt me-3"></i>Dashboard
                </h1>
                <p class="lead">Welcome back, {{ auth()->user()->name }}! You've successfully logged in using Laravel Scan Login.</p>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-content py-5">
    <div class="container">
        <div class="row">
            <!-- User Information -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>User Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="user-avatar text-center mb-3">
                            <div class="avatar-circle">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                        <div class="user-details">
                            <div class="detail-item">
                                <strong>Name:</strong>
                                <span>{{ auth()->user()->name }}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Email:</strong>
                                <span>{{ auth()->user()->email }}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Login Method:</strong>
                                <span class="badge bg-success">
                                    <i class="fas fa-qrcode me-1"></i>Scan Login
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong>Login Time:</strong>
                                <span>{{ now()->format('M j, Y g:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Login Statistics -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Login Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <div class="stat-number" id="total-logins">0</div>
                                    <div class="stat-label">Total Logins</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div class="stat-number" id="scan-logins">0</div>
                                    <div class="stat-label">Scan Logins</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="stat-number" id="mobile-logins">0</div>
                                    <div class="stat-label">Mobile Logins</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-number" id="avg-time">0s</div>
                                    <div class="stat-label">Avg Login Time</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chart-container mt-4">
                            <canvas id="loginChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Activity -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">Successful Login</div>
                                    <div class="activity-time">{{ now()->format('g:i A') }}</div>
                                    <div class="activity-description">Logged in via QR code scan</div>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">QR Code Generated</div>
                                    <div class="activity-time">{{ now()->subMinutes(1)->format('g:i A') }}</div>
                                    <div class="activity-description">New QR code generated for login</div>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon bg-info">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">Mobile Access</div>
                                    <div class="activity-time">{{ now()->subMinutes(2)->format('g:i A') }}</div>
                                    <div class="activity-description">Accessed login page from mobile device</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Demo Actions -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Demo Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Explore different features and scenarios of the Laravel Scan Login package.
                        </p>
                        
                        <div class="demo-actions">
                            <a href="{{ route('demo.basic') }}" class="btn btn-outline-primary btn-block mb-2">
                                <i class="fas fa-play-circle me-2"></i>Try Basic Demo Again
                            </a>
                            
                            <a href="{{ route('demo.custom') }}" class="btn btn-outline-secondary btn-block mb-2">
                                <i class="fas fa-palette me-2"></i>Custom Styling Demo
                            </a>
                            
                            <a href="{{ route('demo.api') }}" class="btn btn-outline-info btn-block mb-2">
                                <i class="fas fa-code me-2"></i>API Integration Demo
                            </a>
                            
                            <a href="{{ route('demo.errors') }}" class="btn btn-outline-warning btn-block mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>Error Scenarios Demo
                            </a>
                            
                            <hr class="my-3">
                            
                            <form method="POST" action="{{ route('demo.logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-block">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Information -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Security Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Session Details</h6>
                                <div class="security-details">
                                    <div class="security-item">
                                        <strong>Session ID:</strong>
                                        <code>{{ session()->getId() }}</code>
                                    </div>
                                    <div class="security-item">
                                        <strong>IP Address:</strong>
                                        <code>{{ request()->ip() }}</code>
                                    </div>
                                    <div class="security-item">
                                        <strong>User Agent:</strong>
                                        <code class="small">{{ request()->userAgent() }}</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Security Features</h6>
                                <div class="security-features">
                                    <div class="security-feature">
                                        <i class="fas fa-check text-success me-2"></i>
                                        CSRF Protection Enabled
                                    </div>
                                    <div class="security-feature">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Secure Session Cookies
                                    </div>
                                    <div class="security-feature">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Token-based Authentication
                                    </div>
                                    <div class="security-feature">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Rate Limiting Active
                                    </div>
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
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0 40px;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
    border-bottom: none;
}

.stat-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 1.2rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 15px;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.activity-time {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 4px;
}

.activity-description {
    font-size: 0.9rem;
    color: #888;
}

.demo-actions .btn {
    width: 100%;
    text-align: left;
    margin-bottom: 8px;
}

.security-details .security-item {
    margin-bottom: 10px;
}

.security-details .security-item strong {
    display: inline-block;
    width: 120px;
}

.security-features .security-feature {
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.chart-container {
    position: relative;
    height: 200px;
}

@media (max-width: 768px) {
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .stat-card {
        margin-bottom: 15px;
    }
    
    .security-details .security-item strong {
        width: auto;
        display: block;
        margin-bottom: 5px;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard statistics
    loadDashboardStats();
    
    // Initialize login chart
    initializeLoginChart();
    
    // Update stats every 30 seconds
    setInterval(loadDashboardStats, 30000);
});

async function loadDashboardStats() {
    try {
        const response = await fetch('/demo/dashboard/statistics');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-logins').textContent = data.data.total_logins || 1;
            document.getElementById('scan-logins').textContent = data.data.scan_logins || 1;
            document.getElementById('mobile-logins').textContent = data.data.mobile_logins || 1;
            document.getElementById('avg-time').textContent = (data.data.avg_login_time || 3) + 's';
        }
    } catch (error) {
        console.error('Failed to load dashboard statistics:', error);
        // Set default values
        document.getElementById('total-logins').textContent = '1';
        document.getElementById('scan-logins').textContent = '1';
        document.getElementById('mobile-logins').textContent = '1';
        document.getElementById('avg-time').textContent = '3s';
    }
}

function initializeLoginChart() {
    const ctx = document.getElementById('loginChart').getContext('2d');
    
    // Sample data for demonstration
    const chartData = {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Scan Logins',
            data: [2, 5, 3, 8, 4, 6, 7],
            backgroundColor: 'rgba(102, 126, 234, 0.2)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }, {
            label: 'Traditional Logins',
            data: [1, 2, 1, 3, 2, 2, 3],
            backgroundColor: 'rgba(118, 75, 162, 0.2)',
            borderColor: 'rgba(118, 75, 162, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    };
    
    const config = {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Login Activity (Last 7 Days)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };
    
    new Chart(ctx, config);
}

// Add smooth animations to stat cards
function animateStatCards() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(element => {
        const finalValue = parseInt(element.textContent);
        let currentValue = 0;
        const increment = Math.ceil(finalValue / 20);
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            element.textContent = currentValue;
        }, 50);
    });
}

// Animate stat cards on page load
setTimeout(animateStatCards, 500);
</script>
@endpush