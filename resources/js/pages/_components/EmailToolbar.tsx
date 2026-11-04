import {ChevronDown, Globe, Layers, Moon, Picture, Sun} from '@gravity-ui/icons'
import {
  Button,
  Dropdown,
  Label,
  ScrollShadow,
  ToggleButton,
  ToggleButtonGroup,
  Toolbar,
  Tooltip,
} from '@heroui/react'
import {useSelector} from '@xstate/store-react'

import {useIsNarrow, usePrefetchPreview, useSelection} from '#/lib/preview'
import {previewStore} from '#/lib/store'
import {ActionMenu} from '#/pages/_components/ActionMenu'
import {WidthGroup, type DisplayControlProps} from '#/pages/_components/EmailViewport'
import {HotkeyKbd} from '#/pages/_components/HotkeyKbd'
import type {RenderedPreview, Selection, ViewMode} from '#/types/preview'

const views = ['html', 'source', 'text'] as const satisfies ViewMode[]

function VariantMenu() {
  const {locale, message, messages, open, variant} = useSelection()
  const prefetch = usePrefetchPreview()

  if (
    message === null ||
    variant === null ||
    messages.every((item) => item.variants.length === 1)
  ) {
    return null
  }

  return (
    <Dropdown>
      <Tooltip>
        <Button
          aria-label={`Variant: ${variant.label}`}
          isDisabled={message.variants.length === 1}
          size="sm"
          variant="ghost"
        >
          <Layers />
          {variant.label}
          <ChevronDown />
        </Button>
        <Tooltip.Content>
          Variant <HotkeyKbd id="variant.previous" /> <HotkeyKbd id="variant.next" />
        </Tooltip.Content>
      </Tooltip>
      <Dropdown.Popover placement="bottom end">
        <Dropdown.Menu
          aria-label="Variant"
          selectedKeys={[variant.key]}
          selectionMode="single"
          onAction={(key) => open({variant: String(key)})}
        >
          {message.variants.map((item) => (
            <Dropdown.Item
              key={item.key}
              id={item.key}
              textValue={item.label}
              onHoverStart={() => prefetch({locale, message: message.key, variant: item.key})}
            >
              <Dropdown.ItemIndicator />
              <Label>{item.label}</Label>
            </Dropdown.Item>
          ))}
        </Dropdown.Menu>
      </Dropdown.Popover>
    </Dropdown>
  )
}

function LocaleMenu() {
  const {locale, locales, open} = useSelection()
  const options = ['default', ...locales]

  if (locales.length < 2) {
    return null
  }

  return (
    <Dropdown>
      <Tooltip>
        <Button aria-label={`Locale: ${locale ?? 'Auto'}`} size="sm" variant="ghost">
          <Globe />
          {locale ?? 'Auto'}
          <ChevronDown />
        </Button>
        <Tooltip.Content>Locale</Tooltip.Content>
      </Tooltip>
      <Dropdown.Popover placement="bottom end">
        <Dropdown.Menu
          aria-label="Locale"
          selectedKeys={[locale ?? 'default']}
          selectionMode="single"
          onAction={(key) => open({locale: key === 'default' ? null : String(key)})}
        >
          {options.map((item) => (
            <Dropdown.Item key={item} id={item} textValue={item === 'default' ? 'Auto' : item}>
              <Dropdown.ItemIndicator />
              <Label>{item === 'default' ? 'Auto' : item}</Label>
            </Dropdown.Item>
          ))}
        </Dropdown.Menu>
      </Dropdown.Popover>
    </Dropdown>
  )
}

function ImagesToggle(props: DisplayControlProps) {
  const {isDisabled} = props
  const blockImages = useSelector(previewStore, (state) => state.context.blockImages)

  return (
    <Tooltip>
      <ToggleButton
        aria-label="Block remote images"
        isDisabled={isDisabled}
        isIconOnly
        isSelected={blockImages}
        size="sm"
        variant="ghost"
        onChange={() => previewStore.trigger.blockImagesToggled()}
      >
        <Picture />
      </ToggleButton>
      <Tooltip.Content>
        Block remote images <HotkeyKbd id="images.toggle" />
      </Tooltip.Content>
    </Tooltip>
  )
}

function SchemeToggle(props: DisplayControlProps) {
  const {isDisabled} = props
  const scheme = useSelector(previewStore, (state) => state.context.scheme)

  return (
    <Tooltip>
      <ToggleButton
        aria-label="Dark mode"
        isDisabled={isDisabled}
        isIconOnly
        isSelected={scheme === 'dark'}
        size="sm"
        variant="ghost"
        onChange={() => previewStore.trigger.schemeToggled()}
      >
        {scheme === 'dark' ? <Moon /> : <Sun />}
      </ToggleButton>
      <Tooltip.Content>
        Dark mode <HotkeyKbd id="scheme.toggle" />
      </Tooltip.Content>
    </Tooltip>
  )
}

interface EmailToolbarProps {
  rendered: null | RenderedPreview
  selection: Selection
  view: ViewMode
}

export function EmailToolbar(props: EmailToolbarProps) {
  const {rendered, selection, view} = props
  const isNarrow = useIsNarrow()
  const showsHtml = rendered?.html != null && view === 'html'

  return (
    <ScrollShadow className="shrink-0" hideScrollBar orientation="horizontal">
      <Toolbar aria-label="Email" className="w-full">
        <div className="grid w-full min-w-min grid-cols-[1fr_auto_1fr] items-center gap-2 p-2">
          <div className="flex">
            <ToggleButtonGroup
              aria-label="View"
              disallowEmptySelection
              isDetached
              isDisabled={rendered === null}
              selectedKeys={[view]}
              selectionMode="single"
              size="sm"
              onSelectionChange={(keys) => {
                const next = views.find((item) => keys.has(item))

                if (next !== undefined) {
                  previewStore.trigger.viewChanged({view: next})
                }
              }}
            >
              <ToggleButton id="html" isDisabled={rendered?.html === null} variant="ghost">
                HTML
              </ToggleButton>
              <ToggleButton id="text" isDisabled={rendered?.text === null} variant="ghost">
                Text
              </ToggleButton>
              <ToggleButton id="source" variant="ghost">
                Source
              </ToggleButton>
            </ToggleButtonGroup>
          </div>
          <div className="flex">
            {!isNarrow && <WidthGroup isDisabled={rendered === null || view !== 'html'} />}
          </div>
          <div className="flex items-center justify-end gap-2">
            <VariantMenu />
            <LocaleMenu />
            <ImagesToggle isDisabled={!showsHtml} />
            <SchemeToggle isDisabled={!showsHtml} />
            <ActionMenu selection={selection} view={view} />
          </div>
        </div>
      </Toolbar>
    </ScrollShadow>
  )
}
