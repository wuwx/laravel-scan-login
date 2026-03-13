<div>
    {{-- Trigger button --}}
    <button
        type="button"
        wire:click="openModal"
        class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
            {{-- Heroicons v2 outline: qr-code --}}
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5ZM6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
        </svg>
        {{ $triggerLabel }}
    </button>

    {{-- Modal overlay (only rendered when open — closing destroys the widget
         so re-opening always gets a fresh token) --}}
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            {{-- Backdrop — click to close --}}
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                wire:click="closeModal"
                aria-hidden="true"
            ></div>

            {{-- Modal panel --}}
            <div class="relative z-10 w-full max-w-sm">
                {{-- Close button --}}
                <button
                    type="button"
                    wire:click="closeModal"
                    class="absolute -top-3 -right-3 z-20 flex size-8 items-center justify-center rounded-full bg-zinc-900 text-white shadow-md hover:bg-zinc-700 focus:outline-none dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                    aria-label="关闭"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- QR code widget
                     The @if($open) above already controls mounting/unmounting.
                     wire:key ensures Livewire creates a fresh child instance
                     each time the modal is opened. --}}
                <livewire:scan-login.qr-code-login :wire:key="'scan-login-modal-widget'" />
            </div>
        </div>
    @endif
</div>
