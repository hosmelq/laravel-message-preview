<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Links\MessageUrls;

it('collects absolute link and image URLs with their kinds and occurrences', function (): void {
    $html = <<<'HTML'
        <a href="https://remote.example/shared?a=1&amp;b=2">Shared</a>
        <img src="https://remote.example/shared?a=1&amp;b=2" srcset="https://remote.example/small.png 1x, https://remote.example/large.png 2x">
        <table background="https://remote.example/shared?a=1&amp;b=2"></table>
        <picture><source srcset="https://remote.example/small.png 320w"></picture>
        <a href="mailto:hello@example.com">Email</a><a href="/relative">Relative</a><a href="#section">Section</a>
        <a href="http://">Empty</a><img src="cid:logo"><img src="data:image/png;base64,aA=="><img src="//remote.example/a.png">
        HTML;

    expect(new MessageUrls()->collect($html))->toBe([
        [
            'kinds' => ['link', 'image'],
            'occurrences' => 3,
            'url' => 'https://remote.example/shared?a=1&b=2',
        ],
        ['kinds' => ['image'], 'occurrences' => 2, 'url' => 'https://remote.example/small.png'],
        ['kinds' => ['image'], 'occurrences' => 1, 'url' => 'https://remote.example/large.png'],
    ]);
});

it('reads every URL of a srcset', function (): void {
    $html = <<<'HTML'
        <img srcset="https://cdn.example/a,b.png 1x, https://cdn.example/c.png 2x,
            https://cdn.example/d.png,, https://cdn.example/e.png (max-width: 1px, 2x) 3x,
            https://cdn.example/añejo-imágen.png 2x">
        <img srcset="https://cdn.example/f.png 1x,https://cdn.example/g.png 2x">
        HTML;

    expect(array_column(new MessageUrls()->collect($html), 'url'))->toBe([
        'https://cdn.example/a,b.png',
        'https://cdn.example/c.png',
        'https://cdn.example/d.png',
        'https://cdn.example/e.png',
        'https://cdn.example/añejo-imágen.png',
        'https://cdn.example/f.png',
        'https://cdn.example/g.png',
    ]);
});
