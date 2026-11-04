import {Bars} from '@gravity-ui/icons'
import {Button, Tooltip, Typography} from '@heroui/react'

import {previewStore} from '#/lib/store'

interface PhoneBarProps {
  title: null | string
}

export function PhoneBar(props: PhoneBarProps) {
  const {title} = props

  return (
    <header className="flex h-12 shrink-0 items-center gap-2">
      <Tooltip>
        <Button
          aria-label="Messages"
          isIconOnly
          size="sm"
          variant="ghost"
          onPress={() => previewStore.trigger.sidebarToggled({isOpen: true})}
        >
          <Bars />
        </Button>
        <Tooltip.Content>Messages</Tooltip.Content>
      </Tooltip>
      <div className="min-w-0 flex-1">
        <Typography truncate type="body-sm" weight="semibold">
          {title}
        </Typography>
      </div>
    </header>
  )
}
