import {
  ChevronDown,
  ChevronUp,
  Display,
  Envelope,
  Link,
  UniversalAccess,
  WeightHanging,
} from '@gravity-ui/icons'
import {
  Button,
  Chip,
  ScrollShadow,
  Separator,
  Spinner,
  ToggleButton,
  ToggleButtonGroup,
  Tooltip,
  Typography,
} from '@heroui/react'
import {useSelector} from '@xstate/store-react'
import {lazy, Suspense, type ComponentType, type SVGProps} from 'react'

import {checkKinds} from '#/lib/checks'
import {checkSummaries, useChecks, usePreview, useSelection, type CheckSummary} from '#/lib/preview'
import {previewStore, type ChecksTab} from '#/lib/store'
import {transition} from '#/lib/transition'
import {EmptyView} from '#/pages/_components/EmptyView'
import {HotkeyKbd} from '#/pages/_components/HotkeyKbd'

const ChecksResults = lazy(() => import('#/pages/_components/ChecksResults'))

const kindIcons = {
  accessibility: UniversalAccess,
  compatibility: Display,
  links: Link,
  message: Envelope,
  size: WeightHanging,
} satisfies Record<ChecksTab, ComponentType<SVGProps<SVGSVGElement>>>

interface TabStatusProps {
  isWaiting: boolean
  summary: CheckSummary | null
}

function TabStatus(props: TabStatusProps) {
  const {isWaiting, summary} = props
  const count = summary === null ? 0 : summary.errors + summary.warnings

  return (
    <span className="ms-1 inline-flex min-w-7 justify-center tabular-nums">
      {summary === null ? null : isWaiting || summary.isFetching ? (
        <Spinner aria-label="Running" color="current" size="sm" />
      ) : summary.isError ? (
        <Chip color="warning" size="sm" variant="soft">
          Failed
        </Chip>
      ) : summary.errors > 0 ? (
        <Chip color="danger" size="sm" variant="soft">
          {count}
        </Chip>
      ) : (
        <Typography color="muted" render={(attributes) => <span {...attributes} />} type="body-xs">
          {count}
        </Typography>
      )}
    </span>
  )
}

export function ChecksPanel() {
  const {catalog, requestedMessage, selection} = useSelection()
  const {isFetching, isPlaceholderData, rendered} = usePreview(selection)
  const checks = useChecks(isPlaceholderData ? null : rendered)
  const summaries = checkSummaries(checks)
  const tab = useSelector(previewStore, (state) => state.context.checksTab)
  const isOpen = useSelector(previewStore, (state) => state.context.isChecksOpen)
  const kind = checkKinds.find((item) => item.id === tab) ?? checkKinds[0]
  const isWaiting =
    selection === null
      ? catalog.isPending && requestedMessage !== null
      : isFetching || isPlaceholderData
  const isDisabled = rendered === null && !isWaiting
  const toggleLabel = isOpen ? 'Hide the checks' : 'Show the checks'

  return (
    <div
      className={
        isOpen
          ? 'checks-bar flex min-h-0 flex-1 flex-col overflow-hidden'
          : 'checks-bar flex max-w-full shrink-0 flex-col self-center overflow-hidden'
      }
    >
      {isOpen && (
        <section aria-label={`${kind.label} checks`} className="flex min-h-0 flex-1 flex-col">
          <ScrollShadow className="flex min-h-0 flex-1 flex-col px-5 pt-5 pb-2" size={16}>
            {rendered === null ? (
              isWaiting ? (
                <Spinner aria-label="Rendering the message" size="sm" />
              ) : (
                <EmptyView
                  description={
                    selection === null
                      ? 'Open a message to run its checks.'
                      : 'The checks run once the message renders.'
                  }
                  icon={Envelope}
                  title="Nothing to check"
                />
              )
            ) : (
              <Suspense fallback={<Spinner aria-label="Loading the checks" size="sm" />}>
                <ChecksResults checks={checks} kind={kind.id} rendered={rendered} />
              </Suspense>
            )}
          </ScrollShadow>
        </section>
      )}
      <div className="flex shrink-0 items-center justify-center gap-1 p-1.5">
        <ScrollShadow className="min-w-0" hideScrollBar orientation="horizontal">
          <ToggleButtonGroup
            aria-label="Checks"
            isDetached
            isDisabled={isDisabled}
            selectedKeys={isOpen ? [kind.id] : []}
            selectionMode="single"
            size="sm"
            onSelectionChange={(keys) => {
              const next = checkKinds.find((item) => keys.has(item.id))

              if (next === undefined) {
                transition('checks-close', () => previewStore.trigger.checksToggled())
              } else if (isOpen) {
                previewStore.trigger.checksTabChanged({tab: next.id})
              } else {
                transition('checks-open', () =>
                  previewStore.trigger.checksTabChanged({tab: next.id}),
                )
              }
            }}
          >
            {checkKinds.map((item) => {
              const Icon = kindIcons[item.id]

              return (
                <ToggleButton key={item.id} id={item.id} variant="ghost">
                  <Icon />
                  {item.label}
                  <TabStatus
                    isWaiting={isWaiting}
                    summary={isDisabled ? null : summaries[item.id]}
                  />
                </ToggleButton>
              )
            })}
          </ToggleButtonGroup>
        </ScrollShadow>
        <div className="flex h-5 shrink-0 items-center">
          <Separator orientation="vertical" />
        </div>
        <Tooltip>
          <Button
            aria-label={toggleLabel}
            className="shrink-0"
            isIconOnly
            size="sm"
            variant="ghost"
            onPress={() =>
              transition(isOpen ? 'checks-close' : 'checks-open', () =>
                previewStore.trigger.checksToggled(),
              )
            }
          >
            {isOpen ? <ChevronDown /> : <ChevronUp />}
          </Button>
          <Tooltip.Content>
            {toggleLabel} <HotkeyKbd id="checks.toggle" />
          </Tooltip.Content>
        </Tooltip>
      </div>
    </div>
  )
}
