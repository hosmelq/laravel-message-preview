import {ArrowDownToLine} from '@gravity-ui/icons'
import {Link, ScrollShadow, Separator, Typography} from '@heroui/react'
import {useSelector} from '@xstate/store-react'
import {Fragment, useReducer, useRef} from 'react'

import {formatAddresses, formatBytes} from '#/lib/format'
import {usePreview, useView} from '#/lib/preview'
import {restoreSpotlight, spotlight} from '#/lib/spotlight'
import {previewStore} from '#/lib/store'
import {EmailViewport} from '#/pages/_components/EmailViewport'
import {MessageBody, MessageSource, readyOnMount} from '#/pages/_components/MessageBody'
import {PreviewFailure} from '#/pages/_components/PreviewFailure'
import type {
  ColorScheme,
  MessageSummary,
  Preview,
  RenderedPreview,
  Selection,
  ViewMode,
} from '#/types/preview'

interface Page {
  blockImages: boolean
  error: Error | null
  id: number
  key: string
  preview: Preview | undefined
  scheme: ColorScheme
  view: ViewMode
}

interface Pages {
  next: null | Page
  shown: null | Page
}

type PagesAction = {id: number; type: 'ready'} | {page: Omit<Page, 'id'>; type: 'changed'}

function pages(state: Pages, action: PagesAction): Pages {
  if (action.type === 'ready') {
    return state.next?.id === action.id ? {next: null, shown: state.next} : state
  }

  return {...state, next: {...action.page, id: ((state.next ?? state.shown)?.id ?? 0) + 1}}
}

const pageContent = ['blockImages', 'error', 'preview', 'scheme', 'view'] as const

interface EmailHeaderProps {
  preview: RenderedPreview
}

function EmailHeader(props: EmailHeaderProps) {
  const {preview} = props
  const addresses = (
    [
      ['From', preview.from],
      ['To', preview.to],
      ['Cc', preview.cc],
      ['Bcc', preview.bcc],
      ['Reply to', preview.reply_to],
    ] as const
  ).filter(([, list]) => list.length > 0)
  const attachments = preview.attachments.filter((attachment) => !attachment.inline)

  return (
    <header className="flex flex-col gap-3 px-5 pt-3 pb-5">
      <Typography
        render={({children, ...attributes}) => <h1 {...attributes}>{children}</h1>}
        type="h4"
      >
        {preview.subject ?? 'No subject'}
      </Typography>
      <dl className="grid grid-cols-[6rem_minmax(0,1fr)] items-baseline gap-x-4 gap-y-1 wrap-anywhere">
        {addresses.map(([label, list]) => (
          <Fragment key={label}>
            <Typography
              color="muted"
              render={(attributes) => <dt {...attributes} />}
              type="body-sm"
            >
              {label}
            </Typography>
            <Typography render={(attributes) => <dd {...attributes} />} type="body-sm">
              {formatAddresses(list)}
            </Typography>
          </Fragment>
        ))}
        {attachments.length > 0 && (
          <>
            <Typography
              color="muted"
              render={(attributes) => <dt {...attributes} />}
              type="body-sm"
            >
              Attachments
            </Typography>
            <Typography render={(attributes) => <dd {...attributes} />} type="body-sm">
              <span className="flex flex-wrap gap-x-4 gap-y-1">
                {attachments.map((attachment) => (
                  <span key={attachment.url} className="flex items-baseline gap-1.5">
                    <Link download href={attachment.url}>
                      {attachment.filename ?? 'Untitled attachment'}
                      <Link.Icon>
                        <ArrowDownToLine />
                      </Link.Icon>
                    </Link>
                    <span className="whitespace-nowrap tabular-nums">
                      <Typography
                        color="muted"
                        render={(attributes) => <span {...attributes} />}
                        type="body-sm"
                      >
                        {formatBytes(attachment.size)}
                      </Typography>
                    </span>
                  </span>
                ))}
              </span>
            </Typography>
          </>
        )}
      </dl>
    </header>
  )
}

