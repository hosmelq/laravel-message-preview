import {Envelope, TriangleExclamation} from '@gravity-ui/icons'
import {Button, Card, Description, Label, ListBox, Typography} from '@heroui/react'
import pluralize from 'pluralize'
import type {ReactNode} from 'react'

import {appHotkeys} from '#/lib/hotkeys'
import {useIsNarrow, usePrefetchNeighbors, usePreview, useSelection, useView} from '#/lib/preview'
import {previewStore} from '#/lib/store'
import {EmailPages} from '#/pages/_components/EmailPages'
import {EmailToolbar} from '#/pages/_components/EmailToolbar'
import {EmptyView} from '#/pages/_components/EmptyView'
import {HotkeyKbd} from '#/pages/_components/HotkeyKbd'
import type {MessageSummary, Selection} from '#/types/preview'

interface MessageViewProps {
  message: MessageSummary
  selection: Selection
}

export function MessageView(props: MessageViewProps) {
  const {message, selection} = props
  const {isPlaceholderData, isSuccess, rendered} = usePreview(selection)
  const view = useView(rendered)

  usePrefetchNeighbors(selection, isSuccess && !isPlaceholderData)

  return (
    <Card className="min-h-0 min-w-0 flex-1 overflow-hidden p-0">
      <div className="flex min-h-0 flex-1 flex-col">
        <EmailToolbar rendered={rendered} selection={selection} view={view} />
        <EmailPages message={message} selection={selection} />
      </div>
    </Card>
  )
}

interface HintProps {
  children: ReactNode
  keys: (keyof typeof appHotkeys)[]
}

function Hint(props: HintProps) {
  const {children, keys} = props

  return (
    <span className="flex items-center gap-1.5 whitespace-nowrap">
      {keys.map((key) => (
        <HotkeyKbd key={key} id={key} />
      ))}
      <Typography color="muted" render={(attributes) => <span {...attributes} />} type="body-xs">
        {children}
      </Typography>
    </span>
  )
}

export function EmptyDetail() {
  const {catalog, messages, open} = useSelection()
  const isNarrow = useIsNarrow()
  const failing = messages.filter(
    (item): item is MessageSummary & {error: string} => item.error !== null,
  )

  if (catalog.isPending) {
    return null
  }

  if (messages.length === 0) {
    return (
      <div className="flex h-full items-center justify-center p-6">
        <EmptyView
          description={
            <>
              Mailables and notifications appear here once they exist in one of the paths configured
              in <Typography.Code>config/message-preview.php</Typography.Code>.
            </>
          }
          icon={Envelope}
          isPageHeading
          title="No messages found"
        />
      </div>
    )
  }

  return (
    <div className="flex min-h-full items-center justify-center p-6">
      <EmptyView
        icon={Envelope}
        isPageHeading
        title={`${pluralize('message', messages.length, true)}${failing.length > 0 ? `, ${failing.length} failing` : ''}`}
      >
        {failing.length > 0 && (
          <div className="w-full max-w-sm text-start">
            <ListBox
              aria-label="Messages that fail"
              onAction={(key) => open({message: String(key)})}
            >
              {failing.map((item) => (
                <ListBox.Item key={item.key} id={item.key} textValue={item.name}>
                  <TriangleExclamation className="size-4 shrink-0 text-danger" />
                  <div className="flex min-w-0 flex-col">
                    <Label>{item.name}</Label>
                    <Description>{item.error.split('\n')[0]}</Description>
                  </div>
                </ListBox.Item>
              ))}
            </ListBox>
          </div>
        )}
        {isNarrow ? (
          <Button
            variant="secondary"
            onPress={() => previewStore.trigger.sidebarToggled({isOpen: true})}
          >
            Browse messages
          </Button>
        ) : (
          <div className="flex flex-wrap items-center justify-center gap-5">
            <Hint keys={['message.next', 'message.previous']}>to move</Hint>
            <Hint keys={['search.focus']}>to search</Hint>
            <Hint keys={['help.shortcuts']}>for shortcuts</Hint>
          </div>
        )}
      </EmptyView>
    </div>
  )
}
