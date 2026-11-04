import type {Result} from 'axe-core'

import {formatBytes, withShortDataUris} from '#/lib/format'
import {textRanges, type Spotlight} from '#/lib/spotlight'
import type {ChecksTab} from '#/lib/store'
import type {LinkCheck, RenderedPreview} from '#/types/preview'

export type CheckSeverity = 'error' | 'info' | 'warning'

export const checkKinds = [
  {id: 'accessibility', label: 'Accessibility'},
  {id: 'compatibility', label: 'Compatibility'},
  {id: 'links', label: 'Links'},
  {id: 'message', label: 'Message'},
  {id: 'size', label: 'Size'},
] as const satisfies {id: ChecksTab; label: string}[]

export interface CheckElement {
  code: string
  image?: string
  spotlight?: Spotlight
  text?: string
}

export interface Check {
  count: number
  description: string
  elements?: CheckElement[]
  id: string
  severity: CheckSeverity
  title: string
  url?: string
}

export const gmailClippingSize = 102 * 1024

export const gmailWarningSize = 90 * 1024

const htmlTag = /<\/?[a-z][a-z0-9-]*(\s[^<>]*)?\/?>/i

const severityOrder: Record<CheckSeverity, number> = {error: 0, info: 2, warning: 1}

function check(
  id: string,
  severity: CheckSeverity,
  title: string,
  description: string,
  elements: CheckElement[],
): Check[] {
  return elements.length === 0
    ? []
    : [{count: elements.length, description, elements, id, severity, title}]
}

function finding(
  count: number,
  id: string,
  severity: CheckSeverity,
  title: string,
  description: string,
): Check[] {
  return count === 0 ? [] : [{count, description, id, severity, title}]
}

function isRelative(url: string) {
  return url !== '' && !url.startsWith('#') && !URL.canParse(url)
}

function isInsecure(url: string) {
  const parsed = URL.parse(url)

  return (
    parsed?.protocol === 'http:' &&
    ![window.location.hostname, 'localhost', '127.0.0.1', '[::1]'].includes(parsed.hostname)
  )
}

function bySeverity(a: Check, b: Check) {
  return severityOrder[a.severity] - severityOrder[b.severity]
}

function shows(text: null | string, lines: string[], part: string | undefined) {
  return text !== null && lines.some((line) => text.includes(line) && line.includes(part ?? line))
}

export function positionIn(document: Document, subject: null | string = null) {
  const walker = document.createTreeWalker(document, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT)
  const nodes = new Map<Node, number>()

  while (walker.nextNode() !== null) {
    nodes.set(walker.currentNode, nodes.size)
  }

  return (spotlight: Spotlight | undefined) => {
    if (spotlight === undefined) {
      return nodes.size
    }

    if ('lines' in spotlight && shows(subject, spotlight.lines, spotlight.part)) {
      return -1
    }

    if ('selector' in spotlight) {
      const element = document.querySelector(spotlight.selector)

      return element === null ? nodes.size : (nodes.get(element) ?? nodes.size)
    }

    const range = textRanges(document.body, spotlight.lines, spotlight.part)[0]

    return range === undefined
      ? nodes.size
      : (nodes.get(range.startContainer) ?? nodes.size) +
          range.startOffset / (range.startContainer as Text).length
  }
}

function byEmailOrder(position: ReturnType<typeof positionIn>) {
  return (a: Check, b: Check) =>
    bySeverity(a, b) || position(a.elements?.[0]?.spotlight) - position(b.elements?.[0]?.spotlight)
}

function locator(preview: RenderedPreview, document: Document) {
  const body = preview.html === null ? null : document.body

  return (id: string, lines: string[], part?: string): Spotlight | undefined => {
    const inSubject = shows(preview.subject, lines, part)
    const views = [
      ...(body !== null && (inSubject || textRanges(body, lines, part).length > 0)
        ? (['html'] as const)
        : []),
      ...(preview.text !== null && (inSubject || shows(preview.text, lines, part))
        ? (['text'] as const)
        : []),
    ]
    const [first, ...rest] = views

    return first === undefined ? undefined : {id, lines, part, views: [first, ...rest]}
  }
}

export function checkMessage(preview: RenderedPreview): Check[] {
  const document = new DOMParser().parseFromString(preview.html ?? '', 'text/html')
  const locate = locator(preview, document)
  const position = positionIn(document, preview.subject)
  const inEmailOrder = (elements: CheckElement[]) =>
    elements.toSorted((a, b) => position(a.spotlight) - position(b.spotlight))
  const tagged = (preview.text ?? '')
    .split('\n')
    .filter((line) => htmlTag.test(line))
    .map((line) => line.trim())

  return [
    ...check(
      'missing-translations',
      'warning',
      'Missing translations',
      `The ${preview.locale} translations do not define these keys, so the message shows them in the fallback language or as the key itself. Add them to the lang directory.`,
      inEmailOrder(
        preview.missing_translations.map(({key, lines}) => ({
          code: key,
          spotlight: locate(key, lines),
        })),
      ),
    ),
    ...check(
      'missing-replacements',
      'warning',
      'Unreplaced placeholders',
      'These translations kept a placeholder as text because no value was passed for it. Add it to the replacements of __() or trans().',
      inEmailOrder(
        preview.missing_replacements.map(({key, lines, placeholder}) => ({
          code: `${placeholder} in ${key}`,
          spotlight: locate(`${placeholder} in ${key}`, lines, placeholder),
        })),
      ),
    ),
    ...check(
      'text-html',
      'warning',
      'HTML in the plain-text body',
      'The plain-text body shows these tags literally.',
      [...new Set(tagged)].map((line) => ({
        code: line,
        spotlight: {id: line, lines: [line], views: ['text']},
      })),
    ),
    ...finding(
      preview.text === null ? 1 : 0,
      'no-text',
      'info',
      'No plain-text body',
      'Some clients and spam filters expect a plain-text alternative. Add a text view or use Markdown mail.',
    ),
  ].toSorted(byEmailOrder(position))
}

