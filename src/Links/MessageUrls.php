<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Links;

use function Safe\preg_match_all;

use Dom\HTMLDocument;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;

final readonly class MessageUrls
{
    /**
     * @return list<array{kinds: list<string>, occurrences: int, url: string}>
     */
    public function collect(string $html): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $urls = [];

        foreach ($document->querySelectorAll('a, img, source, [background]') as $element) {
            if ($element->localName === 'a') {
                $this->add($urls, $element->getAttribute('href'), 'link');
            }

            if ($element->localName === 'img') {
                $this->add($urls, $element->getAttribute('src'), 'image');
            }

            preg_match_all(
                '/[\s,]*([^\s,](?:\S*[^\s,])?)(?:,+|(?:\s+[^\s,]+)*\s*(?:,|$))/',
                $element->getAttribute('srcset') ?? '',
                $candidates,
            );

            foreach ($candidates[1] as $candidate) {
                $this->add($urls, $candidate, 'image');
            }

            $this->add($urls, $element->getAttribute('background'), 'image');
        }

        return array_values($urls);
    }

    /**
     * @param array<string, array{kinds: list<string>, occurrences: int, url: string}> $urls
     */
    private function add(array &$urls, null|string $url, string $kind): void
    {
        $url = mb_trim($url ?? '');

        try {
            $uri = new Uri($url);
        } catch (InvalidArgumentException) {
            return;
        }

        if (! in_array($uri->getScheme(), ['http', 'https'], true) || $uri->getHost() === '') {
            return;
        }

        $urls[$url] ??= ['kinds' => [], 'occurrences' => 0, 'url' => $url];

        ++$urls[$url]['occurrences'];

        if (! in_array($kind, $urls[$url]['kinds'], true)) {
            $urls[$url]['kinds'][] = $kind;
        }
    }
}
