import {
  ChevronDown,
  CircleCheck,
  CircleExclamation,
  CircleQuestion,
  CircleXmark,
  Display,
} from '@gravity-ui/icons'
import {
  Alert,
  Button,
  Dropdown,
  Label,
  Link,
  Popover,
  Spinner,
  Table,
  Tooltip,
  Typography,
} from '@heroui/react'
import {HotkeysProvider} from '@tanstack/react-hotkeys'
import type {UseQueryResult} from '@tanstack/react-query'
import {useSelector} from '@xstate/store-react'
import pluralize from 'pluralize'
import type {ComponentType} from 'react'
import ReactMarkdown from 'react-markdown'

import {
  clientFamilies,
  type ClientSupport,
  type CompatibilityIssue,
  type SupportLevel,
} from '#/lib/caniemail'
import {formatList} from '#/lib/format'
import {previewStore} from '#/lib/store'
import {EmptyView} from '#/pages/_components/EmptyView'
import {InfoTip} from '#/pages/_components/InfoTip'

const levels: Record<
  SupportLevel,
  {className: string; icon: ComponentType<{className?: string}>; label: string}
> = {
  full: {className: 'text-success', icon: CircleCheck, label: 'Supported'},
  none: {className: 'text-danger', icon: CircleXmark, label: 'Not supported'},
  partial: {className: 'text-warning', icon: CircleExclamation, label: 'Partly supported'},
  unknown: {className: 'text-muted', icon: CircleQuestion, label: 'No data'},
}

function supportDetail(client: ClientSupport) {
  const sentences = [
    client.missing.length > 0 && `Not supported on ${formatList(client.missing)}.`,
    client.limited.length > 0 && `Partly supported on ${formatList(client.limited)}.`,
    client.unknown.length > 0 && `No caniemail data for ${formatList(client.unknown)}.`,
  ].filter((sentence) => sentence !== false)

  return sentences.length === 0 ? `${levels[client.level].label}.` : sentences.join(' ')
}

interface NoteProps {
  children: string
}

function Note(props: NoteProps) {
  const {children} = props

  return (
    <ReactMarkdown
      allowedElements={['a', 'code', 'p']}
      components={{
        a: ({children: text, href}) => (
          <Link href={href} rel="noreferrer" target="_blank">
            {text}
          </Link>
        ),
        code: ({children: text}) => <Typography.Code>{text}</Typography.Code>,
        p: ({children: text}) => (
          <Typography color="muted" type="body-sm">
            {text}
          </Typography>
        ),
      }}
      unwrapDisallowed
    >
      {children}
    </ReactMarkdown>
  )
}

interface SupportCellProps {
  client: ClientSupport
}

function SupportCell(props: SupportCellProps) {
  const {client} = props
  const level = levels[client.level]

  return (
    <Popover>
      <Tooltip>
        <Button aria-label={`${client.name}: ${level.label}`} isIconOnly size="sm" variant="ghost">
          <level.icon className={`size-4 ${level.className}`} />
        </Button>
        <Tooltip.Content>
          {client.name}: {level.label}
        </Tooltip.Content>
      </Tooltip>
      <Popover.Content className="max-w-80" placement="top">
        <Popover.Dialog>
          <HotkeysProvider exclusive scope="overlay">
            <Popover.Heading>{client.name}</Popover.Heading>
            <div className="flex flex-col gap-2 pt-2">
              <Typography type="body-sm">{supportDetail(client)}</Typography>
              {client.notes.map((note) => (
                <Note key={note}>{note}</Note>
              ))}
            </div>
          </HotkeysProvider>
        </Popover.Dialog>
      </Popover.Content>
    </Popover>
  )
}

interface CompatibilityTableProps {
  clients: string[]
  issues: CompatibilityIssue[]
}

