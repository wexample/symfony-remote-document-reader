<?php

namespace Wexample\SymfonyRemoteDocumentReader\Class;

/**
 * A file embedded in the document read, with its bytes.
 */
class ReadAttachment
{
    /**
     * @param string|null $relationship `Data`, `Source`, `Alternative`, `Supplement`, `Unspecified`; null when the file says nothing.
     * @param string      $content      The raw bytes.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $mediaType,
        public readonly ?string $description,
        public readonly ?string $relationship,
        public readonly string $content,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['mediaType'],
            $data['description'] ?? null,
            $data['relationship'] ?? null,
            (string) base64_decode($data['content'], true),
        );
    }
}
