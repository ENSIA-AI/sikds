@extends('layouts.app')
@php
    $activeNav = 'chatbot';
@endphp
@section('page_title', 'Assistant')
@section('page_subtitle', 'Votre assistant documentaire intelligent')
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

        /* ── Welcome screen ── */
        .rag-welcome {
            max-width: 680px;
            margin: 3rem auto 0;
            text-align: center;
            padding: 0 1rem;
        }
        .rag-welcome h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 .6rem;
            letter-spacing: -.02em;
        }
        .rag-welcome p {
            color: #64748b;
            font-size: .95rem;
            line-height: 1.65;
            margin: 0 auto 2rem;
            max-width: 520px;
        }
        .rag-badge {
            display: none; /* removed — welcome heading handles identity */
        }
        .rag-prompts {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            max-width: 680px;
            margin: 0 auto;
        }
        .rag-prompt-btn {
            text-align: left;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            border-radius: 12px;
            padding: .8rem 1rem;
            font-size: .84rem;
            line-height: 1.5;
            font-family: inherit;
            cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .rag-prompt-btn:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        /* ── Thread ── */
        .rag-thread {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        /* ── Message rows ── */
        .rag-row {
            display: flex;
            padding: .5rem 0;
        }

        /* User: right-aligned bubble */
        .rag-row--user {
            justify-content: flex-end;
            margin-bottom: .25rem;
        }
        .rag-row--user .rag-message {
            background: #f4f4f4;
            color: #0f172a;
            border-radius: 18px 18px 4px 18px;
            padding: .65rem 1rem;
            max-width: 75%;
            font-size: .93rem;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Assistant: plain text, left, no bubble */
        .rag-row--assistant {
            justify-content: flex-start;
            margin-bottom: .5rem;
        }
        .rag-row--assistant .rag-message {
            max-width: 100%;
            font-size: .93rem;
            line-height: 1.75;
            color: #0f172a;
            padding: .1rem 0;
        }

        .rag-answer-text {
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* ── Citation highlight (keep behaviour, restyle) ── */
        .rag-sentence {
            border-bottom: 1px dashed rgba(37, 99, 235, 0.4);
            background: linear-gradient(180deg, transparent 75%, rgba(59, 130, 246, 0.1) 75%);
            transition: background .12s;
            cursor: default;
        }
        .rag-sentence:hover {
            background: linear-gradient(180deg, transparent 75%, rgba(59, 130, 246, 0.2) 75%);
        }
        .rag-cite-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 15px;
            height: 15px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 50%;
            color: #1d4ed8;
            background: rgba(37, 99, 235, 0.13);
            margin-left: .25rem;
            vertical-align: middle;
        }

        /* ── Tooltip (unchanged behaviour) ── */
        .rag-tooltip {
            position: fixed;
            z-index: 60;
            width: min(340px, calc(100vw - 20px));
            border-radius: 12px;
            border: 1px solid rgba(15, 23, 42, 0.15);
            background: #0f172a;
            color: #e2e8f0;
            padding: .7rem .85rem;
            box-shadow: 0 12px 32px rgba(2, 6, 23, 0.4);
            font-size: .76rem;
            line-height: 1.55;
            pointer-events: none;
            opacity: 0;
            transform: translateY(4px);
            transition: opacity .1s, transform .1s;
        }
        .rag-tooltip--visible {
            opacity: 1;
            transform: translateY(0);
        }
        .rag-tooltip strong {
            color: #fff;
            display: block;
            font-size: .79rem;
            margin-bottom: .15rem;
        }
        .rag-tooltip-meta {
            color: #93c5fd;
            margin-bottom: .3rem;
        }
        .rag-tooltip-chunk {
            display: block;
            color: #cbd5e1;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            padding-top: .3rem;
        }

        /* ── Sources accordion (kept, cleaned) ── */
        .rag-sources {
            margin-top: .75rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .rag-sources summary {
            list-style: none;
            cursor: pointer;
            padding: .5rem .75rem;
            font-size: .76rem;
            color: #475569;
            background: #f8fafc;
        }
        .rag-sources summary::-webkit-details-marker { display: none; }
        .rag-source-item {
            padding: .55rem .75rem;
            border-top: 1px solid #f1f5f9;
            font-size: .78rem;
            color: #475569;
            background: #fff;
        }
        .rag-source-item strong {
            color: #0f172a;
            font-weight: 600;
            display: block;
            margin-bottom: .1rem;
        }

        /* ── Typing dots ── */
        .rag-typing {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .6rem .1rem;
        }
        .rag-typing span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #94a3b8;
            opacity: .3;
            animation: rag-typing 1s infinite ease-in-out;
        }
        .rag-typing span:nth-child(2) { animation-delay: .15s; }
        .rag-typing span:nth-child(3) { animation-delay: .3s; }
        @keyframes rag-typing {
            0%, 80%, 100% { transform: translateY(0); opacity: .28; }
            40% { transform: translateY(-3px); opacity: 1; }
        }

        /* ── Composer ── */
        .rag-composer {
            padding: .75rem 1rem 1.25rem;
            background: transparent;
        }

        .rag-composer-box {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            padding: .6rem .65rem .55rem .85rem;
            /* single clean border, no outline, no box-shadow double ring */
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
            transition: border-color .15s;
        }
        .rag-composer-box:focus-within {
            border-color: #9ca3af;
            box-shadow: 0 1px 6px rgba(15, 23, 42, 0.09);
        }

        .rag-input {
            width: 100%;
            border: 0;
            outline: none;
            box-shadow: none;
            -webkit-appearance: none;
            resize: none;
            min-height: 24px;
            max-height: 180px;
            padding: .25rem 0;
            color: #0f172a;
            background: transparent;
            line-height: 1.55;
            font-size: .93rem;
            font-family: inherit;
        }
        .rag-input::placeholder { color: #9ca3af; }
        /* kill any browser focus ring on the textarea itself */
        .rag-input:focus { outline: none; box-shadow: none; }

        .rag-composer-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-top: .45rem;
        }

        .rag-left-actions {
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .rag-hint {
            color: #9ca3af;
            font-size: .74rem;
        }

        /* Buttons */
        .rag-btn {
            border: 0;
            border-radius: 10px;
            padding: .48rem .8rem;
            font-size: .82rem;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: background .12s, opacity .12s;
            line-height: 1;
        }
        .rag-btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }
        .rag-btn--ghost {
            color: #475569;
            background: #f1f5f9;
        }
        .rag-btn--ghost:not(:disabled):hover { background: #e2e8f0; }

        .rag-btn--primary {
            color: #fff;
            background: #111827;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .rag-btn--primary:not(:disabled):hover { background: #1f2937; }
    </style>

    <section class="rag-shell">
        <div class="rag-chat" id="rag-chat">
            <div class="rag-welcome" id="rag-welcome">
                <h2>Que souhaitez-vous explorer ?</h2>
                <p>Je synthétise vos documents indexés et cite chaque source avec précision.</p>
                <div class="rag-prompts">
                    <button class="rag-prompt-btn" type="button" data-prompt="Résume le cahier des charges en 6 points clés.">
                        Résumer les points clés du cahier des charges
                    </button>
                    <button class="rag-prompt-btn" type="button" data-prompt="Quelles sont les exigences non fonctionnelles prioritaires ?">
                        Identifier les exigences non fonctionnelles prioritaires
                    </button>
                    <button class="rag-prompt-btn" type="button" data-prompt="Liste les acteurs du système et leurs responsabilités.">
                        Lister les acteurs du système et leurs responsabilités
                    </button>
                    <button class="rag-prompt-btn" type="button" data-prompt="Y a-t-il des contraintes de sécurité ou conformité mentionnées ?">
                        Chercher les contraintes sécurité / conformité
                    </button>
                </div>
            </div>

            <div class="rag-thread hidden" id="rag-thread"></div>
        </div>

        <div class="rag-composer">
            <div class="rag-composer-box">
                <textarea
                    id="rag-input"
                    class="rag-input"
                    rows="1"
                    maxlength="500"
                    placeholder="Envoyez un message..."
                ></textarea>
                <div class="rag-composer-actions">
                    <div class="rag-left-actions">
                        <button id="rag-new-chat" type="button" class="rag-btn rag-btn--ghost">Nouvelle discussion</button>
                        <span class="rag-hint">Entrée pour envoyer&nbsp;·&nbsp;Shift+Entrée pour une ligne</span>
                    </div>
                    <button id="rag-send" type="button" class="rag-btn rag-btn--primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        Envoyer
                    </button>
                </div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const endpoint = @json(route('rag.query'));
            const csrf = @json(csrf_token());
            const storageKey = 'sikds-rag-chat-v3';
            const inputEl = document.getElementById('rag-input');
            const sendBtn = document.getElementById('rag-send');
            const newChatBtn = document.getElementById('rag-new-chat');
            const chatEl = document.getElementById('rag-chat');
            const welcomeEl = document.getElementById('rag-welcome');
            const threadEl = document.getElementById('rag-thread');
            const promptBtns = Array.from(document.querySelectorAll('[data-prompt]'));

            let isLoading = false;

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
                } catch (error) {
                    return [];
                }
            }

            function writeHistory(history) {
                try {
                    sessionStorage.setItem(storageKey, JSON.stringify(history.slice(-40)));
                } catch (error) {}
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
                    ? '<i class="fa-solid fa-spinner fa-spin"></i> Génération...'
                    : '<i class="fa-solid fa-paper-plane"></i> Envoyer';
            }

            function toggleWelcome() {
                const hasMessages = threadEl.childElementCount > 0;
                welcomeEl.classList.toggle('hidden', hasMessages);
                threadEl.classList.toggle('hidden', !hasMessages);
            }

            function scrollToBottom() {
                chatEl.scrollTop = chatEl.scrollHeight;
            }

            function cleanAnswerText(text) {
                return String(text || '')
                    .replace(/\s*\[[^\]]*Section:[^\]]*Page[^\]]*\]/gi, '')
                    .replace(/\s*\[[^\]]*§[^\]]*p\.[^\]]*\]/gi, '')
                    .replace(/[ \t]+\n/g, '\n')
                    .trim();
            }

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

                let best = null;
                let bestScore = -1;

                citations.forEach(function (citation) {
                    const chunkWords = new Set(normalizeWords(citation.chunk_text || ''));
                    if (chunkWords.size === 0) return;

                    let overlap = 0;
                    sentenceWords.forEach(function (word) {
                        if (chunkWords.has(word)) overlap += 1;
                    });

                    const score = overlap / sentenceWords.length;
                    if (score > bestScore) {
                        bestScore = score;
                        best = citation;
                    }
                });

                return best || citations[0];
            }

            function buildAnswerWithHighlights(text, citations) {
                const cleaned = cleanAnswerText(text);
                const parts = cleaned.split(/(?<=[.!?])\s+/).filter(Boolean);
                if (parts.length === 0) return escapeHtml(cleaned);

                return parts.map(function (sentence) {
                    const citation = pickCitationForSentence(sentence, citations);
                    if (!citation) return escapeHtml(sentence);

                    const title = String(citation.document_title || 'Document');
                    const section = String(citation.section_heading || 'Section non precisee');
                    const page = Number(citation.page || 1);
                    const score = Math.round(Number(citation.relevance_score || 0) * 100);
                    const chunk = String(citation.chunk_text || citation.content || '').trim();
                    const payload = encodeURIComponent(JSON.stringify({
                        source: title,
                        section: section,
                        page: page,
                        score: score,
                        chunk: chunk,
                    }));

                    return '<span class="rag-sentence" data-citation="' + payload + '">' +
                        escapeHtml(sentence) +
                        '<span class="rag-cite-badge">i</span>' +
                    '</span>';
                }).join(' ');
            }

            function attachTooltips(container) {
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'rag-tooltip';
                document.body.appendChild(tooltipEl);

                function parseCitation(raw) {
                    try {
                        const decoded = decodeURIComponent(String(raw || ''));
                        const parsed = JSON.parse(decoded);
                        return parsed && typeof parsed === 'object' ? parsed : {};
                    } catch (error) {
                        return {};
                    }
                }

                function moveTooltip(event) {
                    const x = Math.min(window.innerWidth - tooltipEl.offsetWidth - 10, event.clientX + 14);
                    const y = Math.max(10, event.clientY - tooltipEl.offsetHeight - 14);
                    tooltipEl.style.left = x + 'px';
                    tooltipEl.style.top = y + 'px';
                }

                container.addEventListener('mouseover', function (event) {
                    const target = event.target instanceof HTMLElement
                        ? event.target.closest('.rag-sentence')
                        : null;
                    if (!target) return;
                    const data = parseCitation(target.getAttribute('data-citation'));
                    const chunkPreview = String(data.chunk || '').trim() || 'Extrait indisponible.';
                    tooltipEl.innerHTML =
                        '<strong>' + escapeHtml(data.source || 'Document') + '</strong>' +
                        '<div class="rag-tooltip-meta">Section: ' + escapeHtml(data.section || '-') + ' • Page ' + escapeHtml(String(data.page || '1')) + ' • Pertinence ' + escapeHtml(String(data.score || '0')) + '%</div>' +
                        '<span class="rag-tooltip-chunk">' + escapeHtml(chunkPreview) + '</span>';
                    tooltipEl.classList.add('rag-tooltip--visible');
                    moveTooltip(event);
                });

                container.addEventListener('mousemove', function (event) {
                    if (!tooltipEl.classList.contains('rag-tooltip--visible')) return;
                    moveTooltip(event);
                });

                container.addEventListener('mouseout', function (event) {
                    const toElement = event.relatedTarget;
                    const stillInside = toElement instanceof HTMLElement && toElement.closest('.rag-sentence');
                    if (stillInside) return;
                    tooltipEl.classList.remove('rag-tooltip--visible');
                });
            }

            function createMessageElement(role, text, citations, isTyping) {
                const row = document.createElement('div');
                row.className = role === 'user'
                    ? 'rag-row rag-row--user'
                    : 'rag-row rag-row--assistant';

                const message = document.createElement('div');
                message.className = 'rag-message';

                if (isTyping) {
                    message.innerHTML = '<div class="rag-typing"><span></span><span></span><span></span></div>';
                } else if (role === 'assistant') {
                    message.innerHTML = '<div class="rag-answer-text"></div>';
                    message.querySelector('.rag-answer-text').innerHTML = buildAnswerWithHighlights(text, citations);
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
                const history = readHistory();
                history.forEach(function (entry) {
                    appendMessage(entry.role, entry.text, entry.citations || []);
                });
            }

            function saveMessage(role, text, citations) {
                const history = readHistory();
                history.push({
                    role: role,
                    text: String(text || ''),
                    citations: Array.isArray(citations) ? citations : [],
                });
                writeHistory(history);
            }

            async function streamIfAvailable(response) {
                if (!response.body || typeof response.body.getReader !== 'function') return null;
                const contentType = String(response.headers.get('content-type') || '');
                if (!contentType.includes('text/event-stream') && !contentType.includes('text/plain')) return null;

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let aggregated = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    aggregated += decoder.decode(value, { stream: true });
                }

                return aggregated.trim();
            }

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
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ question: q }),
                    });

                    let answer = '';
                    let citations = [];
                    let refused = false;

                    if (!response.ok) {
                        const payload = await response.json().catch(function () { return {}; });
                        throw new Error(payload.message || 'Erreur serveur. Veuillez réessayer.');
                    }

                    const streamed = await streamIfAvailable(response);
                    if (streamed !== null && streamed.length > 0) {
                        answer = streamed;
                    } else {
                        const payload = await response.json();
                        answer = String(payload.answer || '');
                        citations = Array.isArray(payload.citations) ? payload.citations : [];
                        refused = Boolean(payload.refused);
                    }

                    if (String(answer).trim() === 'INSUFFICIENT_CONTEXT' || refused) {
                        answer = 'Je n ai pas assez de contexte fiable dans les documents indexes pour repondre avec precision. Reformulez avec des termes plus exacts ou une section specifique.';
                    }

                    if (!answer.trim()) {
                        answer = 'Je n ai pas pu generer une reponse exploitable cette fois. Reessayez avec une question plus precise.';
                    }

                    const assistantNode = appendMessage('assistant', '', citations);
                    typing.row.remove();
                    const textTarget = assistantNode.message.querySelector('.rag-answer-text');
                    const cleanAnswer = cleanAnswerText(answer);
                    await animateText(textTarget, cleanAnswer);
                    textTarget.innerHTML = buildAnswerWithHighlights(cleanAnswer, citations);
                    saveMessage('assistant', cleanAnswer, citations);
                } catch (error) {
                    typing.row.remove();
                    const message = error instanceof Error
                        ? error.message
                        : 'Erreur reseau. Veuillez reessayer.';
                    appendMessage('assistant', message, []);
                    saveMessage('assistant', message, []);
                } finally {
                    setLoading(false);
                    inputEl.focus();
                }
            }

            promptBtns.forEach(function (button) {
                button.addEventListener('click', function () {
                    inputEl.value = String(button.getAttribute('data-prompt') || '');
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

            renderHistory();
            autosizeInput();
            attachTooltips(threadEl);
        })();
    </script>
@endsection