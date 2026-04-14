<x-app-layout :activeNav="'chatbot'">
    <x-slot name="header">
        <h1 class="sikds-page-title">Knowledge Search</h1>
        <p class="sikds-page-subtitle">Recherche dans la base documentaire institutionnelle</p>
    </x-slot>

    <div class="bg-white rounded-[14px] border shadow-sm mb-5" style="border-color:rgba(0,0,0,.1);">
        <div class="px-5 py-5">
            <label class="block text-xs font-medium mb-2" style="color:var(--sikds-muted)">Question</label>
            <textarea
                id="rag-question"
                rows="4"
                placeholder="Posez une question précise sur le document (ex: « Quelles sont les exigences non fonctionnelles ? », « Quels acteurs existent ? », « Quel est le périmètre du système ? »)…"
                class="w-full px-4 py-3 text-sm border border-black/10 rounded-[12px] focus:outline-none focus:ring-2 focus:ring-[color:var(--sikds-primary)]/30"
            ></textarea>

            <div class="mt-4 flex items-center gap-3">
                <button
                    type="button"
                    id="rag-submit"
                    class="px-5 py-2 text-sm font-semibold text-white rounded-[10px]"
                    style="background-color:var(--sikds-primary);"
                >
                    Rechercher
                </button>
                <div id="rag-loading" class="text-sm hidden" style="color:var(--sikds-muted)">
                    Chargement…
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[14px] border overflow-hidden" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
        <div class="px-5 py-4 border-b" style="border-color:rgba(0,0,0,.1);background:#f9f9fb;">
            <p class="text-sm font-medium" style="color:var(--sikds-ink)">Réponse</p>
        </div>
        <div class="px-5 py-5">
            <div id="rag-refused" class="hidden text-sm px-4 py-3 rounded-[12px] border" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted);background:#fbfbfd;">
                Désolé, je n’ai pas assez de contexte dans les documents indexés pour répondre à cette question. Essayez une question plus spécifique (avec un terme exact, une section, ou un acteur).
            </div>
            <div id="rag-answer" class="whitespace-pre-wrap text-sm leading-relaxed" style="color:var(--sikds-ink)"></div>

            <div class="mt-6">
                <button
                    type="button"
                    id="rag-sources-toggle"
                    class="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-[10px] border hover:bg-gray-50 transition-colors"
                    style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)"
                >
                    Sources
                    <span id="rag-sources-count" class="text-xs px-2 py-0.5 rounded-full" style="background:#eef2ff;color:#3730a3;">0</span>
                </button>

                <div id="rag-sources" class="hidden mt-3 border rounded-[12px] overflow-hidden" style="border-color:rgba(0,0,0,.1);">
                    <div id="rag-citations-list"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const submitBtn = document.getElementById('rag-submit');
            const questionEl = document.getElementById('rag-question');
            const loadingEl = document.getElementById('rag-loading');
            const answerEl = document.getElementById('rag-answer');
            const refusedEl = document.getElementById('rag-refused');
            const toggleBtn = document.getElementById('rag-sources-toggle');
            const sourcesEl = document.getElementById('rag-sources');
            const countEl = document.getElementById('rag-sources-count');
            const listEl = document.getElementById('rag-citations-list');

            let sourcesOpen = false;
            toggleBtn.addEventListener('click', function () {
                sourcesOpen = !sourcesOpen;
                sourcesEl.classList.toggle('hidden', !sourcesOpen);
            });

            function setLoading(isLoading) {
                loadingEl.classList.toggle('hidden', !isLoading);
                submitBtn.disabled = isLoading;
                submitBtn.classList.toggle('opacity-60', isLoading);
                submitBtn.classList.toggle('cursor-not-allowed', isLoading);
            }

            function renderCitations(citations) {
                listEl.innerHTML = '';
                countEl.textContent = String((citations || []).length);

                (citations || []).forEach(function (c, idx) {
                    const scorePct = Math.round(((c.relevance_score || 0) * 100));
                    const title = c.document_title || '';
                    const section = c.section_heading || '-';
                    const page = c.page || 1;

                    const row = document.createElement('div');
                    row.className = 'px-5 py-4 border-b';
                    row.style.borderColor = 'rgba(0,0,0,.06)';
                    row.innerHTML = `
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold" style="color:var(--sikds-ink)">${idx + 1}. ${title}</p>
                                <p class="text-xs mt-1" style="color:var(--sikds-muted)">Section: ${section} • Page ${page}</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full" style="background:#f1f5f9;color:#0f172a;">
                                ${scorePct}%
                            </span>
                        </div>
                    `;
                    listEl.appendChild(row);
                });
            }

            async function runQuery() {
                const q = (questionEl.value || '').trim();
                if (q.length < 3) return;

                refusedEl.classList.add('hidden');
                answerEl.textContent = '';
                renderCitations([]);
                setLoading(true);

                try {
                    const resp = await fetch('{{ route('rag.query') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({ question: q }),
                    });

                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Request failed');

                    const refused = !!data.refused;
                    const answer = data.answer || '';
                    const citations = data.citations || [];

                    if (refused) {
                        refusedEl.classList.remove('hidden');
                    }

                    // Hide the sentinel token from the UI.
                    answerEl.textContent = (String(answer).trim() === 'INSUFFICIENT_CONTEXT') ? '' : String(answer);
                    renderCitations(citations);
                    sourcesEl.classList.toggle('hidden', citations.length === 0);
                    sourcesOpen = citations.length > 0;
                } catch (e) {
                    answerEl.textContent = 'Erreur lors de la requête. Veuillez réessayer.';
                } finally {
                    setLoading(false);
                }
            }

            submitBtn.addEventListener('click', runQuery);
        })();
    </script>
</x-app-layout>

