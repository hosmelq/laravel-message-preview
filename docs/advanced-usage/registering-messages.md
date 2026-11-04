---
title: "Registering Messages"
description: "Preview Laravel's own notifications and messages from packages."
weight: 1
---

The preview lists only classes in the directories of `discovery.paths`. Laravel's own notifications, such as `VerifyEmail` and `ResetPassword`, and the messages of other packages live elsewhere, and you cannot add a `previewConfig()` method to them. Register them from a service provider instead.

## Register a message

Call `MessagePreview::register()` in the `boot()` method of a service provider. Pass the class and a closure that returns its configuration:

```php
namespace App\Providers;

use App\Models\User;
use HosmelQ\MessagePreview\Facades\MessagePreview;
use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        MessagePreview::register(
            VerifyEmail::class,
            fn (): PreviewConfig => PreviewConfig::configure()
                ->withNotifiable(fn (): User => User::factory()->create()),
        );

        MessagePreview::register(
            ResetPassword::class,
            fn (): PreviewConfig => PreviewConfig::configure()
                ->withMessage(fn (): ResetPassword => new ResetPassword('preview-token'))
                ->withNotifiable(fn (): User => User::factory()->make()),
        );
    }
}
```

The closure returns the same `PreviewConfig` a `previewConfig()` method would, with [sample data](../basic-usage/sample-data) and variants. `VerifyEmail` needs a saved user because its link contains the user's ID; the preview rolls the user back after rendering. `ResetPassword` takes its token as a constructor argument.

Both notifications build their links from named routes, `verification.verify` and `password.reset`. They render once your application defines those routes, as Laravel's starter kits do.

## How registered messages behave

A registered message joins the tree under its full namespace. Registering `Illuminate\Auth\Notifications\VerifyEmail` next to your `App` messages adds an Illuminate section, and your own messages move under App.

It renders the way your application sends it, including changes made with `VerifyEmail::toMailUsing()` or `ResetPassword::createUrlUsing()`.

The closure runs each time the preview lists or renders messages, not while your application boots. If it throws, the message is marked as Error with the exception's message.

You can register a class inside those directories too. The registered configuration then takes the place of the class's own `previewConfig()`.
