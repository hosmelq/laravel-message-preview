import {useHotkey} from '@tanstack/react-hotkeys'
import {useSelector} from '@xstate/store-react'

import {appHotkeys} from '#/lib/hotkeys'
import {useAutoRefreshToggle, usePreview, useRefresh, useSelection, useView} from '#/lib/preview'
import {mobileWidth, previewStore} from '#/lib/store'

export function PreviewHotkeys() {
  const {next, nextVariant, open, previous, previousVariant, selection} = useSelection()
  const {isPlaceholderData, rendered} = usePreview(selection)
  const view = useView(rendered)
  const refresh = useRefresh(selection)
  const toggleAutoRefresh = useAutoRefreshToggle()
  const isAutoRefresh = useSelector(previewStore, (state) => state.context.isAutoRefresh)
  const width = useSelector(previewStore, (state) => state.context.width)
  const showsHtml = rendered?.html != null && view === 'html'
  const openUrl =
    rendered === null || isPlaceholderData || view === 'source' ? null : rendered.urls[view]

  useHotkey(appHotkeys['help.shortcuts'], () =>
    previewStore.trigger.shortcutsToggled({isOpen: true}),
  )
  useHotkey(appHotkeys['message.next'], () => open({message: next?.key}), {
    enabled: next !== undefined,
  })
  useHotkey(appHotkeys['message.previous'], () => open({message: previous?.key}), {
    enabled: previous !== undefined,
  })
  useHotkey(appHotkeys['variant.next'], () => open({variant: nextVariant?.key}), {
    enabled: nextVariant !== undefined,
  })
  useHotkey(appHotkeys['variant.previous'], () => open({variant: previousVariant?.key}), {
    enabled: previousVariant !== undefined,
  })
  useHotkey(appHotkeys['checks.toggle'], () => previewStore.trigger.checksToggled())
  useHotkey(appHotkeys['send.open'], () => previewStore.trigger.sendToggled({isOpen: true}), {
    enabled: rendered !== null,
  })
  useHotkey(appHotkeys['preview.refresh'], refresh, {enabled: selection !== null})
  useHotkey(appHotkeys['preview.autoRefresh'], () => toggleAutoRefresh(!isAutoRefresh))
  useHotkey(
    appHotkeys['view.toggle'],
    () => previewStore.trigger.viewChanged({view: view === 'html' ? 'text' : 'html'}),
    {enabled: rendered?.html != null && rendered.text !== null},
  )
  useHotkey(
    appHotkeys['view.source'],
    () => previewStore.trigger.viewChanged({view: view === 'source' ? 'html' : 'source'}),
    {enabled: rendered !== null},
  )
  useHotkey(
    appHotkeys['width.toggle'],
    () => previewStore.trigger.widthChanged({width: width === null ? mobileWidth : null}),
    {
      enabled: rendered !== null,
    },
  )
  useHotkey(appHotkeys['scheme.toggle'], () => previewStore.trigger.schemeToggled(), {
    enabled: showsHtml,
  })
  useHotkey(appHotkeys['images.toggle'], () => previewStore.trigger.blockImagesToggled(), {
    enabled: showsHtml,
  })
  useHotkey(
    appHotkeys['preview.open'],
    () => window.open(openUrl ?? '', '_blank', 'noopener,noreferrer'),
    {
      enabled: openUrl !== null,
    },
  )

  return null
}
