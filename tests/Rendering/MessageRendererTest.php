<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\ConfiguredMessage;
use HosmelQ\MessagePreview\Rendering\MessageRenderer;
use HosmelQ\MessagePreview\Rendering\PreviewTranslator;
use HosmelQ\MessagePreview\Rendering\RenderedMessage;
use HosmelQ\MessagePreview\Support\PreviewConfig;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\DatabaseReceipt;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\MailableNotice;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\MailNotice;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\MarkdownNotice;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\Recipient;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\SampleReceipt;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\TextReceipt;
use HosmelQ\MessagePreview\Tests\Fixtures\Registered\RegisteredNotice;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;

uses(DatabaseMigrations::class);

it('explains why a notification renders no email', function (
    PreviewConfig $config,
    string $error,
): void {
    $message = new ConfiguredMessage(RegisteredNotice::class, $config);

    expect(fn (): RenderedMessage => new MessageRenderer()->render($message, 'default'))
        ->toThrow(LogicException::class, $error);
})->with([
    'no mail route' => [
        PreviewConfig::configure()->withNotifiable(
            fn (): AnonymousNotifiable => new AnonymousNotifiable(),
        ),
        'No email was captured.',
    ],
    'no notifiable' => [PreviewConfig::configure(), 'Configure withNotifiable()'],
    'no toMail() method' => [
        PreviewConfig::configure()
            ->withMessage(fn (): Notification => new class () extends Notification {})
            ->withNotifiable(fn (): Recipient => new Recipient('Ana', 'en')),
        'No email was captured.',
    ],
    'not notifiable' => [
        PreviewConfig::configure()->withNotifiable(fn (): stdClass => new stdClass()),
        'No email was captured.',
    ],
]);

it('rejects factories that return neither a mailable nor a notification', function (): void {
    $message = new ConfiguredMessage(
        TextReceipt::class,
        PreviewConfig::configure()->withMessage(fn (): stdClass => new stdClass()),
    );

    expect(fn (): RenderedMessage => new MessageRenderer()->render($message, 'default'))
        ->toThrow(
            LogicException::class,
            'The preview factory must return a mailable or a notification.',
        );
});

it('restores the locale and the translator when rendering fails', function (): void {
    $translator = app(Translator::class);
    $message = new ConfiguredMessage(
        TextReceipt::class,
        PreviewConfig::configure()->withMessage(function (): never {
            app()->setLocale('fr');

            throw new RuntimeException('Rendering failed.');
        }),
    );

    expect(fn (): RenderedMessage => new MessageRenderer()->render($message, 'default', 'es'))
        ->toThrow(RuntimeException::class, 'Rendering failed.')
        ->and(app()->getLocale())->toBe('en')
        ->and(app(Translator::class))->toBe($translator)
        ->and(Lang::getFacadeRoot())->toBe($translator);
});

it('rolls back database changes when rendering fails', function (): void {
    $message = new ConfiguredMessage(DatabaseReceipt::class, DatabaseReceipt::previewConfig());

    expect(fn (): RenderedMessage => new MessageRenderer()->render($message, 'broken'))
        ->toThrow(RuntimeException::class, 'Database receipt failed.')
        ->and(DB::table('preview_records')->count())->toBe(0)
        ->and(DB::transactionLevel())->toBe(0);
});

it('builds a message without a factory through its constructor', function (): void {
    $message = new ConfiguredMessage(TextReceipt::class, PreviewConfig::configure());

    expect(new MessageRenderer()->render($message, 'default')->text)->toContain('Literal text');
});

