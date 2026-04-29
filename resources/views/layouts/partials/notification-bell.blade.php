@php
    $latestUrl = \Illuminate\Support\Facades\Route::has('notifications.latest') ? route('notifications.latest') : '';
    $inboxUrl = \Illuminate\Support\Facades\Route::has('notifications.inbox') ? route('notifications.inbox') : '#';
@endphp

<div
    class="relative"
    x-data="sikdsNotificationBell({ latestUrl: @js($latestUrl), inboxUrl: @js($inboxUrl) })"
    x-init="init()"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="sikds-header-notif"
        aria-haspopup="menu"
        :aria-expanded="open ? 'true' : 'false'"
        aria-label="Notifications"
        @click="toggle()"
    >
        <span class="relative inline-flex">
            <img src="/bell.svg" alt="" class="h-5 w-5">
            <template x-if="unreadCount > 0">
                <span
                    class="absolute -top-1 -right-1 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white"
                    x-text="unreadCount > 9 ? '9+' : unreadCount"
                ></span>
            </template>
        </span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        @click.outside="open = false"
        style="display: none;"
        class="absolute right-0 top-full z-50 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] origin-top-right rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden"
        role="menu"
        aria-label="Notifications récentes"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-900">Notifications</p>
            <button
                type="button"
                class="text-xs font-medium text-[#1E3A8A] hover:underline disabled:opacity-50"
                :disabled="unreadCount === 0 || marking"
                x-show="unreadCount > 0"
                @click="markAllRead()"
            >
                Tout marquer comme lu
            </button>
        </div>

        <div class="max-h-80 overflow-y-auto">
            <template x-if="loading && items.length === 0">
                <div class="px-4 py-6 text-center text-sm text-slate-500">Chargement...</div>
            </template>

            <template x-if="!loading && items.length === 0">
                <div class="px-4 py-10 text-center">
                    <i class="fa-regular fa-bell-slash text-2xl text-slate-300"></i>
                    <p class="mt-2 text-sm text-slate-500">Aucune notification</p>
                </div>
            </template>

            <template x-for="item in items" :key="item.id">
                <a
                    :href="item.url"
                    @click.prevent="openItem(item)"
                    class="flex gap-3 px-4 py-3 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors cursor-pointer"
                    :class="{ 'bg-blue-50/50': !item.read }"
                >
                    <span
                        class="mt-1.5 inline-block h-2 w-2 rounded-full shrink-0"
                        :class="item.read ? 'bg-transparent' : 'bg-[#1E3A8A]'"
                        aria-hidden="true"
                    ></span>
                    <div class="flex-1 min-w-0">
                        <p
                            class="text-sm leading-snug"
                            :class="item.read ? 'text-slate-600 font-normal' : 'text-slate-900 font-semibold'"
                            x-text="item.message"
                        ></p>
                        <p class="text-xs text-slate-500 mt-1" x-text="item.created_at_human"></p>
                    </div>
                </a>
            </template>
        </div>

        <div class="border-t border-slate-100 bg-slate-50 px-4 py-3 text-center">
            <a
                :href="inboxUrl"
                class="text-sm font-medium text-[#1E3A8A] hover:underline"
            >
                Voir toutes les notifications
            </a>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function sikdsNotificationBell(config) {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                return {
                    open: false,
                    loading: false,
                    marking: false,
                    items: [],
                    unreadCount: 0,
                    latestUrl: config.latestUrl,
                    inboxUrl: config.inboxUrl,
                    refreshTimer: null,

                    init() {
                        if (!this.latestUrl) return;
                        this.fetchLatest(true);
                        this.refreshTimer = window.setInterval(() => this.fetchLatest(true), 60000);
                    },

                    async fetchLatest(silent = false) {
                        if (!this.latestUrl) return;
                        if (!silent) this.loading = true;
                        try {
                            const response = await fetch(this.latestUrl, {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!response.ok) return;
                            const payload = await response.json();
                            this.items = Array.isArray(payload.data) ? payload.data : [];
                            this.unreadCount = Number(payload.unread_count || 0);
                            if (payload.inbox_url) this.inboxUrl = payload.inbox_url;
                        } catch (err) {
                            // ignore network errors
                        } finally {
                            if (!silent) this.loading = false;
                        }
                    },

                    toggle() {
                        this.open = !this.open;
                        if (this.open) this.fetchLatest();
                    },

                    async openItem(item) {
                        try {
                            await fetch(item.mark_read_url, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                },
                            });
                        } catch (err) {
                            // ignore — we will still navigate
                        }
                        window.location.href = item.url;
                    },

                    async markAllRead() {
                        if (!this.unreadCount || this.marking) return;
                        this.marking = true;
                        try {
                            const response = await fetch('/notifications/read-all', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                },
                            });
                            if (response.ok) {
                                this.unreadCount = 0;
                                this.items = this.items.map((i) => ({ ...i, read: true }));
                            }
                        } catch (err) {
                            // noop
                        } finally {
                            this.marking = false;
                        }
                    },
                };
            }
            window.sikdsNotificationBell = sikdsNotificationBell;
        </script>
    @endpush
@endonce
