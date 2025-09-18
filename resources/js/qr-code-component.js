/**
 * QrCodeComponent - Standalone JavaScript class for handling QR code login functionality
 * 
 * Features:
 * - QR code generation and display
 * - Automatic polling for login status
 * - Error handling and retry logic
 * - Auto-refresh for expired tokens
 * - Responsive design support
 * - Event-driven architecture
 */
class QrCodeComponent {
    constructor(containerId, options = {}) {
        // Configuration
        this.containerId = containerId;
        this.options = {
            pollingInterval: options.pollingInterval || 3000, // 3 seconds
            maxRetries: options.maxRetries || 3,
            autoRefresh: options.autoRefresh !== false,
            qrCodeSize: options.qrCodeSize || 200,
            apiEndpoints: {
                generate: options.generateEndpoint || '/scan-login/generate',
                status: options.statusEndpoint || '/scan-login/status'
            },
            messages: {
                loading: options.loadingText || '正在生成二维码...',
                error: options.errorText || '二维码生成失败，请刷新页面重试',
                expired: options.expiredText || '二维码已过期，正在刷新...',
                success: options.successText || '登录成功，正在跳转...',
                networkError: options.networkErrorText || '网络连接异常，请刷新页面重试',
                retrying: options.retryingText || '连接失败，正在重试...'
            },
            redirectUrl: options.redirectUrl || '/dashboard',
            ...options
        };
        
        // State management
        this.state = {
            currentToken: null,
            isPolling: false,
            retryCount: 0,
            isInitialized: false,
            lastError: null
        };
        
        // DOM elements (will be set during initialization)
        this.elements = {};
        
        // Timers
        this.pollingTimer = null;
        this.retryTimer = null;
        
        // Event listeners storage for cleanup
        this.eventListeners = [];
        
        // Initialize component
        this.init();
    }
    
    /**
     * Initialize the component
     */
    init() {
        try {
            this.setupDOM();
            this.bindEvents();
            this.generateQrCode();
            this.state.isInitialized = true;
            this.emit('initialized');
        } catch (error) {
            console.error('QrCodeComponent initialization failed:', error);
            this.handleError('组件初始化失败');
        }
    }
    
