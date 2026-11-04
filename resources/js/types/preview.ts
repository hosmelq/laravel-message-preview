export type ColorScheme = 'dark' | 'light'

export type MessageKind = 'mailable' | 'notification'

export type ViewMode = 'html' | 'source' | 'text'

export interface Address {
  address: string
  name: null | string
}

export interface Attachment {
  content_type: string
  filename: null | string
  inline: boolean
  size: number
  url: string
}

export interface Header {
  name: string
  value: string
}

export interface Variant {
  key: string
  label: string
}

export interface MessageSummary {
  class_name: string
  error: null | string
  key: string
  kind: MessageKind
  name: string
  namespace: string
  variants: Variant[]
}

export interface Catalog {
  data: MessageSummary[]
  meta: {discovery_errors: string[]; locales: string[]}
}

export interface Settings {
  app_name: string
  default_mailer: string
  mailers: string[]
  poll_interval: number
}

export interface PreviewError {
  exception: string
  file: null | string
  line: null | number
  message: string
}

export interface FailedPreview {
  error: PreviewError
  status: 'failed'
}

export interface RenderedPreview {
  attachments: Attachment[]
  bcc: Address[]
  cc: Address[]
  from: Address[]
  headers: Header[]
  html: null | string
  html_size: null | number
  locale: string
  missing_replacements: {key: string; lines: string[]; placeholder: string}[]
  missing_translations: {key: string; lines: string[]}[]
  reply_to: Address[]
  status: 'rendered'
  subject: null | string
  text: null | string
  to: Address[]
  urls: {eml: string; html: null | string; text: null | string}
}

export type Preview = FailedPreview | RenderedPreview

export interface LinkCheck {
  content_length: null | number
  final_url: null | string
  http_status: null | number
  kinds: ('image' | 'link')[]
  occurrences: number
  reason: null | string
  route_name: null | string
  status: 'broken' | 'ok' | 'redirected' | 'unreachable'
  url: string
}

export interface Selection {
  locale: null | string
  message: string
  variant: string
}
