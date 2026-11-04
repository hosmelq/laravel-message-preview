import {
  Bell,
  ChevronDown,
  Envelope,
  Folder,
  FolderOpen,
  Keyboard,
  TriangleExclamation,
} from '@gravity-ui/icons'
import {
  Button,
  Chip,
  Popover,
  ScrollShadow,
  SearchField,
  Switch,
  Tooltip,
  Typography,
} from '@heroui/react'
import {HotkeysProvider, useHotkey} from '@tanstack/react-hotkeys'
import {useQuery} from '@tanstack/react-query'
import {useSelector} from '@xstate/store-react'
import pluralize from 'pluralize'
import {useRef, type KeyboardEvent, type MouseEvent, type ReactNode} from 'react'
import {
  Button as AriaButton,
  Link,
  NavigationTree,
  NavigationTreeHeader,
  NavigationTreeItem,
  NavigationTreeItemContent,
  NavigationTreeSection,
  type Key,
} from 'react-aria-components'

import {previewQuery} from '#/lib/api'
import {appHotkeys} from '#/lib/hotkeys'
import {namespaceIds, namespacePath, type Namespace} from '#/lib/messages'
import {cancelPrefetch, useAutoRefreshToggle, usePrefetchPreview, useSelection} from '#/lib/preview'
import {loadSelection, selectionHref} from '#/lib/search'
import {previewStore} from '#/lib/store'
import {useTabs} from '#/lib/tabs'
import {ThemeMenu} from '#/pages/_components/AppIdentity'
import {HotkeyKbd} from '#/pages/_components/HotkeyKbd'
import type {MessageSummary} from '#/types/preview'

function namespaceKey(id: string) {
  return `namespace:${id}`
}

interface MessageItemProps {
  locale: null | string
  message: MessageSummary
}

function MessageItem(props: MessageItemProps) {
  const {locale, message} = props
  const target = {locale, message: message.key, variant: 'default'}
  const {data: preview} = useQuery({...previewQuery(target), enabled: false})
  const prefetch = usePrefetchPreview()
  const Icon = message.kind === 'mailable' ? Envelope : Bell

  return (
    <NavigationTreeItem
      className="message-tree__item"
      href={selectionHref({locale, message: message.key})}
      id={message.key}
      textValue={message.name}
    >
      <NavigationTreeItemContent>
        <Link
          className="message-tree__content"
          onFocus={() => prefetch(target)}
          onHoverEnd={cancelPrefetch}
          onHoverStart={() => prefetch(target)}
        >
          <Icon className="message-tree__icon" />
          <span className="message-tree__label">{message.name}</span>
          {(message.error !== null || preview?.status === 'failed') && (
            <Chip color="danger" size="sm" variant="soft">
              Error
            </Chip>
          )}
        </Link>
      </NavigationTreeItemContent>
    </NavigationTreeItem>
  )
}

function treeItems(namespace: Namespace, locale: null | string): ReactNode[] {
  return [
    ...namespace.namespaces.map((child) => (
      <NamespaceItem key={child.id} locale={locale} namespace={child} />
    )),
    ...namespace.messages.map((message) => (
      <MessageItem key={message.key} locale={locale} message={message} />
    )),
  ]
}

interface NamespaceItemProps {
  locale: null | string
  namespace: Namespace
}

function NamespaceItem(props: NamespaceItemProps) {
  const {locale, namespace} = props

  return (
    <NavigationTreeItem
      className="message-tree__item"
      id={namespaceKey(namespace.id)}
      textValue={namespace.label}
    >
      <NavigationTreeItemContent>
        {({isExpanded}) => (
          <>
            <div className="message-tree__content">
              {isExpanded ? (
                <FolderOpen className="message-tree__icon" />
              ) : (
                <Folder className="message-tree__icon" />
              )}
              <span className="message-tree__label">{namespace.label}</span>
              {!isExpanded && <span className="message-tree__count">{namespace.count}</span>}
            </div>
            <AriaButton className="message-tree__chevron" slot="chevron">
              <ChevronDown />
            </AriaButton>
          </>
        )}
      </NavigationTreeItemContent>
      {treeItems(namespace, locale)}
    </NavigationTreeItem>
  )
}

interface DiscoveryErrorsProps {
  errors: string[]
}

function DiscoveryErrors(props: DiscoveryErrorsProps) {
  const {errors} = props

  return (
    <Popover>
      <Button fullWidth size="sm" variant="ghost">
        <TriangleExclamation />
        {pluralize('class', errors.length, true)} skipped
      </Button>
      <Popover.Content placement="top start">
        <Popover.Dialog>
          <HotkeysProvider exclusive scope="overlay">
            <Popover.Heading>Skipped during discovery</Popover.Heading>
            <ScrollShadow className="max-h-72 max-w-sm pt-2" size={16}>
              <ul className="flex flex-col gap-2">
                {errors.map((error) => (
                  <li key={error} className="wrap-anywhere">
                    <Typography color="muted" type="body-xs">
                      {error}
                    </Typography>
                  </li>
                ))}
              </ul>
            </ScrollShadow>
          </HotkeysProvider>
        </Popover.Dialog>
      </Popover.Content>
    </Popover>
  )
}

function revealCurrent(currentHref: null | string) {
  return (content: HTMLDivElement | null) => {
    if (currentHref !== null) {
      content?.querySelector('[data-current]')?.scrollIntoView({block: 'nearest'})
    }
  }
}

