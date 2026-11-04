import type {ColorScheme} from '#/types/preview'

export function policy(document: Document, content: string) {
  const meta = document.createElement('meta')

  meta.httpEquiv = 'Content-Security-Policy'
  meta.content = content

  return meta
}

export function serialize(document: Document) {
  const doctype =
    document.doctype === null ? '' : new XMLSerializer().serializeToString(document.doctype)

  return doctype + document.documentElement.outerHTML
}

export function applyColorScheme(document: Document, scheme: ColorScheme) {
  const rewrite = (conditions: string) =>
    conditions.replace(/prefers-color-scheme\s*:\s*(dark|light)/gi, (_, value: string) =>
      value.toLowerCase() === scheme ? 'min-width: 0px' : 'max-width: -1px',
    )

  for (const style of document.querySelectorAll('style')) {
    style.textContent = rewrite(style.textContent)
  }

  for (const element of document.querySelectorAll('[media]')) {
    element.setAttribute('media', rewrite(element.getAttribute('media') ?? ''))
  }
}

export function frameDocument(html: string, options: {blockImages: boolean; scheme: ColorScheme}) {
  const document = new DOMParser().parseFromString(html, 'text/html')
  const base = document.createElement('base')

  applyColorScheme(document, options.scheme)
  document.documentElement.style.setProperty('overflow-y', 'hidden')

  base.target = '_blank'
  document.head.prepend(base)

  if (options.blockImages) {
    document.head.prepend(policy(document, 'img-src data:'))
  }

  return serialize(document)
}
