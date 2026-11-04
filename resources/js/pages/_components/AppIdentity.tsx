import {Avatar, Button, Dropdown, Label, Tooltip, Typography, useTheme} from '@heroui/react'

import {initials} from '#/lib/format'
import {themes, withoutTransitions} from '#/lib/theme'

export function ThemeMenu() {
  const {setTheme, theme} = useTheme('system')
  const current = themes.find((item) => item.id === theme) ?? themes[2]

  return (
    <Dropdown>
      <Tooltip>
        <Button aria-label={`Theme: ${current.name}`} isIconOnly size="sm" variant="ghost">
          <current.icon />
        </Button>
        <Tooltip.Content>Theme</Tooltip.Content>
      </Tooltip>
      <Dropdown.Popover placement="bottom end">
        <Dropdown.Menu
          aria-label="Theme"
          selectedKeys={[current.id]}
          selectionMode="single"
          onAction={(key) => withoutTransitions(() => setTheme(String(key)))}
        >
          {themes.map((item) => (
            <Dropdown.Item key={item.id} id={item.id} textValue={item.name}>
              <Dropdown.ItemIndicator />
              <item.icon className="size-4 shrink-0 text-muted" />
              <Label>{item.name}</Label>
            </Dropdown.Item>
          ))}
        </Dropdown.Menu>
      </Dropdown.Popover>
    </Dropdown>
  )
}

interface AppIdentityProps {
  appName: string
}

export function AppIdentity(props: AppIdentityProps) {
  const {appName} = props

  return (
    <div className="flex min-w-0 items-center gap-3 px-2">
      <Avatar color="accent" size="sm" variant="soft">
        <Avatar.Fallback>{initials(appName)}</Avatar.Fallback>
      </Avatar>
      <div className="min-w-0">
        <Typography truncate type="body-sm" weight="semibold">
          {appName}
        </Typography>
      </div>
    </div>
  )
}
