# Laravel Message Preview

Laravel Message Preview renders the mailables and mail notifications of your Laravel application in
the browser, with their subject, addresses, attachments, and HTML and plain-text bodies.

```bash
composer require hosmelq/laravel-message-preview

php artisan message-preview:install
```

Read the [full documentation](https://hosmelq.com/docs/laravel-message-preview).

## Development

Run the test suite with:

```bash
composer test
```

The compiled frontend assets in `resources/dist` are committed. Rebuild them with `nub run build`
after changing `resources/js`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## Contributing

Pull requests are welcome. Please run the test suite before submitting changes.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
