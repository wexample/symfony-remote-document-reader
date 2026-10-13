`symfony-remote-document-reader` is the Symfony client of a document-reader service, which reads documents written by others in a sandbox, never in the application's process. `DocumentReaderClient` is declared as a `symfony-remote`, so `remote:status` checks the service.

`read()` posts any document docling reads (PDF, DOCX, XLSX, CSV, HTML, images…) and returns a `DocumentReading` in the shared norm, the same `document`, `attachments` and `output` that pdf-factory renders from:

- the content of the pages, read by docling;
- for a PDF, what the file says about itself, its attachments with their bytes, and its Factur-X.

The package knows no document of its own: what an invoice's XML says is read by the package that owns invoices.
