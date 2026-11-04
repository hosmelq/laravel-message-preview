import {Chip, Meter, Surface, Typography} from '@heroui/react'
import pluralize from 'pluralize'
import type {ReactNode} from 'react'

import {gmailClippingSize, gmailWarningSize, largeImageSize, type SizeCheck} from '#/lib/checks'
import {formatBytes} from '#/lib/format'
import type {RenderedPreview} from '#/types/preview'

function total(sizes: number[]) {
  return sizes.reduce((sum, size) => sum + size, 0)
}

interface Part {
  detail: ReactNode
  id: string
  label: string
  size: string
  status: null | {color: 'danger' | 'warning'; label: string}
}

interface SizePanelProps {
  check: SizeCheck
  rendered: RenderedPreview
}

export function SizePanel(props: SizePanelProps) {
  const {check, rendered} = props
  const html = rendered.html_size ?? 0
  const text = rendered.text === null ? null : new TextEncoder().encode(rendered.text).length
  const files = rendered.attachments.filter((attachment) => !attachment.inline)
  const unknown = check.images.filter((image) => image.size === null).length
  const clipping = check.findings.find((finding) => finding.id === 'gmail-clipping')
  const heavy = check.findings.find((finding) => finding.id === 'heavy-images')
  const parts: Part[] = [
    {
      detail:
        rendered.html === null ? null : (
          <div className="flex flex-col gap-1.5">
            <Meter
              aria-label={`HTML size against Gmail’s ${formatBytes(gmailClippingSize)} limit`}
              color={
                html >= gmailClippingSize
                  ? 'danger'
                  : html >= gmailWarningSize
                    ? 'warning'
                    : 'success'
              }
              maxValue={gmailClippingSize}
              size="sm"
              value={Math.min(html, gmailClippingSize)}
              valueLabel={`${formatBytes(html)} of ${formatBytes(gmailClippingSize)}`}
            >
              <Meter.Track>
                <Meter.Fill />
              </Meter.Track>
            </Meter>
            <Typography color="muted" type="body-xs">
              Gmail clips past {formatBytes(gmailClippingSize)}
            </Typography>
          </div>
        ),
      id: 'html',
      label: 'HTML body',
      size: rendered.html === null ? 'None' : formatBytes(html),
      status:
        clipping === undefined
          ? null
          : clipping.severity === 'error'
            ? {color: 'danger', label: 'Clipped by Gmail'}
            : {color: 'warning', label: 'Close to the limit'},
    },
    {
      detail: null,
      id: 'text',
      label: 'Plain-text body',
      size: text === null ? 'None' : formatBytes(text),
      status: null,
    },
    {
      detail:
        check.images.length === 0
          ? null
          : [
              pluralize('image', check.images.length, true),
              unknown > 0 ? `${unknown} of unknown size` : null,
              heavy === undefined ? null : `${heavy.count} over ${formatBytes(largeImageSize)}`,
            ]
              .filter((part) => part !== null)
              .join(', '),
      id: 'images',
      label: 'Images',
      size:
        check.images.length === 0
          ? 'None'
          : formatBytes(total(check.images.map((image) => image.size ?? 0))),
      status: heavy === undefined ? null : {color: 'warning', label: 'Heavy'},
    },
    {
      detail: files.length === 0 ? null : pluralize('file', files.length, true),
      id: 'attachments',
      label: 'Attachments',
      size: files.length === 0 ? 'None' : formatBytes(total(files.map((file) => file.size))),
      status: null,
    },
  ]

  return (
    <div className="@container">
      <ul className="grid grid-cols-1 gap-2 @lg:grid-cols-2 @5xl:grid-cols-4">
        {parts.map((part) => (
          <li key={part.id}>
            <Surface className="flex h-full flex-col gap-3 rounded-3xl p-6" variant="secondary">
              <div className="flex items-center justify-between gap-2">
                <Typography color="muted" type="body-sm">
                  {part.label}
                </Typography>
                {part.status !== null && (
                  <Chip color={part.status.color} size="sm" variant="soft">
                    {part.status.label}
                  </Chip>
                )}
              </div>
              <div className="tabular-nums">
                <Typography render={(attributes) => <p {...attributes} />} type="h4">
                  {part.size}
                </Typography>
              </div>
              {typeof part.detail === 'string' ? (
                <Typography color="muted" type="body-xs">
                  {part.detail}
                </Typography>
              ) : (
                part.detail
              )}
            </Surface>
          </li>
        ))}
      </ul>
    </div>
  )
}
