<div class="flex items-center justify-center min-h-screen bg-zinc-50 dark:bg-zinc-900 p-4">
    @if ($result === 'login-approved')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            {{-- 大图标区域 --}}
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-green-100 dark:bg-green-900/30">
                <svg class="size-14 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
            </div>

            {{-- 文字区域 --}}
            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">登录成功</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    您已确认登录<br>请回到电脑端继续操作
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">此页面可以安全关闭</flux:text>
        </div>
    @elseif ($result === 'login-cancelled')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            {{-- 大图标区域 --}}
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-transparent">
                <svg class="size-14 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
            </div>

            {{-- 文字区域 --}}
            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">已取消登录</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    本次登录请求已取消<br>如需登录请回到电脑端重新扫码
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">此页面可以安全关闭</flux:text>
        </div>
    @elseif ($result === 'token-consumed')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-amber-100 dark:bg-amber-900/30">
                <svg class="size-14 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>

            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">二维码已被使用</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    这个登录二维码已经完成登录<br>不能再次扫码确认
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">请回到电脑端刷新后重新生成二维码</flux:text>
        </div>
    @elseif ($result === 'token-cancelled')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-zinc-100 dark:bg-zinc-800/80">
                <svg class="size-14 text-zinc-500 dark:text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
            </div>

            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">二维码已取消</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    这个登录请求已经取消<br>不能再次扫码确认
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">请回到电脑端重新生成二维码</flux:text>
        </div>
    @elseif ($result === 'token-expired')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-zinc-100 dark:bg-zinc-800/80">
                <svg class="size-14 text-zinc-500 dark:text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 6v6l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
            </div>

            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">二维码已失效</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    这个登录二维码已经过期<br>请勿继续确认登录
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">请回到电脑端刷新后重新生成二维码</flux:text>
        </div>
    @elseif ($result === 'token-claimed')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-sky-100 dark:bg-sky-900/30">
                <svg class="size-14 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372A3.375 3.375 0 0 0 21 16.125V15a2.25 2.25 0 0 0-.659-1.591l-3.098-3.098A2.25 2.25 0 0 1 16.5 8.72V6.75a2.25 2.25 0 0 0-.659-1.591l-.69-.69A2.25 2.25 0 0 0 13.56 3.75h-1.06a2.25 2.25 0 0 0-1.591.659l-3.098 3.098A2.25 2.25 0 0 0 7.5 9.098v1.969a2.25 2.25 0 0 1-.659 1.591l-1.06 1.06A2.25 2.25 0 0 0 5.25 15.31v.815A3.375 3.375 0 0 0 8.625 19.5c.904 0 1.777-.133 2.625-.372M15 19.128a9.355 9.355 0 0 1-6 0M15 19.128v-.003c0-1.113-.285-2.16-.786-3.072M9 19.125v-.003c0-1.113.285-2.16.786-3.072M15.214 16.05a3.75 3.75 0 0 0-6.428 0" />
                </svg>
            </div>

            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">二维码已被其他设备领取</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    这个登录请求已经在另一台手机上打开<br>请确认是否为您本人操作
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">如不是本人操作，请回到电脑端刷新二维码</flux:text>
        </div>
    @elseif ($result === 'rate-limit-exceeded')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-red-100 dark:bg-red-900/30">
                <svg class="size-14 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>

            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">操作过于频繁</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    您的操作过于频繁<br>请稍后再试
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">为了您的账户安全，我们限制了操作频率</flux:text>
        </div>
    @elseif ($result === 'token-claimed')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-sky-100 dark:bg-sky-900/30">
                <svg class="size-14 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372A3.375 3.375 0 0 0 21 16.125V15a2.25 2.25 0 0 0-.659-1.591l-3.098-3.098A2.25 2.25 0 0 1 16.5 8.72V6.75a2.25 2.25 0 0 0-.659-1.591l-.69-.69A2.25 2.25 0 0 0 13.56 3.75h-1.06a2.25 2.25 0 0 0-1.591.659l-3.098 3.098A2.25 2.25 0 0 0 7.5 9.098v1.969a2.25 2.25 0 0 1-.659 1.591l-1.06 1.06A2.25 2.25 0 0 0 5.25 15.31v.815A3.375 3.375 0 0 0 8.625 19.5c.904 0 1.777-.133 2.625-.372M15 19.128a9.355 9.355 0 0 1-6 0M15 19.128v-.003c0-1.113-.285-2.16-.786-3.072M9 19.125v-.003c0-1.113.285-2.16.786-3.072M15.214 16.05a3.75 3.75 0 0 0-6.428 0" />
                </svg>
            </div>

            <div class="space-y-2">
                <flux:heading size="xl" class="text-2xl font-semibold">二维码已被其他设备领取</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    这个登录请求已经在另一台手机上打开<br>请确认是否为您本人操作
                </flux:text>
            </div>

            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">如不是本人操作，请回到电脑端刷新二维码</flux:text>
        </div>
    @else
        <flux:card class="w-full max-w-sm space-y-6">
            <div>
                <flux:heading size="xl">扫码登录确认</flux:heading>
                <flux:text class="mt-1">请核对以下信息，确认是否在此设备上登录您的账户</flux:text>
            </div>

            @if ($location || $ip)
                <div class="flex items-center gap-2 px-3 py-2">
                    <flux:icon name="map-pin" variant="outline" class="shrink-0 size-5 text-zinc-500" />
                    <flux:text class="text-sm">
                        登录地点：{{ $location ? $location : '未知' }}
                        @if ($ip)
                            <span class="ml-2 text-xs text-zinc-400">({{ $ip }})</span>
                        @endif
                    </flux:text>
                </div>
            @endif

            <div class="flex items-center gap-2 px-3 py-2">
                <flux:icon name="device-phone-mobile" variant="outline" class="shrink-0 size-5 text-zinc-500" />
                <flux:text class="text-sm">
                    设备：{{ $device ?: '未知设备' }}
                </flux:text>
            </div>
            <div class="flex items-center gap-2 px-3 py-2">
                <flux:icon name="computer-desktop" variant="outline" class="shrink-0 size-5 text-zinc-500" />
                <flux:text class="text-sm">
                    系统：{{ $platform ?: '未知系统' }} @if($platformVersion) <span class="ml-1">{{ $platformVersion }}</span> @endif
                </flux:text>
            </div>
            <div class="flex items-center gap-2 px-3 py-2">
                <flux:icon name="globe-alt" variant="outline" class="shrink-0 size-5 text-zinc-500" />
                <flux:text class="text-sm">
                    浏览器：{{ $browser ?: '未知浏览器' }} @if($browserVersion) <span class="ml-1">{{ $browserVersion }}</span> @endif
                </flux:text>
            </div>

            <div class="flex gap-3">
                <flux:button wire:click="cancel" variant="ghost" class="flex-1">取消</flux:button>
                <flux:button wire:click="consume" variant="primary" class="flex-1">确认登录</flux:button>
            </div>

            <flux:text class="text-xs text-center text-zinc-500 dark:text-zinc-400">
                安全提示：请仔细核对登录地点、设备、系统和浏览器信息，确认是本人操作后再同意登录。如有疑问请点击取消。
            </flux:text>
        </flux:card>
    @endif
</div>