interface MessageSidebarProps {
  variant?: 'primary' | 'secondary'
}

export function MessageSidebar(props: MessageSidebarProps) {
  const {variant = 'primary'} = props
  const {catalog, filtered, listed, locale, open, selection} = useSelection()
  const {openInTab} = useTabs(selection)
  const toggleAutoRefresh = useAutoRefreshToggle()
  const searchInput = useRef<HTMLInputElement>(null)
  const query = useSelector(previewStore, (state) => state.context.query)
  const collapsed = useSelector(previewStore, (state) => state.context.collapsed)
  const isAutoRefresh = useSelector(previewStore, (state) => state.context.isAutoRefresh)
  const isSearching = query.trim() !== ''
  const currentHref =
    selection === null
      ? null
      : selectionHref({locale: selection.locale, message: selection.message})
  const expandable = namespaceIds(filtered).map(namespaceKey)
  const revealed = namespacePath(filtered, selection?.message ?? '').map(({id}) => namespaceKey(id))
  const expandedKeys = new Set(
    expandable.filter((key) => isSearching || !collapsed.includes(key) || revealed.includes(key)),
  )
  const errors = catalog.data?.meta.discovery_errors ?? []

  function changeExpanded(keys: Set<Key>) {
    for (const key of expandable) {
      if (keys.has(key) !== expandedKeys.has(key)) {
        previewStore.trigger.treeItemToggled({isExpanded: keys.has(key), key})
      }
    }
  }

  function openFirstMatch() {
    const [first] = listed

    if (first !== undefined) {
      searchInput.current?.blur()
      open({message: first.key})
    }
  }

  function openLinkInTab(event: KeyboardEvent | MouseEvent) {
    const link = event.target instanceof Element ? event.target.closest('a') : null

    if (link === null) {
      return
    }

    const target = loadSelection(new URL(link.href))

    if (target.message !== null) {
      event.preventDefault()
      openInTab({...target, message: target.message})
    }
  }

  useHotkey(appHotkeys['search.focus'], () => searchInput.current?.focus())

  return (
    <>
      <div className="pb-2">
        <SearchField
          aria-label="Search messages"
          variant={variant}
          value={query}
          onChange={(next) => previewStore.trigger.queryChanged({query: next})}
          onSubmit={openFirstMatch}
        >
          <SearchField.Group>
            <SearchField.SearchIcon />
            <SearchField.Input
              ref={searchInput}
              autoComplete="off"
              className="min-w-0"
              placeholder="Search"
              spellCheck={false}
            />
            {query === '' ? (
              <span className="me-2">
                <HotkeyKbd id="search.focus" />
              </span>
            ) : (
              <SearchField.ClearButton />
            )}
          </SearchField.Group>
        </SearchField>
      </div>
      <ScrollShadow
        ref={revealCurrent(currentHref)}
        className="-mx-2 min-h-0 flex-1 px-2 py-1"
        hideScrollBar
        onAuxClickCapture={(event) => event.button === 1 && openLinkInTab(event)}
        onClickCapture={(event) => (event.metaKey || event.ctrlKey) && openLinkInTab(event)}
        onKeyDownCapture={(event) =>
          event.key === 'Enter' && (event.metaKey || event.ctrlKey) && openLinkInTab(event)
        }
      >
        {catalog.isPending ? null : catalog.isError ? (
          <div className="px-2 py-6">
            <Typography align="center" color="muted" type="body-sm">
              The messages could not be loaded.
            </Typography>
          </div>
        ) : filtered.length === 0 ? (
          <div className="px-2 py-6">
            <Typography align="center" color="muted" type="body-sm">
              {isSearching ? 'No message class contains that text.' : 'No messages yet.'}
            </Typography>
          </div>
        ) : (
          <NavigationTree
            aria-label="Messages"
            className={
              variant === 'secondary' ? 'message-tree message-tree--secondary' : 'message-tree'
            }
            expandedKeys={expandedKeys}
            selectedRoute={currentHref}
            onExpandedChange={changeExpanded}
          >
            {filtered.map((namespace) => (
              <NavigationTreeSection
                key={namespace.id}
                className="message-tree__section"
                id={namespaceKey(namespace.id)}
              >
                <NavigationTreeHeader className="message-tree__header">
                  {namespace.label}
                </NavigationTreeHeader>
                {treeItems(namespace, locale)}
              </NavigationTreeSection>
            ))}
          </NavigationTree>
        )}
      </ScrollShadow>
      <div className="flex flex-col gap-1 pt-2">
        {errors.length > 0 && <DiscoveryErrors errors={errors} />}
        <div className="flex h-9 items-center gap-1 ps-2">
          <Switch isSelected={isAutoRefresh} onChange={toggleAutoRefresh}>
            <Switch.Content>
              <Switch.Control>
                <Switch.Thumb />
              </Switch.Control>
              Auto-refresh
            </Switch.Content>
          </Switch>
          <Tooltip>
            <Button
              aria-label="Keyboard shortcuts"
              className="ms-auto"
              isIconOnly
              size="sm"
              variant="ghost"
              onPress={() => previewStore.trigger.shortcutsToggled({isOpen: true})}
            >
              <Keyboard />
            </Button>
            <Tooltip.Content>
              Keyboard shortcuts <HotkeyKbd id="help.shortcuts" />
            </Tooltip.Content>
          </Tooltip>
          <ThemeMenu />
        </div>
      </div>
    </>
  )
}
