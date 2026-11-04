import {filesize} from 'filesize'

import type {Address} from '#/types/preview'

export function formatBytes(bytes: number) {
  return filesize(bytes, {round: 1, standard: 'jedec'})
}

export function formatList(items: string[]) {
  return new Intl.ListFormat('en-US', {type: 'conjunction'}).format(items)
}

export function initials(name: string) {
  return (
    name
      .split(/[\s._-]+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((word) => word.charAt(0).toUpperCase())
      .join('') || '?'
  )
}

export function formatAddresses(addresses: Address[]) {
  return addresses
    .map((item) => (item.name === null ? item.address : `${item.name} <${item.address}>`))
    .join(', ')
}

export function withShortDataUris(html: string) {
  return html.replace(/(data:[\w/+.-]+;base64,)[\w+/=]{64,}/g, '$1…')
}
