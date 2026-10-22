# Frequency-list Excel export deployment

Frequency-list exports are included in the normal application deployment. No
frontend build or committed `vendor/` directory is required.

```sh
git pull
composer install --no-dev --prefer-dist --optimize-autoloader
```

PhpSpreadsheet 5.10 supports the server's PHP 8.3 runtime and local PHP 8.4.
The existing `gd`, `zip`, `xml`, and `mbstring` extensions must remain enabled.

Exports use the configured eXist frequency API and fetch 500 records per
request. The workbook package and metadata are created with PhpSpreadsheet;
the data worksheet is streamed through temporary files so that the complete
word-form list is not retained as spreadsheet objects in PHP memory.

By default, temporary files use PHP's system temporary directory. Production
may set `MEANMA_EXPORT_TMP_DIR` to an absolute, writable directory outside the
web root. The web-server user must be able to create and remove files there,
and the filesystem must have enough free space for the uncompressed worksheet
during generation. Temporary worksheet and download files are removed after
success and failure.

One worksheet can contain at most 1,048,576 rows. Allowing for the header, the
export rejects more than 1,048,575 matching results with a clear error rather
than truncating data. The current corpus estimates (40,134 lemmas and 402,106
word forms) are within this limit.

After deployment, manually verify that an authenticated user can export both
frequency types with no filters and with minimum, maximum, POS, and order
filters. Open each file in Excel or LibreOffice and confirm the filename,
Gaelic text, numeric frequencies, complete row count, and Export details sheet.
Also confirm that an anonymous export request redirects to sign-in.
