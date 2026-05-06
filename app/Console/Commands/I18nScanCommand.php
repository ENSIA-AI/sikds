<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Extracts every translation key referenced via __('...'), @lang('...') or
 * trans('...') and writes any missing entries into lang/{locale}.json.
 *
 * - For the source locale (FR by default), missing keys are populated with
 *   the key itself (since keys *are* the FR copy).
 * - For other locales, missing keys get the empty string "" so reviewers can
 *   spot what still needs translating.
 */
class I18nScanCommand extends Command
{
    protected $signature = 'i18n:scan
        {--check : Exit non-zero if any locale would gain new keys (CI mode)}';

    protected $description = 'Scan blade/php files for translation keys and sync lang/{locale}.json files.';

    public function handle(): int
    {
        $sourceLocale = (string) config('app.locale', 'fr');
        $supported = array_keys(config('languages.lang', [$sourceLocale => $sourceLocale]));
        $langPath = base_path('lang');

        $keys = $this->extractKeys();
        $this->info(sprintf('Found %d unique translation keys.', count($keys)));

        $hasChanges = false;

        foreach ($supported as $locale) {
            $file = $langPath.DIRECTORY_SEPARATOR.$locale.'.json';
            $existing = is_file($file)
                ? (array) json_decode((string) file_get_contents($file), true)
                : [];

            $merged = $existing;
            $added = 0;
            foreach ($keys as $key) {
                if (! array_key_exists($key, $merged)) {
                    $merged[$key] = $locale === $sourceLocale ? $key : '';
                    $added++;
                }
            }

            if ($added > 0) {
                $hasChanges = true;
                $this->line(sprintf('  [%s] +%d new keys', $locale, $added));

                if (! $this->option('check')) {
                    ksort($merged, SORT_NATURAL | SORT_FLAG_CASE);
                    file_put_contents(
                        $file,
                        json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
                    );
                }
            } else {
                $this->line(sprintf('  [%s] up to date', $locale));
            }
        }

        if ($this->option('check') && $hasChanges) {
            $this->error('Translation files are out of sync. Run: php artisan i18n:scan');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function extractKeys(): array
    {
        $finder = (new Finder())
            ->files()
            ->in([
                base_path('app'),
                base_path('resources/views'),
                base_path('routes'),
            ])
            ->name(['*.php', '*.blade.php']);

        $keys = [];

        // Matches: __('key'), __("key"), @lang('key'), trans('key'), Lang::get('key')
        // Captures the literal string (single or double quoted) only — variables are skipped.
        $pattern = '/(?:__|@lang|trans|Lang::get)\s*\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/u';

        foreach ($finder as $file) {
            $contents = $file->getContents();
            if (! preg_match_all($pattern, $contents, $matches)) {
                continue;
            }
            foreach ($matches[2] as $rawKey) {
                $key = stripcslashes($rawKey);
                if ($key === '') {
                    continue;
                }
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }
}
