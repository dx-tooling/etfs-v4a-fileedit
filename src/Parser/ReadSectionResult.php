<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final readonly class ReadSectionResult
{
    /**
     * @param list<string> $nextContext
     * @param list<Chunk>  $sectionChunks
     */
    public function __construct(
        /** @var list<string> */
        public array $nextContext,
        /** @var list<Chunk> */
        public array $sectionChunks,
        public int   $endIndex,
        public bool  $eof
    ) {
    }
}
