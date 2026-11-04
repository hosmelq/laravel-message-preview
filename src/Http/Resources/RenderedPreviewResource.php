<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use HosmelQ\MessagePreview\Rendering\RenderedMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Part\DataPart;

/**
 * @property RenderedMessage $resource
 */
final class RenderedPreviewResource extends JsonResource
{
    public static $wrap = 'data';

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $email = $this->resource->email;
        $attachments = array_values($email->getAttachments());
        $route = [
            'locale' => $request->query('locale'),
            'message' => $request->route('message'),
            'variant' => $request->route('variant'),
        ];

        return [
            'attachments' => array_map(static fn (DataPart $attachment, int $index): array => [
                'content_type' => $attachment->getContentType(),
                'filename' => $attachment->getFilename(),
                'inline' => $attachment->getDisposition() === 'inline',
                'size' => mb_strlen($attachment->getBody(), '8bit'),
                'url' => route('message-preview.previews.attachment', [
                    ...$route,
                    'index' => $index,
                ]),
            ], $attachments, array_keys($attachments)),
            'bcc' => AddressResource::collection($email->getBcc()),
            'cc' => AddressResource::collection($email->getCc()),
            'from' => AddressResource::collection($email->getFrom()),
            'headers' => array_map(static fn (HeaderInterface $header): array => [
                'name' => $header->getName(),
                'value' => $header->getBodyAsString(),
            ], array_filter(
                iterator_to_array($email->getHeaders()->all(), false),
                static fn (mixed $header): bool => $header instanceof HeaderInterface,
            )),
            'html' => $this->resource->html,
            'html_size' => $this->resource->htmlSize,
            'locale' => $this->resource->locale,
            'missing_replacements' => $this->resource->missingReplacements,
            'missing_translations' => $this->resource->missingTranslations,
            'reply_to' => AddressResource::collection($email->getReplyTo()),
            'status' => 'rendered',
            'subject' => $email->getSubject(),
            'text' => $this->resource->text,
            'to' => AddressResource::collection($email->getTo()),
            'urls' => [
                'eml' => route('message-preview.previews.eml', $route),
                'html' => $this->resource->html === null
                    ? null
                    : route('message-preview.previews.html', $route),
                'text' => $this->resource->text === null
                    ? null
                    : route('message-preview.previews.text', $route),
            ],
        ];
    }
}
