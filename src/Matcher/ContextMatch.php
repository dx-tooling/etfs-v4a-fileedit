<?php

declare(strict_types=1);

namespace V4AFileEdit\Matcher;

final readonly class ContextMatch
{
    public function __construct(
        public int $newIndex,
        public int $fuzz
    ) {
    }
}
