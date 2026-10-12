<?php

namespace Wexample\SymfonyRemoteDocumentReader\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Wexample\PhpRemote\Enum\RemoteState;
use Wexample\SymfonyRemoteDocumentReader\Exception\DocumentReaderException;
use Wexample\SymfonyRemoteDocumentReader\Exception\UnreadableDocumentException;
use Wexample\SymfonyRemoteDocumentReader\Service\DocumentReaderClient;

class DocumentReaderClientTest extends TestCase
{
    private const string XML = '<rsm:CrossIndustryInvoice/>';

    private function extraction(): array
    {
        return [
            'ok' => true,
            'information' => ['title' => 'Invoice F-001', 'created' => '2026-10-12T09:30:00+02:00'],
            'language' => 'fr',
            'pdfa' => ['part' => 3, 'conformance' => 'B'],
            'attachments' => [
                ['name' => 'invoice.json', 'mediaType' => 'application/json', 'description' => '', 'relationship' => 'Data', 'size' => 2, 'content' => base64_encode('{}')],
                ['name' => 'factur-x.xml', 'mediaType' => 'text/xml', 'description' => '', 'relationship' => 'Alternative', 'size' => strlen(self::XML), 'content' => base64_encode(self::XML)],
            ],
            'facturx' => ['fileName' => 'factur-x.xml', 'documentType' => 'INVOICE', 'version' => '1.0', 'conformance' => 'EN 16931', 'declared' => true],
        ];
    }

    public function testThePdfIsPostedAsItIsAndItsContentComesBack(): void
    {
        $sent = null;
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$sent) {
            $sent = ['method' => $method, 'url' => $url, 'body' => $options['body'], 'headers' => $options['headers']];

            return new MockResponse(json_encode($this->extraction()));
        });

        $extraction = (new DocumentReaderClient($http, 'http://document_reader:8000/'))->extract('%PDF-1.7 …');

        $this->assertSame('POST', $sent['method']);
        $this->assertSame('http://document_reader:8000/extract', $sent['url']);
        $this->assertSame('%PDF-1.7 …', $sent['body']);
        $this->assertContains('Content-Type: application/pdf', $sent['headers']);

        $this->assertSame('Invoice F-001', $extraction->information['title']);
        $this->assertSame(3, $extraction->pdfa['part']);
        $this->assertSame('Data', $extraction->getAttachment('invoice.json')->relationship);
        $this->assertSame('{}', $extraction->getAttachment('invoice.json')->content);
        $this->assertSame(self::XML, $extraction->getFacturXXml());
        $this->assertNull($extraction->getAttachment('missing.txt'));
    }

    public function testAPdfWithoutFacturXHasNoXml(): void
    {
        $data = [...$this->extraction(), 'attachments' => [], 'facturx' => null, 'pdfa' => null];
        $http = new MockHttpClient(new MockResponse(json_encode($data)));

        $this->assertNull((new DocumentReaderClient($http, 'http://reader'))->extract('%PDF')->getFacturXXml());
    }

    public function testConvertNamesTheFileAndReturnsTheDoclingDocument(): void
    {
        $sent = null;
        $http = new MockHttpClient(function (string $method, string $url) use (&$sent) {
            $sent = $url;

            return new MockResponse(json_encode(['ok' => true, 'engine' => 'docling', 'document' => ['schema_name' => 'DoclingDocument']]));
        });

        $document = (new DocumentReaderClient($http, 'http://reader'))->convert('relevé octobre.pdf', '%PDF');

        $this->assertSame('http://reader/convert?name=relev%C3%A9+octobre.pdf', $sent);
        $this->assertSame(['schema_name' => 'DoclingDocument'], $document);
    }

    public function testAnUnreadableFileIsTheCallersNotTheServices(): void
    {
        $http = new MockHttpClient(new MockResponse('{"ok":false,"error":"Not a PDF"}', ['http_code' => 422]));

        $this->expectException(UnreadableDocumentException::class);
        $this->expectExceptionMessage('Not a PDF');

        (new DocumentReaderClient($http, 'http://reader'))->extract('hello');
    }

    public function testDoclingAwayIsAServiceFailure(): void
    {
        $http = new MockHttpClient(new MockResponse('{"ok":false,"error":"docling unreachable"}', ['http_code' => 502]));

        try {
            (new DocumentReaderClient($http, 'http://reader'))->convert('a.pdf', '%PDF');
            $this->fail('No exception.');
        } catch (DocumentReaderException $exception) {
            $this->assertNotInstanceOf(UnreadableDocumentException::class, $exception);
            $this->assertStringContainsString('docling unreachable', $exception->getMessage());
        }
    }

    public function testWithoutUrlTheRemoteIsUnconfigured(): void
    {
        $client = new DocumentReaderClient(new MockHttpClient(), null);

        $this->assertSame(RemoteState::Unconfigured, $client->checkStatus()->state);

        $this->expectException(DocumentReaderException::class);
        $client->extract('%PDF');
    }
}
