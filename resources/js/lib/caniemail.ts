import {
  CssTypes,
  parse,
  stringify,
  type CssAtRuleAST,
  type CssContainerAST,
  type CssDeclarationAST,
  type CssDocumentAST,
  type CssHostAST,
  type CssLayerAST,
  type CssMediaAST,
  type CssScopeAST,
  type CssStartingStyleAST,
  type CssSupportsAST,
} from '@adobe/css-tools'
import {caniemail, rawData} from 'caniemail'

import {noData} from '#/lib/caniemail-data'

export type SupportLevel = 'full' | 'none' | 'partial' | 'unknown'

export interface ClientSupport {
  id: string
  level: SupportLevel
  limited: string[]
  missing: string[]
  name: string
  notes: string[]
  unknown: string[]
}

export interface CompatibilityIssue {
  clients: ClientSupport[]
  title: string
  url: string
}

const testedFamilies = new Set(rawData.data.flatMap((feature) => Object.keys(feature.stats)))

export const clientFamilies: Record<string, string> = Object.fromEntries(
  Object.entries(rawData.nicenames.family).filter(([family]) => testedFamilies.has(family)),
)

const platformNames: Record<string, string> = rawData.nicenames.platform

const featureUrls = new Map(rawData.data.map((feature) => [feature.title, feature.url]))

type GroupingRule =
  | CssContainerAST
  | CssDocumentAST
  | CssHostAST
  | CssLayerAST
  | CssMediaAST
  | CssScopeAST
  | CssStartingStyleAST
  | CssSupportsAST

const groupingRuleTypes = new Set<CssTypes>([
  CssTypes.container,
  CssTypes.document,
  CssTypes.host,
  CssTypes.layer,
  CssTypes.media,
  CssTypes.scope,
  CssTypes.startingStyle,
  CssTypes.supports,
])

function isGroupingRule(node: CssAtRuleAST | CssDeclarationAST): node is GroupingRule {
  return groupingRuleTypes.has(node.type)
}

function isRule(node: CssAtRuleAST | CssDeclarationAST): node is CssAtRuleAST {
  return node.type !== CssTypes.declaration
}

function nestedRules(nodes: (CssAtRuleAST | CssDeclarationAST)[]): CssAtRuleAST[] {
  return nodes.filter(isGroupingRule).flatMap((group) => {
    const rules = (group.rules ?? []).filter(isRule)

    return [...rules, ...nestedRules(rules)]
  })
}

function nestedStyleSheet(html: string) {
  const document = new DOMParser().parseFromString(html, 'text/html')
  const rules = [...document.querySelectorAll('style')].flatMap((style) =>
    nestedRules(parse(style.textContent, {silent: true}).stylesheet.rules),
  )

  return rules.length === 0
    ? ''
    : `<style>${stringify({stylesheet: {rules}, type: CssTypes.stylesheet})}</style>`
}

export function checkCompatibility(html: string, families: string[]): CompatibilityIssue[] {
  const {issues} = caniemail({
    clients: families.map(
      (family) => `${family}.*` as Parameters<typeof caniemail>[0]['clients'][number],
    ),
    html: html + nestedStyleSheet(html),
  })
  const features = new Map<string, Map<string, Omit<ClientSupport, 'id' | 'level' | 'name'>>>()

  for (const [key, results] of [
    ['missing', issues.errors],
    ['limited', issues.warnings],
  ] as const) {
    for (const [client, clientIssues] of results) {
      const [family = client, platform = client] = client.split('.')

      for (const issue of clientIssues) {
        const clients = features.get(issue.title) ?? new Map()
        const support = clients.get(family) ?? {limited: [], missing: [], notes: [], unknown: []}

        support[issue.notes.includes(noData) ? 'unknown' : key].push(
          platformNames[platform] ?? platform,
        )
        support.notes.push(
          ...issue.notes.filter((note) => note !== noData && !support.notes.includes(note)),
        )
        clients.set(family, support)
        features.set(issue.title, clients)
      }
    }
  }

  const lacking = (issue: CompatibilityIssue, level: SupportLevel) =>
    issue.clients.filter((client) => client.level === level).length

  return [...features]
    .filter(([, clients]) =>
      [...clients.values()].some((support) => support.missing.length + support.limited.length > 0),
    )
    .map(([title, clients]) => ({
      clients: families.map((family): ClientSupport => {
        const support = clients.get(family) ?? {limited: [], missing: [], notes: [], unknown: []}

        return {
          ...support,
          id: family,
          level:
            support.missing.length > 0
              ? 'none'
              : support.limited.length > 0
                ? 'partial'
                : support.unknown.length > 0
                  ? 'unknown'
                  : 'full',
          name: clientFamilies[family] ?? family,
        }
      }),
      title,
      url: featureUrls.get(title) ?? 'https://www.caniemail.com/',
    }))
    .toSorted(
      (a, b) =>
        lacking(b, 'none') - lacking(a, 'none') || lacking(b, 'partial') - lacking(a, 'partial'),
    )
}
