@inject('clientFamilies', 'HosmelQ\MessagePreview\Compatibility\ClientFamilies')
@inject('vite', 'HosmelQ\MessagePreview\Vite')
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <script>
            {
                const stored = localStorage.getItem('heroui-theme') ?? 'system';
                const dark = matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = stored === 'system' ? (dark ? 'dark' : 'light') : stored;

                document.documentElement.classList.add(theme);
                document.documentElement.dataset.theme = theme;
            }
        </script>

        {{ $vite->reactRefresh() }}
        {{ $vite('resources/js/app.tsx') }}

        <title>Message Preview</title>
    </head>

    <body class="bg-background text-foreground font-sans antialiased">
        <div id="app"></div>

        @php
            $settings = [
                'app_name' => config()->string('app.name') ?: 'Message Preview',
                'clients' => $clientFamilies->all(),
                'default_mailer' => config()->string('mail.default'),
                'mailers' => array_keys(config()->array('mail.mailers')),
                'poll_interval' => config()->integer('message-preview.auto_refresh.interval'),
            ];
        @endphp

        <script id="message-preview-settings" type="application/json">@json($settings)</script>
    </body>
</html>
