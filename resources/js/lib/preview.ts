import {useMediaQuery} from '@heroui/react'
import {
  keepPreviousData,
  queryOptions,
  skipToken,
  usePrefetchQuery,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query'
import {useSelector} from '@xstate/store-react'
import {useQueryStates} from 'nuqs'

import {
  catalogQuery,
  compatibilityQuery,
  fingerprintQuery,
  invalidatePreview,
  linksQuery,
  previewQuery,
} from '#/lib/api'
import {
  accessibilityChecks,
  checkMessage,
  checkSize,
  isBrokenLink,
  linkRows,
  unresolvedLinks,
  type Check,
} from '#/lib/checks'
import {buildMessageTree, filterTree, matchesQuery, treeMessages} from '#/lib/messages'
import {selectionParsers} from '#/lib/search'
import {settings} from '#/lib/settings'
import {previewStore, type ChecksTab} from '#/lib/store'
import type {
  ColorScheme,
  LinkCheck,
  MessageSummary,
  RenderedPreview,
  Selection,
  Variant,
  ViewMode,
} from '#/types/preview'

export function useAutoRefresh() {
  const isAutoRefresh = useSelector(previewStore, (state) => state.context.isAutoRefresh)

  useQuery({
    ...fingerprintQuery,
    enabled: isAutoRefresh,
    refetchInterval: settings.poll_interval * 1000,
  })
}

export function useSelection() {
  const [search, setSearch] = useQueryStates(selectionParsers)
  const catalog = useQuery(catalogQuery)
  const query = useSelector(previewStore, (state) => state.context.query)
  const messages = catalog.data?.data ?? []
  const locales = catalog.data?.meta.locales ?? []
  const filtered = filterTree(buildMessageTree(messages), (item) => matchesQuery(item, query))
  const listed = treeMessages(filtered)
  const message = messages.find((item) => item.key === search.message) ?? null
  const variant =
    message?.variants.find((item) => item.key === search.variant) ?? message?.variants[0] ?? null
  const locale = search.locale !== null && locales.includes(search.locale) ? search.locale : null
  const selection: null | Selection =
    message === null || variant === null
      ? null
      : {locale, message: message.key, variant: variant.key}
  const position = listed.findIndex((item) => item.key === message?.key)
  const variantPosition = message?.variants.findIndex((item) => item.key === variant?.key) ?? -1

  function open(target: {locale?: null | string; message?: string; variant?: string}) {
    void setSearch(
      {
        locale: target.locale === undefined ? locale : target.locale,
        message: target.message ?? message?.key ?? null,
        variant: target.variant ?? (target.message === undefined ? (variant?.key ?? null) : null),
      },
      {history: 'push'},
    )
  }

  return {
    catalog,
    filtered,
    listed,
    locale,
    locales,
    message,
    messages,
    next: position === -1 ? listed[0] : listed[position + 1],
    nextVariant: variantPosition === -1 ? undefined : message?.variants[variantPosition + 1],
    open,
    previous: position === -1 ? listed.at(-1) : listed[position - 1],
    previousVariant: variantPosition < 1 ? undefined : message?.variants[variantPosition - 1],
    requestedMessage: search.message,
    selection,
    variant,
  }
}

const emptySelection: Selection = {locale: null, message: '', variant: 'default'}

export function usePreview(selection: null | Selection) {
  const result = useQuery({
    ...previewQuery(selection ?? emptySelection),
    enabled: selection !== null,
    placeholderData: keepPreviousData,
  })
  const preview = selection === null ? undefined : result.data

  return {...result, preview, rendered: preview?.status === 'rendered' ? preview : null}
}

let intent: number | undefined

export function cancelPrefetch() {
  window.clearTimeout(intent)
}

export function usePrefetchPreview() {
  const queryClient = useQueryClient()

  async function prefetchChecks(selection: Selection) {
    const preview = await queryClient.fetchQuery(previewQuery(selection))

    if (preview.status !== 'rendered') {
      return
    }

    const queries = checkQueries(preview, previewStore.getSnapshot().context)

    void queryClient.prefetchQuery(queries.message)
    void queryClient.prefetchQuery(queries.urls)
    void queryClient.prefetchQuery(queries.accessibility)
    void queryClient.prefetchQuery(queries.compatibility)
    void queryClient.prefetchQuery(queries.size(undefined))
  }

  return (selection: Selection) => {
    void queryClient.prefetchQuery(previewQuery(selection))
    cancelPrefetch()
    intent = window.setTimeout(() => void prefetchChecks(selection), 150)
  }
}

export function usePrefetchNeighbors(selection: Selection, isReady: boolean) {
  const {locale, message, next, nextVariant, previous, previousVariant} = useSelection()
  const ofMessage = (target: MessageSummary | undefined) =>
    target && {locale, message: target.key, variant: 'default'}
  const ofVariant = (target: Variant | undefined) =>
    message === null || target === undefined
      ? undefined
      : {locale, message: message.key, variant: target.key}
  const neighbor = (target: Selection | undefined) =>
    previewQuery(isReady && target !== undefined ? target : selection)

  usePrefetchQuery(neighbor(ofMessage(next)))
  usePrefetchQuery(neighbor(ofMessage(previous)))
  usePrefetchQuery(neighbor(ofVariant(nextVariant)))
  usePrefetchQuery(neighbor(ofVariant(previousVariant)))
}

interface CheckSettings {
  blockImages: boolean
  clients: string[]
  scheme: ColorScheme
}

function checkQueries(
  rendered: null | RenderedPreview,
  {blockImages, clients, scheme}: CheckSettings,
) {
  const html = rendered?.html ?? null

  return {
    accessibility: queryOptions({
      queryFn:
        rendered === null
          ? skipToken
          : async () => {
              if (html === null) {
                return []
              }

              const {auditAccessibility} = await import('#/lib/accessibility')

              return accessibilityChecks(await auditAccessibility(html, {blockImages, scheme}), {
                blockImages,
                html,
              })
            },
      queryKey: ['checks', 'accessibility', html, scheme, blockImages],
    }),
    compatibility: queryOptions({...compatibilityQuery(html, clients), enabled: rendered !== null}),
    links: queryOptions({...linksQuery(html), enabled: rendered !== null}),
    message: queryOptions({
      queryFn: rendered === null ? skipToken : () => checkMessage(rendered),
      queryKey: ['checks', 'message', rendered],
    }),
    size: (links: LinkCheck[] | undefined) =>
      queryOptions({
        queryFn: rendered === null ? skipToken : () => checkSize(rendered, links ?? []),
        queryKey: ['checks', 'size', rendered, links],
      }),
    urls: queryOptions({
      queryFn: rendered === null ? skipToken : () => unresolvedLinks(rendered),
      queryKey: ['checks', 'urls', rendered],
    }),
  }
}

export function useChecks(rendered: null | RenderedPreview) {
  const scheme = useSelector(previewStore, (state) => state.context.scheme)
  const blockImages = useSelector(previewStore, (state) => state.context.blockImages)
  const clients = useSelector(previewStore, (state) => state.context.clients)
  const queries = checkQueries(rendered, {blockImages, clients, scheme})
  const message = useQuery({...queries.message, placeholderData: keepPreviousData})
  const urls = useQuery({...queries.urls, placeholderData: keepPreviousData})
  const accessibility = useQuery({...queries.accessibility, placeholderData: keepPreviousData})
  const compatibility = useQuery({...queries.compatibility, placeholderData: keepPreviousData})
  const links = useQuery({...queries.links, placeholderData: keepPreviousData})
  const size = useQuery({...queries.size(links.data), placeholderData: keepPreviousData})

  return {accessibility, compatibility, links, message, size, urls}
}

export interface CheckSummary {
  errors: number
  isError: boolean
  isFetching: boolean
  warnings: number
}

type SummarizedQuery = {isError: boolean; isFetching: boolean}

function summary(query: SummarizedQuery, errors: number, warnings: number): CheckSummary {
  return {errors, isError: query.isError, isFetching: query.isFetching, warnings}
}

function checksSummary(query: SummarizedQuery, checks: Check[] | undefined) {
  const errors = checks?.filter((check) => check.severity === 'error').length ?? 0

  return summary(query, errors, (checks?.length ?? 0) - errors)
}

export function checkSummaries(
  checks: ReturnType<typeof useChecks>,
): Record<ChecksTab, CheckSummary> {
  const {accessibility, compatibility, links, message, size, urls} = checks
  const rows = linkRows(urls.data ?? [], links.data ?? [])

  return {
    accessibility: checksSummary(accessibility, accessibility.data),
    compatibility: summary(compatibility, 0, compatibility.data?.length ?? 0),
    links: summary(
      links,
      rows.filter(isBrokenLink).length,
      rows.filter((row) => !isBrokenLink(row) && row.status !== 'ok').length,
    ),
    message: checksSummary(message, message.data),
    size: checksSummary(size, size.data?.findings),
  }
}

export function useView(rendered: null | RenderedPreview): ViewMode {
  const view = useSelector(previewStore, (state) => state.context.view)

  if (rendered === null || view === 'source') {
    return view
  }

  if (view === 'text') {
    return rendered.text === null ? 'html' : 'text'
  }

  return rendered.html === null ? 'text' : 'html'
}

export function useRefresh(selection: null | Selection) {
  const queryClient = useQueryClient()

  return () => {
    void queryClient.invalidateQueries({queryKey: ['catalog']})
    void queryClient.invalidateQueries({
      queryKey: ['preview', selection?.message, selection?.variant, selection?.locale],
    })
    void queryClient.invalidateQueries({queryKey: ['links']})
    void queryClient.invalidateQueries({queryKey: ['compatibility']})
  }
}

export function fileName(message: string, selection: Selection) {
  return [message, selection.variant === 'default' ? null : selection.variant, selection.locale]
    .filter((part) => part !== null)
    .join('-')
}

export function useAutoRefreshToggle() {
  const queryClient = useQueryClient()

  return (isAutoRefresh: boolean) => {
    previewStore.trigger.autoRefreshToggled({isAutoRefresh})

    if (isAutoRefresh) {
      void invalidatePreview(queryClient)
    }
  }
}

export function useIsNarrow() {
  return useMediaQuery('(width < 48rem)')
}