it('gives a message that asks for the translator by class the recording one', function (): void {
    $message = new ConfiguredMessage(TextReceipt::class, PreviewConfig::configure()->withMessage(
        fn (): Mailable => new class () extends Mailable {
            public function build(Translator $translator): self
            {
                return $this->html('<p>'.$translator->get('messages.absent').'</p>');
            }
        },
    ));
    $rendered = new MessageRenderer()->render($message, 'default');

    expect($rendered->html)->toBe('<p>messages.absent</p>')
        ->and($rendered->missingTranslations)->toBe([
            ['key' => 'messages.absent', 'lines' => ['messages.absent']],
        ])
        ->and(app(Translator::class))->not->toBeInstanceOf(PreviewTranslator::class);
});

it('ignores the notifiable factory of a mailable', function (): void {
    $message = new ConfiguredMessage(TextReceipt::class, PreviewConfig::configure()->withNotifiable(
        fn (): never => throw new RuntimeException('The notifiable factory must not run.'),
    ));

    expect(new MessageRenderer()->render($message, 'default')->text)->toContain('Literal text');
});

it('keeps database changes when rollback is disabled', function (): void {
    config(['message-preview.database.rollback' => false]);

    $rendered = new MessageRenderer()->render(
        new ConfiguredMessage(DatabaseReceipt::class, DatabaseReceipt::previewConfig()),
        'default',
    );

    expect($rendered->html)->toBe('<p>Factory record: 1</p>')
        ->and(DB::table('preview_records')->count())->toBe(2);
});

it('passes the rendered notification to its notifiable factory', function (): void {
    $notification = new MailNotice('Ana');
    $notifiable = function (MailNotice $selected) use ($notification): Recipient {
        expect($selected)->toBe($notification);

        return new Recipient($selected->name, 'es');
    };
    $unexpected = fn (): never => throw new RuntimeException('The factory must not run.');
    $config = PreviewConfig::configure()
        ->withMessage($unexpected)
        ->withNotifiable($unexpected)
        ->withVariant('selected', fn (): MailNotice => $notification, $notifiable);
    $rendered = new MessageRenderer()->render(
        new ConfiguredMessage(MailNotice::class, $config),
        'selected',
    );

    expect($rendered->email->getTo()[0]->getAddress())->toBe('ana@example.test')
        ->and($rendered->locale)->toBe('es');
});

it('preserves an existing transaction', function (): void {
    DB::beginTransaction();
    DB::table('preview_records')->insert(['name' => 'Existing record']);

    new MessageRenderer()->render(
        new ConfiguredMessage(DatabaseReceipt::class, DatabaseReceipt::previewConfig()),
        'default',
    );

    expect(DB::transactionLevel())->toBe(1)
        ->and(DB::table('preview_records')->pluck('name')->all())->toBe(['Existing record']);
});

it('records the translations of the Markdown notification template', function (): void {
    $rendered = new MessageRenderer()->render(
        new ConfiguredMessage(MarkdownNotice::class, MarkdownNotice::previewConfig()),
        'default',
        'es',
    );

    expect($rendered->html)->toContain('Welcome Ana', 'Your account is ready.')
        ->and($rendered->text)->toContain('Welcome Ana', 'Your account is ready.')
        ->and(array_column($rendered->missingTranslations, 'key'))->toContain('Regards,');
});

it('renders notifications with the metadata Laravel adds', function (): void {
    $config = MailNotice::previewConfig();
    $render = fn (string $variant): string => new MessageRenderer()->render(
        new ConfiguredMessage(MailNotice::class, $config),
        $variant,
    )->html;

    expect($render('default'))->toContain(MailNotice::class, 'Queued: yes')
        ->and(Str::isUuid(Str::between($render('default'), 'ID: ', '</p>')))->toBeTrue()
        ->and(Str::isUuid(Str::between($render('empty-id'), 'ID: ', '</p>')))->toBeTrue()
        ->and($render('existing-id'))->toContain('ID: existing-notification-id');
});

it('renders the same sample data each time it renders a variant', function (): void {
    $message = new ConfiguredMessage(SampleReceipt::class, SampleReceipt::previewConfig());
    $html = new MessageRenderer()->render($message, 'default')->html;

    expect(new MessageRenderer()->render($message, 'default')->html)->toBe($html)
        ->and(new MessageRenderer()->render($message, 'other')->html)->not->toBe($html);
});

