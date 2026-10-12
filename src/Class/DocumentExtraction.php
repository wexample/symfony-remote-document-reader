<?php

namespace Wexample\SymfonyRemoteDocumentReader\Class;

/**
 * What a PDF carries besides its pages, read exactly by the service: what the
 * file does not say is null or absent, never guessed.
 */
class DocumentExtraction
{
    /**
     * @param array<string, string>                               $information Title, author, subject, keywords, creator, producer, created, modified (RFC 3339), as far as the file fills them.
     * @param array{part: int, conformance: ?string}|null         $pdfa        The PDF/A level the file claims: a claim, not a verdict.
     * @param list<ExtractedAttachment>                           $attachments
     * @param array{fileName: string, documentType: ?string, version: ?string, conformance: ?string, declared: bool}|null $facturx
     *                                                                         The structured invoice the file carries (Factur-X, ZUGFeRD 2); `declared` is false when its metadata does not announce it.
     */
    public function __construct(
        public readonly array $information,
        public readonly ?string $language,
        public readonly ?array $pdfa,
        public readonly array $attachments,
        public readonly ?array $facturx,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['information'],
            $data['language'],
            $data['pdfa'],
            array_map(ExtractedAttachment::fromArray(...), $data['attachments']),
            $data['facturx'],
        );
    }

    public function getAttachment(string $name): ?ExtractedAttachment
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment->name === $name) {
                return $attachment;
            }
        }

        return null;
    }

    /**
     * The XML of the structured invoice, CII for Factur-X; null when the PDF carries none.
     */
    public function getFacturXXml(): ?string
    {
        return $this->facturx ? $this->getAttachment($this->facturx['fileName'])?->content : null;
    }
}
