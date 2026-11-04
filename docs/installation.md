---
title: "Installation"
description: "Install Laravel Message Preview and publish its assets."
weight: 2
---

Install the package with Composer and run its install command. The frontend comes prebuilt, so your application needs no Node.js build step.

## Requirements

Laravel Message Preview requires PHP 8.4 or later with the `dom` extension, and Laravel 12 or 13.

## Install the package

Install the package with Composer:

```bash
composer require hosmelq/laravel-message-preview
```

Install it as a regular dependency, not with `--dev`. Your messages reference `PreviewConfig`, and a `MessagePreview::register()` call in a service provider runs in every environment, so it fails where the package is not installed.

Run the install command to publish the configuration file to `config/message-preview.php` and the frontend assets to `public/vendor/message-preview`:

```bash
php artisan message-preview:install
```

[Configuration](configuration) describes the options.

After every update, publish the assets again. The install command does not overwrite files it published before, so use `vendor:publish` with `--force`, for example from the `post-update-cmd` scripts of your `composer.json`:

```json
"post-update-cmd": [
    "@php artisan vendor:publish --tag=message-preview-assets --ansi --force"
]
```

## Open the preview

Open `/message-preview` on your local site, such as `http://my-app.test/message-preview`. The preview answers only when `APP_ENV` is `local`. In every other environment its routes return `404`.

## Next steps

See which messages the preview [discovered](basic-usage/discovering-messages), then add [sample data](basic-usage/sample-data) to the ones that need it.
