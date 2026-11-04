import type {Result, RunOptions} from 'axe-core'
import axeSource from 'axe-core/axe.min.js?raw'
import {nanoid} from 'nanoid'

import {applyColorScheme, policy, serialize} from '#/lib/frame'
import type {ColorScheme} from '#/types/preview'

const options: RunOptions = {
  resultTypes: ['violations'],
  rules: {
    'landmark-one-main': {enabled: false},
    'page-has-heading-one': {enabled: false},
    region: {enabled: false},
  },
  runOnly: {
    type: 'tag',
    values: ['best-practice', 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'],
  },
}

const timeout = 15_000

export function auditAccessibility(
  html: string,
  {blockImages, scheme}: {blockImages: boolean; scheme: ColorScheme},
): Promise<Result[]> {
  const document = new DOMParser().parseFromString(html, 'text/html')
  const nonce = nanoid()
  const axe = document.createElement('script')
  const runner = document.createElement('script')

  axe.setAttribute('nonce', nonce)
  axe.textContent = axeSource
  runner.setAttribute('nonce', nonce)
  runner.textContent = `axe.run(document, ${JSON.stringify(options)}).then(
    (results) => parent.postMessage({violations: results.violations}, '*'),
    (error) => parent.postMessage({error: String(error)}, '*'),
  )`
  for (const refresh of document.querySelectorAll('meta[http-equiv="refresh" i]')) {
    refresh.remove()
  }

  applyColorScheme(document, scheme)
  document.head.prepend(
    policy(
      document,
      `default-src 'none'; img-src ${blockImages ? '' : '* '}data:; script-src 'nonce-${nonce}'; style-src 'unsafe-inline'`,
    ),
  )
  document.body.append(axe, runner)

  const frame = window.document.createElement('iframe')

  frame.setAttribute('aria-hidden', 'true')
  frame.setAttribute('sandbox', 'allow-scripts')
  frame.setAttribute('tabindex', '-1')
  frame.style.cssText = `position:fixed;left:-10000px;top:0;width:600px;height:800px;border:0;color-scheme:${scheme}`
  frame.srcdoc = serialize(document)

  return new Promise((resolve, reject) => {
    const timer = window.setTimeout(finish, timeout, {error: 'The accessibility audit timed out.'})

    function finish(data: {error: string} | {violations: Result[]}) {
      window.clearTimeout(timer)
      window.removeEventListener('message', receive)
      frame.remove()

      if ('violations' in data) {
        resolve(data.violations)
      } else {
        reject(new Error(data.error))
      }
    }

    function receive(event: MessageEvent) {
      if (event.source === frame.contentWindow) {
        finish(event.data)
      }
    }

    window.addEventListener('message', receive)
    window.document.body.append(frame)
  })
}
