@php
    $appLocale = app()->getLocale();
    $isRtl = in_array($appLocale, (array) config('languages.rtl', ['ar']), true);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $appLocale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('SIKDS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="sikds-app-body">
    @php
        $pageTitle = html_entity_decode(trim($__env->yieldContent('page_title')), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: __('Tableau de Bord');
        $pageSubtitle = html_entity_decode(trim($__env->yieldContent('page_subtitle')), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: __("Aperçu de l'activité du système SIKDS");
        $authUser = auth()->user();
        $userRole = $authUser?->roles?->pluck('name')->first() ?? __('Utilisateur');
        $userName = $authUser?->full_name ?? $authUser?->name ?? $authUser?->email ?? __('Utilisateur');
        $canUseRagAssistant = $authUser?->can('rag.query') ?? false;
    @endphp

    <div class="sikds-decor-wrap" aria-hidden="true">
        <img src="/dashboard-decor.svg" alt="" width="812" height="1401" class="sikds-decor-img">
    </div>

    @include('layouts.partials.sidebar', ['activeNav' => $activeNav ?? 'dashboard'])

    <div class="sikds-main-wrap">
        <header class="sikds-top-header">
            <div class="sikds-header-inner">

                <div class="sikds-header-title">
                    <h1 class="sikds-page-title">{{ $pageTitle }}</h1>
                    <p class="sikds-page-subtitle">{{ $pageSubtitle }}</p>
                </div>

                <button
                    type="button"
                    class="sikds-menu-button"
                    id="sikds-sidebar-toggle"
                    aria-controls="sikds-sidebar"
                    aria-expanded="false"
                    aria-label="{{ __('Ouvrir le menu latéral') }}"
                >
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </button>

                <div class="sikds-profile" aria-label="{{ __('Informations du profil') }}">
                    <div class="sikds-user-meta">
                        <p class="sikds-user-role">{{ $userRole }}</p>
                        <p class="sikds-user-name">{{ $userName }}</p>
                    </div>
                    @include('layouts.partials.language-switcher')
                    @include('layouts.partials.notification-bell')
                    <div class="relative">
                        <button
                            type="button"
                            id="sikds-user-menu-toggle"
                            class="sikds-avatar transition-shadow hover:ring-2 hover:ring-[#1E3A8A]/35 hover:ring-offset-2 hover:ring-offset-white focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/45 focus:ring-offset-2 focus:ring-offset-white"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            aria-controls="sikds-user-menu"
                            aria-label="{{ __('Ouvrir le menu utilisateur') }}"
                        >
                            <i class="fa-regular fa-user text-white text-[18px] leading-none" aria-hidden="true"></i>
                            <span class="sr-only">{{ __('Profil') }}</span>
                        </button>
                        <div
                            id="sikds-user-menu"
                            class="hidden absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl py-1 z-50"
                            role="menu"
                            aria-labelledby="sikds-user-menu-toggle"
                        >
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full text-left px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900"
                                    role="menuitem"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                                        {{ __('Se déconnecter') }}
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <main>
            <div class="sikds-main-inner">
                @yield('content')
            </div>
        </main>
    </div>

    <button
        type="button"
        class="sikds-sidebar-overlay"
        id="sikds-sidebar-overlay"
        aria-label="{{ __('Fermer le menu latéral') }}"
    ></button>

    <script>
        (function () {
            const toggle = document.getElementById('sikds-user-menu-toggle');
            const menu = document.getElementById('sikds-user-menu');

            if (!toggle || !menu) {
                return;
            }

            function setOpen(open) {
                menu.classList.toggle('hidden', !open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                setOpen(menu.classList.contains('hidden'));
            });

            document.addEventListener('click', function (event) {
                if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                    setOpen(false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setOpen(false);
                }
            });
        })();
    </script>

    @if ($canUseRagAssistant)
        <div id="sikds-chatbot-widget" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col items-end">
            <div
                id="sikds-chatbot-panel"
                class="hidden w-[min(96vw,390px)] h-[min(68vh,520px)] mb-3 rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden flex flex-col"
            >
                <div class="shrink-0 flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ __('Assistant') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Posez une question sans quitter la page.') }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            id="sikds-chatbot-clear"
                            class="h-8 px-2 inline-flex items-center justify-center rounded-md text-xs text-slate-500 hover:bg-slate-200"
                            aria-label="{{ __('Effacer la conversation') }}"
                        >
                            {{ __('Effacer') }}
                        </button>
                        <button
                            type="button"
                            id="sikds-chatbot-close"
                            class="h-8 w-8 inline-flex items-center justify-center rounded-md text-slate-500 hover:bg-slate-200"
                            aria-label="{{ __('Fermer l\'assistant') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div id="sikds-chatbot-messages" class="flex-1 min-h-0 overflow-y-auto px-4 py-3 space-y-3 bg-white"></div>

                <div class="shrink-0 border-t border-slate-200 px-3 py-3 bg-slate-50">
                    <div class="flex items-end gap-2">
                        <textarea
                            id="sikds-chatbot-input"
                            rows="1"
                            maxlength="500"
                            placeholder="{{ __('Ecrivez votre question...') }}"
                            class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"
                        ></textarea>
                        <button
                            type="button"
                            id="sikds-chatbot-send"
                            class="h-10 px-3 rounded-xl bg-[#1E3A8A] text-white text-sm font-medium hover:bg-[#163171] disabled:opacity-60"
                        >
                            {{ __('Envoyer') }}
                        </button>
                    </div>
                </div>
            </div>

            <button
                type="button"
                id="sikds-chatbot-toggle"
                class="h-14 w-14 rounded-full bg-[#1E3A8A] text-white shadow-xl hover:bg-[#163171] inline-flex items-center justify-center"
                aria-label="{{ __('Ouvrir l\'assistant') }}"
            >
                <i class="fa-regular fa-comments text-white text-lg leading-none" aria-hidden="true"></i>
            </button>
        </div>

        <script>
            (function () {
                const panel = document.getElementById('sikds-chatbot-panel');
                const toggle = document.getElementById('sikds-chatbot-toggle');
                const closeBtn = document.getElementById('sikds-chatbot-close');
                const clearBtn = document.getElementById('sikds-chatbot-clear');
                const input = document.getElementById('sikds-chatbot-input');
                const sendBtn = document.getElementById('sikds-chatbot-send');
                const messages = document.getElementById('sikds-chatbot-messages');

                if (!panel || !toggle || !input || !sendBtn || !messages) {
                    return;
                }

                const endpoint = @json(route('rag.query'));
                const csrf = @json(csrf_token());
                const userKey = @json((string) ($authUser?->id ?? 'guest'));
                const storageKey = 'sikds-chatbot-history-v2-' + userKey;
                const i18n = {
                    seedMessage: @json(__('Bonjour. Je peux vous aider a retrouver des informations dans les documents indexes.')),
                    closeIcon: @json(__('Fermer l assistant')),
                    openIcon: @json(__('Ouvrir l assistant')),
                    sources: @json(__('Sources')),
                    document: @json(__('Document')),
                    queryDenied: @json(__('Acces refuse. Permission rag.query requise.')),
                    queryError: @json(__('Erreur lors de la requete.')),
                    insufficientContext: @json(__('Je n ai pas de contexte suffisant pour repondre avec fiabilite.')),
                    noAnswer: @json(__('Aucune reponse disponible.')),
                    networkError: @json(__('Erreur reseau. Veuillez reessayer.')),
                    citationMeta: @json(__('Section: :section | Page :page | Score :score%')),
                };
                const seedMessage = i18n.seedMessage;

                // Migrate / cleanup any pre-user-scoped legacy key so old chats from
                // a previous account on this browser are not visible to the current one.
                try { sessionStorage.removeItem('sikds-chatbot-history-v1'); } catch (e) {}

                function escapeHtml(value) {
                    return String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#39;');
                }

                function appendMessage(role, text, citations = []) {
                    const row = document.createElement('div');
                    row.className = role === 'user'
                        ? 'flex justify-end'
                        : 'flex justify-start';

                    const bubble = document.createElement('div');
                    bubble.className = role === 'user'
                        ? 'max-w-[85%] rounded-xl bg-[#1E3A8A] text-white text-sm px-3 py-2 whitespace-pre-wrap'
                        : 'max-w-[85%] rounded-xl bg-slate-100 text-slate-700 text-sm px-3 py-2 whitespace-pre-wrap';
                    bubble.textContent = text;

                    if (role === 'bot' && Array.isArray(citations) && citations.length > 0) {
                        const details = document.createElement('details');
                        details.className = 'mt-2 rounded-lg border border-slate-200 bg-white text-slate-700';

                        const summary = document.createElement('summary');
                        summary.className = 'cursor-pointer px-2 py-1 text-xs font-medium text-slate-600';
                        summary.textContent = `${i18n.sources} (${citations.length})`;
                        details.appendChild(summary);

                        const list = document.createElement('div');
                        list.className = 'space-y-2 px-2 pb-2';
                        citations.forEach((c, idx) => {
                            const title = escapeHtml(c.document_title || i18n.document);
                            const section = escapeHtml(c.section_heading || '-');
                            const page = Number(c.page || 1);
                            const score = Math.round((Number(c.relevance_score || 0)) * 100);
                            const item = document.createElement('div');
                            item.className = 'rounded-md border border-slate-200 px-2 py-1 text-xs';
                            const meta = i18n.citationMeta
                                .replace(':section', section)
                                .replace(':page', page)
                                .replace(':score', score);
                            item.innerHTML = `<p class="font-medium text-slate-800">${idx + 1}. ${title}</p>
                                <p class="text-slate-500">${meta}</p>`;
                            list.appendChild(item);
                        });

                        details.appendChild(list);
                        bubble.appendChild(details);
                    }

                    row.appendChild(bubble);
                    messages.appendChild(row);
                    messages.scrollTop = messages.scrollHeight;
                }

                function readHistory() {
                    try {
                        const raw = sessionStorage.getItem(storageKey);
                        if (!raw) return [];
                        const parsed = JSON.parse(raw);
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (error) {
                        return [];
                    }
                }

                function writeHistory(history) {
                    try {
                        sessionStorage.setItem(storageKey, JSON.stringify(history));
                    } catch (error) {
                        // now we ignore storage failures, will be handled later
                    }
                }

                function pushHistory(entry) {
                    const stamped = Object.assign({ ts: Date.now() }, entry);
                    const history = readHistory();
                    history.push(stamped);
                    writeHistory(history.slice(-30));
                    try {
                        window.dispatchEvent(new CustomEvent('sikds:chat-updated'));
                    } catch (e) {}
                }

                function renderHistory() {
                    messages.innerHTML = '';
                    const history = readHistory();
                    if (history.length === 0) {
                        appendMessage('bot', seedMessage, []);
                        writeHistory([{ role: 'bot', text: seedMessage, citations: [] }]);
                        return;
                    }
                    history.forEach((entry) => {
                        appendMessage(entry.role, entry.text, entry.citations || []);
                    });
                }

                function setOpen(open) {
                    panel.classList.toggle('hidden', !open);
                    toggle.innerHTML = open
                        ? '<i class="fa-solid fa-xmark text-white text-xl leading-none"></i>'
                        : '<i class="fa-regular fa-comments text-white text-lg leading-none"></i>';
                    toggle.setAttribute('aria-label', open ? i18n.closeIcon : i18n.openIcon);
                    if (open) {
                        renderHistory();
                        input.focus();
                    }
                }

                async function ask() {
                    const question = (input.value || '').trim();
                    if (question.length < 3) {
                        return;
                    }

                    appendMessage('user', question, []);
                    pushHistory({ role: 'user', text: question, citations: [] });
                    input.value = '';
                    sendBtn.disabled = true;

                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({ question }),
                            credentials: 'same-origin',
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            if (response.status === 403) {
                                const msg = i18n.queryDenied;
                                appendMessage('bot', msg, []);
                                pushHistory({ role: 'bot', text: msg, citations: [] });
                            } else {
                                const msg = payload.message || i18n.queryError;
                                appendMessage('bot', msg, []);
                                pushHistory({ role: 'bot', text: msg, citations: [] });
                            }
                            return;
                        }

                        const answer = String(payload.answer || '').trim();
                        const citations = Array.isArray(payload.citations) ? payload.citations : [];
                        const finalAnswer = answer === 'INSUFFICIENT_CONTEXT'
                            ? i18n.insufficientContext
                            : (answer || i18n.noAnswer);
                        appendMessage('bot', finalAnswer, citations);
                        pushHistory({ role: 'bot', text: finalAnswer, citations });
                    } catch (error) {
                        const msg = i18n.networkError;
                        appendMessage('bot', msg, []);
                        pushHistory({ role: 'bot', text: msg, citations: [] });
                    } finally {
                        sendBtn.disabled = false;
                        input.focus();
                    }
                }

                toggle.addEventListener('click', () => setOpen(panel.classList.contains('hidden')));
                closeBtn?.addEventListener('click', () => setOpen(false));
                clearBtn?.addEventListener('click', () => {
                    writeHistory([]);
                    renderHistory();
                });
                sendBtn.addEventListener('click', ask);
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        ask();
                    }
                });
            })();
        </script>
    @endif

    {{-- Global i18n object for JS files (Path B: French source string + Laravel handles translation). --}}
    {{-- Add new keys here when a JS file needs a translated string; never use __() inside .js files. --}}
    @php
        $sikdsJsI18n = [
            'edit'              => __('Modifier'),
            'genericError'      => __('Une erreur est survenue.'),
            'serverUnreachable' => __('Impossible de contacter le serveur.'),
            'statusActive'      => __('Actif'),
            'statusInactive'    => __('Inactif'),
            'chatbotOpen'       => __("Ouvrir l'assistant"),
            'citationMeta'      => __('Section : :section | Page :page | Score :score%'),
            'noRole'            => __('Aucun rôle pour le moment. Créez un rôle pour commencer.'),
            'noPermission'      => __('Aucune permission pour le moment. Créez une permission pour commencer.'),
            'noUser'            => __('Aucun utilisateur pour le moment. Créez un utilisateur pour commencer.'),
            'permissionsSelectedCount' => __(':count sélectionnées'),
        ];
    @endphp
    <script>
        window.i18n = @json($sikdsJsI18n);
    </script>
</body>
</html>
