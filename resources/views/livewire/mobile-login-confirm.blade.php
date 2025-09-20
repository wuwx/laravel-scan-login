<div class="mobile-login-container">
    <header class="mobile-login-header">
        <h1 class="mobile-login-title">扫码登录确认</h1>
        <p class="mobile-login-subtitle">确认在此设备上登录您的账户</p>
    </header>

    <div class="mobile-login-form">
        @if($errorMessage)
            <div class="mobile-login-alert mobile-login-alert--error">
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        @if($status === 'ready' || $status === 'error')
            @if($user)
                <div class="mobile-login-user-info">
                    <div class="mobile-login-user-avatar">
                        <div class="mobile-login-avatar-placeholder">
                            <span>{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="mobile-login-user-details">
                        <div class="mobile-login-user-name">{{ $user->name ?? '用户' }}</div>
                        <div class="mobile-login-user-email">{{ $user->email ?? '' }}</div>
                    </div>
                </div>
            @endif

            <div class="mobile-login-info">
                <div class="mobile-login-info-item">
                    <div class="mobile-login-info-icon">🖥️</div>
                    <div class="mobile-login-info-content">
                        <div class="mobile-login-info-title">登录设备</div>
                        <div class="mobile-login-info-value">{{ $deviceInfo }}</div>
                    </div>
                </div>
                <div class="mobile-login-info-item">
                    <div class="mobile-login-info-icon">🌐</div>
                    <div class="mobile-login-info-content">
                        <div class="mobile-login-info-title">IP地址</div>
                        <div class="mobile-login-info-value">{{ $ipAddress }}</div>
                    </div>
                </div>
                <div class="mobile-login-info-item">
                    <div class="mobile-login-info-icon">⏰</div>
                    <div class="mobile-login-info-content">
                        <div class="mobile-login-info-title">登录时间</div>
                        <div class="mobile-login-info-value">{{ $loginTime }}</div>
                    </div>
                </div>
            </div>

            <div class="mobile-login-actions">
                <button 
                    type="button" 
                    class="mobile-login-btn mobile-login-btn--cancel" 
                    wire:click="cancelLogin"
                    wire:loading.attr="disabled"
                    @if($isSubmitting) disabled @endif
                >
                    <span wire:loading.remove wire:target="cancelLogin">取消</span>
                    <span wire:loading wire:target="cancelLogin">取消中...</span>
                </button>
                
                <button 
                    type="button" 
                    class="mobile-login-btn mobile-login-btn--confirm" 
                    wire:click="confirmLogin"
                    wire:loading.attr="disabled"
                    @if($isSubmitting) disabled @endif
                >
                    <span wire:loading.remove wire:target="confirmLogin">确认登录</span>
                    <span wire:loading wire:target="confirmLogin">
                        <span class="mobile-login-spinner"></span>
                        确认中...
                    </span>
                </button>
            </div>
        @elseif($status === 'loading')
            <div class="mobile-login-loading">
                <div class="mobile-login-spinner"></div>
                <p>正在加载登录信息...</p>
            </div>
        @elseif($status === 'success')
            <div class="mobile-login-success">
                <div class="mobile-login-success-icon">✓</div>
                <div class="mobile-login-success-title">登录成功！</div>
                <div class="mobile-login-success-message">
                    您已成功登录，桌面端将自动跳转。<br>
                    如果没有自动跳转，请返回桌面端页面。
                </div>
            </div>
        @endif
    </div>

    <footer class="mobile-login-footer">
        安全提示：请确认这是您本人的登录请求，如有疑问请点击取消
    </footer>
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