interface EmailPageProps {
  message: MessageSummary
  onReady: (id: number) => void
  page: Page
}

function EmailPage(props: EmailPageProps) {
  const {message, onReady, page} = props
  const {preview} = page
  const ready = () => onReady(page.id)

  if (preview?.status !== 'rendered') {
    return (
      <div ref={readyOnMount(ready)} className="p-5">
        <PreviewFailure
          error={
            preview?.error ?? {
              exception: 'The preview could not be loaded',
              file: null,
              line: null,
              message: page.error?.message ?? '',
            }
          }
          isConfiguration={preview?.status === 'failed' && message.error !== null}
        />
      </div>
    )
  }

  const Viewport = page.view === 'html' ? EmailViewport : Fragment

  return (
    <article>
      <div className="[@container(min-height:36rem)_and_(min-width:40rem)]:hidden">
        <EmailHeader preview={preview} />
        <Separator />
      </div>
      <div className="overflow-x-auto">
        {page.view === 'source' ? (
          <div ref={readyOnMount(ready)} className="p-5">
            <MessageSource preview={preview} />
          </div>
        ) : (
          <Viewport>
            <MessageBody
              blockImages={page.blockImages}
              preview={preview}
              scheme={page.scheme}
              title={`${message.name} email preview`}
              view={page.view}
              onReady={ready}
            />
          </Viewport>
        )}
      </div>
    </article>
  )
}

interface EmailPagesProps {
  message: MessageSummary
  selection: Selection
}

export function EmailPages(props: EmailPagesProps) {
  const {message, selection} = props
  const {error, preview, rendered} = usePreview(selection)
  const view = useView(rendered)
  const scheme = useSelector(previewStore, (state) => state.context.scheme)
  const blockImages = useSelector(previewStore, (state) => state.context.blockImages)
  const [{next, shown}, dispatch] = useReducer(pages, {next: null, shown: null})
  const shownKey = useRef<null | string>(null)
  const shownId = useRef<null | number>(null)
  const shownPreview = useRef<Preview | undefined>(undefined)
  const showWhenReady = (id: number) => dispatch({id, type: 'ready'})
  const latest = next ?? shown
  const page = {
    blockImages,
    error,
    key: `${selection.message}:${selection.variant}`,
    preview,
    scheme,
    view,
  }

  if (
    (preview !== undefined || error !== null) &&
    (latest === null || pageContent.some((field) => latest[field] !== page[field]))
  ) {
    dispatch({page, type: 'changed'})
  }

  return (
    <div className="@container-size flex min-h-0 flex-1 flex-col">
      {shown?.preview?.status === 'rendered' && (
        <div
          data-shown
          className="hidden [@container(min-height:36rem)_and_(min-width:40rem)]:block"
        >
          <EmailHeader preview={shown.preview} />
          <Separator />
        </div>
      )}
      <ScrollShadow
        ref={(scroller) => {
          if (scroller === null || shown === null || shown.id === shownId.current) {
            return
          }

          shownId.current = shown.id

          if (shown.key !== shownKey.current) {
            scroller.scrollTop = 0
          }

          if (shown.key === shownKey.current && shown.preview === shownPreview.current) {
            restoreSpotlight()
          } else {
            spotlight(null)
          }

          shownKey.current = shown.key
          shownPreview.current = shown.preview
        }}
        className="min-h-0 flex-1"
      >
        <div className="relative">
          {[shown, next].map(
            (item) =>
              item !== null && (
                <div
                  key={item.id}
                  data-shown={item === shown || undefined}
                  data-view={item.view}
                  className={
                    item === shown
                      ? undefined
                      : 'invisible absolute inset-x-0 top-0 h-0 overflow-hidden'
                  }
                >
                  <EmailPage message={message} page={item} onReady={showWhenReady} />
                </div>
              ),
          )}
        </div>
      </ScrollShadow>
    </div>
  )
}
