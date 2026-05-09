@extends('layouts.app')
@php
    $activeNav = 'chatbot';
@endphp
@section('page_title', __('Assistant'))
@section('page_subtitle', __('Votre assistant documentaire intelligent'))
@section('content')
    <style>
        /* ── Reset & Shell ── */
        .rag-shell {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 220px);
            font-family: 'Söhne', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        /* ── Scroll area ── */
        .rag-chat {
            flex: 1;
            overflow-y: auto;
            padding: 2rem 1rem 1.5rem;
            scroll-behavior: smooth;
        }

        /* ────────────────────────────────────────────
           WELCOME SCREEN
        ──────────────────────────────────────────── */
        .rag-welcome {
            max-width: 680px;
            margin: 3.5rem auto 0;
            text-align: center;
            padding: 0 1rem;
        }

        /* Main heading — typewriter effect */
        .rag-welcome-heading {
            font-size: 1.85rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 .7rem;
            letter-spacing: -.025em;
            line-height: 1.25;
            min-height: 2.4rem;
            opacity: 0;
            animation: rag-fade-up .4s ease forwards .2s;
        }
        .rag-cursor {
            display: inline-block;
            width: 2px;
            height: 1em;
            background: #0f172a;
            border-radius: 1px;
            vertical-align: middle;
            margin-left: 1px;
            animation: rag-blink .6s step-end infinite;
        }
        @keyframes rag-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        /* Subtitle */
        .rag-welcome-sub {
            color: #64748b;
            font-size: .94rem;
            line-height: 1.65;
            margin: 0 auto 2.25rem;
            max-width: 480px;
            opacity: 0;
            animation: rag-fade-up .4s ease forwards .4s;
        }

        /* Prompt cards */
        .rag-prompts {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            max-width: 680px;
            margin: 0 auto;
        }
        .rag-prompt-btn {
            text-align: left;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #374151;
            border-radius: 14px;
            padding: .85rem 1rem .85rem 1rem;
            font-size: .84rem;
            line-height: 1.5;
            font-family: inherit;
            cursor: pointer;
            transition: border-color .15s, background .15s, box-shadow .15s, transform .12s;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        .rag-prompt-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(59,130,246,.04) 0%, transparent 60%);
            opacity: 0;
            transition: opacity .2s;
        }
        .rag-prompt-btn:hover::before { opacity: 1; }
        .rag-prompt-btn:hover {
            border-color: #c7d2fe;
            background: #fafbff;
            box-shadow: 0 2px 12px rgba(59,130,246,.08);
            transform: translateY(-1px);
        }
        .rag-prompt-btn:active { transform: translateY(0); }
        .rag-prompt-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 26px; height: 26px;
            border-radius: 8px;
            background: #f1f5f9;
            color: #475569;
            font-size: 13px;
            margin-bottom: .55rem;
        }
        .rag-prompt-title {
            font-size: .82rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: .2rem;
            display: block;
        }
        .rag-prompt-desc {
            font-size: .78rem;
            color: #64748b;
            line-height: 1.45;
            display: block;
        }
        /* Staggered entrance for prompt cards */
        .rag-prompt-btn:nth-child(1) { animation: rag-fade-up .38s ease forwards .5s; }
        .rag-prompt-btn:nth-child(2) { animation: rag-fade-up .38s ease forwards .6s; }
        .rag-prompt-btn:nth-child(3) { animation: rag-fade-up .38s ease forwards .7s; }
        .rag-prompt-btn:nth-child(4) { animation: rag-fade-up .38s ease forwards .8s; }

        @keyframes rag-fade-up {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ────────────────────────────────────────────
           THREAD
        ──────────────────────────────────────────── */
        .rag-thread {
            max-width: 740px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        /* ── Message rows ── */
        .rag-row {
            display: flex;
            padding: .45rem 0;
            animation: rag-fade-up .28s ease forwards;
        }

        /* User bubble */
        .rag-row--user { justify-content: flex-end; margin-bottom: .2rem; }
        .rag-row--user .rag-message {
            background: #f3f4f6;
            color: #0f172a;
            border-radius: 18px 18px 4px 18px;
            padding: .65rem 1rem;
            max-width: 75%;
            font-size: .93rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Assistant — no bubble */
        .rag-row--assistant { justify-content: flex-start; margin-bottom: .4rem; }
        .rag-row--assistant .rag-message {
            max-width: 100%;
            font-size: .93rem;
            line-height: 1.8;
            color: #0f172a;
            padding: .1rem 0;
        }

        .rag-answer-text {
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* ── Citation sentence highlight ── */
        .rag-sentence {
            border-bottom: 1.5px solid rgba(59, 130, 246, 0.5);
            padding-bottom: 0;
            cursor: default;
            transition: border-color .12s;
        }
        .rag-sentence:hover {
            border-color: #3b82f6;
        }
        .rag-cite-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            font-size: 8px;
            font-weight: 700;
            border-radius: 50%;
            color: #1d4ed8;
            background: #dbeafe;
            margin-left: 3px;
            vertical-align: 2px;
            cursor: default;
            transition: background .12s;
        }
        .rag-sentence:hover .rag-cite-badge {
            background: #bfdbfe;
        }

        /* ────────────────────────────────────────────
           TOOLTIP — redesigned
        ──────────────────────────────────────────── */
        .rag-tooltip {
            position: fixed;
            z-index: 60;
            width: min(320px, calc(100vw - 20px));
            border-radius: 14px;
            background: #0f172a;
            border: 0.5px solid rgba(255, 255, 255, 0.1);
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.36),
                0 2px 8px rgba(0, 0, 0, 0.24),
                inset 0 0.5px 0 rgba(255, 255, 255, 0.08);
            overflow: hidden;
            pointer-events: none;
            opacity: 0;
            transform: translateY(8px) scale(0.97);
            transition: opacity .15s ease, transform .15s ease;
            font-family: inherit;
        }
        .rag-tooltip--visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* Tooltip header */
        .rag-tt-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 13px 9px;
            background: rgba(255, 255, 255, 0.04);
            border-bottom: 0.5px solid rgba(255, 255, 255, 0.07);
        }
        .rag-tt-icon {
            width: 28px; height: 28px;
            border-radius: 7px;
            background: rgba(59, 130, 246, 0.18);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: #60a5fa;
            font-size: 13px;
        }
        .rag-tt-title {
            font-size: 12px; font-weight: 600; color: #f1f5f9;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            line-height: 1.3; min-width: 0;
        }
        .rag-tt-section {
            font-size: 10.5px; color: #64748b;
            line-height: 1.3; margin-top: 1px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* Tooltip meta row */
        .rag-tt-meta {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 13px;
            border-bottom: 0.5px solid rgba(255, 255, 255, 0.06);
        }
        .rag-tt-pill {
            font-size: 10px; font-weight: 500;
            border-radius: 5px; padding: 2px 7px;
            background: rgba(255, 255, 255, 0.07); color: #94a3b8;
            display: flex; align-items: center; gap: 4px;
            white-space: nowrap; flex-shrink: 0;
        }
        .rag-tt-pill i { font-size: 11px; }
        .rag-tt-score {
            display: flex; align-items: center; gap: 6px;
            margin-left: auto; flex: 1; min-width: 0;
        }
        .rag-tt-score-label { font-size: 10px; color: #64748b; white-space: nowrap; }
        .rag-tt-score-track {
            flex: 1; height: 4px; border-radius: 2px;
            background: rgba(255, 255, 255, 0.08);
        }
        .rag-tt-score-fill {
            height: 4px; border-radius: 2px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }
        .rag-tt-score-pct {
            font-size: 10px; font-weight: 600; color: #93c5fd; white-space: nowrap;
        }

        /* Tooltip excerpt */
        .rag-tt-chunk {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            padding: 9px 13px 12px;
            font-size: 11.5px; line-height: 1.65; color: #94a3b8;
        }

        /* ── Sources accordion ── */
        .rag-sources {
            margin-top: .75rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .rag-sources summary {
            list-style: none; cursor: pointer;
            padding: .5rem .75rem;
            font-size: .76rem; color: #475569;
            background: #f8fafc;
        }
        .rag-sources summary::-webkit-details-marker { display: none; }
        .rag-source-item {
            padding: .55rem .75rem;
            border-top: 1px solid #f1f5f9;
            font-size: .78rem; color: #475569;
            background: #fff;
        }
        .rag-source-item strong {
            color: #0f172a; font-weight: 600;
            display: block; margin-bottom: .1rem;
        }

        /* ── Typing dots ── */
        .rag-typing {
            display: inline-flex; align-items: center;
            gap: .3rem; padding: .7rem .1rem;
        }
        .rag-typing span {
            width: 6px; height: 6px; border-radius: 50%;
            background: #94a3b8; opacity: .3;
            animation: rag-typing 1s infinite ease-in-out;
        }
        .rag-typing span:nth-child(2) { animation-delay: .15s; }
        .rag-typing span:nth-child(3) { animation-delay: .3s; }
        @keyframes rag-typing {
            0%, 80%, 100% { transform: translateY(0); opacity: .28; }
            40% { transform: translateY(-3px); opacity: 1; }
        }

        /* ────────────────────────────────────────────
           COMPOSER
        ──────────────────────────────────────────── */
        .rag-composer {
            padding: .75rem 1rem 1.25rem;
            background: transparent;
        }
        .rag-composer-box {
            max-width: 740px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            padding: .6rem .65rem .55rem .9rem;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
            transition: border-color .15s, box-shadow .15s;
        }
        .rag-composer-box:focus-within {
            border-color: #9ca3af;
            box-shadow: 0 1px 8px rgba(15, 23, 42, 0.1);
        }
        .rag-input {
            width: 100%;
            border: 0; outline: none; box-shadow: none;
            -webkit-appearance: none;
            resize: none;
            min-height: 24px; max-height: 180px;
            padding: .25rem 0;
            color: #0f172a; background: transparent;
            line-height: 1.55;
            font-size: .93rem; font-family: inherit;
        }
        .rag-input::placeholder { color: #9ca3af; }
        .rag-input:focus { outline: none; box-shadow: none; }

        .rag-composer-actions {
            display: flex; align-items: center;
            justify-content: space-between;
            gap: .5rem; margin-top: .45rem;
        }
        .rag-left-actions {
            display: flex; align-items: center; gap: .6rem;
        }
        .rag-hint { color: #9ca3af; font-size: .74rem; }

        /* Buttons */
        .rag-btn {
            border: 0; border-radius: 10px;
            padding: .48rem .85rem;
            font-size: .82rem; font-weight: 500;
            font-family: inherit; cursor: pointer;
            transition: background .12s, opacity .12s, transform .1s;
            line-height: 1;
        }
        .rag-btn:disabled { opacity: .45; cursor: not-allowed; }
        .rag-btn:not(:disabled):active { transform: scale(.97); }

        .rag-btn--ghost { color: #475569; background: #f1f5f9; }
        .rag-btn--ghost:not(:disabled):hover { background: #e2e8f0; }

        .rag-btn--primary {
            color: #fff; background: #111827;
            display: flex; align-items: center; gap: .4rem;
        }
        .rag-btn--primary:not(:disabled):hover { background: #1f2937; }

        /* Utility */
        .hidden { display: none !important; }
    </style>

    <section class="rag-shell">
        <div class="rag-chat" id="rag-chat">

            {{-- ── Welcome Screen ── --}}
            <div class="rag-welcome" id="rag-welcome">

                {{-- Typewriter heading --}}
                <h2 class="rag-welcome-heading" id="rag-heading">
                    <span id="rag-typed-text"></span><span class="rag-cursor" id="rag-cursor"></span>
                </h2>

                {{-- Subtitle --}}
                <p class="rag-welcome-sub">
                    {{ __('Je synthétise vos documents indexés et cite chaque source avec précision.') }}
                </p>

                {{-- Prompt cards --}}
                <div class="rag-prompts">
                    <button class="rag-prompt-btn" type="button"
                        data-prompt="{{ __('Résume le cahier des charges en 6 points clés.') }}">
                        <div class="rag-prompt-icon"><i class="fa-solid fa-list-check"></i></div>
                        <span class="rag-prompt-title">{{ __('Points clés') }}</span>
                        <span class="rag-prompt-desc">{{ __('Résumer les points essentiels du cahier des charges') }}</span>
                    </button>
                    <button class="rag-prompt-btn" type="button"
                        data-prompt="{{ __('Quelles sont les exigences non fonctionnelles prioritaires ?') }}">
                        <div class="rag-prompt-icon"><i class="fa-solid fa-gauge-high"></i></div>
                        <span class="rag-prompt-title">{{ __('Exigences non fonct.') }}</span>
                        <span class="rag-prompt-desc">{{ __('Identifier les contraintes de performance et fiabilité') }}</span>
                    </button>
                    <button class="rag-prompt-btn" type="button"
                        data-prompt="{{ __('Liste les acteurs du système et leurs responsabilités.') }}">
                        <div class="rag-prompt-icon"><i class="fa-solid fa-users"></i></div>
                        <span class="rag-prompt-title">{{ __('Acteurs & rôles') }}</span>
                        <span class="rag-prompt-desc">{{ __('Lister les acteurs du système et leurs responsabilités') }}</span>
                    </button>
                    <button class="rag-prompt-btn" type="button"
                        data-prompt="{{ __('Y a-t-il des contraintes de sécurité ou conformité mentionnées ?') }}">
                        <div class="rag-prompt-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <span class="rag-prompt-title">{{ __('Sécurité / conformité') }}</span>
                        <span class="rag-prompt-desc">{{ __('Chercher les contraintes réglementaires et sécuritaires') }}</span>
                    </button>
                </div>
            </div>

            <div class="rag-thread hidden" id="rag-thread"></div>
        </div>

        {{-- ── Composer ── --}}
        <div class="rag-composer">
            <div class="rag-composer-box">
                <textarea
                    id="rag-input"
                    class="rag-input"
                    rows="1"
                    maxlength="500"
                    placeholder="{{ __('Envoyez un message…') }}"
                ></textarea>
                <div class="rag-composer-actions">
                    <div class="rag-left-actions">
                        <button id="rag-new-chat" type="button" class="rag-btn rag-btn--ghost">
                            <i class="fa-solid fa-plus" style="font-size:.75rem;margin-right:.3rem"></i>{{ __('Nouvelle discussion') }}
                        </button>
                        <span class="rag-hint">{{ __('Entrée pour envoyer · Shift+Entrée pour une ligne') }}</span>
                    </div>
                    <button id="rag-send" type="button" class="rag-btn rag-btn--primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        {{ __('Envoyer') }}
                    </button>
                </div>
            </div>
        </div>
    </section>

    <script>
    (function () {
        /* ─── Config ─────────────────────────────────────────────── */
        const endpoint       = @json(route('rag.query'));
        const csrf           = @json(csrf_token());
        const i18n = {
            generating: @json(__('Génération…')),
            send: @json(__('Envoyer')),
            typewriter1: @json(__('Que souhaitez-vous explorer ?')),
            typewriter2: @json(__('Comment puis-je vous aider ?')),
            typewriter3: @json(__('Posez-moi une question sur vos docs.')),
            typewriter4: @json(__('Cherchons ensemble dans vos documents.')),
            serverErrorRetry: @json(__('Erreur serveur. Veuillez réessayer.')),
            insufficientContext: @json(__('Je n\'ai pas assez de contexte fiable dans les documents indexés pour répondre avec précision. Reformulez avec des termes plus exacts ou une section spécifique.')),
            noUsableAnswer: @json(__('Je n\'ai pas pu générer une réponse exploitable cette fois. Réessayez avec une question plus précise.')),
            networkErrorRetry: @json(__('Erreur réseau. Veuillez réessayer.')),
            document: @json(__('Document')),
            sectionUnknown: @json(__('Section non précisée')),
            excerptUnavailable: @json(__('Extrait indisponible.')),
            page: @json(__('Page')),
            relevance: @json(__('Pertinence')),
        };
        const authUserId     = @json((int) auth()->id());
        const legacyKey      = 'sikds-rag-chat-v3';
        const storageKey     = `sikds-rag-chat-v3-user-${authUserId || 'guest'}`;

        /* ─── DOM refs ───────────────────────────────────────────── */
        const inputEl    = document.getElementById('rag-input');
        const sendBtn    = document.getElementById('rag-send');
        const newChatBtn = document.getElementById('rag-new-chat');
        const chatEl     = document.getElementById('rag-chat');
        const welcomeEl  = document.getElementById('rag-welcome');
        const threadEl   = document.getElementById('rag-thread');
        const promptBtns = Array.from(document.querySelectorAll('[data-prompt]'));

        let isLoading = false;

        /* ─── Typewriter welcome animation (loop) ────────────────── */
        (function initTypewriter() {
            const phrases = [
                i18n.typewriter1,
                i18n.typewriter2,
                i18n.typewriter3,
                i18n.typewriter4,
            ];
            const textEl = document.getElementById('rag-typed-text');
            if (!textEl) return;

            let phraseIndex = 0;
            let charIndex   = 0;
            let deleting    = false;

            const TYPE_SPEED   = 48;   // ms per char while typing
            const DELETE_SPEED = 22;   // ms per char while deleting
            const PAUSE_AFTER  = 1800; // ms pause after full phrase is typed
            const PAUSE_BEFORE = 300;  // ms pause before typing next phrase

            function tick() {
                const current = phrases[phraseIndex];

                if (!deleting) {
                    // Typing forward
                    charIndex++;
                    textEl.textContent = current.slice(0, charIndex);
                    if (charIndex === current.length) {
                        // Finished typing — pause then start deleting
                        deleting = true;
                        setTimeout(tick, PAUSE_AFTER);
                        return;
                    }
                    setTimeout(tick, TYPE_SPEED + Math.random() * 18);
                } else {
                    // Deleting backward
                    charIndex--;
                    textEl.textContent = current.slice(0, charIndex);
                    if (charIndex === 0) {
                        // Finished deleting — move to next phrase
                        deleting = false;
                        phraseIndex = (phraseIndex + 1) % phrases.length;
                        setTimeout(tick, PAUSE_BEFORE);
                        return;
                    }
                    setTimeout(tick, DELETE_SPEED);
                }
            }

            // Kick off after the heading fades in
            setTimeout(tick, 350);
        })();

        /* ─── Cleanup legacy key ─────────────────────────────────── */
        try { sessionStorage.removeItem(legacyKey); } catch (_) {}

        /* ─── Utilities ──────────────────────────────────────────── */
        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function readHistory() {
            try {
                const raw = sessionStorage.getItem(storageKey);
                if (!raw) return [];
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (_) { return []; }
        }

        function writeHistory(history) {
            try { sessionStorage.setItem(storageKey, JSON.stringify(history.slice(-40))); } catch (_) {}
        }

        function autosizeInput() {
            inputEl.style.height = 'auto';
            inputEl.style.height = Math.min(inputEl.scrollHeight, 180) + 'px';
        }

        function setLoading(value) {
            isLoading = value;
            sendBtn.disabled = value;
            inputEl.disabled = value;
            sendBtn.innerHTML = value
                ? '<i class="fa-solid fa-spinner fa-spin"></i> ' + i18n.generating
                : '<i class="fa-solid fa-paper-plane"></i> ' + i18n.send;
        }

        function toggleWelcome() {
            const hasMessages = threadEl.childElementCount > 0;
            welcomeEl.classList.toggle('hidden', hasMessages);
            threadEl.classList.toggle('hidden', !hasMessages);
        }

        function scrollToBottom() {
            chatEl.scrollTop = chatEl.scrollHeight;
        }

        /* ─── Answer text cleanup ────────────────────────────────── */
        function cleanAnswerText(text) {
            return String(text || '')
                .replace(/\s*\[[^\]]*Section:[^\]]*Page[^\]]*\]/gi, '')
                .replace(/\s*\[[^\]]*§[^\]]*p\.[^\]]*\]/gi, '')
                .replace(/[ \t]+\n/g, '\n')
                .trim();
        }

        /* ─── Citation matching ──────────────────────────────────── */
        function normalizeWords(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s]/g, ' ')
                .split(/\s+/)
                .filter(function (w) { return w.length > 3; });
        }

        function pickCitationForSentence(sentence, citations) {
            if (!Array.isArray(citations) || citations.length === 0) return null;
            const sentenceWords = normalizeWords(sentence);
            if (sentenceWords.length === 0) return citations[0];

            let best = null, bestScore = -1;
            citations.forEach(function (citation) {
                const chunkWords = new Set(normalizeWords(citation.chunk_text || ''));
                if (chunkWords.size === 0) return;
                let overlap = 0;
                sentenceWords.forEach(function (w) { if (chunkWords.has(w)) overlap++; });
                const score = overlap / sentenceWords.length;
                if (score > bestScore) { bestScore = score; best = citation; }
            });
            return best || citations[0];
        }

        /* ─── Build highlighted answer ───────────────────────────── */
        function buildAnswerWithHighlights(text, citations) {
            const cleaned = cleanAnswerText(text);
            const parts   = cleaned.split(/(?<=[.!?])\s+/).filter(Boolean);
            if (parts.length === 0) return escapeHtml(cleaned);

            return parts.map(function (sentence) {
                const citation = pickCitationForSentence(sentence, citations);
                if (!citation) return escapeHtml(sentence);

                const title   = String(citation.document_title || i18n.document);
                const section = String(citation.section_heading || i18n.sectionUnknown);
                const page    = Number(citation.page || 1);
                const score   = Math.round(Number(citation.relevance_score || 0) * 100);
                const chunk   = String(citation.chunk_text || citation.content || '').trim();
                const payload = encodeURIComponent(JSON.stringify({
                    source: title, section: section, page: page, score: score, chunk: chunk,
                }));

                return '<span class="rag-sentence" data-citation="' + payload + '">'
                    + escapeHtml(sentence)
                    + '<span class="rag-cite-badge">i</span>'
                    + '</span>';
            }).join(' ');
        }

        /* ─── Tooltip ────────────────────────────────────────────── */
        function attachTooltips(container) {
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'rag-tooltip';
            document.body.appendChild(tooltipEl);

            function parseCitation(raw) {
                try { return JSON.parse(decodeURIComponent(String(raw || ''))) || {}; }
                catch (_) { return {}; }
            }

            function moveTooltip(event) {
                const tw = tooltipEl.offsetWidth;
                const th = tooltipEl.offsetHeight;
                let x = event.clientX + 16;
                let y = event.clientY - th - 14;
                if (x + tw > window.innerWidth - 10) x = event.clientX - tw - 10;
                if (y < 8) y = event.clientY + 20;
                tooltipEl.style.left = x + 'px';
                tooltipEl.style.top  = y + 'px';
            }

            container.addEventListener('mouseover', function (event) {
                const target = event.target instanceof HTMLElement
                    ? event.target.closest('.rag-sentence') : null;
                if (!target) return;

                const data    = parseCitation(target.getAttribute('data-citation'));
                const chunk   = String(data.chunk || '').trim() || i18n.excerptUnavailable;
                const score   = Math.min(100, Math.max(0, Number(data.score) || 0));

                tooltipEl.innerHTML =
                    /* Header */
                    '<div class="rag-tt-header">' +
                        '<div class="rag-tt-icon"><i class="fa-solid fa-file-lines"></i></div>' +
                        '<div style="min-width:0">' +
                            '<div class="rag-tt-title">' + escapeHtml(data.source || i18n.document) + '</div>' +
                            '<div class="rag-tt-section">§&nbsp;' + escapeHtml(data.section || '—') + '</div>' +
                        '</div>' +
                    '</div>' +
                    /* Meta */
                    '<div class="rag-tt-meta">' +
                        '<div class="rag-tt-pill">' +
                            '<i class="fa-regular fa-file"></i>' + i18n.page + '&nbsp;' + escapeHtml(String(data.page || '1')) +
                        '</div>' +
                        '<div class="rag-tt-score">' +
                            '<span class="rag-tt-score-label">' + i18n.relevance + '</span>' +
                            '<div class="rag-tt-score-track">' +
                                '<div class="rag-tt-score-fill" style="width:' + score + '%"></div>' +
                            '</div>' +
                            '<span class="rag-tt-score-pct">' + score + '%</span>' +
                        '</div>' +
                    '</div>' +
                    /* Excerpt */
                    '<div class="rag-tt-chunk">' + escapeHtml(chunk) + '</div>';

                tooltipEl.classList.add('rag-tooltip--visible');
                moveTooltip(event);
            });

            container.addEventListener('mousemove', function (event) {
                if (!tooltipEl.classList.contains('rag-tooltip--visible')) return;
                moveTooltip(event);
            });

            container.addEventListener('mouseout', function (event) {
                const to = event.relatedTarget;
                if (to instanceof HTMLElement && to.closest('.rag-sentence')) return;
                tooltipEl.classList.remove('rag-tooltip--visible');
            });
        }

        /* ─── Message rendering ──────────────────────────────────── */
        function createMessageElement(role, text, citations, isTyping) {
            const row = document.createElement('div');
            row.className = role === 'user' ? 'rag-row rag-row--user' : 'rag-row rag-row--assistant';

            const message = document.createElement('div');
            message.className = 'rag-message';

            if (isTyping) {
                message.innerHTML = '<div class="rag-typing"><span></span><span></span><span></span></div>';
            } else if (role === 'assistant') {
                message.innerHTML = '<div class="rag-answer-text"></div>';
                message.querySelector('.rag-answer-text').innerHTML =
                    buildAnswerWithHighlights(text, citations);
            } else {
                message.textContent = text;
            }

            row.appendChild(message);
            threadEl.appendChild(row);
            toggleWelcome();
            scrollToBottom();
            return { row, message };
        }

        function appendMessage(role, text, citations) {
            return createMessageElement(role, text, citations, false);
        }
        function appendTyping() {
            return createMessageElement('assistant', '', [], true);
        }
        function clearThread() {
            threadEl.innerHTML = '';
            toggleWelcome();
        }
        function renderHistory() {
            clearThread();
            readHistory().forEach(function (entry) {
                appendMessage(entry.role, entry.text, entry.citations || []);
            });
        }
        function saveMessage(role, text, citations) {
            const history = readHistory();
            history.push({ role, text: String(text || ''), citations: Array.isArray(citations) ? citations : [] });
            writeHistory(history);
        }

        /* ─── Streaming helper ───────────────────────────────────── */
        async function streamIfAvailable(response) {
            if (!response.body || typeof response.body.getReader !== 'function') return null;
            const ct = String(response.headers.get('content-type') || '');
            if (!ct.includes('text/event-stream') && !ct.includes('text/plain')) return null;
            const reader  = response.body.getReader();
            const decoder = new TextDecoder();
            let aggregated = '';
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                aggregated += decoder.decode(value, { stream: true });
            }
            return aggregated.trim();
        }

        /* ─── Text animation ─────────────────────────────────────── */
        function animateText(target, finalText) {
            return new Promise(function (resolve) {
                const text = String(finalText || '');
                if (!text) { target.textContent = ''; resolve(); return; }
                const steps = text.length > 900 ? 18 : 12;
                const chunk = Math.max(1, Math.floor(text.length / steps));
                let index = 0;
                function tick() {
                    index = Math.min(text.length, index + chunk);
                    target.textContent = text.slice(0, index);
                    scrollToBottom();
                    if (index < text.length) {
                        window.setTimeout(tick, 18);
                    } else {
                        resolve();
                    }
                }
                tick();
            });
        }

        /* ─── Core ask() ─────────────────────────────────────────── */
        async function ask(question) {
            if (isLoading) return;
            const q = String(question || '').trim();
            if (q.length < 3) return;

            setLoading(true);
            appendMessage('user', q, []);
            saveMessage('user', q, []);
            inputEl.value = '';
            autosizeInput();

            const typing = appendTyping();

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ question: q }),
                });

                let answer = '', citations = [];

                if (!response.ok) {
                    const payload = await response.json().catch(function () { return {}; });
                    throw new Error(payload.message || i18n.serverErrorRetry);
                }

                const streamed = await streamIfAvailable(response);
                if (streamed !== null && streamed.length > 0) {
                    answer = streamed;
                } else {
                    const payload = await response.json();
                    answer    = String(payload.answer || '');
                    citations = Array.isArray(payload.citations) ? payload.citations : [];
                }

                if (String(answer).trim() === 'INSUFFICIENT_CONTEXT') {
                    answer = i18n.insufficientContext;
                }
                if (!answer.trim()) {
                    answer = i18n.noUsableAnswer;
                }

                const assistantNode = appendMessage('assistant', '', citations);
                typing.row.remove();
                const textTarget  = assistantNode.message.querySelector('.rag-answer-text');
                const cleanAnswer = cleanAnswerText(answer);
                await animateText(textTarget, cleanAnswer);
                textTarget.innerHTML = buildAnswerWithHighlights(cleanAnswer, citations);
                saveMessage('assistant', cleanAnswer, citations);

            } catch (error) {
                typing.row.remove();
                const message = error instanceof Error ? error.message : i18n.networkErrorRetry;
                appendMessage('assistant', message, []);
                saveMessage('assistant', message, []);
            } finally {
                setLoading(false);
                inputEl.focus();
            }
        }

        /* ─── Event listeners ────────────────────────────────────── */
        promptBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                inputEl.value = String(btn.getAttribute('data-prompt') || '');
                autosizeInput();
                inputEl.focus();
            });
        });

        sendBtn.addEventListener('click', function () { ask(inputEl.value); });

        inputEl.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                ask(inputEl.value);
            }
        });

        inputEl.addEventListener('input', autosizeInput);

        newChatBtn.addEventListener('click', function () {
            writeHistory([]);
            clearThread();
            inputEl.value = '';
            autosizeInput();
            inputEl.focus();
        });

        /* ─── Init ───────────────────────────────────────────────── */
        renderHistory();
        autosizeInput();
        attachTooltips(threadEl);
    })();
    </script>
@endsection