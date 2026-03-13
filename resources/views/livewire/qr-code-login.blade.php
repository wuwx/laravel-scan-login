<div wire:poll.{{ $pollingIntervalMs }}ms>
    <div class="w-full max-w-sm space-y-6 text-center bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">扫码登录</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">使用手机扫描下方二维码登录</p>
        </div>

        @if ($this->shouldDisplayQrCode())
            <div class="flex justify-center p-4 bg-white rounded-lg border border-zinc-200 dark:border-zinc-600">
                {!! $qrCode !!}
            </div>
        @else
            @php($placeholder = $this->qrPlaceholder())
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-6 py-8 dark:border-zinc-700 dark:bg-zinc-900/60">
                <div class="mx-auto flex size-20 items-center justify-center rounded-full {{ $placeholder['background'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10 {{ $placeholder['color'] }}">{!! $placeholder['icon_path'] !!}</svg>
                </div>

                <div class="mt-5 space-y-2">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $placeholder['title'] }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $placeholder['description'] }}</p>
                </div>

                @if (!$token->state->equals(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed::class))
                    <div class="mt-5">
                        <button
                            type="button"
                            wire:click="refreshQrCode"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 disabled:opacity-50 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" wire:loading.class="animate-spin" wire:target="refreshQrCode">
                                {{-- Heroicons v2 outline: arrow-path --}}
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span wire:loading.remove wire:target="refreshQrCode">换一个码</span>
                            <span wire:loading wire:target="refreshQrCode">生成中…</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <div class="text-left space-y-1">
            @if ($this->shouldDisplayQrCode())
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">登录步骤：</p>
                <ol class="list-decimal list-inside space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                    <li>使用手机打开应用并登录</li>
                    <li>扫描上方二维码</li>
                    <li>在手机上确认登录</li>
                </ol>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    当前二维码已不再展示，避免被其他人继续扫描。需要新的登录请求时，请刷新页面重新生成。
                </p>
            @endif
        </div>

        <div class="flex justify-center">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                    {{-- Heroicons v2 outline: signal --}}
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 0 1 0-5.303m5.304-.001a3.75 3.75 0 0 1 0 5.304m-7.425 2.122a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.981 0 13.789M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                {{ $token->state->getDescription() }}
            </span>
        </div>
    </div>
</div>
