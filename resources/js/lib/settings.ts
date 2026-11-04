import type {Settings} from '#/types/preview'

export const settings: Settings = JSON.parse(
  (document.getElementById('message-preview-settings') as HTMLScriptElement).text,
)
