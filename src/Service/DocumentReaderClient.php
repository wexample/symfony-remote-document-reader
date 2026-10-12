<?php

namespace Wexample\SymfonyRemoteDocumentReader\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Wexample\PhpRemote\Class\RemoteStatus;
use Wexample\PhpRemote\Interface\RemoteInterface;
use Wexample\SymfonyRemoteDocumentReader\Class\DocumentExtraction;
use Wexample\SymfonyRemoteDocumentReader\Exception\DocumentReaderException;
use Wexample\SymfonyRemoteDocumentReader\Exception\UnreadableDocumentException;

/**
 * Posts a document to a document-reader service and gets back what it carries.
 * Files from third parties are read there, in a sandbox, never in this process.
 *
 * - extract (POST /extract): a PDF's description, attachments and Factur-X, read exactly;
 * - convert (POST /convert): any document docling reads, as a docling document (pages, text, tables).
 *
 * Its health (GET /health) is checked as a remote.
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
     * @param string $pdf The PDF bytes.
     */
    public function extract(string $pdf): DocumentExtraction
    {
        return DocumentExtraction::fromArray($this->post('/extract', $pdf, 'application/pdf'));
    }

    /**
     * @param string $fileName The document's file name, extension included: docling recognises the format by it.
     * @return array The docling document.
     */
    public function convert(
        string $fileName,
        string $content
    ): array {
        return $this->post('/convert?'.http_build_query(['name' => $fileName]), $content, 'application/octet-stream')['document'];
    }

    public function isConfigured(): bool
    {
        return null !== $this->httpClient && null !== $this->baseUrl && '' !== $this->baseUrl;
    }

    private function post(
        string $path,
        string $body,
        string $contentType
    ): array {
        if (! $this->isConfigured()) {
            throw new DocumentReaderException('document-reader is not configured (wexample_symfony_remote_document_reader.url).');
        }

        $response = $this->httpClient->request('POST', $this->url($path), [
            'body' => $body,
            'headers' => ['Content-Type' => $contentType],
        ]);
        $status = $response->getStatusCode();

        if (422 === $status) {
            throw new UnreadableDocumentException('document-reader cannot read the document: '.$response->getContent(false));
        }

        if (200 !== $status) {
            throw new DocumentReaderException('document-reader refused the request (HTTP '.$status.'): '.$response->getContent(false));
        }

        return $response->toArray();
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->baseUrl, '/').$path;
    }
}
