<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseReceipt extends Mailable
{
    public function __construct(public int $recordId, private readonly bool $broken = false) {}

    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()
            ->withMessage(fn (): self => new self(
                DB::table('preview_records')->insertGetId(['name' => 'Factory record']),
            ))
            ->withVariant('broken', fn (): self => new self(
                DB::table('preview_records')->insertGetId(['name' => 'Factory record']),
                true,
            ));
    }

    public function content(): Content
    {
        DB::table('preview_records')->insert(['name' => 'Pipeline record']);

        if ($this->broken) {
            throw new RuntimeException('Database receipt failed.');
        }

        return new Content(htmlString: '<p>'.DB::table('preview_records')
            ->where('id', $this->recordId)->value('name').': '.$this->recordId.'</p>');
    }
}
