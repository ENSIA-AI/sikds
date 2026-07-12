/**
 * RAG chat page — extracted from resources/views/rag/index.blade.php so the
 * page ships as a Vite module instead of an inline script (CSP hardening).
 * Blade passes endpoint / i18n / user id via the #rag-page-config JSON tag.
 */
    (function () {
        /* ─── Config ─────────────────────────────────────────────── */
        const cfgEl = document.getElementById('rag-page-config');
        if (!cfgEl) return;
        const cfg = JSON.parse(cfgEl.textContent || '{}');

        const endpoint       = cfg.endpoint;
        const csrf           = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const i18n           = cfg.i18n || {};
        const authUserId     = cfg.authUserId;
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
