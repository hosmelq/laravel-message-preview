---
title: "Security"
description: "Who can open the preview and what the link check requests."
weight: 3
---

The preview runs your application's code to render messages, so it is meant for your own machine. These are the limits it applies to who can reach it and to the requests it sends.

## Local environment only

The preview's routes answer only when `APP_ENV` is `local`. In every other environment they return `404`, so deploying the package does not expose the preview.

## Middleware

The preview needs no session or authentication, so its routes skip the `web` middleware group.

## What the Links check requests

The [Links check](../basic-usage/checks) requests the absolute HTTP and HTTPS URLs in a message's links and images. URLs on the `APP_URL` host or the host the preview is open on are never requested. The check matches them against the application's GET routes and the files in `public` without running a route's middleware or controller, so it never uses one of your application's signed verification, password-reset, or unsubscribe links.

Every other URL gets a `GET` request, including tracking and unsubscribe links on another host. The request stops reading once the headers arrive, so no body is downloaded. It follows up to five redirects, and a redirect to one of those hosts is matched against the routes instead of requested. Each request gets five seconds.
