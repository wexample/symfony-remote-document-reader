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

    private const array UNMAPPED = ['file' => [], 'attachments' => [], 'content' => []];

    private function client(array $answer, ?array &$sent = null): DocumentReaderClient
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($answer, &$sent) {
            $sent = ['method' => $method, 'url' => $url, 'body' => $options['body']];

            return new MockResponse(json_encode(['ok' => true, ...$answer]));
        });

        return new DocumentReaderClient($http, 'http://document_reader:8000/');
    }

    private function cell(string $type, string $text): array
    {
        return ['type' => $type, 'content' => '' === $text ? [] : [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]];
    }

    public function testAFacturXIsReadWithoutItsPages(): void
    {
        $client = $this->client([
            'document' => ['type' => 'doc', 'title' => 'Invoice F-001', 'language' => 'fr'],
            'attachments' => [['name' => 'invoice.json', 'mediaType' => 'application/json', 'relationship' => 'Data', 'content' => base64_encode('{}')]],
            'output' => ['variant' => 'pdf/a-3b', 'facturx' => ['conformance' => 'EN 16931', 'relationship' => 'Alternative', 'xml' => base64_encode(self::XML)]],
            'unmapped' => self::UNMAPPED,
        ], $sent);

        $reading = $client->read('F-001.pdf', '%PDF-1.7 …', pages: false);

        $this->assertSame('POST', $sent['method']);
        $this->assertSame('http://document_reader:8000/read?name=F-001.pdf&content=false&raw=false', $sent['url']);
        $this->assertSame('%PDF-1.7 …', $sent['body']);

        $this->assertSame('Invoice F-001', $reading->document['title']);
        $this->assertSame('pdf/a-3b', $reading->output['variant']);
        $this->assertSame('{}', $reading->getAttachment('invoice.json')->content);
        $this->assertNull($reading->getAttachment('invoice.json')->description);
        $this->assertTrue($reading->isFacturXDeclared());
        $this->assertSame(self::XML, $reading->getFacturXXml());
    }

    public function testAnUndeclaredFacturXStillGivesItsXml(): void
    {
        $reading = $this->client([
            'document' => ['type' => 'doc'],
            'attachments' => [['name' => 'factur-x.xml', 'mediaType' => 'text/xml', 'content' => base64_encode(self::XML)]],
            'unmapped' => [...self::UNMAPPED, 'file' => ['facturx' => ['fileName' => 'factur-x.xml', 'declared' => false]]],
        ])->read('a.pdf', '%PDF');

        $this->assertFalse($reading->isFacturXDeclared());
        $this->assertSame(self::XML, $reading->getFacturXXml());
    }

    public function testAPlainPdfHasNoXml(): void
    {
        $reading = $this->client(['document' => ['type' => 'doc'], 'unmapped' => self::UNMAPPED])->read('a.pdf', '%PDF');

        $this->assertNull($reading->getFacturXXml());
        $this->assertSame([], $reading->attachments);
    }

    public function testTablesComeBackAsRowsOfTexts(): void
    {
        $reading = $this->client([
            'document' => ['type' => 'doc', 'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Before']]],
                ['type' => 'table', 'content' => [
                    ['type' => 'tableRow', 'content' => [$this->cell('tableHeader', 'Title'), $this->cell('tableHeader', 'Price')]],
                    ['type' => 'tableRow', 'content' => [
                        ['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Design'], ['type' => 'text', 'text' => ' work', 'marks' => [['type' => 'bold']]]]]]],
                        $this->cell('tableCell', '1 200,50'),
                    ]],
                    ['type' => 'tableRow', 'content' => [$this->cell('tableCell', 'Print'), $this->cell('tableCell', '')]],
                ]],
            ]],
            'unmapped' => self::UNMAPPED,
        ])->read('lines.xlsx', 'PK…');

        $this->assertSame([[['Title', 'Price'], ['Design work', '1 200,50'], ['Print', '']]], $reading->getTables());
    }

    public function testAnUnreadableFileIsTheCallersNotTheServices(): void
    {
        $http = new MockHttpClient(new MockResponse('{"ok":false,"error":"Not a PDF"}', ['http_code' => 422]));

        $this->expectException(UnreadableDocumentException::class);
        $this->expectExceptionMessage('Not a PDF');

        (new DocumentReaderClient($http, 'http://reader'))->read('a.pdf', 'hello');
    }

    public function testDoclingAwayIsAServiceFailure(): void
    {
        $http = new MockHttpClient(new MockResponse('{"ok":false,"error":"docling unreachable"}', ['http_code' => 502]));

        try {
            (new DocumentReaderClient($http, 'http://reader'))->read('a.docx', 'PK');
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
        $client->read('a.pdf', '%PDF');
    }
}
