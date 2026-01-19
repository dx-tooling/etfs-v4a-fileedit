<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final readonly class ReadSectionResult
{
    /**
     * @param array<string> $nextContext
     * @param array<Chunk> $sectionChunks
     */
    public function __construct(
        /** @var array<string> */
        public array $nextContext,
        /** @var array<Chunk> */
        public array $sectionChunks,
        public int $endIndex,
        public bool $eof
    ) {
    }
}
