<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final readonly class Chunk
{
    public function __construct(
        public int $origIndex,
        /** @var array<string> */
        public array $delLines,
        /** @var array<string> */
        public array $insLines
    ) {
    }
}
