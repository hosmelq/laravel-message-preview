import {Copy} from '@gravity-ui/icons'
import {Button, Tooltip, Typography} from '@heroui/react'
import {useRef} from 'react'

import {canCopy, copyToClipboard} from '#/lib/export'
import {withShortDataUris} from '#/lib/format'
import {frameDocument} from '#/lib/frame'
import {HighlightedHtml} from '#/pages/_components/HighlightedHtml'
import type {ColorScheme, RenderedPreview} from '#/types/preview'

function fit(frame: HTMLIFrameElement) {
  const root = frame.contentDocument?.documentElement
  const parent = frame.parentElement

  if (root !== undefined && root !== null && parent !== null) {
    parent.style.minHeight = `${parent.offsetHeight}px`
    frame.style.height = '0px'
    frame.style.height = `${root.scrollHeight}px`
    parent.style.minHeight = ''
  }
}

function fitToContent(frame: HTMLIFrameElement) {
  const root = frame.contentDocument?.documentElement

  fit(frame)

  if (root !== undefined && root !== null) {
    new ResizeObserver(() => fit(frame)).observe(root)
  }
}

export function readyOnMount(onReady: () => void) {
  return (element: HTMLElement | null) => {
    if (element !== null) {
      onReady()
    }
  }
}

const imageWait = 1000

interface MessageFrameProps {
  onReady: () => void
  scheme: ColorScheme
  srcDoc: string
  title: string
}

function MessageFrame(props: MessageFrameProps) {
  const {onReady, scheme, srcDoc, title} = props
  const imageTimer = useRef<number>(undefined)

  return (
    <iframe
      ref={(frame) => {
        if (frame === null) {
          return
        }

        imageTimer.current = window.setTimeout(() => {
          fit(frame)
          onReady()
        }, imageWait)

        return () => window.clearTimeout(imageTimer.current)
      }}
      className="block w-full"
      referrerPolicy="no-referrer"
      sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
      srcDoc={srcDoc}
      style={{colorScheme: scheme}}
      title={title}
      onLoad={(event) => {
        window.clearTimeout(imageTimer.current)
        fitToContent(event.currentTarget)
        onReady()
      }}
    />
  )
}

interface MessageSourceProps {
  preview: RenderedPreview
}

export function MessageSource(props: MessageSourceProps) {
  const {preview} = props
  const headers = preview.headers.map((header) => `${header.name}: ${header.value}`).join('\n')
  const body = preview.html ?? preview.text ?? ''
  const withHeaders = (text: string) => (headers === '' ? text : `${headers}\n\n${text}`)
  const shown = withShortDataUris(body)

  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-center gap-2">
        <Typography color="muted" type="body-xs">
          {preview.html === null ? 'Headers and plain text' : 'Headers and HTML'}
        </Typography>
        {canCopy && (
          <Tooltip>
            <Button
              aria-label="Copy the source"
              className="ms-auto"
              isIconOnly
              size="sm"
              variant="ghost"
              onPress={() => void copyToClipboard(withHeaders(body), 'source')}
            >
              <Copy />
            </Button>
            <Tooltip.Content>Copy the source</Tooltip.Content>
          </Tooltip>
        )}
      </div>
      <Typography.Prose>
        <pre className="my-0 wrap-anywhere whitespace-pre-wrap">
          {headers !== '' && `${headers}\n\n`}
          {preview.html === null ? shown : <HighlightedHtml code={shown} />}
        </pre>
      </Typography.Prose>
    </div>
  )
}

interface MessageBodyProps {
  blockImages: boolean
  onReady: () => void
  preview: RenderedPreview
  scheme: ColorScheme
  title: string
  view: 'html' | 'text'
}

export function MessageBody(props: MessageBodyProps) {
  const {blockImages, onReady, preview, scheme, title, view} = props

  if (view === 'text' && preview.text !== null) {
    return (
      <div ref={readyOnMount(onReady)} className="p-4 wrap-anywhere whitespace-pre-wrap">
        <Typography render={(attributes) => <div {...attributes} />} type="body-sm">
          {preview.text}
        </Typography>
      </div>
    )
  }

  if (preview.html !== null) {
    return (
      <MessageFrame
        scheme={scheme}
        srcDoc={frameDocument(preview.html, {blockImages, scheme})}
        title={title}
        onReady={onReady}
      />
    )
  }

  return (
    <div ref={readyOnMount(onReady)} className="p-4">
      <Typography color="muted" type="body-sm">
        This message has no body.
      </Typography>
    </div>
  )
}
