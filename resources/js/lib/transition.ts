import {flushSync} from 'react-dom'

export function transition(type: string, change: () => void) {
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
    change()

    return
  }

  void document
    .startViewTransition({types: [type], update: () => flushSync(change)})
    .ready.catch(() => undefined)
}
