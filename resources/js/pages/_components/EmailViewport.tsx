import {ToggleButton, ToggleButtonGroup, Tooltip} from '@heroui/react'
import {useSelector} from '@xstate/store-react'
import type {ReactNode} from 'react'

import {useIsNarrow} from '#/lib/preview'
import {mobileWidth, previewStore, tabletWidth} from '#/lib/store'
import {HotkeyKbd} from '#/pages/_components/HotkeyKbd'

export interface DisplayControlProps {
  isDisabled: boolean
}

export function WidthGroup(props: DisplayControlProps) {
  const {isDisabled} = props
  const width = useSelector(previewStore, (state) => state.context.width)

  return (
    <ToggleButtonGroup
      aria-label="Width"
      disallowEmptySelection
      isDetached
      isDisabled={isDisabled}
      selectedKeys={[width === null ? 'full' : String(width)]}
      selectionMode="single"
      size="sm"
      onSelectionChange={(keys) => {
        const next = ([tabletWidth, mobileWidth] as const).find((item) => keys.has(String(item)))

        previewStore.trigger.widthChanged({width: next ?? null})
      }}
    >
      <ToggleButton id="full" variant="ghost">
        Full
      </ToggleButton>
      <Tooltip>
        <ToggleButton id={String(tabletWidth)} variant="ghost">
          {tabletWidth}
        </ToggleButton>
        <Tooltip.Content>Tablet, {tabletWidth} px</Tooltip.Content>
      </Tooltip>
      <Tooltip>
        <ToggleButton id={String(mobileWidth)} variant="ghost">
          {mobileWidth}
        </ToggleButton>
        <Tooltip.Content>
          Phone, {mobileWidth} px <HotkeyKbd id="width.toggle" />
        </Tooltip.Content>
      </Tooltip>
    </ToggleButtonGroup>
  )
}

interface EmailViewportProps {
  children: ReactNode
}

export function EmailViewport(props: EmailViewportProps) {
  const {children} = props
  const width = useSelector(previewStore, (state) => state.context.width)
  const isNarrow = useIsNarrow()

  return (
    <div className="mx-auto w-full" style={{width: isNarrow ? undefined : (width ?? undefined)}}>
      {children}
    </div>
  )
}
