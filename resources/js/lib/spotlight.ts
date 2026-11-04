import {previewStore} from '#/lib/store'

export type BodyView = 'html' | 'text'

export type Spotlight = {id: string; views: [BodyView, ...BodyView[]]} & (
  | {lines: string[]; part?: string}
  | {selector: string}
)

const highlight = 'message-preview-spotlight'

const margin = 48

let restores: (() => void)[] = []

let appliedView: null | string = null

function offsets(text: string, search: string) {
  const found: number[] = []

  for (
    let index = text.indexOf(search);
    index !== -1;
    index = text.indexOf(search, index + search.length)
  ) {
    found.push(index)
  }

  return found
}

export function textRanges(root: HTMLElement, lines: string[], part?: string) {
  const walker = root.ownerDocument.createTreeWalker(root, NodeFilter.SHOW_TEXT)
  const ranges: Range[] = []

  while (walker.nextNode() !== null) {
    const node = walker.currentNode as Text

    for (const line of lines) {
      for (const start of offsets(node.data, line)) {
        for (const offset of offsets(line, part ?? line)) {
          const range = root.ownerDocument.createRange()

          range.setStart(node, start + offset)
          range.setEnd(node, start + offset + (part ?? line).length)
          ranges.push(range)
        }
      }
    }
  }

  return ranges
}

function outline(frame: HTMLIFrameElement | null, selector: string) {
  const accent = getComputedStyle(document.documentElement).getPropertyValue('--accent')
  const elements = [...(frame?.contentDocument?.querySelectorAll<HTMLElement>(selector) ?? [])]

  for (const element of elements) {
    const style = element.getAttribute('style')

    element.style.setProperty('outline', `2px solid ${accent}`, 'important')
    element.style.setProperty('outline-offset', '2px', 'important')
    restores.push(() =>
      style === null ? element.removeAttribute('style') : element.setAttribute('style', style),
    )
  }

  return elements
}

function mark(owner: Document, roots: HTMLElement[], lines: string[], part: string | undefined) {
  const realm = owner.defaultView
  const ranges = roots.flatMap((root) => textRanges(root, lines, part))

  if (realm !== null && ranges.length > 0) {
    const styles = getComputedStyle(document.documentElement)
    const color = (token: string) =>
      owner === document ? `var(${token})` : styles.getPropertyValue(token)
    const style = owner.createElement('style')

    style.textContent = `::highlight(${highlight}) { background-color: ${color('--warning')}; color: ${color('--warning-foreground')}; }`
    owner.head.append(style)
    realm.CSS.highlights.set(highlight, new realm.Highlight(...ranges))
    restores.push(() => {
      style.remove()
      realm.CSS.highlights.delete(highlight)
    })
  }

  return ranges
}

function reveal(page: HTMLElement, frame: HTMLIFrameElement | null, found: (Element | Range)[]) {
  const target = found.find((item) => item.getClientRects().length > 0)
  const container = page.closest<HTMLElement>('.scroll-shadow')

  if (target === undefined || container === null) {
    return
  }

  const node = 'startContainer' in target ? target.startContainer : target
  const inFrame = frame !== null && node.ownerDocument === frame.contentDocument

  if (!inFrame && !container.contains(node)) {
    return
  }

  const box = target.getBoundingClientRect()
  const shift = inFrame ? frame.getBoundingClientRect().top : 0
  const bounds = container.getBoundingClientRect()
  const above = box.top + shift - margin - bounds.top
  const below = box.bottom + shift + margin - bounds.bottom

  container.scrollBy({
    behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
    top: above < 0 ? above : Math.max(0, Math.min(above, below)),
  })
}

function clear() {
  for (const restore of restores) {
    restore()
  }

  restores = []
}

function shownPage(target: Spotlight) {
  const page = document.querySelector<HTMLElement>('[data-shown][data-view]')
  const view = target.views.find((item) => item === page?.dataset.view)

  return page === null || view === undefined ? null : {page, view}
}

function apply(page: HTMLElement, target: Spotlight, scroll: boolean) {
  const frame = page.querySelector('iframe')
  const frameDocument = frame?.contentDocument ?? null
  const found =
    'selector' in target
      ? outline(frame, target.selector)
      : [
          ...mark(
            document,
            [...document.querySelectorAll<HTMLElement>('[data-shown]')],
            target.lines,
            target.part,
          ),
          ...(frameDocument === null
            ? []
            : mark(frameDocument, [frameDocument.body], target.lines, target.part)),
        ]

  appliedView = page.dataset.view ?? null

  if (scroll) {
    reveal(page, frame, found)
  }
}

export function spotlight(target: null | Spotlight) {
  clear()
  appliedView = null
  previewStore.trigger.spotlightChanged({spotlight: target})

  if (target === null) {
    return
  }

  const shown = shownPage(target)
  const pending = document.querySelector<HTMLElement>('[data-view]:not([data-shown])')

  if (shown === null) {
    previewStore.trigger.viewChanged({view: target.views[0]})

    return
  }

  if (pending !== null && pending.dataset.view !== shown.view) {
    previewStore.trigger.viewChanged({view: shown.view})
  }

  apply(shown.page, target, true)
}

export function restoreSpotlight() {
  const target = previewStore.getSnapshot().context.spotlight
  const shown = target === null ? null : shownPage(target)

  if (target === null || shown === null) {
    spotlight(null)
  } else {
    clear()
    apply(shown.page, target, shown.view !== appliedView)
  }
}

export function releaseSpotlight() {
  return () => spotlight(null)
}