    /**
     * Set up DOM structure
     */
    setupDOM() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            throw new Error(`Container with ID '${this.containerId}' not found`);
        }
        
        container.innerHTML = this.getTemplate();
        
        // Cache DOM elements
        this.elements = {
            container,
            qrDisplay: container.querySelector('.qr-code-display'),
            loadingSpinner: container.querySelector('.loading-spinner'),
            statusMessage: container.querySelector('.status-message'),
            statusText: container.querySelector('.status-text'),
            refreshContainer: container.querySelector('.refresh-button-container'),
            refreshBtn: container.querySelector('.refresh-btn')
        };
    }
    
    /**
     * Get HTML template for the component
     */
    getTemplate() {
        return `
            <div class="qr-code-wrapper">
                <div class="qr-code-display">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>${this.options.messages.loading}</p>
                    </div>
                </div>
                
                <div class="status-message" style="display: none;">
                    <p class="status-text"></p>
                </div>
                
                <div class="refresh-button-container" style="display: none;">
                    <button type="button" class="refresh-btn">刷新二维码</button>
                </div>
            </div>
        `;
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Refresh button click
        this.addEventListener(this.elements.refreshBtn, 'click', () => {
            this.generateQrCode();
        });
        
        // Page visibility change
        this.addEventListener(document, 'visibilitychange', () => {
            if (document.hidden) {
                this.pausePolling();
            } else if (this.state.currentToken && !this.state.isPolling) {
                this.resumePolling();
            }
        });
        
        // Page unload cleanup
        this.addEventListener(window, 'beforeunload', () => {
            this.cleanup();
        });
        
        // Handle online/offline events
        this.addEventListener(window, 'online', () => {
            if (this.state.currentToken && !this.state.isPolling) {
                this.resumePolling();
            }
        });
        
        this.addEventListener(window, 'offline', () => {
            this.pausePolling();
            this.showStatus(this.options.messages.networkError, 'error');
        });
    }
    
    /**
     * Add event listener with cleanup tracking
     */
    addEventListener(element, event, handler) {
        element.addEventListener(event, handler);
        this.eventListeners.push({ element, event, handler });
    }
    
    /**
     * Generate QR code
     */
    async generateQrCode() {
        try {
            this.showLoading();
            this.hideRefreshButton();
            this.state.retryCount = 0;
            this.state.lastError = null;
            
            this.emit('qr-generation-start');
            
            const response = await this.makeRequest(this.options.apiEndpoints.generate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.state.currentToken = data.token;
                this.displayQrCode(data.qr_code);
                this.startPolling();
                this.emit('qr-generated', { token: data.token });
            } else {
                throw new Error(data.message || 'Failed to generate QR code');
            }
            
        } catch (error) {
            console.error('QR Code generation failed:', error);
            this.handleError(this.options.messages.error);
            this.showRefreshButton();
            this.emit('qr-generation-error', { error });
        }
    }
    
    /**
     * Display QR code
     */
    displayQrCode(qrCodeSvg) {
        this.hideLoading();
        this.hideStatus();
        
        this.elements.qrDisplay.innerHTML = `
            <div class="qr-code-image">
                ${qrCodeSvg}
            </div>
        `;
        
        this.emit('qr-displayed');
    }
    
    /**
     * Start polling for login status
     */
    startPolling() {
        if (this.state.isPolling || !this.state.currentToken) return;
        
        this.state.isPolling = true;
        this.pollingTimer = setInterval(() => {
            this.checkLoginStatus();
        }, this.options.pollingInterval);
        
        this.emit('polling-started');
    }
    
    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
        this.state.isPolling = false;
        this.emit('polling-stopped');
    }
    
    /**
     * Pause polling (can be resumed)
     */
    pausePolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
        // Keep isPolling true so we can resume
        this.emit('polling-paused');
    }
    
    /**
     * Resume polling
     */
    resumePolling() {
        if (this.state.isPolling && !this.pollingTimer && this.state.currentToken) {
            this.pollingTimer = setInterval(() => {
                this.checkLoginStatus();
            }, this.options.pollingInterval);
            this.emit('polling-resumed');
        }
    }
    
    /**
     * Check login status
     */
    async checkLoginStatus() {
        if (!this.state.currentToken) return;
        
        try {
            const response = await this.makeRequest(
                `${this.options.apiEndpoints.status}/${this.state.currentToken}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
            );
            
            const data = await response.json();
            
            if (data.success) {
                this.handleStatusResponse(data);
            } else {
                throw new Error(data.message || 'Status check failed');
            }
            
            // Reset retry count on successful request
            this.state.retryCount = 0;
            
        } catch (error) {
            console.error('Status check failed:', error);
            this.handlePollingError(error);
        }
    }
    
    /**
     * Handle status response
     */
    handleStatusResponse(data) {
        switch (data.status) {
            case 'used':
                this.handleLoginSuccess(data.redirect_url);
                break;
            case 'expired':
                this.handleTokenExpired();
                break;
            case 'pending':
                // Continue polling
                this.emit('status-pending');
                break;
            default:
                console.warn('Unknown status:', data.status);
                this.emit('status-unknown', { status: data.status });
        }
    }
    
    /**
     * Handle polling errors
     */
    handlePollingError(error) {
        this.state.retryCount++;
        this.state.lastError = error;
        
        if (this.state.retryCount >= this.options.maxRetries) {
            this.stopPolling();
            this.handleError(this.options.messages.networkError);
            this.showRefreshButton();
            this.emit('polling-failed', { error, retryCount: this.state.retryCount });
        } else {
            // Show retry message briefly
            this.showStatus(this.options.messages.retrying, 'info');
            setTimeout(() => {
                this.hideStatus();
            }, 2000);
            this.emit('polling-retry', { error, retryCount: this.state.retryCount });
        }
    }
    
    /**
     * Handle successful login
     */
    handleLoginSuccess(redirectUrl) {
        this.stopPolling();
        this.showStatus(this.options.messages.success, 'success');
        this.emit('login-success', { redirectUrl });
        
        // Redirect after a short delay
        setTimeout(() => {
            window.location.href = redirectUrl || this.options.redirectUrl;
        }, 1500);
    }
    
    /**
     * Handle token expiration
     */
    handleTokenExpired() {
        this.stopPolling();
        this.emit('token-expired');
        
        if (this.options.autoRefresh) {
            this.showStatus(this.options.messages.expired, 'info');
            setTimeout(() => {
                this.generateQrCode();
            }, 2000);
        } else {
            this.handleError('二维码已过期');
            this.showRefreshButton();
        }
    }
    
    /**
     * Handle errors
     */
    handleError(message) {
        this.showStatus(message, 'error');
        this.state.lastError = new Error(message);
        this.emit('error', { message });
    }
    
    /**
     * Make HTTP request with error handling
     */
    async makeRequest(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response;
    }
    
    /**
     * Get CSRF token
     */
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            console.warn('CSRF token not found');
        }
        return token || '';
    }
    
    /**
     * Show loading state
     */
    showLoading() {
        this.elements.loadingSpinner.style.display = 'flex';
        this.hideStatus();
    }
    
    /**
     * Hide loading state
     */
    hideLoading() {
        this.elements.loadingSpinner.style.display = 'none';
    }
    
    /**
     * Show status message
     */
    showStatus(message, type = 'info') {
        this.elements.statusText.textContent = message;
        this.elements.statusMessage.className = `status-message ${type}`;
        this.elements.statusMessage.style.display = 'block';
    }
    
    /**
     * Hide status message
     */
    hideStatus() {
        this.elements.statusMessage.style.display = 'none';
    }
    
    /**
     * Show refresh button
     */
    showRefreshButton() {
        this.elements.refreshContainer.style.display = 'block';
    }
    
    /**
     * Hide refresh button
     */
    hideRefreshButton() {
        this.elements.refreshContainer.style.display = 'none';
    }
    
    /**
     * Event emitter
     */
    emit(eventName, data = {}) {
        const event = new CustomEvent(`qr-code-${eventName}`, {
            detail: { component: this, ...data }
        });
        this.elements.container.dispatchEvent(event);
    }
    
    /**
     * Add event listener for component events
     */
    on(eventName, handler) {
        this.elements.container.addEventListener(`qr-code-${eventName}`, handler);
    }
    
    /**
     * Remove event listener for component events
     */
    off(eventName, handler) {
        this.elements.container.removeEventListener(`qr-code-${eventName}`, handler);
    }
    
    /**
     * Get current state
     */
    getState() {
        return { ...this.state };
    }
    
    /**
     * Get current options
     */
    getOptions() {
        return { ...this.options };
    }
    
    /**
     * Update options
     */
    updateOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        this.emit('options-updated', { options: this.options });
    }
    
    /**
     * Refresh QR code manually
     */
    refresh() {
        this.generateQrCode();
    }
    
    /**
     * Reset component state
     */
    reset() {
        this.stopPolling();
        this.state.currentToken = null;
        this.state.retryCount = 0;
        this.state.lastError = null;
        this.hideStatus();
        this.hideRefreshButton();
        this.emit('reset');
    }
    
    /**
     * Cleanup resources
     */
    cleanup() {
        this.stopPolling();
        
        if (this.retryTimer) {
            clearTimeout(this.retryTimer);
            this.retryTimer = null;
        }
        
        // Remove all event listeners
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners = [];
        
        this.emit('cleanup');
    }
    
    /**
     * Destroy component
     */
    destroy() {
        this.cleanup();
        if (this.elements.container) {
            this.elements.container.innerHTML = '';
        }
        this.emit('destroyed');
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = QrCodeComponent;
}

// Global registration
if (typeof window !== 'undefined') {
    window.QrCodeComponent = QrCodeComponent;
}

// Auto-initialize if container exists
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('scan-login-container');
    if (container && !window.qrCodeComponentInstance) {
        window.qrCodeComponentInstance = new QrCodeComponent('scan-login-container');
    }
});