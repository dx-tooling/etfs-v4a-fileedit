<?php

declare(strict_types=1);

namespace V4AFileEdit\Parser;

use V4AFileEdit\Exception\InvalidContextException;
use V4AFileEdit\Exception\InvalidDiffException;
use V4AFileEdit\Matcher\ContextMatcher;

final class DiffParser
{
    private const END_PATCH           = '*** End Patch';
    private const END_FILE            = '*** End of File';
    private const SECTION_TERMINATORS = [
        self::END_PATCH,
        '*** Update File:',
        '*** Delete File:',
        '*** Add File:',
    ];
    private const END_SECTION_MARKERS = [
        self::END_PATCH,
        '*** Update File:',
        '*** Delete File:',
        '*** Add File:',
        self::END_FILE,
    ];

    public function __construct(
        private readonly ContextMatcher $contextMatcher = new ContextMatcher()
    ) {
    }

    /**
     * @param array<string> $diffLines
     */
    public function parseCreateDiff(array $diffLines): string
    {
        $parser = new ParserState([...$diffLines, self::END_PATCH]);
        $output = [];

        while (!$this->isDone($parser, self::SECTION_TERMINATORS)) {
            if ($parser->index >= count($parser->lines)) {
                break;
            }

            $line = $parser->lines[$parser->index];
            ++$parser->index;

            if (!str_starts_with($line, '+')) {
                throw new InvalidDiffException("Invalid Add File Line: {$line}");
            }

            $output[] = substr($line, 1);
        }

        return implode("\n", $output);
    }

    /**
     * @param array<string> $lines
     */
    public function parseUpdateDiff(array $lines, string $input): ParsedUpdateDiff
    {
        $parser     = new ParserState([...$lines, self::END_PATCH]);
        $inputLines = explode("\n", $input);
        $chunks     = [];
        $cursor     = 0;

        while (!$this->isDone($parser, self::END_SECTION_MARKERS)) {
            $anchor        = $this->readStr($parser, '@@ ');
            $hasBareAnchor = $anchor === '' && $parser->index < count($parser->lines) && $parser->lines[$parser->index] === '@@';
            if ($hasBareAnchor) {
                ++$parser->index;
            }

            if (!($anchor || $hasBareAnchor || $cursor === 0)) {
                $currentLine = $parser->index < count($parser->lines) ? $parser->lines[$parser->index] : '';
                throw new InvalidDiffException("Invalid Line:\n{$currentLine}");
            }

            if (trim($anchor) !== '') {
                $cursor = $this->advanceCursorToAnchor($anchor, $inputLines, $cursor, $parser);
            }

            $section    = $this->readSection($parser->lines, $parser->index);
            $findResult = $this->contextMatcher->findContext($inputLines, $section->nextContext, $cursor, $section->eof);

            if ($findResult->newIndex === -1) {
                $ctxText = implode("\n", $section->nextContext);
                if ($section->eof) {
                    throw new InvalidContextException("Invalid EOF Context {$cursor}:\n{$ctxText}");
                }
                throw new InvalidContextException("Invalid Context {$cursor}:\n{$ctxText}");
            }

            $cursor = $findResult->newIndex + count($section->nextContext);
            $parser->fuzz += $findResult->fuzz;
            $parser->index = $section->endIndex;

            foreach ($section->sectionChunks as $ch) {
                $chunks[] = new Chunk(
                    $ch->origIndex + $findResult->newIndex,
                    [...$ch->delLines],
                    [...$ch->insLines]
                );
            }
        }

        return new ParsedUpdateDiff($chunks, $parser->fuzz);
    }

