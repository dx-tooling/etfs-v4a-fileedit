<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final class ParserState
{
    /** @var array<string> */
    public array $lines;

    public int $index = 0;

    public int $fuzz = 0;

    /**
     * @param array<string> $lines
     */
    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }
}
