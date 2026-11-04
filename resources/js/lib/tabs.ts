import {createStore} from '@xstate/store'
import {useSelector} from '@xstate/store-react'
import {persist} from '@xstate/store/persist'
import {nanoid} from 'nanoid'
import {useQueryStates} from 'nuqs'

import {selectionParsers} from '#/lib/search'
import type {Selection} from '#/types/preview'

interface PreviewTab {
  id: string
  selection: null | Selection
}

function rememberActive(tabs: PreviewTab[], activeTab: string, current: null | Selection) {
  return tabs.map((tab) => (tab.id === activeTab ? {...tab, selection: current} : tab))
}

const tabStore = createStore({
  context: {
    activeTab: 'first',
    tabs: [{id: 'first', selection: null}] as PreviewTab[],
  },
  on: {
    activated: (context, event: {current: null | Selection; id: string}) => ({
      activeTab: event.id,
      tabs: rememberActive(context.tabs, context.activeTab, event.current),
    }),
    closed: (context, event: {id: string}) => {
      const index = context.tabs.findIndex((tab) => tab.id === event.id)
      const tabs = context.tabs.filter((tab) => tab.id !== event.id)
      const next = tabs[index] ?? tabs.at(-1)

      return next === undefined
        ? context
        : {activeTab: event.id === context.activeTab ? next.id : context.activeTab, tabs}
    },
    opened: (
      context,
      event: {current: null | Selection; id: string; selection: null | Selection},
    ) => {
      const tabs = rememberActive(context.tabs, context.activeTab, event.current)
      const index = tabs.findIndex((tab) => tab.id === context.activeTab)

      return {
        activeTab: event.id,
        tabs: tabs.toSpliced(index + 1, 0, {id: event.id, selection: event.selection}),
      }
    },
  },
}).with(persist({name: 'message-preview:tabs', storage: sessionStorage}))

export function useTabs(current: null | Selection) {
  const [, setSearch] = useQueryStates(selectionParsers)
  const stored = useSelector(tabStore, (state) => state.context.tabs)
  const activeTab = useSelector(tabStore, (state) => state.context.activeTab)
  const tabs = rememberActive(stored, activeTab, current)

  function show(selection: null | Selection) {
    void setSearch(selection ?? {locale: null, message: null, variant: null}, {history: 'push'})
  }

  function activate(id: string) {
    if (id !== activeTab) {
      tabStore.trigger.activated({current, id})
      show(stored.find((tab) => tab.id === id)?.selection ?? null)
    }
  }

  function close(id: string) {
    tabStore.trigger.closed({id})

    if (id === activeTab) {
      const {activeTab: next, tabs: remaining} = tabStore.getSnapshot().context

      show(remaining.find((tab) => tab.id === next)?.selection ?? null)
    }
  }

  function openInTab(selection: Selection) {
    const open = tabs.find(
      (tab) =>
        tab.selection?.message === selection.message &&
        tab.selection.variant === selection.variant &&
        tab.selection.locale === selection.locale,
    )

    if (open !== undefined) {
      activate(open.id)

      return
    }

    tabStore.trigger.opened({current, id: nanoid(), selection})
    show(selection)
  }

  function openBlank() {
    tabStore.trigger.opened({current, id: nanoid(), selection: null})
    show(null)
  }

  return {activate, activeTab, close, openBlank, openInTab, tabs}
}
