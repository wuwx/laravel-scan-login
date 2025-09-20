<div class="login-container">
    <div class="login-header">
        <h1>扫码登录确认</h1>
        <p>确认在此设备上登录您的账户</p>
    </div>

    <div class="login-form">
        @if($errorMessage)
            <div class="alert alert-error">
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        @if($status === 'ready' || $status === 'error')
            @if($user)
                <div class="user-info">
                    <div class="user-avatar">
                        <div class="avatar-placeholder">
                            <span>{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ $user->name ?? '用户' }}</div>
                        <div class="user-email">{{ $user->email ?? '' }}</div>
                    </div>
                </div>
            @endif

            <div class="login-info">
                <div class="info-item">
                    <div class="info-icon">🖥️</div>
                    <div class="info-content">
                        <div class="info-title">登录设备</div>
                        <div class="info-value">{{ $deviceInfo }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">🌐</div>
                    <div class="info-content">
                        <div class="info-title">IP地址</div>
                        <div class="info-value">{{ $ipAddress }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">⏰</div>
                    <div class="info-content">
                        <div class="info-title">登录时间</div>
                        <div class="info-value">{{ $loginTime }}</div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button 
                    type="button" 
                    class="cancel-button" 
                    wire:click="cancelLogin"
                    wire:loading.attr="disabled"
                    @if($isSubmitting) disabled @endif
                >
                    <span wire:loading.remove wire:target="cancelLogin">取消</span>
                    <span wire:loading wire:target="cancelLogin">取消中...</span>
                </button>
                
                <button 
                    type="button" 
                    class="confirm-button" 
                    wire:click="confirmLogin"
                    wire:loading.attr="disabled"
                    @if($isSubmitting) disabled @endif
                >
                    <span wire:loading.remove wire:target="confirmLogin">确认登录</span>
                    <span wire:loading wire:target="confirmLogin">
                        <span class="button-spinner"></span>
                        确认中...
                    </span>
                </button>
            </div>
        @elseif($status === 'loading')
            <div class="loading-container">
                <div class="spinner"></div>
                <p>正在加载登录信息...</p>
            </div>
        @elseif($status === 'success')
            <div class="success-container">
                <div class="success-icon"></div>
                <div class="success-title">登录成功！</div>
                <div class="success-message">
                    您已成功登录，桌面端将自动跳转。<br>
                    如果没有自动跳转，请返回桌面端页面。
                </div>
            </div>
        @endif
    </div>

    <div class="footer-text">
        安全提示：请确认这是您本人的登录请求，如有疑问请点击取消
    </div>
</div>

@script
<script>
    // Handle successful login
    $wire.on('loginSuccess', () => {
        setTimeout(() => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.close();
            }
        }, 3000);
    });
    
    // Handle cancelled login
    $wire.on('loginCancelled', () => {
        setTimeout(() => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.close();
            }
        }, 500);
    });
    
    // Handle keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !$wire.isSubmitting) {
            $wire.confirmLogin();
        } else if (e.key === 'Escape' && !$wire.isSubmitting) {
            $wire.cancelLogin();
        }
    });
</script>
@endscript

<style>
    .loading-container {
        text-align: center;
        padding: 40px 20px;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .button-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 8px;
    }
</style>