import {toast} from '@heroui/react'

export const canCopy = window.isSecureContext

export async function copyToClipboard(content: string, label: string) {
  try {
    await navigator.clipboard.writeText(content)
    toast.success(`Copied the ${label}`)
  } catch {
    toast.danger(`Could not copy the ${label}`)
  }
}
