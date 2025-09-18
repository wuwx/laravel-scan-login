@props([
    'pollingInterval' => config('scan-login.polling_interval_seconds', 3) * 1000,
    'qrCodeSize' => config('scan-login.qr_code_size', 200),
    'autoRefresh' => true,
    'containerClass' => 'scan-login-container',
    'loadingText' => '正在生成二维码...',
    'errorText' => '二维码生成失败，请刷新页面重试',
    'expiredText' => '二维码已过期，正在刷新...',
    'successText' => '登录成功，正在跳转...'
])

<div class="{{ $containerClass }}" id="scan-login-container">
    <div class="qr-code-wrapper">
        <div class="qr-code-display" id="qr-code-display">
            <div class="loading-spinner" id="loading-spinner">
                <div class="spinner"></div>
                <p>{{ $loadingText }}</p>
            </div>
        </div>
        
        <div class="status-message" id="status-message" style="display: none;">
            <p id="status-text"></p>
        </div>
        
        <div class="refresh-button-container" id="refresh-container" style="display: none;">
            <button type="button" class="refresh-btn" id="refresh-btn">刷新二维码</button>
        </div>
    </div>
</div>

<style>
.scan-login-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    max-width: 400px;
    margin: 0 auto;
}

.qr-code-wrapper {
    text-align: center;
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.qr-code-display {
    min-height: {{ $qrCodeSize + 40 }}px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.qr-code-image {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}

.status-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    font-size: 14px;
}

.status-message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-message.info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.refresh-button-container {
    margin-top: 15px;
}

.refresh-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
}

.refresh-btn:hover {
    background-color: #0056b3;
}

.refresh-btn:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

@media (max-width: 480px) {
    .scan-login-container {
        padding: 10px;
        max-width: 100%;
    }
    
    .qr-code-wrapper {
        padding: 15px;
    }
}
</style>

<script>
class QrCodeComponent {
    constructor(options = {}) {
        this.pollingInterval = options.pollingInterval || {{ $pollingInterval }};
        this.autoRefresh = options.autoRefresh !== false;
        this.maxRetries = options.maxRetries || 3;
        this.retryCount = 0;
        
        // DOM elements
        this.container = document.getElementById('scan-login-container');
        this.qrDisplay = document.getElementById('qr-code-display');
        this.loadingSpinner = document.getElementById('loading-spinner');
        this.statusMessage = document.getElementById('status-message');
        this.statusText = document.getElementById('status-text');
        this.refreshContainer = document.getElementById('refresh-container');
        this.refreshBtn = document.getElementById('refresh-btn');
        
        // State
        this.currentToken = null;
        this.pollingTimer = null;
        this.isPolling = false;
        
        // Messages
        this.messages = {
            loading: '{{ $loadingText }}',
            error: '{{ $errorText }}',
            expired: '{{ $expiredText }}',
            success: '{{ $successText }}'
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.generateQrCode();
    }
    
    bindEvents() {
        this.refreshBtn.addEventListener('click', () => {
            this.generateQrCode();
        });
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else if (this.currentToken && !this.isPolling) {
                this.startPolling();
            }
        });
        
        // Handle page unload
        window.addEventListener('beforeunload', () => {
            this.stopPolling();
        });
    }
    
    async generateQrCode() {
        try {
            this.showLoading();
            this.hideRefreshButton();
            this.retryCount = 0;
            
            const response = await fetch('/scan-login/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.currentToken = data.token;
                this.displayQrCode(data.qr_code);
                this.startPolling();
            } else {
                throw new Error(data.message || 'Failed to generate QR code');
            }
            
        } catch (error) {
            console.error('QR Code generation failed:', error);
            this.showError(this.messages.error);
            this.showRefreshButton();
        }
    }
    
    displayQrCode(qrCodeSvg) {
        this.hideLoading();
        this.hideStatus();
        
        this.qrDisplay.innerHTML = `
            <div class="qr-code-image">
                ${qrCodeSvg}
            </div>
        `;
    }
    
    startPolling() {
        if (this.isPolling || !this.currentToken) return;
        
        this.isPolling = true;
        this.pollingTimer = setInterval(() => {
            this.checkLoginStatus();
        }, this.pollingInterval);
    }
    
    stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
        this.isPolling = false;
    }
    
    async checkLoginStatus() {
        if (!this.currentToken) return;
        
        try {
            const response = await fetch(`/scan-login/status/${this.currentToken}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                switch (data.status) {
                    case 'used':
                        this.handleLoginSuccess(data.redirect_url);
                        break;
                    case 'expired':
                        this.handleTokenExpired();
                        break;
                    case 'pending':
                        // Continue polling
                        break;
                    default:
                        console.warn('Unknown status:', data.status);
                }
            } else {
                throw new Error(data.message || 'Status check failed');
            }
            
            this.retryCount = 0; // Reset retry count on successful request
            
        } catch (error) {
            console.error('Status check failed:', error);
            this.retryCount++;
            
            if (this.retryCount >= this.maxRetries) {
                this.stopPolling();
                this.showError('网络连接异常，请刷新页面重试');
                this.showRefreshButton();
            }
        }
    }
    
    handleLoginSuccess(redirectUrl) {
        this.stopPolling();
        this.showSuccess(this.messages.success);
        
        // Redirect after a short delay
        setTimeout(() => {
            window.location.href = redirectUrl || '/dashboard';
        }, 1500);
    }
    
    handleTokenExpired() {
        this.stopPolling();
        
        if (this.autoRefresh) {
            this.showInfo(this.messages.expired);
            setTimeout(() => {
                this.generateQrCode();
            }, 2000);
        } else {
            this.showError('二维码已过期');
            this.showRefreshButton();
        }
    }
    
    showLoading() {
        this.loadingSpinner.style.display = 'flex';
        this.hideStatus();
    }
    
    hideLoading() {
        this.loadingSpinner.style.display = 'none';
    }
    
    showStatus(message, type = 'info') {
        this.statusText.textContent = message;
        this.statusMessage.className = `status-message ${type}`;
        this.statusMessage.style.display = 'block';
    }
    
    showSuccess(message) {
        this.showStatus(message, 'success');
    }
    
    showError(message) {
        this.showStatus(message, 'error');
    }
    
    showInfo(message) {
        this.showStatus(message, 'info');
    }
    
    hideStatus() {
        this.statusMessage.style.display = 'none';
    }
    
    showRefreshButton() {
        this.refreshContainer.style.display = 'block';
    }
    
    hideRefreshButton() {
        this.refreshContainer.style.display = 'none';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('scan-login-container')) {
        window.qrCodeComponent = new QrCodeComponent();
    }
});
</script>