    /**
     * @param array<string> $prefixes
     */
    private function isDone(ParserState $state, array $prefixes): bool
    {
        if ($state->index >= count($state->lines)) {
            return true;
        }

        foreach ($prefixes as $prefix) {
            if (str_starts_with($state->lines[$state->index], $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function readStr(ParserState $state, string $prefix): string
    {
        if ($state->index >= count($state->lines)) {
            return '';
        }

        $current = $state->lines[$state->index];
        if (str_starts_with($current, $prefix)) {
            ++$state->index;

            return substr($current, strlen($prefix));
        }

        return '';
    }

    /**
     * @param array<string> $inputLines
     */
    private function advanceCursorToAnchor(string $anchor, array $inputLines, int $cursor, ParserState $parser): int
    {
        $found = false;

        // Check if anchor exists before cursor
        $foundBefore = false;
        for ($i = 0; $i < $cursor; ++$i) {
            if ($inputLines[$i] === $anchor) {
                $foundBefore = true;
                break;
            }
        }

        if (!$foundBefore) {
            // Search forward from cursor
            for ($i = $cursor; $i < count($inputLines); ++$i) {
                if ($inputLines[$i] === $anchor) {
                    $cursor = $i;
                    $found  = true;
                    break;
                }
            }
        }

        if (!$found && !$foundBefore) {
            // Try with trimmed comparison
            $foundBeforeTrimmed = false;
            for ($i = 0; $i < $cursor; ++$i) {
                if (trim($inputLines[$i]) === trim($anchor)) {
                    $foundBeforeTrimmed = true;
                    break;
                }
            }

            if (!$foundBeforeTrimmed) {
                for ($i = $cursor; $i < count($inputLines); ++$i) {
                    if (trim($inputLines[$i]) === trim($anchor)) {
                        $cursor = $i;
                        ++$parser->fuzz;
                        $found = true;
                        break;
                    }
                }
            }
        }

        return $cursor;
    }

    /**
     * @param array<string> $lines
     */
    private function readSection(array $lines, int $startIndex): ReadSectionResult
    {
        $context       = [];
        $delLines      = [];
        $insLines      = [];
        $sectionChunks = [];
        $mode          = 'keep';
        $index         = $startIndex;
        $origIndex     = $index;

        while ($index < count($lines)) {
            $raw = $lines[$index];
            if (
                str_starts_with($raw, '@@')
                || str_starts_with($raw, self::END_PATCH)
                || str_starts_with($raw, '*** Update File:')
                || str_starts_with($raw, '*** Delete File:')
                || str_starts_with($raw, '*** Add File:')
                || str_starts_with($raw, self::END_FILE)
            ) {
                break;
            }

            if ($raw === '***') {
                break;
            }

            if (str_starts_with($raw, '***')) {
                throw new InvalidDiffException("Invalid Line: {$raw}");
            }

            ++$index;
            $lastMode = $mode;
            $line     = $raw !== '' ? $raw : ' ';
            $prefix   = $line[0] ?? '';

            if ($prefix === '+') {
                $mode = 'add';
            } elseif ($prefix === '-') {
                $mode = 'delete';
            } elseif ($prefix === ' ') {
                $mode = 'keep';
            } else {
                throw new InvalidDiffException("Invalid Line: {$line}");
            }

            $lineContent        = substr($line, 1);
            $switchingToContext = $mode === 'keep' && $lastMode !== $mode;
            if ($switchingToContext && (count($delLines) > 0 || count($insLines) > 0)) {
                $sectionChunks[] = new Chunk(
                    count($context) - count($delLines),
                    [...$delLines],
                    [...$insLines]
                );
                $delLines = [];
                $insLines = [];
            }

            if ($mode === 'delete') {
                $delLines[] = $lineContent;
                $context[]  = $lineContent;
            } elseif ($mode === 'add') {
                $insLines[] = $lineContent;
            } else {
                $context[] = $lineContent;
            }
        }

        if (count($delLines) > 0 || count($insLines) > 0) {
            $sectionChunks[] = new Chunk(
                count($context) - count($delLines),
                [...$delLines],
                [...$insLines]
            );
        }

        if ($index < count($lines) && $lines[$index] === self::END_FILE) {
            return new ReadSectionResult($context, $sectionChunks, $index + 1, true);
        }

        if ($index === $origIndex) {
            $nextLine = $index < count($lines) ? $lines[$index] : '';
            throw new InvalidDiffException("Nothing in this section - index={$index} {$nextLine}");
        }

        return new ReadSectionResult($context, $sectionChunks, $index, false);
    }

    /**
     * @return array<string>
     */
    public function normalizeDiffLines(string $diff): array
    {
        $lines = [];
        $split = preg_split('/\r?\n/', $diff);
        if ($split === false) {
            return [];
        }

        foreach ($split as $line) {
            $lines[] = rtrim($line, "\r");
        }

        if (count($lines) > 0 && $lines[count($lines) - 1] === '') {
            array_pop($lines);
        }

        return $lines;
    }
}
