import html from '@shikijs/langs/html'
import githubDark from '@shikijs/themes/github-dark'
import githubLight from '@shikijs/themes/github-light'
import {createHighlighterCoreSync} from 'shiki/core'
import {createJavaScriptRegexEngine} from 'shiki/engine/javascript'

const highlighter = createHighlighterCoreSync({
  engine: createJavaScriptRegexEngine(),
  langs: [html],
  themes: [githubLight, githubDark],
})

export function highlightHtml(code: string) {
  return highlighter.codeToTokensWithThemes(code, {
    lang: 'html',
    themes: {dark: 'github-dark', light: 'github-light'},
  })
}
