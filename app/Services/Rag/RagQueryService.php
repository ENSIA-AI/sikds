<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

/**
 * Orchestrates RAG: authorize docs → embed → vector search → rerank → LLM answer with citations.
 */
class RagQueryService
{
    public function __construct(
        protected JinaEmbeddingService $embeddings,
        protected JinaRerankerService $reranker,
    ) {}

    /**
     * @return array{answer:string, citations:array<int, array<string, mixed>>, refused:bool}
     */
    public function query(string $question, int $userId): array
    {
        $user = User::find($userId);
        if (! $user) {
            return [
                'answer' => 'Utilisateur introuvable.',
                'citations' => [],
                'refused' => true,
            ];
        }

        $authorizedIds = $this->resolveAuthorizedDocumentIds($user);
        if ($authorizedIds === []) {
            return [
                'answer' => 'Aucun document autorisé et indexé n’est disponible pour répondre à votre question.',
                'citations' => [],
                'refused' => true,
            ];
        }

        $queryVector = $this->embeddings->embedQuery($question);
        if ($queryVector === []) {
            throw new RuntimeException('Query embedding returned an empty vector.');
        }

        $candidates = $this->vectorSearch($authorizedIds, $queryVector);
        if ($candidates === []) {
            return [
                'answer' => 'Aucun extrait pertinent n’a été trouvé dans les documents autorisés.',
                'citations' => [],
                'refused' => true,
            ];
        }

        $topN = (int) config('rag.reranking.top_n');
        $reranked = $this->reranker->rerank($question, $candidates, $topN);
        if ($reranked === []) {
            return [
                'answer' => 'Aucun extrait pertinent n’a été trouvé dans les documents autorisés.',
                'citations' => [],
                'refused' => true,
            ];
        }

        $context = $this->buildContext($reranked);
        $answer = $this->callLlm($question, $context);

        if (trim($answer) === 'INSUFFICIENT_CONTEXT') {
            return [
                'answer' => 'INSUFFICIENT_CONTEXT',
                'citations' => [],
                'refused' => true,
            ];
        }

        $citations = array_map(function (array $c) {
            $meta = is_array($c['metadata'] ?? null) ? $c['metadata'] : [];
            $chunkText = trim((string) ($c['content'] ?? ''));
            if (mb_strlen($chunkText) > 500) {
                $chunkText = mb_substr($chunkText, 0, 500) . '...';
            }

            return [
                'document_title' => (string) ($meta['document_title'] ?? ''),
                'section_heading' => $meta['section_heading'] ?? null,
                'page' => (int) ($meta['page'] ?? 1),
                'relevance_score' => (float) ($c['relevance_score'] ?? 0.0),
                'score' => (float) ($c['score'] ?? 0.0),
                'chunk_id' => (int) ($c['id'] ?? 0),
                'document_id' => (int) ($c['document_id'] ?? 0),
                'chunk_text' => $chunkText,
            ];
        }, $reranked);

        return [
            'answer' => $answer,
            'citations' => $citations,
            'refused' => false,
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function resolveAuthorizedDocumentIds(User $user): array
    {
        $q = Document::query()
            ->where('status', 'active')
            ->where('indexing_status', 'indexed')
            ->whereNull('deleted_at');

        if (! (bool) config('rag.authorization.enforce', true)) {
            return $q->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        $q->visibleTo($user);

        return $q->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param  array<int, int>  $authorizedIds
     * @param  array<int, float>  $vector
     * @return array<int, array<string, mixed>>
     */
    protected function vectorSearch(array $authorizedIds, array $vector): array
    {
        $candidatePool = (int) config('rag.retrieval.candidate_pool');
        $vectorLiteral = $this->vectorLiteral($vector);

        $placeholders = implode(',', array_fill(0, count($authorizedIds), '?'));
        $sql = "
            SELECT dc.id, dc.document_id, dc.content, dc.metadata,
                   (1 - (dc.embedding <=> (?::vector))) AS score
            FROM document_chunks dc
            WHERE dc.document_id = ANY(ARRAY[$placeholders]::int[])
            ORDER BY dc.embedding <=> (?::vector)
            LIMIT $candidatePool
        ";

        $bindings = array_merge([$vectorLiteral], $authorizedIds, [$vectorLiteral]);
        $rows = DB::select($sql, $bindings);

        return array_map(function ($r) {
            $arr = (array) $r;
            $arr['metadata'] = is_string($arr['metadata'] ?? null)
                ? json_decode($arr['metadata'], true)
                : $arr['metadata'];

            return $arr;
        }, $rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $chunks
     */
    protected function buildContext(array $chunks): string
    {
        $parts = [];
        foreach ($chunks as $c) {
            $meta = is_array($c['metadata'] ?? null) ? $c['metadata'] : [];
            $title = (string) ($meta['document_title'] ?? '');
            $section = $meta['section_heading'] ?? null;
            $page = (int) ($meta['page'] ?? 1);

            $parts[] = '[SOURCE: ' . $title . ', Section: ' . ($section ?: '-') . ', Page ' . $page . "]\n"
                . (string) ($c['content'] ?? '');
        }

        return implode("\n\n", $parts);
    }

    protected function callLlm(string $question, string $context): string
    {
        $system = "You are an institutional assistant. Answer ONLY using the provided document\n"
            . "excerpts. Do not include inline citations, brackets, or source markers in the response text.\n"
            . "Write clear and concise prose in the same language as the user question.\n"
            . "If the context does not contain enough information to answer confidently,\n"
            . "respond with exactly: INSUFFICIENT_CONTEXT";

        $provider = (string) env('PRISM_LLM_PROVIDER', 'groq');
        $model = (string) env('PRISM_LLM_MODEL', 'llama-3.3-70b-versatile');

        $response = Prism::text()
            ->using(Provider::from($provider), $model)
            ->withSystemPrompt($system)
            ->withPrompt("Question:\n{$question}\n\nContext:\n{$context}")
            ->asText();

        return trim((string) $response->text);
    }

    /**
     * @param  array<int, float>  $vector
     */
    protected function vectorLiteral(array $vector): string
    {
        return '[' . implode(',', array_map(
            fn ($v) => rtrim(rtrim(sprintf('%.10F', (float) $v), '0'), '.'),
            $vector
        )) . ']';
    }
}

