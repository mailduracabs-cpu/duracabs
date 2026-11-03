<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">WhatsApp group booking monitor</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                This control runs through the WhatsApp bridge installed on this computer. Keep the bridge CMD window running.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="http://127.0.0.1:3210"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="fi-btn fi-btn-size-md relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm outline-none transition duration-75 hover:bg-primary-500"
                >
                    Open WhatsApp Control
                </a>
                <button
                    type="button"
                    onclick="document.getElementById('whatsapp-bridge-frame').src = 'http://127.0.0.1:3210?refresh=' + Date.now()"
                    class="fi-btn fi-btn-size-md relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm outline-none transition duration-75 hover:bg-gray-200 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                >
                    Refresh status
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <iframe
                id="whatsapp-bridge-frame"
                src="http://127.0.0.1:3210"
                title="WhatsApp Live Import"
                class="h-[760px] w-full border-0"
                loading="lazy"
            ></iframe>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            If the embedded screen is blocked by the browser, use “Open WhatsApp Control”. The bridge remains private on this computer and is not exposed publicly.
        </p>
    </div>
</x-filament-panels::page>
