<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final readonly class ParsedUpdateDiff
{
    /**
     * @param list<Chunk> $chunks
     */
    public function __construct(
        /** @var list<Chunk> */
        public array $chunks,
        public int   $fuzz
    ) {
    }
}
