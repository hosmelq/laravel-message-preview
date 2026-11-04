import {TriangleExclamation} from '@gravity-ui/icons'
import {Button, Card, Drawer, ScrollShadow} from '@heroui/react'
import {useSelector} from '@xstate/store-react'

import {messageLabel} from '#/lib/messages'
import {useAutoRefresh, useIsNarrow, useSelection} from '#/lib/preview'
import {settings} from '#/lib/settings'
import {previewStore} from '#/lib/store'
import {AppIdentity} from '#/pages/_components/AppIdentity'
import {ChecksLayout} from '#/pages/_components/ChecksLayout'
import {EmptyView} from '#/pages/_components/EmptyView'
import {MessageSidebar} from '#/pages/_components/MessageSidebar'
import {MessageTabs} from '#/pages/_components/MessageTabs'
import {EmptyDetail, MessageView} from '#/pages/_components/MessageView'
import {PhoneBar} from '#/pages/_components/PhoneBar'
import {PreviewHotkeys} from '#/pages/_components/PreviewHotkeys'
import {ShortcutsDialog} from '#/pages/_components/ShortcutsDialog'

function SidebarDrawer() {
  const isOpen = useSelector(previewStore, (state) => state.context.isSidebarOpen)

  return (
    <Drawer
      isOpen={isOpen}
      onOpenChange={(next) => previewStore.trigger.sidebarToggled({isOpen: next})}
    >
      <Drawer.Backdrop>
        <Drawer.Content placement="left">
          <Drawer.Dialog aria-label="Messages">
            <MessageSidebar variant="secondary" />
          </Drawer.Dialog>
        </Drawer.Content>
      </Drawer.Backdrop>
    </Drawer>
  )
}

export function Preview() {
  const {catalog, message, requestedMessage, selection, variant} = useSelection()
  const isNarrow = useIsNarrow()
  const appName = settings.app_name
  const name = message === null ? null : messageLabel(message, variant)
  const heading = name ?? (catalog.data === undefined && requestedMessage !== null ? null : appName)
  const content = catalog.isError ? (
    <Card className="min-h-0 flex-1 overflow-hidden p-0">
      <div className="flex h-full items-center justify-center p-6">
        <EmptyView
          description={catalog.error.message}
          isPageHeading
          icon={TriangleExclamation}
          title="The messages could not be loaded"
          tone="danger"
        >
          <Button size="sm" variant="secondary" onPress={() => void catalog.refetch()}>
            Try again
          </Button>
        </EmptyView>
      </div>
    </Card>
  ) : selection === null || message === null ? (
    <Card className="min-h-0 flex-1 overflow-hidden p-0">
      <ScrollShadow className="min-h-0 flex-1">
        <EmptyDetail />
      </ScrollShadow>
    </Card>
  ) : (
    <MessageView message={message} selection={selection} />
  )

  useAutoRefresh()

  return (
    <>
      <title>{name === null ? 'Message Preview' : `${name} - Message Preview`}</title>
      {isNarrow ? (
        <div className="flex h-dvh flex-col px-2 pb-2">
          <PhoneBar title={heading} />
          <main className="flex min-h-0 flex-1 flex-col gap-2">
            <ChecksLayout>{content}</ChecksLayout>
          </main>
          <SidebarDrawer />
        </div>
      ) : (
        <div className="flex h-dvh gap-2 px-2 pb-2">
          <div className="flex w-60 shrink-0 flex-col">
            <header className="flex h-12 shrink-0 items-center">
              <AppIdentity appName={appName} />
            </header>
            <aside aria-label="Messages" className="flex min-h-0 flex-1 flex-col p-2">
              <MessageSidebar />
            </aside>
          </div>
          <main className="flex min-w-0 flex-1 flex-col">
            <MessageTabs>
              <ChecksLayout>{content}</ChecksLayout>
            </MessageTabs>
          </main>
        </div>
      )}
      <ShortcutsDialog />
      <PreviewHotkeys />
    </>
  )
}
