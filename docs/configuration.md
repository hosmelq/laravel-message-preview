---
title: "Configuration"
description: "Configure auto-refresh, database rollback, the scanned directories, and the preview route."
weight: 3
---

The published `config/message-preview.php` file groups its options by what they control. Three of them can be set through environment variables, listed at the end of this page.

| Option | Default | Description |
| --- | --- | --- |
| `auto_refresh.interval` | `5` | Seconds between checks for changed files while Auto-refresh is on, at least `1`. |
| `database.rollback` | `true` | Roll back database changes made while rendering a message. |
| `discovery.paths` | `[app_path()]` | Directories scanned for messages. |
| `route.path` | `message-preview` | URL prefix of the preview. |

## Auto-refresh

`auto_refresh.interval` sets how often the preview looks for changed files while the Auto-refresh switch is on. [Previewing messages](basic-usage/previewing-messages) lists the files it watches.

## Database rollback

`database.rollback` runs each render inside a transaction on the default database connection and rolls it back afterwards. [Sample data](basic-usage/sample-data) explains what it covers and what it does not.

## Scanned directories

The package loads every class in `discovery.paths` through Composer's autoloader, so list only the directories that contain messages, such as `app_path('Mail')` and `app_path('Notifications')`. [Discovering messages](basic-usage/discovering-messages) shows which classes are listed. Messages outside these directories, such as Laravel's own notifications, can be [registered](advanced-usage/registering-messages).

## Route path

`route.path` sets the URL prefix of the preview. With `MESSAGE_PREVIEW_ROUTE_PATH=mail-preview`, the preview opens at `/mail-preview`. The preview needs no session or authentication, so its routes skip the `web` middleware group and the middleware your application adds to it. They answer only in the `local` environment, as [security](advanced-usage/security) explains.

## Environment variables

| Option | Environment variable |
| --- | --- |
| `auto_refresh.interval` | `MESSAGE_PREVIEW_AUTO_REFRESH_INTERVAL` |
| `database.rollback` | `MESSAGE_PREVIEW_DATABASE_ROLLBACK` |
| `route.path` | `MESSAGE_PREVIEW_ROUTE_PATH` |

`discovery.paths` has no environment variable. Edit the list in `config/message-preview.php`.
