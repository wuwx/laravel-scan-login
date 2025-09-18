<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>扫码登录</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .login-form {
            padding: 30px 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fff;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-input.error {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .button-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        .login-button.loading .button-spinner {
            display: inline-block;
        }

        .login-button.loading .button-text {
            opacity: 0.7;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success-container {
            display: none;
            text-align: center;
            padding: 40px 20px;
        }

        .success-container.show {
            display: block;
        }

        .success-icon {
            width: 60px;
            height: 60px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: successPulse 0.6s ease-out;
        }

        .success-icon::after {
            content: '✓';
            color: white;
            font-size: 30px;
            font-weight: bold;
        }

        @keyframes successPulse {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .success-message {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }

        .footer-text {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e1e5e9;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                border-radius: 8px;
            }
            
            .login-header {
                padding: 25px 15px;
            }
            
            .login-form {
                padding: 25px 15px;
            }
            
            .login-header h1 {
                font-size: 22px;
            }
        }

        /* iOS Safari specific fixes */
        @supports (-webkit-touch-callout: none) {
            .form-input {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>扫码登录</h1>
            <p>请输入您的登录凭据完成验证</p>
        </div>

        <div class="login-form" id="login-form">
            <div class="alert alert-error" id="error-alert">
                <span id="error-message"></span>
            </div>

            <form id="mobile-login-form" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">用户名/邮箱</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        required 
                        autocomplete="username"
                        placeholder="请输入用户名或邮箱"
                    >
                    <div class="error-message" id="username-error"></div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        required 
                        autocomplete="current-password"
                        placeholder="请输入密码"
                    >
                    <div class="error-message" id="password-error"></div>
                </div>

                <button type="submit" class="login-button" id="login-button">
                    <span class="button-spinner"></span>
                    <span class="button-text">登录</span>
                </button>
            </form>
        </div>

        <div class="success-container" id="success-container">
            <div class="success-icon"></div>
            <div class="success-title">登录成功！</div>
            <div class="success-message">
                您已成功登录，桌面端将自动跳转。<br>
                如果没有自动跳转，请返回桌面端页面。
            </div>
        </div>

        <div class="footer-text">
            安全提示：请确保在安全的网络环境下登录
        </div>
    </div>

    <script>
        class MobileLoginForm {
            constructor() {
                this.form = document.getElementById('mobile-login-form');
                this.loginButton = document.getElementById('login-button');
                this.errorAlert = document.getElementById('error-alert');
                this.errorMessage = document.getElementById('error-message');
                this.successContainer = document.getElementById('success-container');
                this.loginForm = document.getElementById('login-form');
                
                this.usernameInput = document.getElementById('username');
                this.passwordInput = document.getElementById('password');
                this.usernameError = document.getElementById('username-error');
                this.passwordError = document.getElementById('password-error');
                
                this.token = this.getTokenFromUrl();
                this.isSubmitting = false;
                
                this.init();
            }
            
            init() {
                if (!this.token) {
                    this.showError('无效的登录链接，请重新扫码');
                    return;
                }
                
                this.bindEvents();
                this.focusFirstInput();
            }
            
            getTokenFromUrl() {
                const pathParts = window.location.pathname.split('/');
                return pathParts[pathParts.length - 1];
            }
            
            bindEvents() {
                this.form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleSubmit();
                });
                
                // Real-time validation
                this.usernameInput.addEventListener('blur', () => {
                    this.validateUsername();
                });
                
                this.passwordInput.addEventListener('blur', () => {
                    this.validatePassword();
                });
                
                // Clear errors on input
                this.usernameInput.addEventListener('input', () => {
                    this.clearFieldError('username');
                });
                
                this.passwordInput.addEventListener('input', () => {
                    this.clearFieldError('password');
                });
                
                // Handle Enter key
                this.form.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !this.isSubmitting) {
                        this.handleSubmit();
                    }
                });
            }
            
            focusFirstInput() {
                setTimeout(() => {
                    this.usernameInput.focus();
                }, 100);
            }
            
            validateUsername() {
                const username = this.usernameInput.value.trim();
                
                if (!username) {
                    this.showFieldError('username', '请输入用户名或邮箱');
                    return false;
                }
                
                if (username.length < 3) {
                    this.showFieldError('username', '用户名至少需要3个字符');
                    return false;
                }
                
                this.clearFieldError('username');
                return true;
            }
            
            validatePassword() {
                const password = this.passwordInput.value;
                
                if (!password) {
                    this.showFieldError('password', '请输入密码');
                    return false;
                }
                
                if (password.length < 6) {
                    this.showFieldError('password', '密码至少需要6个字符');
                    return false;
                }
                
                this.clearFieldError('password');
                return true;
            }
            
            validateForm() {
                const isUsernameValid = this.validateUsername();
                const isPasswordValid = this.validatePassword();
                
                return isUsernameValid && isPasswordValid;
            }
            
            async handleSubmit() {
                if (this.isSubmitting) return;
                
                this.hideError();
                
                if (!this.validateForm()) {
                    return;
                }
                
                this.setLoading(true);
                
                try {
                    const formData = new FormData(this.form);
                    const credentials = {
                        username: formData.get('username').trim(),
                        password: formData.get('password')
                    };
                    
                    const response = await fetch(`/scan-login/${this.token}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(credentials)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showSuccess();
                    } else {
                        this.handleLoginError(data);
                    }
                    
                } catch (error) {
                    console.error('Login error:', error);
                    this.showError('网络连接异常，请检查网络后重试');
                } finally {
                    this.setLoading(false);
                }
            }
            
            handleLoginError(data) {
                if (data.errors) {
                    // Handle validation errors
                    Object.keys(data.errors).forEach(field => {
                        const errors = data.errors[field];
                        if (errors && errors.length > 0) {
                            this.showFieldError(field, errors[0]);
                        }
                    });
                } else {
                    // Handle general error
                    this.showError(data.message || '登录失败，请检查用户名和密码');
                }
            }
            
            showFieldError(field, message) {
                const input = document.getElementById(field);
                const errorElement = document.getElementById(`${field}-error`);
                
                if (input && errorElement) {
                    input.classList.add('error');
                    errorElement.textContent = message;
                    errorElement.classList.add('show');
                }
            }
            
            clearFieldError(field) {
                const input = document.getElementById(field);
                const errorElement = document.getElementById(`${field}-error`);
                
                if (input && errorElement) {
                    input.classList.remove('error');
                    errorElement.classList.remove('show');
                }
            }
            
            showError(message) {
                this.errorMessage.textContent = message;
                this.errorAlert.classList.add('show');
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    this.hideError();
                }, 5000);
            }
            
            hideError() {
                this.errorAlert.classList.remove('show');
            }
            
            showSuccess() {
                this.loginForm.style.display = 'none';
                this.successContainer.classList.add('show');
                
                // Optional: Auto-close after some time
                setTimeout(() => {
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.close();
                    }
                }, 5000);
            }
            
            setLoading(loading) {
                this.isSubmitting = loading;
                this.loginButton.disabled = loading;
                
                if (loading) {
                    this.loginButton.classList.add('loading');
                } else {
                    this.loginButton.classList.remove('loading');
                }
            }
        }
        
        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            new MobileLoginForm();
        });
        
        // Handle back button
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was loaded from cache, reset form state
                document.getElementById('mobile-login-form').reset();
            }
        });
    </script>
</body>
</html>