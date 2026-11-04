import {Eye} from '@gravity-ui/icons'
import {Accordion, Chip, Link, ListBox, Typography} from '@heroui/react'
import {useSelector} from '@xstate/store-react'
import type {ReactNode} from 'react'

import type {Check, CheckElement, CheckSeverity} from '#/lib/checks'
import {releaseSpotlight, spotlight} from '#/lib/spotlight'
import {previewStore} from '#/lib/store'
import {HighlightedHtml} from '#/pages/_components/HighlightedHtml'

const severities = {
  error: {color: 'danger', label: 'Error'},
  info: {color: 'default', label: 'Info'},
  warning: {color: 'warning', label: 'Warning'},
} as const satisfies Record<CheckSeverity, {color: string; label: string}>

interface ElementsProps {
  elements: CheckElement[]
  label: string
}

function Elements(props: ElementsProps) {
  const {elements, label} = props
  const selected = useSelector(previewStore, (state) => state.context.spotlight?.id)
  const located = elements.flatMap(({spotlight: target, ...element}) =>
    target === undefined ? [] : [{...element, target}],
  )
  const hidden = elements.filter((element) => element.spotlight === undefined)
  const hasImages = located.some((element) => element.image !== undefined)

  return (
    <>
      {located.length > 0 && (
        <div className="-mx-3">
          <ListBox
            ref={releaseSpotlight}
            aria-label={label}
            selectedKeys={located
              .filter((element) => element.target.id === selected)
              .map((element) => element.target.id)}
            selectionMode="single"
            onSelectionChange={(keys) =>
              spotlight(
                located.find((element) => keys !== 'all' && keys.has(element.target.id))?.target ??
                  null,
              )
            }
          >
            {located.map(({code, image, target, text}) => (
              <ListBox.Item key={target.id} id={target.id} textValue={text ?? code}>
                {text === undefined ? (
                  <>
                    {hasImages &&
                      (image === undefined ? (
                        <span className="h-10 w-14 shrink-0" />
                      ) : (
                        <img alt="" className="h-10 w-14 shrink-0 object-contain" src={image} />
                      ))}
                    <Typography.Code className="min-w-0" truncate>
                      <HighlightedHtml code={code} />
                    </Typography.Code>
                  </>
                ) : (
                  <Typography className="min-w-0" truncate type="body-sm">
                    {text}
                  </Typography>
                )}
                <ListBox.ItemIndicator>
                  {({isSelected}) => (isSelected ? <Eye /> : null)}
                </ListBox.ItemIndicator>
              </ListBox.Item>
            ))}
          </ListBox>
        </div>
      )}
      {hidden.length > 0 && (
        <ul className="flex flex-col items-start gap-3">
          {hidden.map(({code}) => (
            <li key={code} className="flex max-w-full flex-col items-start gap-1">
              <Typography.Code className="max-w-full" truncate>
                <HighlightedHtml code={code} />
              </Typography.Code>
              <Typography color="muted" type="body-xs">
                Not shown as text
              </Typography>
            </li>
          ))}
        </ul>
      )}
    </>
  )
}

interface ChecksListProps {
  checks: Check[]
  empty: ReactNode
}

export function ChecksList(props: ChecksListProps) {
  const {checks, empty} = props

  if (checks.length === 0) {
    return empty
  }

  return (
    <Accordion
      key={checks.map((check) => check.id).join(' ')}
      allowsMultipleExpanded
      defaultExpandedKeys={checks.slice(0, 1).map((check) => check.id)}
      onExpandedChange={(keys) => {
        const selected = previewStore.getSnapshot().context.spotlight?.id
        const holders = checks.filter((check) =>
          check.elements?.some((element) => element.spotlight?.id === selected),
        )

        if (selected !== undefined && holders.every((check) => !keys.has(check.id))) {
          spotlight(null)
        }
      }}
    >
      {checks.map((check) => (
        <Accordion.Item key={check.id} id={check.id}>
          <Accordion.Heading level={2}>
            <Accordion.Trigger>
              <span className="flex min-w-0 flex-1 items-center gap-2">
                <span className="flex w-15 shrink-0">
                  <Chip color={severities[check.severity].color} size="sm" variant="soft">
                    {severities[check.severity].label}
                  </Chip>
                </span>
                {check.title}
              </span>
              <span className="ms-3 me-2 tabular-nums">
                <Typography
                  color="muted"
                  render={(attributes) => <span {...attributes} />}
                  type="body-xs"
                >
                  {check.count}
                </Typography>
              </span>
              <Accordion.Indicator />
            </Accordion.Trigger>
          </Accordion.Heading>
          <Accordion.Panel>
            <Accordion.Body>
              <div className="flex flex-col gap-3">
                <p>
                  {check.description}{' '}
                  {check.url !== undefined && (
                    <Link href={check.url} rel="noreferrer" target="_blank">
                      Learn more
                      <Link.Icon />
                    </Link>
                  )}
                </p>
                {check.elements !== undefined && (
                  <Elements elements={check.elements} label={check.title} />
                )}
              </div>
            </Accordion.Body>
          </Accordion.Panel>
        </Accordion.Item>
      ))}
    </Accordion>
  )
}
