import {createStore} from '@xstate/store'
import {persist} from '@xstate/store/persist'

import type {Spotlight} from '#/lib/spotlight'
import type {ColorScheme, ViewMode} from '#/types/preview'

export type ChecksTab = 'accessibility' | 'compatibility' | 'links' | 'message' | 'size'

export const mobileWidth = 375

export const tabletWidth = 768

export type PreviewWidth = null | typeof mobileWidth | typeof tabletWidth

export const previewStore = createStore({
  context: {
    blockImages: false,
    checksTab: 'accessibility' as ChecksTab,
    clients: ['gmail', 'apple-mail', 'outlook', 'yahoo'],
    collapsed: [] as string[],
    isChecksOpen: false,
    isAutoRefresh: false,
    isSendOpen: false,
    isShortcutsOpen: false,
    isSidebarOpen: false,
    mailer: null as null | string,
    query: '',
    recipient: '',
    scheme: 'light' as ColorScheme,
    spotlight: null as null | Spotlight,
    view: 'html' as ViewMode,
    width: null as PreviewWidth,
  },
  on: {
    blockImagesToggled: (context) => ({...context, blockImages: !context.blockImages}),
    checksTabChanged: (context, event: {tab: ChecksTab}) => ({
      ...context,
      checksTab: event.tab,
      isChecksOpen: true,
    }),
    checksToggled: (context) => ({...context, isChecksOpen: !context.isChecksOpen}),
    clientsChanged: (context, event: {clients: string[]}) => ({...context, clients: event.clients}),
    mailerChanged: (context, event: {mailer: string}) => ({...context, mailer: event.mailer}),
    autoRefreshToggled: (context, event: {isAutoRefresh: boolean}) => ({
      ...context,
      isAutoRefresh: event.isAutoRefresh,
    }),
    queryChanged: (context, event: {query: string}) => ({...context, query: event.query}),
    recipientChanged: (context, event: {recipient: string}) => ({
      ...context,
      recipient: event.recipient,
    }),
    sendToggled: (context, event: {isOpen: boolean}) => ({...context, isSendOpen: event.isOpen}),
    spotlightChanged: (context, event: {spotlight: null | Spotlight}) => ({
      ...context,
      spotlight: event.spotlight,
    }),
    schemeToggled: (context) => ({
      ...context,
      scheme: context.scheme === 'light' ? 'dark' : 'light',
    }),
    shortcutsToggled: (context, event: {isOpen: boolean}) => ({
      ...context,
      isShortcutsOpen: event.isOpen,
    }),
    sidebarToggled: (context, event: {isOpen: boolean}) => ({
      ...context,
      isSidebarOpen: event.isOpen,
    }),
    treeItemToggled: (context, event: {isExpanded: boolean; key: string}) => ({
      ...context,
      collapsed: event.isExpanded
        ? context.collapsed.filter((key) => key !== event.key)
        : [...context.collapsed, event.key],
    }),
    viewChanged: (context, event: {view: ViewMode}) => ({...context, view: event.view}),
    widthChanged: (context, event: {width: PreviewWidth}) => ({...context, width: event.width}),
  },
}).with(
  persist({
    name: 'message-preview:preferences',
    pick: (context) => ({
      blockImages: context.blockImages,
      checksTab: context.checksTab,
      clients: context.clients,
      collapsed: context.collapsed,
      isChecksOpen: context.isChecksOpen,
      isAutoRefresh: context.isAutoRefresh,
      mailer: context.mailer,
      recipient: context.recipient,
      scheme: context.scheme,
      view: context.view,
      width: context.width,
    }),
  }),
)
