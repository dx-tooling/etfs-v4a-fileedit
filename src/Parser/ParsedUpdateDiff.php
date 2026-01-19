<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final readonly class ParsedUpdateDiff
{
    /**
     * @param array<Chunk> $chunks
     */
    public function __construct(
        /** @var array<Chunk> */
        public array $chunks,
        public int $fuzz
    ) {
    }
}
