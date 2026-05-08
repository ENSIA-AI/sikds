@php
    $latestUrl = \Illuminate\Support\Facades\Route::has('notifications.latest') ? route('notifications.latest') : '';
    $inboxUrl = \Illuminate\Support\Facades\Route::has('notifications.inbox') ? route('notifications.inbox') : '#';
    $readAllUrl = \Illuminate\Support\Facades\Route::has('notifications.read-all') ? route('notifications.read-all') : '';
@endphp

<div class="relative" id="sikds-notification-bell-wrap">
    <button
        type="button"
        id="sikds-notification-bell-toggle"
        class="sikds-header-notif"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-label="{{ __('Notifications') }}"
    >
        <span class="relative inline-flex">
            <i class="fa-regular fa-bell text-white text-[18px] leading-none" aria-hidden="true"></i>
            <span
                id="sikds-notification-bell-badge"
                class="hidden absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"
                aria-hidden="true"
            ></span>
        </span>
    </button>

    <div
        id="sikds-notification-bell-panel"
        class="hidden absolute right-0 top-full z-50 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] origin-top-right rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden"
        role="menu"
        aria-label="{{ __('Notifications récentes') }}"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-900">{{ __('Notifications') }}</p>
            <button
                type="button"
                id="sikds-notification-bell-mark-all"
                class="hidden text-xs font-medium text-[#1E3A8A] hover:underline disabled:opacity-50"
            >
                {{ __('Tout marquer comme lu') }}
            </button>
        </div>

        <div id="sikds-notification-bell-list" class="max-h-80 overflow-y-auto">
            <div class="px-4 py-10 text-center" id="sikds-notification-bell-empty">
                <i class="fa-regular fa-bell-slash text-2xl text-slate-300"></i>
                <p class="mt-2 text-sm text-slate-500">{{ __('Aucune notification') }}</p>
            </div>
        </div>

        <div class="border-t border-slate-100 bg-slate-50 px-4 py-3 text-center">
            <a href="{{ $inboxUrl }}" class="text-sm font-medium text-[#1E3A8A] hover:underline">
                {{ __('Voir toutes les notifications') }}
            </a>
        </div>
    </div>
</div>

<script>
            function sikdsNotificationBellInit() {
                const wrap = document.getElementById('sikds-notification-bell-wrap');
                const toggle = document.getElementById('sikds-notification-bell-toggle');
                const panel = document.getElementById('sikds-notification-bell-panel');
                const list = document.getElementById('sikds-notification-bell-list');
                const empty = document.getElementById('sikds-notification-bell-empty');
                const badge = document.getElementById('sikds-notification-bell-badge');
                const markAllBtn = document.getElementById('sikds-notification-bell-mark-all');

                if (!wrap || !toggle || !panel || !list || !badge) {
                    return;
                }
                if (toggle.dataset.sikdsBellBound === '1') {
                    return;
                }
                toggle.dataset.sikdsBellBound = '1';

                const latestUrl = @json($latestUrl);
                const inboxUrl = @json($inboxUrl);
                const readAllUrl = @json($readAllUrl);
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                let unreadCount = 0;
                let isOpen = false;
                let fetchedOnce = false;

                function setOpen(open) {
                    isOpen = open;
                    panel.classList.toggle('hidden', !open);
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open && !fetchedOnce) {
                        fetchLatest();
                    } else if (open) {
                        fetchLatest();
                    }
                }

                function setUnread(count) {
                    unreadCount = Math.max(0, Number(count) || 0);
                    badge.classList.toggle('hidden', unreadCount === 0);
                    markAllBtn?.classList.toggle('hidden', unreadCount === 0);
                }

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#39;');
                }

                function renderItems(items) {
                    if (!Array.isArray(items) || items.length === 0) {
                        list.innerHTML = '';
                        list.appendChild(empty);
                        return;
                    }

                    const html = items.map((item) => {
                        const url = escapeHtml(item.url || inboxUrl);
                        const message = escapeHtml(item.message || '');
                        const time = escapeHtml(item.created_at_human || '');
                        const isRead = !!item.read;
                        const dotClass = isRead ? 'bg-transparent' : 'bg-[#1E3A8A]';
                        const textClass = isRead ? 'text-slate-600 font-normal' : 'text-slate-900 font-semibold';
                        const rowBg = isRead ? '' : 'bg-blue-50/50';
                        return '<a href="' + url + '" data-id="' + Number(item.id) + '" data-mark-url="' + escapeHtml(item.mark_read_url || '') + '"' +
                            ' class="sikds-notification-bell-row flex gap-3 px-4 py-3 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors cursor-pointer ' + rowBg + '">' +
                            '<span class="mt-1.5 inline-block h-2 w-2 rounded-full shrink-0 ' + dotClass + '" aria-hidden="true"></span>' +
                            '<div class="flex-1 min-w-0">' +
                                '<p class="text-sm leading-snug ' + textClass + '">' + message + '</p>' +
                                '<p class="text-xs text-slate-500 mt-1">' + time + '</p>' +
                            '</div>' +
                        '</a>';
                    }).join('');

                    list.innerHTML = html;

                    list.querySelectorAll('.sikds-notification-bell-row').forEach((row) => {
                        row.addEventListener('click', (event) => {
                            event.preventDefault();
                            const markUrl = row.getAttribute('data-mark-url') || '';
                            const target = row.getAttribute('href') || inboxUrl;
                            if (markUrl) {
                                fetch(markUrl, {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                    },
                                }).catch(() => {}).finally(() => {
                                    window.location.href = target;
                                });
                            } else {
                                window.location.href = target;
                            }
                        });
                    });
                }

                async function fetchLatest() {
                    if (!latestUrl) return;
                    try {
                        const response = await fetch(latestUrl, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) return;
                        const payload = await response.json();
                        fetchedOnce = true;
                        setUnread(payload.unread_count || 0);
                        renderItems(Array.isArray(payload.data) ? payload.data : []);
                    } catch (e) {
                        // ignore
                    }
                }

                async function markAllRead() {
                    if (!readAllUrl || unreadCount === 0) return;
                    try {
                        const response = await fetch(readAllUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                        });
                        if (response.ok) {
                            setUnread(0);
                            fetchLatest();
                        }
                    } catch (e) {
                        // ignore
                    }
                }

                toggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    setOpen(!isOpen);
                });

                markAllBtn?.addEventListener('click', function (event) {
                    event.stopPropagation();
                    markAllRead();
                });

                document.addEventListener('click', function (event) {
                    if (!isOpen) return;
                    if (panel.contains(event.target) || toggle.contains(event.target)) return;
                    setOpen(false);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && isOpen) {
                        setOpen(false);
                    }
                });

                // Initial unread fetch (silent, doesn't open the panel)
                fetchLatest();
                setInterval(fetchLatest, 60000);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', sikdsNotificationBellInit);
            } else {
                sikdsNotificationBellInit();
            }
        </script>
