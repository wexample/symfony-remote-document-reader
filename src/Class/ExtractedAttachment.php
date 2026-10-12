<?php

namespace Wexample\SymfonyRemoteDocumentReader\Class;

/**
 * A file embedded in a PDF, with its bytes.
 */
class ExtractedAttachment
{
    /**
     * @param string|null $relationship `Data`, `Source`, `Alternative`…; null when the file says nothing (before PDF/A-3).
     * @param string      $content      The raw bytes.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $mediaType,
        public readonly string $description,
        public readonly ?string $relationship,
        public readonly string $content,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['mediaType'],
            $data['description'],
            $data['relationship'],
            (string) base64_decode($data['content'], true),
        );
    }
}
