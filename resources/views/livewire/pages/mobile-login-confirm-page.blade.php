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
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">登录成功</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    您已确认登录<br>请回到电脑端继续操作
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">此页面可以安全关闭</p>
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
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">已取消登录</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    本次登录请求已取消<br>如需登录请回到电脑端重新扫码
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">此页面可以安全关闭</p>
        </div>
    @elseif ($result === 'token-consumed')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-amber-100 dark:bg-amber-900/30">
                <svg class="size-14 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">二维码已被使用</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    这个登录二维码已经完成登录<br>不能再次扫码确认
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">请回到电脑端刷新后重新生成二维码</p>
        </div>
    @elseif ($result === 'token-cancelled')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-zinc-100 dark:bg-zinc-800/80">
                <svg class="size-14 text-zinc-500 dark:text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">二维码已取消</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    这个登录请求已经取消<br>不能再次扫码确认
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">请回到电脑端重新生成二维码</p>
        </div>
    @elseif ($result === 'token-expired')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-zinc-100 dark:bg-zinc-800/80">
                <svg class="size-14 text-zinc-500 dark:text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 6v6l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">二维码已失效</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    这个登录二维码已经过期<br>请勿继续确认登录
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">请回到电脑端刷新后重新生成二维码</p>
        </div>
    @elseif ($result === 'token-claimed')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-sky-100 dark:bg-sky-900/30">
                <svg class="size-14 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372A3.375 3.375 0 0 0 21 16.125V15a2.25 2.25 0 0 0-.659-1.591l-3.098-3.098A2.25 2.25 0 0 1 16.5 8.72V6.75a2.25 2.25 0 0 0-.659-1.591l-.69-.69A2.25 2.25 0 0 0 13.56 3.75h-1.06a2.25 2.25 0 0 0-1.591.659l-3.098 3.098A2.25 2.25 0 0 0 7.5 9.098v1.969a2.25 2.25 0 0 1-.659 1.591l-1.06 1.06A2.25 2.25 0 0 0 5.25 15.31v.815A3.375 3.375 0 0 0 8.625 19.5c.904 0 1.777-.133 2.625-.372M15 19.128a9.355 9.355 0 0 1-6 0M15 19.128v-.003c0-1.113-.285-2.16-.786-3.072M9 19.125v-.003c0-1.113.285-2.16.786-3.072M15.214 16.05a3.75 3.75 0 0 0-6.428 0" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">二维码已被其他设备领取</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    这个登录请求已经在另一台手机上打开<br>请确认是否为您本人操作
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">如不是本人操作，请回到电脑端刷新二维码</p>
        </div>
    @elseif ($result === 'rate-limit-exceeded')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-red-100 dark:bg-red-900/30">
                <svg class="size-14 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">操作过于频繁</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    您的操作过于频繁<br>请稍后再试
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">为了您的账户安全，我们限制了操作频率</p>
        </div>
    @elseif ($result === 'token-claimed')
        <div class="w-full max-w-xs text-center space-y-6 py-12">
            <div class="flex items-center justify-center mx-auto size-24 rounded-full bg-sky-100 dark:bg-sky-900/30">
                <svg class="size-14 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372A3.375 3.375 0 0 0 21 16.125V15a2.25 2.25 0 0 0-.659-1.591l-3.098-3.098A2.25 2.25 0 0 1 16.5 8.72V6.75a2.25 2.25 0 0 0-.659-1.591l-.69-.69A2.25 2.25 0 0 0 13.56 3.75h-1.06a2.25 2.25 0 0 0-1.591.659l-3.098 3.098A2.25 2.25 0 0 0 7.5 9.098v1.969a2.25 2.25 0 0 1-.659 1.591l-1.06 1.06A2.25 2.25 0 0 0 5.25 15.31v.815A3.375 3.375 0 0 0 8.625 19.5c.904 0 1.777-.133 2.625-.372M15 19.128a9.355 9.355 0 0 1-6 0M15 19.128v-.003c0-1.113-.285-2.16-.786-3.072M9 19.125v-.003c0-1.113.285-2.16.786-3.072M15.214 16.05a3.75 3.75 0 0 0-6.428 0" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">二维码已被其他设备领取</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    这个登录请求已经在另一台手机上打开<br>请确认是否为您本人操作
                </p>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">如不是本人操作，请回到电脑端刷新二维码</p>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl shadow-sm p-8 w-full max-w-sm space-y-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">扫码登录确认</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">请核对以下信息，确认是否在此设备上登录您的账户</p>
            </div>

            @if ($location || $ip)
                <div class="flex items-center gap-2 px-3 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-5 text-zinc-500">
                        {{-- Heroicons v2 outline: map-pin --}}
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0z" />
                    </svg>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        登录地点：{{ $location ? $location : '未知' }}
                        @if ($ip)
                            <span class="ml-2 text-xs text-zinc-400">({{ $ip }})</span>
                        @endif
                    </p>
                </div>
            @endif

            <div class="flex items-center gap-2 px-3 py-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-5 text-zinc-500">
                    {{-- Heroicons v2 outline: device-phone-mobile --}}
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3" />
                </svg>
                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                    设备：{{ $device ?: '未知设备' }}
                </p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-5 text-zinc-500">
                    {{-- Heroicons v2 outline: computer-desktop --}}
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" />
                </svg>
                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                    系统：{{ $platform ?: '未知系统' }} @if($platformVersion) <span class="ml-1">{{ $platformVersion }}</span> @endif
                </p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 size-5 text-zinc-500">
                    {{-- Heroicons v2 outline: globe-alt --}}
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                    浏览器：{{ $browser ?: '未知浏览器' }} @if($browserVersion) <span class="ml-1">{{ $browserVersion }}</span> @endif
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button" wire:click="cancel" class="flex-1 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">取消</button>
                <button type="button" wire:click="consume" class="flex-1 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">确认登录</button>
            </div>

            <p class="text-xs text-center text-zinc-500 dark:text-zinc-400">
                安全提示：请仔细核对登录地点、设备、系统和浏览器信息，确认是本人操作后再同意登录。如有疑问请点击取消。
            </p>
        </div>
    @endif
</div>
