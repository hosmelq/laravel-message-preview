import {
  ArrowDownToLine,
  ArrowRotateRight,
  ArrowUpRightFromSquare,
  Copy,
  Ellipsis,
  PaperPlane,
} from '@gravity-ui/icons'
import {Button, Description, Dropdown, Label, Separator, Tooltip} from '@heroui/react'

import {canCopy, copyToClipboard} from '#/lib/export'
import {fileName, usePreview, useRefresh, useSelection} from '#/lib/preview'
import {previewStore} from '#/lib/store'
import {HotkeyKbd} from '#/pages/_components/HotkeyKbd'
import {SendTestDialog} from '#/pages/_components/SendTestDialog'
import type {Selection, ViewMode} from '#/types/preview'

const copyLabels = {'copy-html': 'Copy HTML', 'copy-text': 'Copy plain text'}

interface ActionMenuProps {
  selection: Selection
  view: ViewMode
}

export function ActionMenu(props: ActionMenuProps) {
  const {selection, view} = props
  const {message} = useSelection()
  const {isPlaceholderData, rendered} = usePreview(selection)
  const preview = isPlaceholderData ? null : rendered
  const refresh = useRefresh(selection)
  const name = fileName(message?.name ?? 'message', selection)
  const html = preview?.html ?? null
  const text = preview?.text ?? null
  const htmlUrl = preview?.urls.html ?? null
  const emlUrl = preview?.urls.eml ?? null
  const openUrl = preview === null || view === 'source' ? null : preview.urls[view]
  const disabledKeys = [
    openUrl === null && 'open',
    (html === null || !canCopy) && 'copy-html',
    (text === null || !canCopy) && 'copy-text',
    htmlUrl === null && 'download-html',
    emlUrl === null && 'download-eml',
    rendered === null && 'send',
  ].filter((key) => key !== false)

  return (
    <>
      <Dropdown>
        <Tooltip>
          <Button aria-label="Actions" isIconOnly size="sm" variant="ghost">
            <Ellipsis />
          </Button>
          <Tooltip.Content>Actions</Tooltip.Content>
        </Tooltip>
        <Dropdown.Popover placement="bottom end">
          <Dropdown.Menu
            aria-label="Actions"
            disabledKeys={disabledKeys}
            onAction={(key) => {
              if (key === 'copy-html' && html !== null) {
                void copyToClipboard(html, 'HTML')
              } else if (key === 'copy-text' && text !== null) {
                void copyToClipboard(text, 'plain text')
              } else if (key === 'send') {
                previewStore.trigger.sendToggled({isOpen: true})
              } else if (key === 'refresh') {
                refresh()
              }
            }}
          >
            <Dropdown.Item
              href={openUrl ?? undefined}
              id="open"
              rel="noreferrer"
              target="_blank"
              textValue="Open in a browser tab"
            >
              <ArrowUpRightFromSquare className="size-4 shrink-0 text-muted" />
              <Label>Open in a browser tab</Label>
              <HotkeyKbd className="ms-auto" id="preview.open" variant="light" />
            </Dropdown.Item>
            {(['copy-html', 'copy-text'] as const).map((id) => (
              <Dropdown.Item key={id} id={id} textValue={copyLabels[id]}>
                {canCopy ? (
                  <>
                    <Copy className="size-4 shrink-0 text-muted" />
                    <Label>{copyLabels[id]}</Label>
                  </>
                ) : (
                  <>
                    <div className="flex h-8 items-start justify-center pt-px">
                      <Copy className="size-4 shrink-0 text-muted" />
                    </div>
                    <div className="flex flex-col">
                      <Label>{copyLabels[id]}</Label>
                      <Description>Needs HTTPS or localhost</Description>
                    </div>
                  </>
                )}
              </Dropdown.Item>
            ))}
            <Dropdown.Item
              download={`${name}.html`}
              href={htmlUrl ?? undefined}
              id="download-html"
              textValue="Download HTML"
            >
              <ArrowDownToLine className="size-4 shrink-0 text-muted" />
              <Label>Download HTML</Label>
            </Dropdown.Item>
            <Dropdown.Item
              download
              href={emlUrl ?? undefined}
              id="download-eml"
              textValue="Download EML"
            >
              <ArrowDownToLine className="size-4 shrink-0 text-muted" />
              <Label>Download EML</Label>
            </Dropdown.Item>
            <Dropdown.Item id="send" textValue="Send a test email">
              <PaperPlane className="size-4 shrink-0 text-muted" />
              <Label>Send a test email</Label>
              <HotkeyKbd className="ms-auto" id="send.open" variant="light" />
            </Dropdown.Item>
            <Separator orientation="horizontal" />
            <Dropdown.Item id="refresh" textValue="Refresh">
              <ArrowRotateRight className="size-4 shrink-0 text-muted" />
              <Label>Refresh</Label>
              <HotkeyKbd className="ms-auto" id="preview.refresh" variant="light" />
            </Dropdown.Item>
          </Dropdown.Menu>
        </Dropdown.Popover>
      </Dropdown>
      <SendTestDialog selection={selection} />
    </>
  )
}
