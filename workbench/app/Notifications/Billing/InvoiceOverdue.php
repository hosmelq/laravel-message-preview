<?php

declare(strict_types=1);

namespace Workbench\App\Notifications\Billing;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;

final class InvoiceOverdue extends Notification
{
    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()
            ->withNotifiable(
                fn (): AnonymousNotifiable
                    => new AnonymousNotifiable()->route('mail', 'ana@example.test'),
            )
            ->withVariant('missing-invoice', function (): self {
                throw new RuntimeException(
                    'Invoice SG-1042 could not be found. Choose another invoice fixture.',
                );
            });
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject('A reminder about invoice SG-1042')
            ->cc('billing@studio.test', 'Studio Goods Billing')
            ->greeting('Hi Ana,')
            ->line(
                'Your $48.00 payment for invoice SG-1042 was due on October 28. You can review the invoice and pay securely below.',
            )
            ->action('View invoice', url('/invoices/SG-1042'))
            ->line('Already paid? Reply to this email and we will check it for you.');
    }
}
