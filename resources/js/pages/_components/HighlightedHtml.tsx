import {useQuery} from '@tanstack/react-query'

interface HighlightedHtmlProps {
  code: string
}

export function HighlightedHtml(props: HighlightedHtmlProps) {
  const {code} = props
  const {data: lines} = useQuery({
    queryFn: async () => (await import('#/lib/highlight')).highlightHtml(code),
    queryKey: ['highlight', code],
  })

  if (lines === undefined) {
    return code
  }

  return lines.flatMap((line, index) => [
    ...(index === 0 ? [] : ['\n']),
    ...line.map((token) => (
      <span
        key={token.offset}
        style={{color: `light-dark(${token.variants.light?.color}, ${token.variants.dark?.color})`}}
      >
        {token.content}
      </span>
    )),
  ])
}