export type LinkRow = Omit<LinkCheck, 'status'> & {
  status: 'empty' | 'insecure' | 'relative' | LinkCheck['status']
}

export function unresolvedLinks(preview: RenderedPreview): LinkRow[] {
  const document = new DOMParser().parseFromString(preview.html ?? '', 'text/html')
  const found = [
    ...[...document.querySelectorAll('a[href]')].map((link) => ({
      kind: 'link' as const,
      url: link.getAttribute('href')?.trim() ?? '',
    })),
    ...[...document.querySelectorAll('img[src]')].map((image) => ({
      kind: 'image' as const,
      url: image.getAttribute('src')?.trim() ?? '',
    })),
  ].filter(({kind, url}) => isRelative(url) || (kind === 'link' && (url === '' || url === '#')))

  return [...new Set(found.map(({url}) => url))].map((url) => {
    const uses = found.filter((item) => item.url === url)

    return {
      content_length: null,
      final_url: null,
      http_status: null,
      kinds: [...new Set(uses.map((item) => item.kind))],
      occurrences: uses.length,
      reason: isRelative(url)
        ? 'Mail clients cannot resolve relative URLs. Generate absolute ones with url() or asset().'
        : 'The link leads nowhere when the message is opened.',
      route_name: null,
      status: isRelative(url) ? 'relative' : 'empty',
      url,
    }
  })
}

function withInsecure(link: LinkCheck): LinkRow {
  return isInsecure(link.url) && (link.status === 'ok' || link.status === 'redirected')
    ? {
        ...link,
        reason: 'Plain http is not encrypted, and some clients block images served over it.',
        status: 'insecure',
      }
    : link
}

export function linkRows(unresolved: LinkRow[], links: LinkCheck[]): LinkRow[] {
  return [...unresolved, ...links.map(withInsecure)]
}

export const largeImageSize = 1024 * 1024

export interface MessageImage {
  size: null | number
  source: string
}

export interface SizeCheck {
  findings: Check[]
  images: MessageImage[]
}

async function imageSize(source: string, links: LinkCheck[]) {
  return source.startsWith('data:')
    ? (await (await fetch(source)).blob()).size
    : (links.find((link) => link.url === source)?.content_length ?? null)
}

export async function checkSize(preview: RenderedPreview, links: LinkCheck[]): Promise<SizeCheck> {
  const size = preview.html_size ?? 0
  const document = new DOMParser().parseFromString(preview.html ?? '', 'text/html')
  const sources = new Set(
    [...document.querySelectorAll('img[src], [background]')]
      .map((image) => (image.getAttribute('src') ?? image.getAttribute('background') ?? '').trim())
      .filter((source) => source !== ''),
  )
  const images = await Promise.all(
    [...sources].map(async (source) => ({size: await imageSize(source, links), source})),
  )

  return {
    findings: [
      ...finding(
        size >= gmailWarningSize ? 1 : 0,
        'gmail-clipping',
        size >= gmailClippingSize ? 'error' : 'warning',
        size >= gmailClippingSize ? 'Clipped by Gmail' : 'Close to Gmail’s clipping limit',
        `Gmail shows only the first ${formatBytes(gmailClippingSize)} of the HTML and hides the rest behind a link. This message has ${formatBytes(size)}.`,
      ),
      ...finding(
        images.filter((image) => (image.size ?? 0) > largeImageSize).length,
        'heavy-images',
        'warning',
        'Heavy images',
        `Images over ${formatBytes(largeImageSize)} slow the message down on mobile data. Compress them or send smaller ones.`,
      ),
    ].toSorted(bySeverity),
    images,
  }
}

const impacts: Record<NonNullable<Result['impact']>, CheckSeverity> = {
  critical: 'error',
  minor: 'info',
  moderate: 'warning',
  serious: 'error',
}

function ownText(element: Element | null) {
  const hasText = [...(element?.childNodes ?? [])].some(
    (child) => child.nodeType === Node.TEXT_NODE && child.textContent?.trim() !== '',
  )

  return hasText ? element?.textContent?.replace(/\s+/g, ' ').trim() : undefined
}

function shownImage(element: Element | null, blockImages: boolean) {
  const source =
    (element?.matches('img') ? element : element?.querySelector('img'))?.getAttribute('src') ?? ''

  return URL.canParse(source) && (source.startsWith('data:') || !blockImages) ? source : undefined
}

export function accessibilityChecks(
  results: Result[],
  {blockImages, html}: {blockImages: boolean; html: string},
): Check[] {
  const document = new DOMParser().parseFromString(html, 'text/html')

  return results
    .map((result) => ({
      count: result.nodes.length,
      description: result.description.endsWith('.') ? result.description : `${result.description}.`,
      elements: result.nodes.map((node): CheckElement => {
        const selector = String(node.target[0])
        const element = document.querySelector(selector)

        return {
          code: withShortDataUris(node.html),
          image: shownImage(element, blockImages),
          spotlight: {id: selector, selector, views: ['html']},
          text: ownText(element),
        }
      }),
      id: result.id,
      severity: impacts[result.impact ?? 'minor'],
      title: result.help,
      url: result.helpUrl,
    }))
    .toSorted(byEmailOrder(positionIn(document)))
}

export function isBrokenLink(link: LinkRow) {
  return link.status === 'broken' || link.status === 'relative' || link.status === 'unreachable'
}
