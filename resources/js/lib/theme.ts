import {Display, Moon, Sun} from '@gravity-ui/icons'

export const themes = [
  {icon: Sun, id: 'light', name: 'Light'},
  {icon: Moon, id: 'dark', name: 'Dark'},
  {icon: Display, id: 'system', name: 'System'},
] as const

export function withoutTransitions(change: () => void) {
  const root = document.documentElement

  root.classList.add('theme-switching')
  change()
  requestAnimationFrame(() => requestAnimationFrame(() => root.classList.remove('theme-switching')))
}
