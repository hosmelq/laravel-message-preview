import data from 'caniemail/caniemail.json'

export const noData = 'caniemail has no data for this client.'

interface Feature {
  notes_by_num: null | Record<string, string>
  stats: Record<string, Record<string, Record<string, string> | undefined> | undefined>
}

const features = data.data as unknown as Feature[]
const platforms = new Map<string, Set<string>>()

for (const feature of features) {
  for (const [family, stats] of Object.entries(feature.stats)) {
    for (const platform of Object.keys(stats ?? {})) {
      platforms.set(family, (platforms.get(family) ?? new Set()).add(platform))
    }
  }
}

const versions = new Intl.Collator('en', {numeric: true})

for (const feature of features) {
  for (const [family, known] of platforms) {
    const stats = (feature.stats[family] ??= {})

    for (const platform of known) {
      const latest = Object.entries(stats[platform] ?? {})
        .toSorted(([a], [b]) => versions.compare(a, b))
        .at(-1)

      if (latest === undefined) {
        stats[platform] = {'0': 'u #0'}
        feature.notes_by_num = {...feature.notes_by_num, '0': noData}
      } else {
        stats[platform] = Object.fromEntries([latest])
      }
    }
  }
}

export const caniEmailJson = data
