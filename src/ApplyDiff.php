<?php

declare(strict_types=1);

namespace V4AFileEdit;

use V4AFileEdit\Exception\InvalidDiffException;
use V4AFileEdit\Exception\OverlappingChunkException;
use V4AFileEdit\Parser\Chunk;
use V4AFileEdit\Parser\DiffParser;

final class ApplyDiff
{
    public function __construct(
        private readonly DiffParser $parser = new DiffParser()
    ) {
    }

    /**
     * Apply a V4A diff to the provided text.
     *
     * This parser understands both the create-file syntax (only "+" prefixed
     * lines) and the default update syntax that includes context hunks.
     *
     * @param string             $input The original text content
     * @param string             $diff  The V4A diff to apply
     * @param 'default'|'create' $mode  The mode to use ('default' for update, 'create' for new files)
     *
     * @return string The modified text
     *
     * @throws InvalidDiffException
     * @throws OverlappingChunkException
     */
    public function applyDiff(string $input, string $diff, string $mode = 'default'): string
    {
        $diffLines = $this->parser->normalizeDiffLines($diff);

        if ($mode === 'create') {
            return $this->parser->parseCreateDiff($diffLines);
        }

        // Normalize input line endings to Unix style
        $normalizedInput = str_replace(["\r\n", "\r"], "\n", $input);

        $parsed = $this->parser->parseUpdateDiff($diffLines, $normalizedInput);

        return $this->applyChunks($normalizedInput, $parsed->chunks);
    }

    /**
     * @param array<Chunk> $chunks
     */
    private function applyChunks(string $input, array $chunks): string
    {
        // Handle empty input
        if ($input === '') {
            $origLines = [];
        } else {
            $origLines = explode("\n", $input);
        }

        $destLines = [];
        $cursor    = 0;

        foreach ($chunks as $chunk) {
            if ($chunk->origIndex > count($origLines)) {
                throw new InvalidDiffException(
                    "applyDiff: chunk.origIndex {$chunk->origIndex} > input length " . count($origLines)
                );
            }

            if ($cursor > $chunk->origIndex) {
                throw new OverlappingChunkException(
                    "applyDiff: overlapping chunk at {$chunk->origIndex} (cursor {$cursor})"
                );
            }

            // Add lines before the chunk
            for ($i = $cursor; $i < $chunk->origIndex; ++$i) {
                $destLines[] = $origLines[$i];
            }

            $cursor = $chunk->origIndex;

            // Add inserted lines
            if (count($chunk->insLines) > 0) {
                $destLines = array_merge($destLines, $chunk->insLines);
            }

            // Skip deleted lines
            $cursor += count($chunk->delLines);
        }

        // Add remaining lines
        for ($i = $cursor; $i < count($origLines); ++$i) {
            $destLines[] = $origLines[$i];
        }

        // Join with newlines, but don't add trailing newline for empty result
        if (count($destLines) === 0) {
            return '';
        }

        return implode("\n", $destLines);
    }
}
