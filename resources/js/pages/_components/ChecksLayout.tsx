import {useSelector} from '@xstate/store-react'
import type {ReactNode} from 'react'
import {Group, Panel, Separator, useDefaultLayout} from 'react-resizable-panels'

import {previewStore} from '#/lib/store'
import {ChecksPanel} from '#/pages/_components/ChecksPanel'

interface ChecksLayoutProps {
  children: ReactNode
}

export function ChecksLayout(props: ChecksLayoutProps) {
  const {children} = props
  const isChecksOpen = useSelector(previewStore, (state) => state.context.isChecksOpen)
  const {defaultLayout, onLayoutChanged} = useDefaultLayout({
    id: 'message-preview:checks',
    panelIds: isChecksOpen ? ['email', 'checks'] : ['email'],
  })

  return (
    <div className="flex min-h-0 min-w-0 flex-1 flex-col gap-2">
      <Group
        className="min-h-0 flex-1"
        defaultLayout={defaultLayout}
        orientation="vertical"
        style={{overflow: 'visible'}}
        onLayoutChanged={onLayoutChanged}
      >
        <Panel
          className="email-area flex flex-col"
          id="email"
          minSize={200}
          style={{overflow: 'visible'}}
        >
          {children}
        </Panel>
        {isChecksOpen && (
          <>
            <Separator className="checks-separator" />
            <Panel
              className="flex flex-col"
              defaultSize={400}
              groupResizeBehavior="preserve-pixel-size"
              id="checks"
              minSize={200}
              style={{overflow: 'visible'}}
            >
              <ChecksPanel />
            </Panel>
          </>
        )}
      </Group>
      {!isChecksOpen && <ChecksPanel />}
    </div>
  )
}
