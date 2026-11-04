import {createLoader, createSerializer, parseAsString} from 'nuqs'

export const selectionParsers = {
  locale: parseAsString,
  message: parseAsString,
  variant: parseAsString.withDefault('default'),
}

export const selectionHref = createSerializer(selectionParsers)

export const loadSelection = createLoader(selectionParsers)
