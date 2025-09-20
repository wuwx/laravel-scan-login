<div class="container">
    <h1 class="title">扫码登录</h1>
    <p class="subtitle">使用手机扫描下方二维码登录</p>
    
    <div class="qr-container">
        @if($status === 'loading')
            <div class="loading">
                <div class="spinner"></div>
                <span>正在生成二维码...</span>
            </div>
        @elseif($qrCode)
            <div class="qr-code">
                {!! $qrCode !!}
            </div>
        @else
            <div class="loading">
                <span>二维码生成失败</span>
            </div>
        @endif
    </div>
    
    <div class="instructions">
        <p>1. 使用手机打开应用并登录</p>
        <p>2. 扫描上方二维码</p>
        <p>3. 在手机上确认登录</p>
    </div>
    
    <div class="status {{ $status }}">
        {{ $statusMessage }}
    </div>
    
    @if($showRefreshButton)
        <button wire:click="refreshQrCode" class="refresh-btn" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="refreshQrCode">刷新二维码</span>
            <span wire:loading wire:target="refreshQrCode">刷新中...</span>
        </button>
    @endif
    
    @if($showDiagnoseButton)
        <button wire:click="diagnose" class="refresh-btn" style="background: #f59e0b;" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="diagnose">诊断问题</span>
            <span wire:loading wire:target="diagnose">诊断中...</span>
        </button>
    @endif
    
    <div class="footer">
        二维码有效期：{{ $config['token_expiry_minutes'] ?? 5 }}分钟
    </div>
</div>

@script
<script>
    let pollingInterval = null;
    
    // Start polling when component is ready
    $wire.on('startPolling', () => {
        startPolling();
    });
    
    // Handle redirects
    $wire.on('redirectTo', (event) => {
        setTimeout(() => {
            window.location.href = event.url;
        }, 1500);
    });
    
    // Show diagnostics
    $wire.on('showDiagnostics', (event) => {
        console.log('Scan Login Diagnostics:', event.diagnostics);
        alert('诊断完成，请查看浏览器控制台获取详细信息');
    });
    
    // Show alerts
    $wire.on('showAlert', (event) => {
        alert(event.message);
    });
    
    function startPolling() {
        stopPolling(); // Clear any existing interval
        
        const pollingIntervalSeconds = @js($config['polling_interval_seconds'] ?? 3);
        const maxDurationMinutes = @js($config['max_polling_duration_minutes'] ?? 10);
        
        pollingInterval = setInterval(() => {
            $wire.checkLoginStatus();
        }, pollingIntervalSeconds * 1000);
        
        // Stop polling after max duration
        setTimeout(() => {
            stopPolling();
            $wire.set('status', 'error');
            $wire.set('statusMessage', '二维码已过期，请刷新');
            $wire.set('showRefreshButton', true);
            $wire.set('isPolling', false);
        }, maxDurationMinutes * 60 * 1000);
    }
    
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }
    
    // Handle page visibility change
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
        } else if ($wire.isPolling) {
            startPolling();
        }
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        stopPolling();
    });
</script>
@endscript