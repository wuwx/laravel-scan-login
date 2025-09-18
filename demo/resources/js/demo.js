/**
 * Laravel Scan Login Demo JavaScript
 * 
 * This file contains demo-specific JavaScript functionality
 * for showcasing the Laravel Scan Login package.
 */

class DemoScanLogin {
    constructor(options = {}) {
        this.options = {
            container: '.qr-code-container',
            generateUrl: '/demo/generate-qr',
            statusUrl: '/demo/status',
            pollingInterval: 3000,
            maxPollingDuration: 600000, // 10 minutes
            onQrGenerated: null,
            onPolling: null,
            onLoginSuccess: null,
            onTokenExpired: null,
            onError: null,
            ...options
        };
        
        this.token = null;
        this.pollingTimer = null;
        this.pollingStartTime = null;
        this.pollingCount = 0;
        this.container = document.querySelector(this.options.container);
        
        if (!this.container) {
            console.error('Demo scan login container not found');
            return;
        }
        
        this.init();
    }
    
    async init() {
        try {
            this.showLoading('Generating QR code...');
            await this.generateQrCode();
            this.startPolling();
        } catch (error) {
            this.handleError(error);
        }
    }
    
    async generateQrCode() {
        const response = await fetch(this.options.generateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCSRFToken()
            }
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error?.message || 'Failed to generate QR code');
        }
        
        this.token = data.data.token;
        this.displayQrCode(data.data);
        
        if (this.options.onQrGenerated) {
            this.options.onQrGenerated(data.data);
        }
        
        // Increment demo statistics
        this.incrementStat('qr_generated');
        
        return data.data;
    }
    
    displayQrCode(qrData) {
        const expiresAt = new Date(qrData.expires_at);
        const expiresIn = Math.round((expiresAt - new Date()) / 1000 / 60);
        
        this.container.innerHTML = `
            <div class="qr-code-wrapper">
                <div class="qr-code-image">
                    ${qrData.qr_code}
                </div>
                <div class="qr-code-info">
                    <div class="qr-code-status">
                        <div class="status-indicator waiting">
                            <div class="pulse-dot"></div>
                            <span>Waiting for mobile scan...</span>
                        </div>
                    </div>
                    <div class="qr-code-details">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Expires in ${expiresIn} minutes
                        </small>
                    </div>
                    <div class="demo-instructions mt-3">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-mobile-alt me-2"></i>Demo Instructions:</h6>
                            <ol class="mb-0">
                                <li>Scan this QR code with your mobile device</li>
                                <li>Use credentials: <strong>demo@example.com</strong> / <strong>password</strong></li>
                                <li>Watch this page automatically log you in!</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="qr-code-actions mt-3">
                    <button class="btn btn-outline-primary btn-sm" onclick="this.refresh()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh QR Code
                    </button>
                </div>
            </div>
        `;
    }
    
    startPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
        }
        
        this.pollingStartTime = Date.now();
        this.pollingCount = 0;
        
        this.pollingTimer = setInterval(() => {
            this.checkStatus();
        }, this.options.pollingInterval);
        
        // Stop polling after max duration
        setTimeout(() => {
            this.stopPolling();
            this.handleTokenExpired();
        }, this.options.maxPollingDuration);
    }
    
    stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    }
    
    async checkStatus() {
        if (!this.token) return;
        
        this.pollingCount++;
        
        if (this.options.onPolling) {
            this.options.onPolling(this.pollingCount);
        }
        
        try {
            const response = await fetch(`${this.options.statusUrl}/${this.token}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error?.message || 'Status check failed');
            }
            
            const status = data.data;
            
            switch (status.status) {
                case 'pending':
                    // Continue polling
                    this.updateStatus('Waiting for mobile login...', 'waiting');
                    break;
                    
                case 'used':
                    this.handleLoginSuccess(status);
                    break;
                    
                case 'expired':
                    this.handleTokenExpired();
                    break;
                    
                default:
                    throw new Error('Unknown status: ' + status.status);
            }
        } catch (error) {
            this.handleError(error);
        }
    }
    
    handleLoginSuccess(data) {
        this.stopPolling();
        
        this.updateStatus('Login successful! Redirecting...', 'success');
        
        // Show success animation
        this.showSuccessAnimation();
        
        // Increment demo statistics
        this.incrementStat('successful_logins');
        
        if (this.options.onLoginSuccess) {
            this.options.onLoginSuccess(data);
        } else {
            // Default behavior: redirect after delay
            setTimeout(() => {
                window.location.href = data.redirect_url || '/demo/dashboard';
            }, 2000);
        }
    }
    
    handleTokenExpired() {
        this.stopPolling();
        
        this.updateStatus('QR code expired, refreshing...', 'expired');
        
        if (this.options.onTokenExpired) {
            this.options.onTokenExpired();
        } else {
            // Auto-refresh after 2 seconds
            setTimeout(() => {
                this.refresh();
            }, 2000);
        }
    }
    
    handleError(error) {
        this.stopPolling();
        
        console.error('Demo scan login error:', error);
        
        this.updateStatus(`Error: ${error.message}`, 'error');
        
        // Increment demo statistics
        this.incrementStat('failed_attempts');
        
        if (this.options.onError) {
            this.options.onError(error);
        } else {
            // Show retry button after error
            setTimeout(() => {
                this.showRetryButton();
            }, 3000);
        }
    }
    
    updateStatus(message, type) {
        const statusElement = this.container.querySelector('.status-indicator');
        if (statusElement) {
            statusElement.className = `status-indicator ${type}`;
            statusElement.innerHTML = this.getStatusIcon(type) + `<span>${message}</span>`;
        }
    }
    
    getStatusIcon(type) {
        const icons = {
            waiting: '<div class="pulse-dot"></div>',
            success: '<i class="fas fa-check-circle text-success"></i>',
            expired: '<i class="fas fa-clock text-warning"></i>',
            error: '<i class="fas fa-exclamation-circle text-danger"></i>'
        };
        
        return icons[type] || icons.waiting;
    }
    
    showLoading(message = 'Loading...') {
        this.container.innerHTML = `
            <div class="qr-code-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">${message}</p>
            </div>
        `;
    }
    
    showSuccessAnimation() {
        const wrapper = this.container.querySelector('.qr-code-wrapper');
        if (wrapper) {
            wrapper.classList.add('success-animation');
        }
    }
    
    showRetryButton() {
        const actionsDiv = this.container.querySelector('.qr-code-actions');
        if (actionsDiv) {
            actionsDiv.innerHTML = `
                <button class="btn btn-primary" onclick="window.demoScanLogin.refresh()">
                    <i class="fas fa-redo me-1"></i>Try Again
                </button>
            `;
        }
    }
    
    async refresh() {
        try {
            this.pollingCount = 0;
            await this.generateQrCode();
            this.startPolling();
        } catch (error) {
            this.handleError(error);
        }
    }
    
    destroy() {
        this.stopPolling();
        this.token = null;
        
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
    
    getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }
    
    async incrementStat(statName) {
        try {
            await fetch('/demo/increment-stat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({ stat: statName })
            });
        } catch (error) {
            // Silently fail for demo statistics
            console.debug('Failed to increment demo stat:', error);
        }
    }
}

// Demo utilities
class DemoUtils {
    static async simulateError(errorType) {
        try {
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
                throw new Error(data.error.message);
            }
            
            return data;
        } catch (error) {
            console.error('Failed to simulate error:', error);
            throw error;
        }
    }
    
    static async loadStatistics() {
        try {
            const response = await fetch('/demo/statistics');
            const data = await response.json();
            
            if (data.success) {
                return data.data;
            } else {
                throw new Error('Failed to load statistics');
            }
        } catch (error) {
            console.error('Failed to load demo statistics:', error);
            return null;
        }
    }
    
    static async resetDemo() {
        try {
            const response = await fetch('/demo/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data;
            } else {
                throw new Error('Failed to reset demo');
            }
        } catch (error) {
            console.error('Failed to reset demo:', error);
            throw error;
        }
    }
    
    static showToast(message, type = 'info') {
        // Use the global showToast function if available
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
    
    static formatDuration(seconds) {
        if (seconds < 60) {
            return `${seconds}s`;
        } else if (seconds < 3600) {
            return `${Math.floor(seconds / 60)}m ${seconds % 60}s`;
        } else {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return `${hours}h ${minutes}m`;
        }
    }
    
    static copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy to clipboard:', err);
                this.showToast('Failed to copy to clipboard', 'error');
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                this.showToast('Copied to clipboard!', 'success');
            } catch (err) {
                console.error('Failed to copy to clipboard:', err);
                this.showToast('Failed to copy to clipboard', 'error');
            }
            document.body.removeChild(textArea);
        }
    }
}

// Export for global use
window.DemoScanLogin = DemoScanLogin;
window.DemoUtils = DemoUtils;

// Auto-initialize demo scan login if container exists
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.qr-code-container') && !window.demoScanLogin) {
        window.demoScanLogin = new DemoScanLogin();
    }
});