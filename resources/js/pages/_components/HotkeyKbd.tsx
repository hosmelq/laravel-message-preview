import {Kbd} from '@heroui/react'
import {formatForDisplay, type AppHotkeyId} from '@tanstack/react-hotkeys'
import type {ComponentProps} from 'react'

import {appHotkeys} from '#/lib/hotkeys'

interface HotkeyKbdProps {
  className?: string
  id: AppHotkeyId
  variant?: ComponentProps<typeof Kbd>['variant']
}

export function HotkeyKbd(props: HotkeyKbdProps) {
  const {className, id, variant} = props

  return (
    <Kbd className={className} variant={variant}>
      <Kbd.Content>{formatForDisplay(appHotkeys[id].defaultHotkey)}</Kbd.Content>
    </Kbd>
  )
}
