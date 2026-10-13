# symfony-remote-document-reader

Version: 2.0.1

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

## Table of Contents

- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- wexample/symfony-helpers: >=16.0.0
- wexample/symfony-remote: >=2.0.0
- symfony/http-client: >=7.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
