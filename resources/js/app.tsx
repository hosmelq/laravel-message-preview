import './index.css'
import {I18nProvider, RouterProvider, Toast} from '@heroui/react'
import {HotkeysProvider} from '@tanstack/react-hotkeys'
import {QueryClient, QueryClientProvider} from '@tanstack/react-query'
import {enableHistorySync, NuqsAdapter} from 'nuqs/adapters/react'
import {StrictMode} from 'react'
import {createRoot} from 'react-dom/client'

import {appHotkeys} from '#/lib/hotkeys'
import {previewStore} from '#/lib/store'
import {Preview} from '#/pages/Preview'

const queryClient = new QueryClient({
  defaultOptions: {queries: {retry: false, staleTime: Infinity}},
})

enableHistorySync()

createRoot(document.getElementById('app') as HTMLElement).render(
  <StrictMode>
    <I18nProvider locale="en-US">
      <QueryClientProvider client={queryClient}>
        <NuqsAdapter>
          <HotkeysProvider appHotkeys={appHotkeys} defaultOptions={{hotkey: {capture: true}}}>
            <RouterProvider
              navigate={(href) => {
                window.history.pushState(null, '', href)
                previewStore.trigger.sidebarToggled({isOpen: false})
              }}
            >
              <Preview />
              <Toast.Provider />
            </RouterProvider>
          </HotkeysProvider>
        </NuqsAdapter>
      </QueryClientProvider>
    </I18nProvider>
  </StrictMode>,
)
