<div class="mobile-login-container">
    <header class="mobile-login-header">
        <h1 class="mobile-login-title">扫码登录确认</h1>
        <p class="mobile-login-subtitle">确认在此设备上登录您的账户</p>

        {{ $agent->platform() }}
    </header>

    <div class="mobile-login-actions">
        <button
            type="button"
            class="mobile-login-btn mobile-login-btn--cancel"
            wire:click="cancel"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="cancel">取消</span>
            <span wire:loading wire:target="cancel">取消中...</span>
        </button>

        <button
            type="button"
            class="mobile-login-btn mobile-login-btn--confirm"
            wire:click="consume"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="consume">确认登录</span>
            <span wire:loading wire:target="consume">
                        <span class="mobile-login-spinner"></span>
                        确认中...
                    </span>
        </button>
    </div>

    <footer class="mobile-login-footer">
        安全提示：请确认这是您本人的登录请求，如有疑问请点击取消
    </footer>
</div>
