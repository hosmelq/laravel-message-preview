import {CircleCheck, Display, UniversalAccess} from '@gravity-ui/icons'
import {Alert, Spinner} from '@heroui/react'
import type {UseQueryResult} from '@tanstack/react-query'
import type {ReactNode} from 'react'

import type {useChecks} from '#/lib/preview'
import type {ChecksTab} from '#/lib/store'
import {ChecksList} from '#/pages/_components/ChecksList'
import {CompatibilityPanel} from '#/pages/_components/CompatibilityPanel'
import {EmptyView} from '#/pages/_components/EmptyView'
import {LinksPanel} from '#/pages/_components/LinksPanel'
import {SizePanel} from '#/pages/_components/SizePanel'
import type {RenderedPreview} from '#/types/preview'

interface ResultsProps<T> {
  children: (data: T) => ReactNode
  failure: string
  query: UseQueryResult<T>
}

function Results<T>(props: ResultsProps<T>) {
  const {children, failure, query} = props

  if (query.isError) {
    return (
      <Alert status="danger">
        <Alert.Indicator />
        <Alert.Content>
          <Alert.Title>{failure}</Alert.Title>
          <Alert.Description>{query.error.message}</Alert.Description>
        </Alert.Content>
      </Alert>
    )
  }

  return query.data === undefined ? (
    <Spinner aria-label="Running the checks" size="sm" />
  ) : (
    children(query.data)
  )
}

interface ChecksResultsProps {
  checks: ReturnType<typeof useChecks>
  kind: ChecksTab
  rendered: RenderedPreview
}

export default function ChecksResults(props: ChecksResultsProps) {
  const {checks, kind, rendered} = props

  if (kind === 'accessibility') {
    return (
      <Results failure="The accessibility audit failed" query={checks.accessibility}>
        {(found) => (
          <ChecksList
            checks={found}
            empty={
              <EmptyView
                description="axe-core checked the message against WCAG 2.2 AA and its best practices."
                icon={UniversalAccess}
                title="No accessibility problems"
                tone="success"
              />
            }
          />
        )}
      </Results>
    )
  }

  if (kind === 'compatibility') {
    return rendered.html === null ? (
      <EmptyView
        description="Client support is checked for messages with an HTML body."
        icon={Display}
        title="No HTML body"
      />
    ) : (
      <CompatibilityPanel query={checks.compatibility} />
    )
  }

  if (kind === 'links') {
    return (
      <Results failure="The links could not be checked" query={checks.links}>
        {(found) => (
          <LinksPanel html={rendered.html} links={found} unresolved={checks.urls.data ?? []} />
        )}
      </Results>
    )
  }

  if (kind === 'size') {
    return (
      <Results failure="The size could not be measured" query={checks.size}>
        {(found) => <SizePanel check={found} rendered={rendered} />}
      </Results>
    )
  }

  return (
    <Results failure="The message checks failed" query={checks.message}>
      {(found) => (
        <ChecksList
          checks={found}
          empty={
            <EmptyView
              description="The plain text and the translations of this message pass every check."
              icon={CircleCheck}
              title="No problems"
              tone="success"
            />
          }
        />
      )}
    </Results>
  )
}
