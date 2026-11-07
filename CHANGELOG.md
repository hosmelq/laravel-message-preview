# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- Check compatibility on the server with `hosmelq/caniemail` instead of the `caniemail` npm package in the browser.
- Use the last version caniemail.com lists for each client, so Outlook for macOS shows the support caniemail.com shows.
- Show clients caniemail has not tested as no data instead of partly supported.
- Stop reporting `background`, `border`, and `list-style` for their longhand properties, or chaining for descendant selectors, and report grouping and attribute selectors, `display:none`, `prefers-color-scheme`, `rgb()`, `rgba()`, base64 images, and the HTML5 doctype.
- List each platform once in a client's support details.

## v1.0.1 - 2026-11-04

- Serve the preview page from a package controller instead of Laravel's `ViewController`.

## v1.0.0 - 2026-11-04

- Initial release.
