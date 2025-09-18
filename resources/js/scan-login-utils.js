/**
 * Scan Login Utilities
 * Common utility functions for the scan login system
 */

const ScanLoginUtils = {
    /**
     * Get CSRF token from meta tag
     */
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            console.warn('CSRF token not found in meta tags');
        }
        return token || '';
    },

    /**
     * Make HTTP request with proper headers and error handling
     */
    async makeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCSRFToken()
            }
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, mergedOptions);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response;
        } catch (error) {
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('网络连接失败，请检查网络连接');
            }
            throw error;
        }
    },

    /**
     * Debounce function calls
     */
    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(this, args);
        };
    },

    /**
     * Throttle function calls
     */
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Check if device is mobile
     */
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },

    /**
     * Check if device supports touch
     */
    isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    },

    /**
     * Get device type
     */
    getDeviceType() {
        if (this.isMobile()) {
            return 'mobile';
        } else if (this.isTouchDevice()) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    },

    /**
     * Format error message for display
     */
    formatErrorMessage(error) {
        if (typeof error === 'string') {
            return error;
        }
        
        if (error.message) {
            return error.message;
        }
        
        if (error.errors && typeof error.errors === 'object') {
            const firstError = Object.values(error.errors)[0];
            return Array.isArray(firstError) ? firstError[0] : firstError;
        }
        
        return '发生未知错误，请重试';
    },

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    /**
     * Validate username format
     */
    isValidUsername(username) {
        // Allow alphanumeric, underscore, hyphen, and dot
        const usernameRegex = /^[a-zA-Z0-9._-]{3,}$/;
        return usernameRegex.test(username);
    },

    /**
     * Check if string is likely an email or username
     */
    getCredentialType(credential) {
        if (this.isValidEmail(credential)) {
            return 'email';
        } else if (this.isValidUsername(credential)) {
            return 'username';
        } else {
            return 'unknown';
        }
    },

    /**
     * Generate random string
     */
    generateRandomString(length = 32) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },

    /**
     * Storage utilities with fallback
     */
    storage: {
        set(key, value, useSession = false) {
            try {
                const storage = useSession ? sessionStorage : localStorage;
                storage.setItem(key, JSON.stringify(value));
                return true;
            } catch (error) {
                console.warn('Storage not available:', error);
                return false;
            }
        },

        get(key, defaultValue = null, useSession = false) {
            try {
                const storage = useSession ? sessionStorage : localStorage;
                const item = storage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (error) {
                console.warn('Storage not available:', error);
                return defaultValue;
            }
        },

        remove(key, useSession = false) {
            try {
                const storage = useSession ? sessionStorage : localStorage;
                storage.removeItem(key);
                return true;
            } catch (error) {
                console.warn('Storage not available:', error);
                return false;
            }
        },

        clear(useSession = false) {
            try {
                const storage = useSession ? sessionStorage : localStorage;
                storage.clear();
                return true;
            } catch (error) {
                console.warn('Storage not available:', error);
                return false;
            }
        }
    },

    /**
     * Event emitter utility
     */
    createEventEmitter() {
        const events = {};
        
        return {
            on(event, callback) {
                if (!events[event]) {
                    events[event] = [];
                }
                events[event].push(callback);
            },

            off(event, callback) {
                if (events[event]) {
                    events[event] = events[event].filter(cb => cb !== callback);
                }
            },

            emit(event, data) {
                if (events[event]) {
                    events[event].forEach(callback => {
                        try {
                            callback(data);
                        } catch (error) {
                            console.error('Event callback error:', error);
                        }
                    });
                }
            },

            once(event, callback) {
                const onceCallback = (data) => {
                    callback(data);
                    this.off(event, onceCallback);
                };
                this.on(event, onceCallback);
            }
        };
    },

    /**
     * URL utilities
     */
    url: {
        getParam(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        },

        setParam(name, value) {
            const url = new URL(window.location);
            url.searchParams.set(name, value);
            window.history.replaceState({}, '', url);
        },

        removeParam(name) {
            const url = new URL(window.location);
            url.searchParams.delete(name);
            window.history.replaceState({}, '', url);
        },

        getTokenFromPath() {
            const pathParts = window.location.pathname.split('/');
            return pathParts[pathParts.length - 1];
        }
    },

    /**
     * DOM utilities
     */
    dom: {
        ready(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
            } else {
                callback();
            }
        },

        createElement(tag, attributes = {}, children = []) {
            const element = document.createElement(tag);
            
            Object.entries(attributes).forEach(([key, value]) => {
                if (key === 'className') {
                    element.className = value;
                } else if (key === 'innerHTML') {
                    element.innerHTML = value;
                } else {
                    element.setAttribute(key, value);
                }
            });
            
            children.forEach(child => {
                if (typeof child === 'string') {
                    element.appendChild(document.createTextNode(child));
                } else {
                    element.appendChild(child);
                }
            });
            
            return element;
        },

        show(element) {
            if (element) {
                element.style.display = '';
            }
        },

        hide(element) {
            if (element) {
                element.style.display = 'none';
            }
        },

        toggle(element) {
            if (element) {
                element.style.display = element.style.display === 'none' ? '' : 'none';
            }
        }
    },

    /**
     * Time utilities
     */
    time: {
        formatDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        },

        isExpired(timestamp, expiryMinutes = 5) {
            const now = Date.now();
            const expiry = new Date(timestamp).getTime() + (expiryMinutes * 60 * 1000);
            return now > expiry;
        },

        getTimeUntilExpiry(timestamp, expiryMinutes = 5) {
            const now = Date.now();
            const expiry = new Date(timestamp).getTime() + (expiryMinutes * 60 * 1000);
            return Math.max(0, expiry - now);
        }
    },

    /**
     * Retry utility with exponential backoff
     */
    async retry(fn, options = {}) {
        const {
            maxAttempts = 3,
            delay = 1000,
            backoff = 2,
            onRetry = null
        } = options;

        let lastError;
        
        for (let attempt = 1; attempt <= maxAttempts; attempt++) {
            try {
                return await fn();
            } catch (error) {
                lastError = error;
                
                if (attempt === maxAttempts) {
                    throw error;
                }
                
                if (onRetry) {
                    onRetry(error, attempt);
                }
                
                const waitTime = delay * Math.pow(backoff, attempt - 1);
                await new Promise(resolve => setTimeout(resolve, waitTime));
            }
        }
        
        throw lastError;
    }
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ScanLoginUtils;
}

// Global registration
if (typeof window !== 'undefined') {
    window.ScanLoginUtils = ScanLoginUtils;
}