function CompatibilityTable(props: CompatibilityTableProps) {
  const {clients, issues} = props
  const columns = [
    {id: 'feature', name: 'Feature'},
    ...clients.map((id) => ({id, name: clientFamilies[id] ?? id})),
  ]

  return (
    <Table key={clients.join(',')} className="min-h-0">
      <Table.ScrollContainer>
        <Table.Content
          aria-label="Features some clients do not fully support"
          className="min-w-[600px]"
        >
          <Table.Header columns={columns}>
            {(column) => (
              <Table.Column id={column.id} isRowHeader={column.id === 'feature'}>
                <span className="whitespace-nowrap">{column.name}</span>
              </Table.Column>
            )}
          </Table.Header>
          <Table.Body items={issues}>
            {(issue) => (
              <Table.Row
                columns={columns}
                href={issue.url}
                id={issue.title}
                rel="noreferrer"
                target="_blank"
              >
                {(column) => {
                  const client = issue.clients.find((item) => item.id === column.id)

                  return (
                    <Table.Cell>
                      {column.id === 'feature' ? (
                        <span className="whitespace-nowrap">
                          <Typography
                            render={(attributes) => <span {...attributes} />}
                            type="body-sm"
                            weight="medium"
                          >
                            {issue.title}
                          </Typography>
                        </span>
                      ) : (
                        client !== undefined && <SupportCell client={client} />
                      )}
                    </Table.Cell>
                  )
                }}
              </Table.Row>
            )}
          </Table.Body>
        </Table.Content>
      </Table.ScrollContainer>
    </Table>
  )
}

function ClientPicker() {
  const clients = useSelector(previewStore, (state) => state.context.clients)

  return (
    <Dropdown>
      <Button size="sm" variant="ghost">
        <Display />
        {pluralize('client', clients.length, true)}
        <ChevronDown />
      </Button>
      <Dropdown.Popover placement="bottom start">
        <Dropdown.Menu
          aria-label="Mail clients"
          selectedKeys={clients}
          selectionMode="multiple"
          onSelectionChange={(keys) =>
            previewStore.trigger.clientsChanged({
              clients: Object.keys(clientFamilies).filter(
                (family) => keys === 'all' || keys.has(family),
              ),
            })
          }
        >
          {Object.entries(clientFamilies).map(([id, name]) => (
            <Dropdown.Item key={id} id={id} textValue={name}>
              <Dropdown.ItemIndicator />
              <Label>{name}</Label>
            </Dropdown.Item>
          ))}
        </Dropdown.Menu>
      </Dropdown.Popover>
    </Dropdown>
  )
}

interface CompatibilityPanelProps {
  query: UseQueryResult<CompatibilityIssue[]>
}

export function CompatibilityPanel(props: CompatibilityPanelProps) {
  const {query} = props
  const issues = query.data
  const clients = useSelector(previewStore, (state) => state.context.clients)

  return (
    <div className="flex min-h-0 flex-1 flex-col gap-3">
      <div className="flex items-center justify-between gap-3">
        <ClientPicker />
        <InfoTip
          label="About the client support data"
          text="Support data from caniemail.com, for the latest tested version of each client. Press a status for its platforms and notes."
        />
      </div>
      {clients.length === 0 ? (
        <EmptyView
          description="Choose the mail clients to check."
          icon={Display}
          title="No clients chosen"
        />
      ) : query.isError ? (
        <Alert status="danger">
          <Alert.Indicator />
          <Alert.Content>
            <Alert.Title>The client support check failed</Alert.Title>
            <Alert.Description>{query.error.message}</Alert.Description>
          </Alert.Content>
        </Alert>
      ) : issues === undefined ? (
        <Spinner aria-label="Checking client support" size="sm" />
      ) : issues.length === 0 ? (
        <EmptyView
          description="caniemail.com lists no missing or partial support in the chosen clients."
          icon={CircleCheck}
          title="No known problems"
          tone="success"
        />
      ) : (
        <CompatibilityTable clients={clients} issues={issues} />
      )}
    </div>
  )
}
