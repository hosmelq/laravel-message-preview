<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Support;

use Symfony\Component\Finder\Finder;

final readonly class PreviewLocales
{
    /**
     * @return list<string>
     */
    public function all(): array
    {
        $locales = [config()->string('app.locale'), config()->string('app.fallback_locale')];

        if (is_dir(lang_path())) {
            foreach (Finder::create()
                ->directories()
                ->in(lang_path())
                ->depth(0)
                ->exclude('vendor') as $directory) {
                $locales[] = $directory->getFilename();
            }

            foreach (Finder::create()
                ->files()
                ->in(lang_path())
                ->depth(0)
                ->name('*.json') as $file) {
                $locales[] = $file->getFilenameWithoutExtension();
            }
        }

        $locales = array_unique($locales);

        sort($locales, SORT_STRING);

        return $locales;
    }
}
