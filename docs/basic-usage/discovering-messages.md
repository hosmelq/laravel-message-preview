---
title: "Discovering Messages"
description: "See which classes the preview lists, choose the directories it scans, and fix messages that fail."
weight: 1
---

The preview scans the directories in the `discovery.paths` option for mailables and mail notifications and lists them in the sidebar. Open `/message-preview` to see them. Until you select one, the page shows how many messages it found and lists the ones whose preview configuration fails.

## Listed classes

The preview lists every class in the scanned directories that is:

- A subclass of `Illuminate\Mail\Mailable`.
- A subclass of `Illuminate\Notifications\Notification` with a public `toMail()` method.

Abstract classes and classes with a private or protected constructor are left out, because the preview cannot create them.

The sidebar shows the messages as a tree of their namespaces, starting below the namespace they all share. With messages in `App\Mail` and `App\Notifications`, `App\Mail\Orders\OrderShipped` appears under Mail, in the Orders folder. The search field above the tree filters it by class name, namespace included.

## Choose the scanned directories

`discovery.paths` defaults to `[app_path()]`. The preview loads every class it finds there through Composer's autoloader each time it lists or renders messages, so list only the directories that hold messages:

```php
'discovery' => [
    'paths' => [
        app_path('Mail'),
        app_path('Notifications'),
    ],
],
```

The option lives in `config/message-preview.php`, which the [install command](../installation) publishes. Messages outside these directories, such as Laravel's `VerifyEmail` and `ResetPassword` notifications, can be [registered](../advanced-usage/registering-messages).

## Fix messages that fail

A class that throws while it loads is skipped. A button at the bottom of the sidebar counts the skipped classes and the directories in `discovery.paths` that could not be scanned. Select it to read the errors.

The tree marks a message as Error when its `previewConfig()` throws or returns something other than a `PreviewConfig`, or after its default variant fails to render. Open the message to read the exception class, the error text, and the file and line it came from.

These are the errors a new installation usually shows:

| Error | Fix |
| --- | --- |
| `Too few arguments to function ...::__construct()` | The constructor needs arguments. Build the message with [sample data](sample-data). |
| `Configure withNotifiable() ...` | The notification has no notifiable. Add one with [`withNotifiable()`](sample-data). |
| `No email was captured. ...` | The notifiable has no mail route, or `toMail()` returned neither a `MailMessage` nor a mailable. Return a model that uses the `Notifiable` trait and has an `email` attribute, or an `AnonymousNotifiable` with a `mail` route. |
