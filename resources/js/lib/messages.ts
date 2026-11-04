import type {MessageSummary, Variant} from '#/types/preview'

export interface Namespace {
  count: number
  id: string
  label: string
  messages: MessageSummary[]
  namespaces: Namespace[]
}

function sharedPrefix(paths: string[][]) {
  const [first = [], ...rest] = paths
  let length = first.length

  for (const path of rest) {
    let index = 0

    while (index < length && path[index] === first[index]) index++

    length = index
  }

  return first.slice(0, length)
}

function sortTree(namespace: Namespace): Namespace {
  return {
    ...namespace,
    messages: namespace.messages.toSorted((a, b) => a.name.localeCompare(b.name)),
    namespaces: namespace.namespaces
      .map(sortTree)
      .toSorted((a, b) => a.label.localeCompare(b.label)),
  }
}

export function buildMessageTree(messages: MessageSummary[]): Namespace[] {
  const paths = messages.map((message) => message.namespace.split('\\').filter(Boolean))
  const prefix = sharedPrefix(paths)
  const base = paths.some((path) => path.length === prefix.length) ? prefix.slice(0, -1) : prefix
  const root: Namespace = {count: 0, id: '', label: 'Messages', messages: [], namespaces: []}

  messages.forEach((message, index) => {
    const path = (paths[index] ?? []).slice(base.length)
    let parent = root

    path.forEach((label, depth) => {
      let child = parent.namespaces.find((item) => item.label === label)

      if (child === undefined) {
        child = {
          count: 0,
          id: path.slice(0, depth + 1).join('\\'),
          label,
          messages: [],
          namespaces: [],
        }
        parent.namespaces.push(child)
      }

      parent = child
    })

    parent.messages.push(message)
  })

  const tree = sortTree(root)

  return tree.messages.length > 0 ? [tree] : tree.namespaces
}

export function filterTree(
  namespaces: Namespace[],
  matches: (message: MessageSummary) => boolean,
): Namespace[] {
  return namespaces.flatMap((namespace) => {
    const children = filterTree(namespace.namespaces, matches)
    const messages = namespace.messages.filter(matches)
    const count = children.reduce((total, child) => total + child.count, messages.length)

    return count === 0 ? [] : [{...namespace, count, messages, namespaces: children}]
  })
}

export function treeMessages(namespaces: Namespace[]): MessageSummary[] {
  return namespaces.flatMap((namespace) => [
    ...treeMessages(namespace.namespaces),
    ...namespace.messages,
  ])
}

export function namespaceIds(namespaces: Namespace[]): string[] {
  return namespaces.flatMap((namespace) => [namespace.id, ...namespaceIds(namespace.namespaces)])
}

export function namespacePath(namespaces: Namespace[], messageKey: string): Namespace[] {
  for (const namespace of namespaces) {
    if (namespace.messages.some((message) => message.key === messageKey)) {
      return [namespace]
    }

    const path = namespacePath(namespace.namespaces, messageKey)

    if (path.length > 0) {
      return [namespace, ...path]
    }
  }

  return []
}

export function messageLabel(message: MessageSummary, variant: null | undefined | Variant) {
  return variant === null || variant === undefined || variant.key === 'default'
    ? message.name
    : `${message.name} · ${variant.label}`
}

export function matchesQuery(message: MessageSummary, query: string) {
  const needle = query.trim().toLocaleLowerCase()

  return message.class_name.toLocaleLowerCase().includes(needle)
}
