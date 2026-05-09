<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Two checks:
 *  1) Each non-source locale (en, ar) must have a value for every key found in
 *     the source locale (fr) JSON file. Empty strings count as missing.
 *  2) Blade templates should not contain hardcoded human-readable text.
 *     Reports any plain text run inside a tag that is not wrapped in __()
 *     and is not in the allowlist (acronyms, brand names, single chars, etc.).
 *
 * The Blade scan is heuristic — exit code only fails on missing translations
 * by default; pass --strict to also fail on hardcoded text.
 */
class I18nLintCommand extends Command
{
    protected $signature = 'i18n:lint
        {--strict : Also fail when blade templates contain hardcoded text}
        {--paths=* : Limit blade scanning to these paths (relative to resources/views)}';

    protected $description = 'Verify translation completeness and detect hardcoded UI text in blade files.';

    /**
     * Strings that are allowed to appear hardcoded (brand, ascii icons, etc.).
     */
    private const ALLOWLIST = [
        'SIKDS', 'MESRS', 'API', 'PDF', 'JSON', 'URL', 'OK', 'ID',
        '✕', '×', '...', '…', '-', '—', '•', '|',
    ];

    public function handle(): int
    {
        $sourceLocale = (string) config('app.locale', 'fr');
        $supported = array_keys(config('languages.lang', [$sourceLocale => $sourceLocale]));
        $langPath = base_path('lang');

        $missing = $this->checkTranslationCompleteness($sourceLocale, $supported, $langPath);
        $hardcoded = $this->checkHardcodedText();

        $this->reportMissing($missing);
        $this->reportHardcoded($hardcoded);

        if ($missing !== []) {
            return self::FAILURE;
        }

        if ($this->option('strict') && $hardcoded !== []) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<int, string>>  locale => list of missing keys
     */
    private function checkTranslationCompleteness(string $sourceLocale, array $supported, string $langPath): array
    {
        $sourceFile = $langPath.DIRECTORY_SEPARATOR.$sourceLocale.'.json';
        if (! is_file($sourceFile)) {
            $this->error("Source locale file missing: {$sourceFile}");

            return [];
        }

        $sourceKeys = array_keys((array) json_decode((string) file_get_contents($sourceFile), true));
        $missing = [];

        foreach ($supported as $locale) {
            if ($locale === $sourceLocale) {
                continue;
            }
            $file = $langPath.DIRECTORY_SEPARATOR.$locale.'.json';
            $entries = is_file($file)
                ? (array) json_decode((string) file_get_contents($file), true)
                : [];

            foreach ($sourceKeys as $key) {
                $value = $entries[$key] ?? null;
                if (! is_string($value) || trim($value) === '') {
                    $missing[$locale][] = $key;
                }
            }
        }

        return $missing;
    }

    /**
     * @return array<int, array{file: string, line: int, text: string}>
     */
    private function checkHardcodedText(): array
    {
        $bases = [base_path('resources/views')];
        $paths = (array) $this->option('paths');
        if ($paths !== []) {
            $bases = array_map(fn ($p) => base_path('resources/views/'.ltrim($p, '/')), $paths);
            $bases = array_filter($bases, 'is_dir');
        }

        if ($bases === []) {
            return [];
        }

        $finder = (new Finder())->files()->in($bases)->name('*.blade.php');

        $findings = [];

        // Match plain text between two tags: >TEXT<
        // We deliberately avoid trying to parse blade — we just look at the
        // raw character runs between '>' and '<' and skip anything that
        // contains blade/php directives or that is inside <script>/<style>.
        $tagText = '/>([^<>{}@]+?)</u';

        foreach ($finder as $file) {
            $contents = $file->getContents();
            // Strip <script>/<style> blocks entirely.
            $stripped = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#sui', '', $contents) ?? $contents;

            if (! preg_match_all($tagText, $stripped, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[1] as [$text, $offset]) {
                $trimmed = trim((string) $text);
                if (! $this->looksLikeHumanText($trimmed)) {
                    continue;
                }

                $line = substr_count(substr($stripped, 0, (int) $offset), "\n") + 1;
                $findings[] = [
                    'file' => str_replace(base_path().'/', '', $file->getRealPath() ?: $file->getPathname()),
                    'line' => $line,
                    'text' => mb_substr($trimmed, 0, 80),
                ];
            }
        }

        return $findings;
    }

    private function looksLikeHumanText(string $text): bool
    {
        if ($text === '' || mb_strlen($text) < 2) {
            return false;
        }
        if (in_array($text, self::ALLOWLIST, true)) {
            return false;
        }
        // Skip pure punctuation / digits / symbols.
        if (! preg_match('/\p{L}/u', $text)) {
            return false;
        }
        // Skip strings that look like CSS classes or identifiers.
        if (preg_match('/^[a-z0-9_-]+$/i', $text) && ! str_contains($text, ' ')) {
            return false;
        }
        // Skip blade/twig leftovers and obvious code fragments.
        if (str_contains($text, '{{') || str_contains($text, '}}') || str_starts_with($text, '@')) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, array<int, string>>  $missing
     */
    private function reportMissing(array $missing): void
    {
        if ($missing === []) {
            $this->info('All locales are complete.');

            return;
        }

        foreach ($missing as $locale => $keys) {
            $this->error(sprintf('[%s] %d missing translations:', $locale, count($keys)));
            foreach (array_slice($keys, 0, 20) as $key) {
                $this->line('  - '.$key);
            }
            if (count($keys) > 20) {
                $this->line(sprintf('  ... and %d more', count($keys) - 20));
            }
        }
    }

    /**
     * @param  array<int, array{file: string, line: int, text: string}>  $hardcoded
     */
    private function reportHardcoded(array $hardcoded): void
    {
        if ($hardcoded === []) {
            $this->info('No hardcoded text detected in blade templates.');

            return;
        }

        $level = $this->option('strict') ? 'error' : 'warn';
        $this->{$level}(sprintf('%d hardcoded text occurrences in blade templates:', count($hardcoded)));
        foreach (array_slice($hardcoded, 0, 50) as $hit) {
            $this->line(sprintf('  %s:%d  %s', $hit['file'], $hit['line'], $hit['text']));
        }
        if (count($hardcoded) > 50) {
            $this->line(sprintf('  ... and %d more (run with --paths=... to narrow)', count($hardcoded) - 50));
        }
    }
}
