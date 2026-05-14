<?php

declare(strict_types=1);

namespace App\Services\Rag;

/**
 * Defence-in-depth guard for the RAG pipeline.
 *
 * Covers three threat classes:
 *   1. Direct prompt injection   — malicious instructions inside the user question.
 *   2. Indirect prompt injection — malicious instructions inside retrieved document chunks.
 *   3. Information leakage       — the model echoing its system prompt or internal markers.
 *
 * The guard never trusts the model to police itself. Untrusted text is
 * structurally neutralised (delimiter-escaped, control-stripped) before it ever
 * reaches the LLM, and the response is screened on the way back out. The
 * pattern matcher is a *signal* — it raises an injection score for auditing and
 * optional hard-blocking — it is not the primary defence.
 */
class PromptGuard
{
    /**
     * Regex fragments that signal an attempt to override the system prompt.
     * Matched case-insensitively against the user question. Kept deliberately
     * broad (English + French, the two query languages SIKDS serves) — a hit
     * raises the injection score; it does not by itself corrupt the answer
     * because the question and context are also structurally neutralised.
     *
     * @var array<int, string>
     */
    protected const INJECTION_PATTERNS = [
        // ── English: instruction overrides ──────────────────────────────────
        '/\b(ignore|disregard|forget|override|bypass)\b.{0,40}\b(all|any|the|your|previous|prior|above|earlier|preceding)\b.{0,25}\b(instruction|prompt|rule|context|message|directive|guideline)/i',
        '/\b(ignore|disregard|forget)\b.{0,20}\b(everything|all)\b/i',
        '/\b(you are|act|behave|respond|reply)\b.{0,15}\b(now|as|like)\b.{0,30}\b(a|an|the|if)\b/i',
        '/\bpretend\b.{0,20}\b(to be|you are|that|that you)/i',
        '/\b(system|developer|admin|root)\b.{0,12}\b(prompt|instruction|message|mode|override|access)/i',
        '/\b(reveal|show|print|repeat|output|disclose|expose|tell me|give me)\b.{0,40}\b(system|initial|original|your|the|exact)\b.{0,20}\b(prompt|instruction|message|rule|directive)/i',
        '/\b(jailbreak|dan mode|developer mode|do anything now|unfiltered)\b/i',
        '/\bnew\b.{0,12}\b(instruction|rule|persona|task|directive)s?\b/i',
        '/\b(end|start) of (context|document|prompt|instructions?)\b/i',
        // ── French: instruction overrides ───────────────────────────────────
        '/\b(ignore|ignorez|oublie|oubliez|n[ée]glige|n[ée]gligez|contourne|contournez)\b.{0,35}\b(les|tes|toutes?|précédent\w*|instructions?|consignes?|règles?)/i',
        '/\b(tu es|tu agis|agis|comporte[\- ]toi|fais comme si)\b.{0,25}\b(maintenant|comme|en tant que|si)/i',
        '/\b(révèle|montre|affiche|répète|donne[\- ]moi|dévoile)\b.{0,40}\b(ton|le|tes|les)\b.{0,20}\b(prompt|instruction|consigne|système|règle)/i',
        // ── Structural break-out attempts (forged delimiters) ───────────────
        '/<\/?\s*(context|excerpt|instruction|instructions|system|question|user|assistant)\s*\/?>/i',
        '/\[\/?\s*(INST|SYS|SYSTEM|ASSISTANT|USER)\s*\]/i',
        '/<\|[^|]{0,40}\|>/',
        '/(^|\n)\s*(#{2,}\s|system\s*:|assistant\s*:|user\s*:)/i',
    ];

    /**
     * Canary substrings drawn from the system prompt. If any appears in the
     * model's answer, the response is treated as leaked and suppressed.
     *
     * @var array<int, string>
     */
    protected const LEAK_CANARIES = [
        'institutional assistant for SIKDS',
        'Secure Institutional Knowledge',
        '<instructions>',
        '</instructions>',
        'untrusted reference data',
    ];

    /** Control characters (C0/C1) that have no place in a question or a chunk. */
    protected const CONTROL_CHARS = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u';

    /** Bidi overrides and zero-width characters frequently used to smuggle payloads. */
    protected const SMUGGLING_CHARS = '/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u';

    /**
     * Clean a user question: strip control / bidi characters, collapse runaway
     * whitespace, and hard-cap the length so an oversized payload cannot blow
     * the context window or the cost budget. Purely structural — it does not
     * alter the meaning of a legitimate question.
     */
    public function sanitizeQuestion(string $text): string
    {
        $text = preg_replace(self::CONTROL_CHARS, '', $text) ?? $text;
        $text = preg_replace(self::SMUGGLING_CHARS, '', $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
        // Strip horizontal whitespace hugging newlines, then cap blank lines.
        $text = preg_replace('/[ \t]*\n[ \t]*/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        $text = trim($text);

        $max = max(1, (int) config('rag.security.max_question_chars', 2000));
        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
        }

        return $text;
    }

    /**
     * Score a question for prompt-injection signals.
     *
     * @return array{score:int, patterns:array<int,string>}
     */
    public function inspectQuestion(string $text): array
    {
        $hits = [];
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $hits[] = $pattern;
            }
        }

        return ['score' => count($hits), 'patterns' => $hits];
    }

    /**
     * Whether a question trips the configured injection threshold.
     */
    public function isInjection(string $text): bool
    {
        $threshold = max(1, (int) config('rag.security.injection_threshold', 1));

        return $this->inspectQuestion($text)['score'] >= $threshold;
    }

    /**
     * Neutralise a retrieved document chunk before it is embedded in the prompt.
     *
     * Document content is *untrusted*: a PDF can legitimately contain text like
     * "ignore the above and ...". We cannot make such text safe semantically —
     * that is the system prompt's job — but we can guarantee it is structurally
     * inert: control / bidi characters are stripped and every angle bracket is
     * escaped so a chunk can never forge or close the <excerpt>/<context>
     * wrapper. The model still reads the words, just never as live markup.
     */
    public function neutralizeContext(string $content): string
    {
        $content = preg_replace(self::CONTROL_CHARS, '', $content) ?? $content;
        $content = preg_replace(self::SMUGGLING_CHARS, '', $content) ?? $content;

        return trim($this->escapeDelimiters($content));
    }

    /**
     * Escape angle brackets so a string cannot open or close a prompt
     * delimiter. Used for both the user question and document chunks.
     */
    public function escapeDelimiters(string $text): string
    {
        return str_replace(['<', '>'], ['&lt;', '&gt;'], $text);
    }

    /**
     * Screen the model's answer for system-prompt leakage. Returns the answer
     * unchanged, or the INSUFFICIENT_CONTEXT sentinel when the response looks
     * like it echoed internal instructions back to the user.
     */
    public function screenAnswer(string $answer): string
    {
        foreach (self::LEAK_CANARIES as $canary) {
            if (stripos($answer, $canary) !== false) {
                return 'INSUFFICIENT_CONTEXT';
            }
        }

        return $answer;
    }
}
