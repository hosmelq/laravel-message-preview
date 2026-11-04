---
title: "Locales and Translations"
description: "Preview a message in another locale and find the translations it is missing."
weight: 2
---

Each message renders in one locale. The [Message check](../basic-usage/checks) reports the translation keys that locale does not define and the placeholders left without a value. The locale menu renders the message in another locale.

## Set the locale of a message

With Auto selected in the locale menu, the preview picks the locale like this:

- A mailable renders in the locale set with its `locale()` method, otherwise in the application locale. When you send it with `Mail::to($user)` and it sets no locale, Laravel uses the user's `preferredLocale()`. The preview never calls `Mail::to()`, so set that locale in its sample data.
- A notification renders in the locale set with its `locale()` method, then in the notifiable's `preferredLocale()` when the notifiable implements `HasLocalePreference`, then in the application locale.

To preview a message in the locale your application sends it in, set that locale in its sample data:

```php
->withMessage(fn (): self => new self(Order::factory()->make())->locale('es'))
```

## Switch locales in the preview

The locale menu in the toolbar lists the application locale, the fallback locale, and every locale in the `lang` directory, whether it is a folder or a JSON file. Package translations in `lang/vendor` add no locales. The menu appears when the list has more than one locale.

Choosing a locale other than Auto overrides the message's own locale and stays selected while you move between messages. Test emails and downloads use the selected locale too.

## Missing translations

A missing translation is a key the rendered locale does not define, so the message shows the fallback locale's text or the key itself. The check records every key the message looks up through `__()`, `trans()`, `trans_choice()`, or `@lang` while it renders, and reports:

- File keys, such as `orders.subject`, even when the fallback locale defines them.
- JSON strings, such as `Regards,` in Laravel's notification template.
- Namespaced package keys, such as `package::messages.title`.

In the fallback locale, a JSON string is its own text, so the check reports only file and namespaced keys that no translation defines.

Select a key in the Message check to highlight the text the email shows for it.

![The Message check open on a missing Spanish translation, with the English text the message falls back to highlighted in the email](../images/translations.png)

## Unreplaced placeholders

An unreplaced placeholder is one a translation kept as text because no value was passed for it. With `'greeting' => 'Hi :name, here is what is new this week.'` in `lang/en/newsletter.php`, `__('newsletter.greeting')` reports `:name`, and `__('newsletter.greeting', ['name' => $user->name])` does not. Placeholders that `trans_choice()` fills with the count are not reported.
