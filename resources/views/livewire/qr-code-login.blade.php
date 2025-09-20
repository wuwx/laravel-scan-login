<div class="scan-login-container" 
     @if($status === 'pending') wire:poll.{{ $pollingInterval }}="checkLoginStatus" @endif>
    
    <header class="scan-login-header">
        <h1 class="scan-login-title">扫码登录</h1>
        <p class="scan-login-subtitle">使用手机扫描下方二维码登录</p>
    </header>
    
    <div class="scan-login-qr-container">
        @if($status === 'loading')
            <div class="scan-login-loading">
                <div class="scan-login-spinner"></div>
                <span>正在生成二维码...</span>
            </div>
        @elseif($qrCode)
            <div class="scan-login-qr-code">
                {!! $qrCode !!}
            </div>
        @else
            <div class="scan-login-loading">
                <span>二维码生成失败</span>
            </div>
        @endif
    </div>
    
    <div class="scan-login-instructions">
        <ol>
            <li>使用手机打开应用并登录</li>
            <li>扫描上方二维码</li>
            <li>在手机上确认登录</li>
        </ol>
    </div>
    
    <div class="scan-login-status scan-login-status--{{ $status }}">
        {{ $statusMessage }}
    </div>
    
    @if($showRefreshButton)
        <button wire:click="refreshQrCode" class="scan-login-btn scan-login-btn--refresh" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="refreshQrCode">刷新二维码</span>
            <span wire:loading wire:target="refreshQrCode">刷新中...</span>
        </button>
    @endif
    
    @if($showDiagnoseButton)
        <button wire:click="diagnose" class="scan-login-btn scan-login-btn--diagnose" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="diagnose">诊断问题</span>
            <span wire:loading wire:target="diagnose">诊断中...</span>
        </button>
    @endif
    
    <footer class="scan-login-footer">
        二维码有效期：{{ $tokenExpiryMinutes }}分钟
    </footer>

    {{-- 显示诊断信息 --}}
    @if(session('scan_login_diagnostics'))
        <div class="scan-login-diagnostics">
            <h3>诊断信息</h3>
            <pre>{{ json_encode(session('scan_login_diagnostics'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif

    {{-- 显示错误信息 --}}
    @if(session('scan_login_error'))
        <div class="scan-login-alert scan-login-alert--error">
            {{ session('scan_login_error') }}
        </div>
    @endif
</div>