<div class="flex items-center justify-center min-h-screen p-4" wire:poll>
    <flux:card class="w-full max-w-sm space-y-6 text-center">
        <div>
            <flux:heading size="xl">扫码登录</flux:heading>
            <flux:text class="mt-1">使用手机扫描下方二维码登录</flux:text>
        </div>

        @if ($this->shouldDisplayQrCode())
            <div class="flex justify-center p-4 bg-white rounded-lg border border-zinc-200 dark:border-zinc-600">
                {!! $qrCode !!}
            </div>
        @else
            @php($placeholder = $this->qrPlaceholder())
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-6 py-8 dark:border-zinc-700 dark:bg-zinc-900/60">
                <div class="mx-auto flex size-20 items-center justify-center rounded-full {{ $placeholder['background'] }}">
                    <flux:icon :name="$placeholder['icon']" variant="outline" class="size-10 {{ $placeholder['color'] }}" />
                </div>

                <div class="mt-5 space-y-2">
                    <flux:heading size="lg">{{ $placeholder['title'] }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $placeholder['description'] }}
                    </flux:text>
                </div>

                @if (!$token->state->equals(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed::class))
                    <div class="mt-5">
                        <flux:button wire:click="refreshQrCode" icon="arrow-path" variant="subtle" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="refreshQrCode">换一个码</span>
                            <span wire:loading wire:target="refreshQrCode">生成中…</span>
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif

        <div class="text-left space-y-1">
            @if ($this->shouldDisplayQrCode())
                <flux:text class="font-medium">登录步骤：</flux:text>
                <ol class="list-decimal list-inside space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                    <li>使用手机打开应用并登录</li>
                    <li>扫描上方二维码</li>
                    <li>在手机上确认登录</li>
                </ol>
            @else
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    当前二维码已不再展示，避免被其他人继续扫描。需要新的登录请求时，请刷新页面重新生成。
                </flux:text>
            @endif
        </div>

        <div class="flex justify-center">
            <flux:badge color="zinc" icon="signal">{{ $token->state->getDescription() }}</flux:badge>
        </div>
    </flux:card>
</div>
