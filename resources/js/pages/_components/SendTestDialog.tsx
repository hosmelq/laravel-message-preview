import {Modal} from '@heroui/react'
import {HotkeysProvider} from '@tanstack/react-hotkeys'
import {useSelector} from '@xstate/store-react'

import {previewStore} from '#/lib/store'
import {SendTestForm} from '#/pages/_components/SendTestForm'
import type {Selection} from '#/types/preview'

interface SendTestDialogProps {
  selection: Selection
}

export function SendTestDialog(props: SendTestDialogProps) {
  const {selection} = props
  const isOpen = useSelector(previewStore, (state) => state.context.isSendOpen)

  return (
    <Modal
      isOpen={isOpen}
      onOpenChange={(next) => previewStore.trigger.sendToggled({isOpen: next})}
    >
      <Modal.Backdrop>
        <Modal.Container size="sm">
          <Modal.Dialog>
            <HotkeysProvider exclusive scope="send-dialog">
              <Modal.CloseTrigger />
              <Modal.Header>
                <Modal.Heading>Send a test email</Modal.Heading>
              </Modal.Header>
              <Modal.Body>
                <SendTestForm selection={selection} />
              </Modal.Body>
            </HotkeysProvider>
          </Modal.Dialog>
        </Modal.Container>
      </Modal.Backdrop>
    </Modal>
  )
}
