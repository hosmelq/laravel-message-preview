import {Alert, Typography} from '@heroui/react'

import type {PreviewError} from '#/types/preview'

interface PreviewFailureProps {
  error: PreviewError
  isConfiguration: boolean
}

export function PreviewFailure(props: PreviewFailureProps) {
  const {error, isConfiguration} = props
  const location = error.line === null ? error.file : `${error.file}:${error.line}`

  return (
    <div className="wrap-anywhere">
      <Alert status="danger">
        <Alert.Indicator />
        <Alert.Content>
          <Alert.Title>
            {isConfiguration ? 'The preview configuration failed' : 'This variant failed to render'}
          </Alert.Title>
          <Alert.Description>
            <span className="whitespace-pre-wrap">{error.message}</span>
          </Alert.Description>
          <Typography color="muted" type="body-xs">
            {error.file === null ? error.exception : `${error.exception} in ${location}`}
          </Typography>
        </Alert.Content>
      </Alert>
    </div>
  )
}
