<?php

namespace Wexample\SymfonyRemoteDocumentReader\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Wexample\PhpRemote\Class\RemoteStatus;
use Wexample\PhpRemote\Interface\RemoteInterface;
use Wexample\SymfonyRemoteDocumentReader\Class\DocumentReading;
use Wexample\SymfonyRemoteDocumentReader\Exception\DocumentReaderException;
use Wexample\SymfonyRemoteDocumentReader\Exception\UnreadableDocumentException;

/**
 * Posts a document to a document-reader service (POST /read) and gets it back in
 * the shared norm: its pages read by docling, and for a PDF what the file says of
 * itself, its attachments and its Factur-X. Files from third parties are read
 * there, in a sandbox, never in this process. Its health (GET /health) is checked
 * as a remote.
 */
class DocumentReaderClient implements RemoteInterface
{
    public function __construct(
        private readonly ?HttpClientInterface $httpClient = null,
        private readonly ?string $baseUrl = null,
    ) {
    }

    public function getKey(): string
    {
        return 'document_reader';
    }

    public function getLabel(): string
    {
        return 'Document reader';
    }

    public function checkStatus(): RemoteStatus
    {
        if (! $this->isConfigured()) {
            return RemoteStatus::unconfigured('No document-reader URL.');
        }

        $response = $this->httpClient->request('GET', $this->url('/health'));

        return 200 === $response->getStatusCode() ? RemoteStatus::up() : RemoteStatus::down('HTTP '.$response->getStatusCode());
    }

    /**
     * @param string $fileName The document's file name, extension included: its format is recognised by it.
     * @param bool   $pages    False to skip docling: only what a PDF's file says (metadata, attachments, Factur-X), in seconds rather than minutes.
     * @param bool   $raw      True to get docling's own answer too, under `engine`.
     */
    public function read(
        string $fileName,
        string $content,
        bool $pages = true,
        bool $raw = false
    ): DocumentReading {
        if (! $this->isConfigured()) {
            throw new DocumentReaderException('document-reader is not configured (wexample_symfony_remote_document_reader.url).');
        }

        $response = $this->httpClient->request('POST', $this->url('/read?'.http_build_query([
            'name' => $fileName,
            'content' => $pages ? 'true' : 'false',
            'raw' => $raw ? 'true' : 'false',
        ])), [
            'body' => $content,
            'headers' => ['Content-Type' => 'application/octet-stream'],
        ]);
        $status = $response->getStatusCode();

        if (422 === $status) {
            throw new UnreadableDocumentException('document-reader cannot read the document: '.$response->getContent(false));
        }

        if (200 !== $status) {
            throw new DocumentReaderException('document-reader refused the request (HTTP '.$status.'): '.$response->getContent(false));
        }

        return DocumentReading::fromArray($response->toArray());
    }

    public function isConfigured(): bool
    {
        return null !== $this->httpClient && null !== $this->baseUrl && '' !== $this->baseUrl;
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->baseUrl, '/').$path;
    }
}
