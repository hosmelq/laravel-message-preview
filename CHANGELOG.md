# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.1.0 - 2026-11-07

- Check compatibility on the server with `hosmelq/caniemail` instead of the `caniemail` npm package.
- Use the last version caniemail.com lists for each client, which fixes the support shown for Outlook for macOS.
- Show clients caniemail has not tested as no data instead of partly supported.
- Detect more features, such as `display:none` and `prefers-color-scheme`, and stop reporting `background`, `border`, and `list-style` for their longhand properties.

## v1.0.1 - 2026-11-04

- Serve the preview page from a package controller instead of Laravel's `ViewController`.

## v1.0.0 - 2026-11-04

- Initial release.
