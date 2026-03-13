<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('scan-login::scan-login.qr_code_page_title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 1; }
            50% { transform: scale(1); opacity: 0.7; }
            100% { transform: scale(0.95); opacity: 1; }
        }
        .qr-pulse {
            animation: pulse-ring 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="flex items-center justify-center min-h-screen p-4" wire:poll.{{ $pollingIntervalMs }}ms>
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ __('scan-login::scan-login.qr_code_page_title') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('scan-login::scan-login.qr_code_page_subtitle') }}
                    </p>
                </div>

                <!-- QR Code Container -->
                <div class="flex justify-center mb-8">
                    <div class="relative">
                        <!-- QR Code with pulse effect -->
                        <div class="qr-pulse bg-white p-4 rounded-2xl shadow-lg">
                            <div class="bg-white p-2 rounded-xl">
                                {!! $qrCode !!}
                            </div>
                        </div>
                        
                        <!-- Corner decorations -->
                        <div class="absolute -top-2 -left-2 w-6 h-6 border-t-4 border-l-4 border-blue-500 rounded-tl-lg"></div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 border-t-4 border-r-4 border-blue-500 rounded-tr-lg"></div>
                        <div class="absolute -bottom-2 -left-2 w-6 h-6 border-b-4 border-l-4 border-blue-500 rounded-bl-lg"></div>
                        <div class="absolute -bottom-2 -right-2 w-6 h-6 border-b-4 border-r-4 border-blue-500 rounded-br-lg"></div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6 mb-6">
                    <ol class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</span>
                            <span class="text-gray-700 dark:text-gray-300 pt-0.5">{{ __('scan-login::scan-login.instructions.step1') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</span>
                            <span class="text-gray-700 dark:text-gray-300 pt-0.5">{{ __('scan-login::scan-login.instructions.step2') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">3</span>
                            <span class="text-gray-700 dark:text-gray-300 pt-0.5">{{ __('scan-login::scan-login.instructions.step3') }}</span>
                        </li>
                    </ol>
                </div>

                <!-- Status -->
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ $token->state->getDescription() }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer tip -->
            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                {{ __('scan-login::scan-login.hints.qr_code_expires', ['minutes' => config('scan-login.token_expiry_minutes', 5)]) }}
            </p>
        </div>
    </div>
</body>
</html>
