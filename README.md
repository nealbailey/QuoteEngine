# QuoteEngine

[![PHP](https://img.shields.io/badge/PHP-application-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![JavaScript](https://img.shields.io/badge/JavaScript-vanilla-F7DF1E?logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)

A small quote collection web app. Browse random quotes, search and sort the collection, and add new entries. The PHP service stores quotes in an XML file; no database is required.

![QuoteEngine app screenshot](screenshot.png)

## Requirements

- PHP with the `dom` and `json` extensions enabled.
- A web server configured to execute PHP. PHP's built-in development server is enough for local use.
- Write access to `data/quotes.xml` for the account running PHP if you want to add quotes.

The frontend uses plain HTML, CSS, and JavaScript. There are no Composer or npm packages to install.

## Run locally

From the project directory, start PHP's built-in server:

```sh
php -S 127.0.0.1:8000 -t .
```

Open <http://127.0.0.1:8000/> in a browser. Keep the server running in the terminal; PHP errors and request logs are printed there.

To confirm the required extensions are enabled:

```sh
php -m
```

Check that `dom` and `json` appear in the output. If quote creation fails, make sure the PHP/web-server user can write to `data/quotes.xml` and its containing directory.

## Quote service

`getquotes.php` returns quotes. By default it responds with plain text and one quote. Add `format=json` for a JSON object containing `quotes`, `authors`, `page`, `pageSize`, `total`, and `totalPages`. Each quote has `Value`, `Author`, and `Added` fields.

Supported query parameters:

| Parameter | Description |
| --- | --- |
| `format` | Set to `json` for JSON; otherwise the response is plain text. |
| `author` | Filter by an author name (case-insensitive partial match). |
| `search` | Search quote text (JSON mode). |
| `sortby` | Sort by `author`, `date`, or `random`. |
| `sortorder` | `asc` or `desc` for author/date sorting. |
| `page` | 1-based page number (JSON mode). |
| `limit` | Results per request, from 1 to 100; defaults to 1. |

Examples:

```sh
# Random quote as JSON
curl 'http://127.0.0.1:8000/getquotes.php?format=json&sortby=random&limit=1'

# Search and page through quotes
curl 'http://127.0.0.1:8000/getquotes.php?format=json&search=world&page=1&limit=10&sortby=date&sortorder=desc'
```

`submit.php` accepts a POST with `author` and `quote` form fields. It returns JSON; successful creation responds with HTTP 201. Missing fields return 422, duplicate quotes return 409, and requests that are not POST return 405.

```sh
curl -i -X POST \
	-F 'author=Example Author' \
	-F 'quote=A memorable line.' \
	http://127.0.0.1:8000/submit.php
```

Quotes are stored in [`data/quotes.xml`](data/quotes.xml). Back up this file before making manual edits.

## Debugging

- With the PHP Debug VS Code extension and Xdebug installed, select **Debug QuoteEngine (PHP server)** in Run and Debug and press F5. It starts the local server at <http://127.0.0.1:8000/> and opens the app. Set a breakpoint in `getquotes.php` or `submit.php`, then make the corresponding request in the browser. Stop the debug session to stop the server. Do not run a separate server on port 8000 at the same time.
- Watch the terminal running `php -S` for PHP errors and request details.
- Run PHP's syntax checker on a file, for example `php -l getquotes.php`. Repeat for the PHP files you changed.
- Use `curl -i` on an endpoint to inspect its HTTP status, headers, and response body.
- If the browser UI shows a request error, check the browser developer tools' Network and Console panels, then request the same endpoint directly with `curl`.
- If reads fail, verify the PHP process can read `data/quotes.xml`; if submissions fail, verify it can also write to that file and directory.
- For local-only troubleshooting, errors can be shown in the development server terminal. Do not enable error display on a public production server; use the server's PHP error log instead.

## License

This project is distributed under the GNU General Public License v3.0. See [LICENSE](LICENSE).
