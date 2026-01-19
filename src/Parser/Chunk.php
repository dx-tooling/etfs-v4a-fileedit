<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final readonly class Chunk
{
    public function __construct(
        public int   $origIndex,
        /** @var list<string> */
        public array $delLines,
        /** @var list<string> */
        public array $insLines
    ) {
    }
}
