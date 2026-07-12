@php
    $appLocale = app()->getLocale();
    $isRtl = in_array($appLocale, (array) config('languages.rtl', ['ar']), true);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $appLocale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('SIKDS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@600;700&family=Inter:wght@400;500;600;700&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                            class="sikds-avatar transition-shadow hover:ring-2 hover:ring-[#1c398e]/35 hover:ring-offset-2 hover:ring-offset-white focus:outline-none focus:ring-2 focus:ring-[#1c398e]/45 focus:ring-offset-2 focus:ring-offset-white"
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
                            class="hidden absolute end-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl py-1 z-50"
                            role="menu"
                            aria-labelledby="sikds-user-menu-toggle"
                        >
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full text-start px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900"
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

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
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
        <div id="sikds-chatbot-widget" class="fixed bottom-4 end-4 sm:bottom-6 sm:end-6 z-50 flex flex-col items-end">
            <div
                id="sikds-chatbot-panel"
                class="sikds-chatbot-panel hidden w-[min(95vw,440px)] h-[min(82vh,624px)] mb-3 overflow-hidden flex flex-col"
            >
                {{-- Indigo gradient header --}}
                <div class="sikds-chatbot-header shrink-0 flex items-center gap-3 px-4 py-3">
                    <div class="sikds-chatbot-avatar shrink-0">
                        <x-chatbot-mascot variant="glyph" uid="hdr" :width="26" :height="26" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white leading-tight">{{ __('Assistant SIKDS') }}</p>
                        <p class="text-xs text-white/75 leading-tight flex items-center gap-1.5">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            {{ __('En ligne') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            id="sikds-chatbot-clear"
                            class="sikds-chatbot-hbtn"
                            aria-label="{{ __('Nouvelle discussion') }}"
                            title="{{ __('Nouvelle discussion') }}"
                        >
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            id="sikds-chatbot-close"
                            class="sikds-chatbot-hbtn"
                            aria-label="{{ __('Fermer l\'assistant') }}"
                            title="{{ __('Fermer') }}"
                        >
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div id="sikds-chatbot-messages" class="sikds-chatbot-messages flex-1 min-h-0 overflow-y-auto px-4 py-4 space-y-3"></div>

                {{-- Welcome state (cloned into the messages area by JS when the chat is empty). --}}
                <template id="sikds-chatbot-welcome-tpl">
                    <div class="sikds-chat-welcome">
                        <span class="sikds-chat-welcome-bubble" aria-hidden="true">
                            <span></span><span></span><span></span>
                        </span>
                        <div class="sikds-chat-welcome-mascot">
                            <x-chatbot-mascot variant="idle" uid="wel" :width="120" :height="120" />
                        </div>
                        <p class="sikds-chat-welcome-title">{{ __('Bonjour 👋') }}</p>
                        <p class="sikds-chat-welcome-sub">{{ __('Interrogez vos documents indexés. Par où commencer ?') }}</p>
                        <div class="sikds-chat-suggestions">
                            <button type="button" class="sikds-chat-suggestion" data-suggestion="{{ __('Résume les points essentiels du dernier document publié.') }}">
                                <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                <span>{{ __('Résumer un document') }}</span>
                            </button>
                            <button type="button" class="sikds-chat-suggestion" data-suggestion="{{ __('Quelles sont les procédures à suivre pour une demande ?') }}">
                                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                                <span>{{ __('Trouver une procédure') }}</span>
                            </button>
                            <button type="button" class="sikds-chat-suggestion" data-suggestion="{{ __('Quelles sont les exigences de sécurité et de conformité ?') }}">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                <span>{{ __('Sécurité & conformité') }}</span>
                            </button>
                            <button type="button" class="sikds-chat-suggestion" data-suggestion="{{ __('Qui sont les acteurs concernés et leurs responsabilités ?') }}">
                                <i class="fa-solid fa-users" aria-hidden="true"></i>
                                <span>{{ __('Acteurs & rôles') }}</span>
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Typing indicator (cloned into the messages area while the assistant answers). --}}
                <template id="sikds-chatbot-thinking-tpl">
                    <div class="sikds-chat-thinking" role="status" aria-live="polite">
                        <span class="sikds-chat-thinking-dots" aria-hidden="true">
                            <span></span><span></span><span></span>
                        </span>
                        <span class="sr-only">{{ __('Génération…') }}</span>
                    </div>
                </template>

                <div class="sikds-chatbot-composer shrink-0 px-3 py-3">
                    <div class="flex items-end gap-2">
                        <textarea
                            id="sikds-chatbot-input"
                            rows="1"
                            maxlength="500"
                            placeholder="{{ __('Ecrivez votre question...') }}"
                            class="sikds-chatbot-input w-full resize-none px-4 py-2.5 text-sm focus:outline-none"
                        ></textarea>
                        <button
                            type="button"
                            id="sikds-chatbot-send"
                            class="sikds-chatbot-send shrink-0 h-11 w-11 inline-flex items-center justify-center text-white disabled:opacity-60"
                            aria-label="{{ __('Envoyer') }}"
                        >
                            <i class="fa-solid fa-paper-plane text-sm" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button
                type="button"
                id="sikds-chatbot-toggle"
                class="sikds-chatbot-fab h-14 w-14 inline-flex items-center justify-center"
                aria-label="{{ __('Ouvrir l\'assistant') }}"
            >
                <x-chatbot-mascot variant="glyph" uid="fab" :width="56" :height="56" />
            </button>
        </div>

        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            (function () {
                const widget = document.getElementById('sikds-chatbot-widget');
                const panel = document.getElementById('sikds-chatbot-panel');
                const toggle = document.getElementById('sikds-chatbot-toggle');
                const closeBtn = document.getElementById('sikds-chatbot-close');
                const clearBtn = document.getElementById('sikds-chatbot-clear');
                const input = document.getElementById('sikds-chatbot-input');
                const sendBtn = document.getElementById('sikds-chatbot-send');
                const messages = document.getElementById('sikds-chatbot-messages');
                const welcomeTpl = document.getElementById('sikds-chatbot-welcome-tpl');
                const thinkingTpl = document.getElementById('sikds-chatbot-thinking-tpl');

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
                    page: @json(__('Page')),
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
                        ? 'sikds-chat-bubble sikds-chat-bubble--user max-w-[85%] text-sm px-3.5 py-2.5 whitespace-pre-wrap'
                        : 'sikds-chat-bubble sikds-chat-bubble--bot max-w-[85%] text-sm px-3.5 py-2.5 whitespace-pre-wrap';
                    bubble.textContent = text;

                    if (role === 'bot' && Array.isArray(citations) && citations.length > 0) {
                        const details = document.createElement('details');
                        details.className = 'sikds-cites';

                        const summary = document.createElement('summary');
                        summary.className = 'sikds-cites-summary';
                        summary.innerHTML =
                            '<i class="fa-solid fa-chevron-right sikds-cites-chevron" aria-hidden="true"></i>'
                            + '<span>' + escapeHtml(i18n.sources) + '</span>'
                            + '<span class="sikds-cites-count">' + citations.length + '</span>';
                        details.appendChild(summary);

                        const list = document.createElement('div');
                        list.className = 'sikds-cites-list';
                        citations.forEach((c, idx) => {
                            const title = escapeHtml(c.document_title || i18n.document);
                            const page = Number(c.page || 1);
                            const score = Math.round((Number(c.relevance_score || 0)) * 100);
                            const item = document.createElement('div');
                            item.className = 'sikds-cite';
                            item.innerHTML =
                                '<span class="sikds-cite-icon"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>'
                                + '<div class="min-w-0">'
                                +   '<p class="sikds-cite-title">' + (idx + 1) + '. ' + title + '</p>'
                                +   '<div class="sikds-cite-meta">'
                                +     '<span class="sikds-cite-pill"><i class="fa-regular fa-file" aria-hidden="true"></i>' + i18n.page + ' ' + page + '</span>'
                                +     '<span class="sikds-cite-pill"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i>' + score + '%</span>'
                                +   '</div>'
                                + '</div>';
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

                // Welcome screen: big idle mascot + suggested questions, shown until the
                // user asks something. Cloned from the Blade <template> so the SVG mascot
                // lives in markup (not JS strings) and the CSP nonce stays clean.
                function renderWelcome() {
                    if (!welcomeTpl) {
                        appendMessage('bot', seedMessage, []);
                        return;
                    }
                    const node = welcomeTpl.content.cloneNode(true);
                    node.querySelectorAll('[data-suggestion]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            input.value = btn.getAttribute('data-suggestion') || '';
                            autosizeInput();
                            ask();
                        });
                    });
                    messages.appendChild(node);
                }

                function renderHistory() {
                    messages.innerHTML = '';
                    const history = readHistory();
                    const hasConversation = history.some((e) => e && e.role === 'user');
                    if (!hasConversation) {
                        renderWelcome();
                        return;
                    }
                    history.forEach((entry) => {
                        appendMessage(entry.role, entry.text, entry.citations || []);
                    });
                }

                function autosizeInput() {
                    input.style.height = 'auto';
                    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
                }

                let thinkingEl = null;
                function showThinking() {
                    if (!thinkingTpl) return;
                    hideThinking();
                    thinkingEl = thinkingTpl.content.firstElementChild.cloneNode(true);
                    messages.appendChild(thinkingEl);
                    messages.scrollTop = messages.scrollHeight;
                }
                function hideThinking() {
                    if (thinkingEl && thinkingEl.parentNode) {
                        thinkingEl.parentNode.removeChild(thinkingEl);
                    }
                    thinkingEl = null;
                }

                function clearWelcome() {
                    const w = messages.querySelector('.sikds-chat-welcome');
                    if (w) w.remove();
                }

                function setOpen(open) {
                    panel.classList.toggle('hidden', !open);
                    if (widget) widget.classList.toggle('is-open', open);
                    toggle.setAttribute('aria-label', open ? i18n.closeIcon : i18n.openIcon);
                    if (open) {
                        renderHistory();
                        setTimeout(() => input.focus(), 60);
                    }
                }

                async function ask() {
                    const question = (input.value || '').trim();
                    if (question.length < 3) {
                        return;
                    }

                    clearWelcome();
                    appendMessage('user', question, []);
                    pushHistory({ role: 'user', text: question, citations: [] });
                    input.value = '';
                    autosizeInput();
                    sendBtn.disabled = true;
                    showThinking();

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
                        hideThinking();

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
                        hideThinking();
                        const msg = i18n.networkError;
                        appendMessage('bot', msg, []);
                        pushHistory({ role: 'bot', text: msg, citations: [] });
                    } finally {
                        hideThinking();
                        sendBtn.disabled = false;
                        input.focus();
                    }
                }

                input.addEventListener('input', autosizeInput);

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
            'documentDeleted'   => __('Document supprimé.'),
            'serverRedirect'    => __('La requête a été redirigée par le serveur. Vérifiez votre session.'),
            'actionImpossible'  => __('Action impossible.'),
        ];
    @endphp
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        window.i18n = @json($sikdsJsI18n);
    </script>
</body>
</html>
