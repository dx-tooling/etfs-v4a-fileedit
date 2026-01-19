<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

final class ParserState
{
    /** @var list<string> */
    public array $lines;

    public int $index = 0;

    public int $fuzz = 0;

    /**
     * @param list<string> $lines
     */
    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }
}
