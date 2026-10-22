# symfony-remote-document-reader

Version: 2.0.4

Client of a document-reader service: what a PDF carries besides its pages (attachments, Factur-X), and documents converted by docling, declared as a symfony-remote

## Table of Contents

- [Installation](#installation)
- [Introduction](#introduction)

## Installation

```php
// config/bundles.php
Wexample\SymfonyRemote\WexampleSymfonyRemoteBundle::class => ['all' => true],
Wexample\SymfonyRemoteDocumentReader\WexampleSymfonyRemoteDocumentReaderBundle::class => ['all' => true],
```

```yaml
# config/packages/wexample_symfony_remote_document_reader.yaml
wexample_symfony_remote_document_reader:
  url: '%env(default::DOCUMENT_READER_URL)%'   # e.g. http://document_reader:8000
```

Without a URL the remote reports itself unconfigured and every call throws.

`symfony-remote-document-reader` is the Symfony client of a document-reader service, which reads documents written by others in a sandbox, never in the application's process. `DocumentReaderClient` is declared as a `symfony-remote`, so `remote:status` checks the service.

`read()` posts any document docling reads (PDF, DOCX, XLSX, CSV, HTML, images…) and returns a `DocumentReading` in the shared norm, the same `document`, `attachments` and `output` that pdf-factory renders from:

- the content of the pages, read by docling;
- for a PDF, what the file says about itself, its attachments with their bytes, and its Factur-X.

The package knows no document of its own: what an invoice's XML says is read by the package that owns invoices.
