<?php

declare(strict_types=1);

namespace V4AFileEdit\Matcher;

final class ContextMatcher
{
    /**
     * @param array<string> $lines
     * @param array<string> $context
     */
    public function findContext(array $lines, array $context, int $start, bool $eof): ContextMatch
    {
        if ($eof) {
            $endStart = max(0, count($lines) - count($context));
            $endMatch = $this->findContextCore($lines, $context, $endStart);
            if ($endMatch->newIndex !== -1) {
                return $endMatch;
            }
            $fallback = $this->findContextCore($lines, $context, $start);
            return new ContextMatch($fallback->newIndex, $fallback->fuzz + 10000);
        }

        return $this->findContextCore($lines, $context, $start);
    }

    /**
     * @param array<string> $lines
     * @param array<string> $context
     */
    private function findContextCore(array $lines, array $context, int $start): ContextMatch
    {
        if (count($context) === 0) {
            return new ContextMatch($start, 0);
        }

        // Try exact match
        for ($i = $start; $i < count($lines); $i++) {
            if ($this->equalsSlice($lines, $context, $i, fn (string $value): string => $value)) {
                return new ContextMatch($i, 0);
            }
        }

        // Try rstrip match
        for ($i = $start; $i < count($lines); $i++) {
            if ($this->equalsSlice($lines, $context, $i, fn (string $value): string => rtrim($value))) {
                return new ContextMatch($i, 1);
            }
        }

        // Try strip match
        for ($i = $start; $i < count($lines); $i++) {
            if ($this->equalsSlice($lines, $context, $i, fn (string $value): string => trim($value))) {
                return new ContextMatch($i, 100);
            }
        }

        return new ContextMatch(-1, 0);
    }

    /**
     * @param array<string> $source
     * @param array<string> $target
     * @param callable(string): string $mapFn
     */
    private function equalsSlice(array $source, array $target, int $start, callable $mapFn): bool
    {
        if ($start + count($target) > count($source)) {
            return false;
        }

        foreach ($target as $offset => $targetValue) {
            if ($mapFn($source[$start + $offset]) !== $mapFn($targetValue)) {
                return false;
            }
        }

        return true;
    }
}
