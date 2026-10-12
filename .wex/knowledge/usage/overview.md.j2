```php
// A received invoice: only what the PDF's file says, without docling (seconds rather than minutes).
$reading = $documentReaderClient->read('F-001.pdf', $pdf, pages: false);

$reading->isFacturXDeclared();              // declared as Factur-X expects
$xml = $reading->getFacturXXml();           // the CII, declared or not, or null
$json = $reading->getAttachment('invoice.json')?->content;

// A spreadsheet or a scanned statement: its pages, read by docling.
$reading = $documentReaderClient->read('lines.xlsx', $content);
$rows = $reading->getTables()[0] ?? [];     // rows of cell texts
$reading->document;                         // the whole `doc` node
```

The file name keeps its extension: its format is recognised by it.

What the norm has no place for is set aside under `unmapped`, with the reason. A Factur-X declaration that does not fit, for example, lands in `unmapped.file.facturx`, and its XML stays among the attachments, where `getFacturXXml()` still finds it.

Two failures, kept apart. `UnreadableDocumentException` means the service is up but the file cannot be read (not a PDF, a broken one); retrying is pointless. Its parent `DocumentReaderException` covers everything else: no URL configured, a refused request, docling unreachable.

The contract belongs to the service (`SERVICES/local/document-reader`, `read_response.schema.json`); the norm itself lives in `SERVICES/local/json-schema`, under `document/`.
