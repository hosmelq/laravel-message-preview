import {
  Button,
  Description,
  FieldError,
  Form,
  Input,
  Label,
  ListBox,
  Select,
  Spinner,
  TextField,
  toast,
} from '@heroui/react'
import {useMutation} from '@tanstack/react-query'
import {useSelector} from '@xstate/store-react'

import {sendTestEmailMutation} from '#/lib/api'
import {settings} from '#/lib/settings'
import {previewStore} from '#/lib/store'
import type {Selection} from '#/types/preview'

interface SendTestFormProps {
  selection: Selection
}

export function SendTestForm(props: SendTestFormProps) {
  const {selection} = props
  const recipient = useSelector(previewStore, (state) => state.context.recipient)
  const chosenMailer = useSelector(previewStore, (state) => state.context.mailer)
  const mailers = settings.mailers
  const mailer =
    chosenMailer !== null && mailers.includes(chosenMailer) ? chosenMailer : settings.default_mailer
  const send = useMutation({
    ...sendTestEmailMutation(selection),
    onError: (error) => toast.danger(error.message),
    onSuccess: (_, email) => {
      toast.success(`Sent to ${email.to} through ${email.mailer}`)
      previewStore.trigger.sendToggled({isOpen: false})
    },
  })

  return (
    <Form
      className="flex flex-col gap-3"
      onSubmit={(event) => {
        event.preventDefault()
        send.mutate({mailer, to: recipient})
      }}
    >
      <TextField
        autoFocus
        isRequired
        name="to"
        type="email"
        value={recipient}
        variant="secondary"
        onChange={(to) => previewStore.trigger.recipientChanged({recipient: to})}
      >
        <Label>To</Label>
        <Input placeholder="you@example.com" />
        <Description>Replaces every recipient, including cc and bcc.</Description>
        <FieldError />
      </TextField>
      {mailers.length > 1 && (
        <Select
          selectedKey={mailer}
          variant="secondary"
          onSelectionChange={(key) => {
            if (typeof key === 'string') {
              previewStore.trigger.mailerChanged({mailer: key})
            }
          }}
        >
          <Label>Mailer</Label>
          <Select.Trigger>
            <Select.Value />
            <Select.Indicator />
          </Select.Trigger>
          <Select.Popover>
            <ListBox>
              {mailers.map((item) => (
                <ListBox.Item key={item} id={item} textValue={item}>
                  {item}
                  <ListBox.ItemIndicator />
                </ListBox.Item>
              ))}
            </ListBox>
          </Select.Popover>
        </Select>
      )}
      <div className="flex justify-end gap-2">
        <Button slot="close" variant="tertiary">
          Cancel
        </Button>
        <Button isPending={send.isPending} type="submit">
          {({isPending}) => (
            <>
              {isPending && <Spinner color="current" size="sm" />}
              {isPending ? 'Sending' : 'Send'}
            </>
          )}
        </Button>
      </div>
    </Form>
  )
}
