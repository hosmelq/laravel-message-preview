import {Typography} from '@heroui/react'
import type {ComponentType, ReactNode} from 'react'

const tones = {
  danger: 'text-danger',
  default: 'text-muted',
  success: 'text-success',
} as const

interface EmptyViewProps {
  children?: ReactNode
  description?: ReactNode
  icon: ComponentType<{className?: string}>
  isPageHeading?: boolean
  title: string
  tone?: keyof typeof tones
}

export function EmptyView(props: EmptyViewProps) {
  const {children, description, icon: Icon, isPageHeading = false, title, tone = 'default'} = props

  return (
    <div className="flex flex-1 flex-col items-center justify-center">
      <div className="flex flex-col items-center gap-4 text-center">
        <div className="flex size-12 items-center justify-center rounded-full bg-default">
          <Icon className={`size-5 ${tones[tone]}`} />
        </div>
        <div className="flex max-w-xs flex-col gap-1 text-pretty">
          <Typography
            align="center"
            render={({children: text, ...attributes}) =>
              isPageHeading ? <h1 {...attributes}>{text}</h1> : <p {...attributes}>{text}</p>
            }
            type="h6"
          >
            {title}
          </Typography>
          {description !== undefined && (
            <Typography align="center" color="muted" type="body-sm">
              {description}
            </Typography>
          )}
        </div>
        {children}
      </div>
    </div>
  )
}
