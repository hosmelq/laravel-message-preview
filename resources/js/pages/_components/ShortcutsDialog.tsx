import {Modal, Typography} from '@heroui/react'
import {HotkeysProvider, useAppHotkeyList, useHotkey} from '@tanstack/react-hotkeys'
import {useSelector} from '@xstate/store-react'

import {appHotkeys} from '#/lib/hotkeys'
import {previewStore} from '#/lib/store'
import {HotkeyKbd} from '#/pages/_components/HotkeyKbd'

function close() {
  previewStore.trigger.shortcutsToggled({isOpen: false})
}

function ShortcutsList() {
  const items = useAppHotkeyList()
  const groups = Map.groupBy(items, (item) => item.appHotkey.meta?.group ?? 'General')

  useHotkey(appHotkeys['help.shortcuts'], close)

  return (
    <>
      <Modal.CloseTrigger />
      <Modal.Header>
        <Modal.Heading>Keyboard shortcuts</Modal.Heading>
      </Modal.Header>
      <Modal.Body>
        <div className="flex flex-col gap-5">
          {[...groups].map(([group, entries]) => (
            <section key={group} className="flex flex-col gap-2">
              <Typography color="muted" type="body-xs" weight="medium">
                {group}
              </Typography>
              <dl className="flex flex-col gap-2">
                {entries.map(({appHotkey}) => (
                  <div key={appHotkey.id} className="flex items-center justify-between gap-4">
                    <Typography render={(attributes) => <dt {...attributes} />} type="body-sm">
                      {appHotkey.meta?.name}
                    </Typography>
                    <dd>
                      <HotkeyKbd id={appHotkey.id} />
                    </dd>
                  </div>
                ))}
              </dl>
            </section>
          ))}
        </div>
      </Modal.Body>
    </>
  )
}

export function ShortcutsDialog() {
  const isOpen = useSelector(previewStore, (state) => state.context.isShortcutsOpen)

  return (
    <Modal
      isOpen={isOpen}
      onOpenChange={(next) => previewStore.trigger.shortcutsToggled({isOpen: next})}
    >
      <Modal.Backdrop>
        <Modal.Container size="sm">
          <Modal.Dialog>
            <HotkeysProvider exclusive scope="shortcuts-dialog">
              <ShortcutsList />
            </HotkeysProvider>
          </Modal.Dialog>
        </Modal.Container>
      </Modal.Backdrop>
    </Modal>
  )
}
