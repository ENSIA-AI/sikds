@extends('layouts.app')
@php
    $activeNav = 'chatbot';
@endphp
@section('page_title', 'Assistant RAG')
@section('page_subtitle', 'Recherche augmentée par contexte dans la base documentaire')
@section('content')

    <section class="sikds-grid-panels xl:gap-6 mb-5">
        <x-dashboard-panel
            title="Question"
            subtitle="Posez une question précise sur la documentation institutionnelle"
        >
            <div class="min-h-0 flex-1 px-4 py-4">
                <label class="sikds-doc-edit-label">
                    <span><i class="fa-regular fa-message"></i> Votre demande</span>
                    <textarea
                        id="rag-question"
                        rows="6"
                        placeholder="Exemple: Quelles sont les exigences non fonctionnelles, les acteurs principaux et le périmètre du système ?"
                        class="sikds-doc-edit-textarea"
                    ></textarea>
                </label>

                <div class="mt-3 flex items-center justify-end gap-2">
                    <button type="button" id="rag-clear" class="sikds-doc-edit-btn sikds-doc-edit-btn--cancel">
                        Effacer
                    </button>
                    <button type="button" id="rag-submit" class="sikds-doc-edit-btn sikds-doc-edit-btn--save">
                        <i class="fa-solid fa-paper-plane"></i>
                        Rechercher
                    </button>
                </div>

                <p id="rag-loading" class="text-sm sikds-muted-text mt-3 hidden">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Génération de la réponse...
                </p>
            </div>
            <x-slot:footer>Astuce: utilisez Ctrl/Cmd + Entrée pour lancer la recherche.</x-slot:footer>
        </x-dashboard-panel>

        <x-summary-card title="Bonnes pratiques">
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <p class="sikds-muted-text">Posez des questions ciblées par section, acteur ou thème.</p>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-link mt-0.5 text-indigo-600"></i>
                    <p class="sikds-muted-text">Utilisez les sources pour vérifier le contexte de la réponse.</p>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-shield-halved mt-0.5 text-amber-600"></i>
                    <p class="sikds-muted-text">Si le contexte est insuffisant, reformulez avec des termes exacts.</p>
                </div>
            </div>
        </x-summary-card>
    </section>

    <x-dashboard-panel
        title="Réponse"
        subtitle="Résultat généré à partir des documents indexés"
    >
        <div class="min-h-0 flex-1 space-y-4 px-4 py-4">
            <div id="rag-refused" class="sikds-alert sikds-alert--warn hidden">
                <p class="sikds-alert-message text-sm">
                    <i class="fa-solid fa-triangle-exclamation mt-[2px]"></i>
                    Désolé, je n'ai pas assez de contexte dans les documents indexés pour répondre à cette question.
                    Essayez une question plus spécifique (terme exact, section, acteur...).
                </p>
            </div>

            <div class="sikds-alert sikds-alert--info">
                <p class="sikds-alert-message text-sm">
                    <i class="fa-solid fa-robot mt-[2px]"></i>
                    <span id="rag-answer" class="whitespace-pre-wrap leading-relaxed">
                        Posez une question pour obtenir une réponse contextualisée.
                    </span>
                </p>
            </div>

            <div>
                <button
                    type="button"
                    id="rag-sources-toggle"
                    class="sikds-docs-filter-btn"
                >
                    <i class="fa-solid fa-book-open"></i>
                    <span>Sources</span>
                    <span id="rag-sources-count" class="sikds-docs-extra-tags">0</span>
                </button>

                <div id="rag-sources" class="hidden mt-3 border border-black/10 rounded-[12px] overflow-hidden">
                    <div id="rag-citations-list"></div>
                </div>
            </div>
        </div>
        <x-slot:footer>Les citations affichent le document, la section et le score de pertinence.</x-slot:footer>
    </x-dashboard-panel>

    <script>
        (function () {
            const submitBtn = document.getElementById('rag-submit');
            const clearBtn = document.getElementById('rag-clear');
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
            clearBtn.addEventListener('click', function () {
                questionEl.value = '';
                refusedEl.classList.add('hidden');
                answerEl.textContent = 'Posez une question pour obtenir une réponse contextualisée.';
                listEl.innerHTML = '';
                countEl.textContent = '0';
                sourcesOpen = false;
                sourcesEl.classList.add('hidden');
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
                    row.className = 'px-4 py-3 border-b border-black/10';
                    row.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="sikds-docs-title">${idx + 1}. ${title}</p>
                                <p class="sikds-docs-ref">Section: ${section} • Page ${page}</p>
                            </div>
                            <span class="sikds-status sikds-status--draft">
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
                    answerEl.textContent = (String(answer).trim() === 'INSUFFICIENT_CONTEXT')
                        ? 'Je n ai pas de contexte suffisant pour répondre avec fiabilité.'
                        : String(answer);
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
            questionEl.addEventListener('keydown', function (event) {
                if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                    runQuery();
                }
            });
        })();
    </script>
@endsection