it('rolls back the connection the render began on', function (): void {
    config(['database.connections.other' => ['database' => ':memory:', 'driver' => 'sqlite']]);

    new MessageRenderer()->render(
        new ConfiguredMessage(
            TextReceipt::class,
            PreviewConfig::configure()->withMessage(function (): TextReceipt {
                DB::table('preview_records')->insert(['name' => 'Written before switching']);
                DB::setDefaultConnection('other');

                return new TextReceipt();
            }),
        ),
        'default',
    );

    DB::setDefaultConnection('testing');

    expect(DB::table('preview_records')->count())->toBe(0)->and(DB::transactionLevel())->toBe(0);
});

it('rolls back the database changes of a render', function (): void {
    $config = PreviewConfig::configure()
        ->withMessage(function (): MailNotice {
            DB::table('preview_records')->insert(['name' => 'Message']);

            return new MailNotice('Ana');
        })
        ->withNotifiable(function (): Recipient {
            DB::table('preview_records')->insert(['name' => 'Notifiable']);

            return new Recipient('Ana', 'en');
        });
    $rendered = new MessageRenderer()->render(
        new ConfiguredMessage(DatabaseReceipt::class, DatabaseReceipt::previewConfig()),
        'default',
    );

    new MessageRenderer()->render(new ConfiguredMessage(MailNotice::class, $config), 'default');

    expect($rendered->html)->toBe('<p>Factory record: 1</p>')
        ->and(DB::table('preview_records')->count())->toBe(0)
        ->and(DB::transactionLevel())->toBe(0);
});

it('swaps in a recording translator and restores the original one', function (): void {
    $translator = app(Translator::class);

    expect(Lang::getFacadeRoot())->toBe($translator);

    $message = new ConfiguredMessage(
        TextReceipt::class,
        PreviewConfig::configure()->withMessage(function (): TextReceipt {
            expect(app(Translator::class))->toBeInstanceOf(PreviewTranslator::class);

            return new TextReceipt();
        }),
    );

    new MessageRenderer()->render($message, 'default', 'es');

    expect(app(Translator::class))->toBe($translator)
        ->and(Lang::getFacadeRoot())->toBe($translator)
        ->and(app()->getLocale())->toBe('en');
});

it('uses the locale of the notification, then the recipient, then the application', function (
    string $className,
    string $variant,
    string $locale,
): void {
    $message = new ConfiguredMessage($className, $className::previewConfig());

    expect(new MessageRenderer()->render($message, $variant)->locale)->toBe($locale);
})->with([
    'notification' => [MailNotice::class, 'explicit-locale', 'fr'],
    'recipient' => [MailNotice::class, 'default', 'es'],
    'application' => [MailNotice::class, 'app-locale', 'en'],
    'mailable from toMail()' => [MailableNotice::class, 'default', 'de'],
]);

it('uses the selected locale over the locales the message and the recipient prefer', function (
    ConfiguredMessage $message,
    string $variant,
): void {
    expect(new MessageRenderer()->render($message, $variant, 'en')->locale)->toBe('en');
})->with([
    'mailable' => [
        new ConfiguredMessage(TextReceipt::class, PreviewConfig::configure()->withMessage(
            fn (): TextReceipt => new TextReceipt()->locale('fr'),
        )),
        'default',
    ],
    'mailable from toMail()' => [
        new ConfiguredMessage(MailableNotice::class, MailableNotice::previewConfig()),
        'default',
    ],
    'notification' => [
        new ConfiguredMessage(MailNotice::class, MailNotice::previewConfig()),
        'explicit-locale',
    ],
    'recipient' => [
        new ConfiguredMessage(MailNotice::class, MailNotice::previewConfig()),
        'default',
    ],
]);
