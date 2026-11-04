import {defineAppHotkeys} from '@tanstack/react-hotkeys'

export const appHotkeys = defineAppHotkeys({
  'search.focus': {hotkey: '/', meta: {group: 'General', name: 'Search messages'}},
  'help.shortcuts': {hotkey: '?', meta: {group: 'General', name: 'Keyboard shortcuts'}},
  'message.next': {hotkey: 'J', meta: {group: 'Navigation', name: 'Next message'}},
  'message.previous': {hotkey: 'K', meta: {group: 'Navigation', name: 'Previous message'}},
  'variant.next': {hotkey: ']', meta: {group: 'Navigation', name: 'Next variant'}},
  'variant.previous': {hotkey: '[', meta: {group: 'Navigation', name: 'Previous variant'}},
  'checks.toggle': {
    hotkey: 'C',
    meta: {group: 'Preview', name: 'Show or hide the checks'},
    options: {requireReset: true},
  },
  'send.open': {hotkey: 'E', meta: {group: 'Preview', name: 'Send a test email'}},
  'preview.refresh': {
    hotkey: 'R',
    meta: {group: 'Preview', name: 'Refresh'},
    options: {requireReset: true},
  },
  'preview.autoRefresh': {
    hotkey: 'L',
    meta: {group: 'Preview', name: 'Auto-refresh'},
    options: {requireReset: true},
  },
  'view.toggle': {
    hotkey: 'T',
    meta: {group: 'Preview', name: 'HTML or text'},
    options: {requireReset: true},
  },
  'view.source': {
    hotkey: 'S',
    meta: {group: 'Preview', name: 'Source'},
    options: {requireReset: true},
  },
  'width.toggle': {
    hotkey: 'M',
    meta: {group: 'Preview', name: 'Full or mobile width'},
    options: {requireReset: true},
  },
  'scheme.toggle': {
    hotkey: 'D',
    meta: {group: 'Preview', name: 'Email in light or dark mode'},
    options: {requireReset: true},
  },
  'images.toggle': {
    hotkey: 'I',
    meta: {group: 'Preview', name: 'Load or block remote images'},
    options: {requireReset: true},
  },
  'preview.open': {
    hotkey: 'O',
    meta: {group: 'Preview', name: 'Open in a browser tab'},
    options: {requireReset: true},
  },
})

declare module '@tanstack/react-hotkeys' {
  interface Register {
    appHotkeys: typeof appHotkeys
  }
}
