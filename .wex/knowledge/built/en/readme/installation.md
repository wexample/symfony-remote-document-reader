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
