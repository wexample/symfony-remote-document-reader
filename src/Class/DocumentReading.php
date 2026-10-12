<?php

namespace Wexample\SymfonyRemoteDocumentReader\Class;

/**
 * A document as the service read it, in the shared norm (`SERVICES/local/json-schema`,
 * `document/`): the same `document`, `attachments` and `output` pdf-factory renders
 * from. What the norm has no place for is under `unmapped`, with the reason.
 */
class DocumentReading
{
    /**
     * @param array                $document    A `doc` node: its content from the pages, its title, author, language… from the file.
     * @param list<ReadAttachment> $attachments The embedded files the norm carries; a declared Factur-X XML is not among them.
     * @param array                $output      `variant` (the PDF/A level claimed) and `facturx` (`conformance`, `relationship`, `xml` in base64).
     * @param array                $unmapped    `file`, `attachments`, `content`: what was read and does not fit the norm.
     * @param array|null           $engine      Docling's own answer, when asked for.
     */
    public function __construct(
        public readonly array $document,
        public readonly array $attachments,
        public readonly array $output,
        public readonly array $unmapped,
        public readonly ?array $engine = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['document'],
            array_map(ReadAttachment::fromArray(...), $data['attachments'] ?? []),
            $data['output'] ?? [],
            $data['unmapped'],
            $data['engine']['document'] ?? null,
        );
    }

    public function getAttachment(string $name): ?ReadAttachment
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment->name === $name) {
                return $attachment;
            }
        }

        return null;
    }

    /**
     * Whether the PDF declares its structured invoice as Factur-X expects.
     */
    public function isFacturXDeclared(): bool
    {
        return isset($this->output['facturx']);
    }

    /**
     * The structured invoice's XML (CII for Factur-X), declared or not: a declaration
     * that does not fit the norm leaves its XML among the attachments, still readable.
     */
    public function getFacturXXml(): ?string
    {
        if ($this->isFacturXDeclared()) {
            return (string) base64_decode($this->output['facturx']['xml'], true);
        }

        $found = $this->unmapped['file']['facturx']['fileName'] ?? null;

        return $found ? $this->getAttachment($found)?->content : null;
    }

    /**
     * Every table of the document as rows of cell texts, in reading order. A cell
     * spanning several columns stays one cell.
     *
     * @return list<list<list<string>>>
     */
    public function getTables(): array
    {
        $tables = [];
        $walk = function (array $nodes) use (&$walk, &$tables): void {
            foreach ($nodes as $node) {
                if ('table' === ($node['type'] ?? null)) {
                    $tables[] = array_map(
                        fn (array $row) => array_map(fn (array $cell) => $this->text($cell), $row['content'] ?? []),
                        $node['content'] ?? []
                    );
                } elseif (isset($node['content'])) {
                    $walk($node['content']);
                }
            }
        };
        $walk($this->document['content'] ?? []);

        return $tables;
    }

    /**
     * A node's text: inline pieces joined as they are, blocks one per line.
     */
    private function text(array $node): string
    {
        if ('text' === ($node['type'] ?? null)) {
            return $node['text'];
        }

        $children = array_map(fn (array $child) => $this->text($child), $node['content'] ?? []);
        $inline = in_array($node['type'] ?? null, ['paragraph', 'heading'], true);

        return trim(implode($inline ? '' : "\n", $children));
    }
}
