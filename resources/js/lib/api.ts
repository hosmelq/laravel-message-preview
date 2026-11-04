import {mutationOptions, queryOptions, type QueryClient} from '@tanstack/react-query'

import {withShortDataUris} from '#/lib/format'
import type {Catalog, LinkCheck, Preview, Selection} from '#/types/preview'

const basePath = location.pathname.replace(/\/+$/, '')

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${basePath}/api${path}`, {
    ...init,
    headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
  })

  if (response.status === 204) {
    return undefined as T
  }

  const body = await response.json()

  if (!response.ok) {
    throw new Error(body.message)
  }

  return body
}

function previewPath(selection: Selection) {
  return `/messages/${encodeURIComponent(selection.message)}/previews/${encodeURIComponent(selection.variant)}`
}

export function invalidatePreview(client: QueryClient) {
  return client.invalidateQueries({
    predicate: ({queryKey: [scope]}) =>
      scope === 'catalog' || scope === 'links' || scope === 'preview',
  })
}

export const fingerprintQuery = queryOptions({
  queryFn: async ({client, queryKey}) => {
    const {data} = await request<{data: {fingerprint: string}}>('/fingerprint')
    const last = client.getQueryData<string>(queryKey)

    if (last !== undefined && last !== data.fingerprint) {
      void invalidatePreview(client)
    }

    return data.fingerprint
  },
  queryKey: ['fingerprint'],
})

export const catalogQuery = queryOptions({
  queryFn: () => request<Catalog>('/messages'),
  queryKey: ['catalog'],
})

export function previewQuery(selection: Selection) {
  const query = selection.locale === null ? '' : `?locale=${encodeURIComponent(selection.locale)}`

  return queryOptions({
    queryFn: async () => (await request<{data: Preview}>(previewPath(selection) + query)).data,
    queryKey: ['preview', selection.message, selection.variant, selection.locale],
  })
}

export function linksQuery(html: null | string) {
  return queryOptions({
    queryFn: async ({signal}) =>
      html === null
        ? []
        : (
            await request<{data: LinkCheck[]}>('/links', {
              body: JSON.stringify({html: withShortDataUris(html)}),
              method: 'POST',
              signal,
            })
          ).data,
    queryKey: ['links', html],
  })
}

export function sendTestEmailMutation(selection: Selection) {
  return mutationOptions({
    mutationFn: (email: {mailer: string; to: string}) =>
      request<undefined>(`${previewPath(selection)}/send`, {
        body: JSON.stringify({...email, locale: selection.locale}),
        method: 'POST',
      }),
  })
}
