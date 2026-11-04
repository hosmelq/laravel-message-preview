import {CircleInfo} from '@gravity-ui/icons'
import {Button, Popover, Tooltip, Typography} from '@heroui/react'

interface InfoTipProps {
  label: string
  text: string
}

export function InfoTip(props: InfoTipProps) {
  const {label, text} = props

  return (
    <Popover>
      <Tooltip>
        <Button aria-label={label} isIconOnly size="sm" variant="ghost">
          <CircleInfo />
        </Button>
        <Tooltip.Content>{label}</Tooltip.Content>
      </Tooltip>
      <Popover.Content className="max-w-64">
        <Popover.Dialog>
          <div className="flex flex-col gap-2">
            <Popover.Heading>{label}</Popover.Heading>
            <Typography color="muted" type="body-sm">
              {text}
            </Typography>
          </div>
        </Popover.Dialog>
      </Popover.Content>
    </Popover>
  )
}
