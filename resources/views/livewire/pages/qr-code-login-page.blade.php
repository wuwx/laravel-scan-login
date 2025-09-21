<div class="scan-login-container" wire:poll>

    <header class="scan-login-header">
        <h1 class="scan-login-title">扫码登录</h1>
        <p class="scan-login-subtitle">使用手机扫描下方二维码登录</p>
    </header>

    <div class="scan-login-qr-container">
        <div class="scan-login-qr-code">
            {!! $qrCode !!}
        </div>
    </div>

    <div class="scan-login-instructions">
        <ol>
            <li>使用手机打开应用并登录</li>
            <li>扫描上方二维码</li>
            <li>在手机上确认登录</li>
        </ol>
    </div>

    <div class="scan-login-status">
        {{ $token->state->getDescription() }}
    </div>
</div>
