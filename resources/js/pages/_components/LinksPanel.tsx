import {Eye, Link} from '@gravity-ui/icons'
import {Chip, Description, Label, ListBox, Typography} from '@heroui/react'
import {useSelector} from '@xstate/store-react'

import {linkRows, positionIn, type LinkRow} from '#/lib/checks'
import {formatBytes, formatList} from '#/lib/format'
import {releaseSpotlight, spotlight, type Spotlight} from '#/lib/spotlight'
import {previewStore} from '#/lib/store'
import {EmptyView} from '#/pages/_components/EmptyView'
import {InfoTip} from '#/pages/_components/InfoTip'
import type {LinkCheck} from '#/types/preview'

const statuses = {
  broken: {color: 'danger', label: 'Broken', rank: 0},
  empty: {color: 'warning', label: 'No destination', rank: 1},
  insecure: {color: 'warning', label: 'Insecure', rank: 1},
  ok: {color: 'success', label: 'OK', rank: 2},
  redirected: {color: 'warning', label: 'Redirected', rank: 1},
  relative: {color: 'danger', label: 'Relative', rank: 0},
  unreachable: {color: 'danger', label: 'Unreachable', rank: 0},
} as const satisfies Record<LinkRow['status'], {color: string; label: string; rank: number}>

function detail(link: LinkRow) {
  return [
    formatList(link.kinds),
    link.occurrences > 1 ? `${link.occurrences} times` : null,
    link.http_status === null ? null : `HTTP ${link.http_status}`,
    link.status === 'redirected' && link.final_url !== null ? `to ${link.final_url}` : null,
    link.route_name === null ? null : `route ${link.route_name}`,
    link.reason,
    link.kinds.includes('image') && link.content_length !== null
      ? formatBytes(link.content_length)
      : null,
  ]
    .filter((part) => part !== null)
    .join(' · ')
}

function selectorOf(url: string) {
  const value = CSS.escape(url)

  return `[href="${value}"], [src="${value}"], [background="${value}"], [srcset*="${value}"]`
}

interface LinksPanelProps {
  html: null | string
  links: LinkCheck[]
  unresolved: LinkRow[]
}

export function LinksPanel(props: LinksPanelProps) {
  const {html, links, unresolved} = props
  const selected = useSelector(previewStore, (state) => state.context.spotlight?.id)
  const position = positionIn(new DOMParser().parseFromString(html ?? '', 'text/html'))
  const rows = linkRows(unresolved, links)
    .map((link) => {
      const id = URL.canParse(link.url)
        ? link.url
        : `${link.kinds.includes('link') ? 'href' : 'src'}="${link.url}"`
      const spotlight = {id, selector: selectorOf(link.url), views: ['html']} satisfies Spotlight

      return {...link, id, position: position(spotlight), spotlight}
    })
    .toSorted(
      (a, b) => statuses[a.status].rank - statuses[b.status].rank || a.position - b.position,
    )

  if (rows.length === 0) {
    return <EmptyView icon={Link} title="No links or images" />
  }

  return (
    <div className="flex min-h-0 flex-1 flex-col gap-3">
      <div className="flex items-center justify-between gap-3">
        <Typography color="muted" type="body-sm">
          {rows.length === 1 ? '1 URL' : `${rows.length} URLs`}
        </Typography>
        <InfoTip
          label="About the link check"
          text="Select a URL to outline its links and images in the email. Links to this application are checked against its routes and public files without being requested, so signed links are never used."
        />
      </div>
      <div className="-mx-3">
        <ListBox
          ref={releaseSpotlight}
          aria-label="Links and images"
          selectedKeys={rows.filter((row) => row.id === selected).map((row) => row.id)}
          selectionMode="single"
          onSelectionChange={(keys) =>
            spotlight(rows.find((row) => keys !== 'all' && keys.has(row.id))?.spotlight ?? null)
          }
        >
          {rows.map((link) => (
            <ListBox.Item key={link.id} id={link.id} textValue={link.id}>
              <div className="flex min-w-0 flex-1 flex-col">
                <span className="min-w-0 wrap-anywhere">
                  <Label>{link.id}</Label>
                </span>
                <Description>{detail(link)}</Description>
              </div>
              <Chip color={statuses[link.status].color} size="sm" variant="soft">
                {statuses[link.status].label}
              </Chip>
              <ListBox.ItemIndicator>
                {({isSelected}) => (isSelected ? <Eye /> : null)}
              </ListBox.ItemIndicator>
            </ListBox.Item>
          ))}
        </ListBox>
      </div>
    </div>
  )
}
