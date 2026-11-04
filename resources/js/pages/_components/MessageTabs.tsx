import {Bell, Envelope, Plus} from '@gravity-ui/icons'
import {Button, Tag, TagGroup, Tooltip} from '@heroui/react'
import type {ReactNode} from 'react'

import {messageLabel} from '#/lib/messages'
import {cancelPrefetch, usePrefetchPreview, useSelection} from '#/lib/preview'
import {useTabs} from '#/lib/tabs'

interface MessageTabsProps {
  children: ReactNode
}

export function MessageTabs(props: MessageTabsProps) {
  const {children} = props
  const {catalog, messages, selection} = useSelection()
  const {activate, activeTab, close, openBlank, tabs} = useTabs(selection)
  const prefetch = usePrefetchPreview()

  return (
    <>
      <div className="flex min-h-12 shrink-0 items-center gap-1 py-2">
        {catalog.data !== undefined && (
          <>
            <TagGroup
              aria-label="Open messages"
              disallowEmptySelection
              selectedKeys={[activeTab]}
              selectionMode="single"
              size="lg"
              onRemove={
                tabs.length > 1 ? (keys) => keys.forEach((key) => close(String(key))) : undefined
              }
              onSelectionChange={(keys) => {
                const next = tabs.find((tab) => keys === 'all' || keys.has(tab.id))

                if (next !== undefined) {
                  activate(next.id)
                }
              }}
            >
              <TagGroup.List>
                {tabs.map((tab) => {
                  const message = messages.find((item) => item.key === tab.selection?.message)
                  const variant = message?.variants.find(
                    (item) => item.key === tab.selection?.variant,
                  )
                  const label = message === undefined ? 'New tab' : messageLabel(message, variant)
                  const target = tab.selection

                  return (
                    <Tag
                      key={tab.id}
                      id={tab.id}
                      textValue={label}
                      onHoverEnd={cancelPrefetch}
                      onHoverStart={target === null ? undefined : () => prefetch(target)}
                    >
                      {message?.kind === 'notification' ? <Bell /> : <Envelope />}
                      <span className="max-w-48 truncate">{label}</span>
                      <Tag.RemoveButton aria-label={`Close ${label}`} />
                    </Tag>
                  )
                })}
              </TagGroup.List>
            </TagGroup>
            <Tooltip>
              <Button aria-label="New tab" isIconOnly size="sm" variant="ghost" onPress={openBlank}>
                <Plus />
              </Button>
              <Tooltip.Content>New tab</Tooltip.Content>
            </Tooltip>
          </>
        )}
      </div>
      {children}
    </>
  )
}
