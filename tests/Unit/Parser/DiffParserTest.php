<?php

declare(strict_types=1);

namespace V4AFileEdit\Tests\Unit\Parser;

use V4AFileEdit\Exception\InvalidContextException;
use V4AFileEdit\Exception\InvalidDiffException;
use V4AFileEdit\Parser\DiffParser;

test('normalizeDiffLines handles Unix line endings', function (): void {
    $parser = new DiffParser();
    $diff = "line 1\nline 2\nline 3";
    $result = $parser->normalizeDiffLines($diff);
    expect($result)->toBe(['line 1', 'line 2', 'line 3']);
});

test('normalizeDiffLines handles Windows line endings', function (): void {
    $parser = new DiffParser();
    $diff = "line 1\r\nline 2\r\nline 3";
    $result = $parser->normalizeDiffLines($diff);
    expect($result)->toBe(['line 1', 'line 2', 'line 3']);
});

test('normalizeDiffLines removes trailing empty line', function (): void {
    $parser = new DiffParser();
    $diff = "line 1\nline 2\n";
    $result = $parser->normalizeDiffLines($diff);
    expect($result)->toBe(['line 1', 'line 2']);
});

test('parseCreateDiff with valid content', function (): void {
    $parser = new DiffParser();
    $diff = ['+line 1', '+line 2', '+line 3'];
    $result = $parser->parseCreateDiff($diff);
    expect($result)->toBe("line 1\nline 2\nline 3");
});

test('parseCreateDiff throws on invalid line', function (): void {
    $parser = new DiffParser();
    $diff = ['+line 1', '-line 2'];
    expect(fn () => $parser->parseCreateDiff($diff))
        ->toThrow(InvalidDiffException::class, 'Invalid Add File Line');
});

test('parseUpdateDiff with simple context', function (): void {
    $parser = new DiffParser();
    $input = "line 1\nline 2\nline 3";
    $diff = [' line 1', '-line 2', '+line 2 updated', ' line 3'];
    $parsed = $parser->parseUpdateDiff($diff, $input);
    expect(count($parsed->chunks))->toBe(1);
    expect($parsed->chunks[0]->origIndex)->toBe(1);
    expect($parsed->chunks[0]->delLines)->toBe(['line 2']);
    expect($parsed->chunks[0]->insLines)->toBe(['line 2 updated']);
});

test('parseUpdateDiff with anchor', function (): void {
    $parser = new DiffParser();
    $input = "line 1\nline 2\nline 3";
    $diff = ['@@ line 2', '-line 2', '+line 2 updated', ' line 3'];
    $parsed = $parser->parseUpdateDiff($diff, $input);
    expect(count($parsed->chunks))->toBe(1);
});

test('parseUpdateDiff throws on invalid context', function (): void {
    $parser = new DiffParser();
    $input = "line 1\nline 2\nline 3";
    $diff = [' line 999', '-line 2', '+line 2 updated'];
    expect(fn () => $parser->parseUpdateDiff($diff, $input))
        ->toThrow(InvalidContextException::class);
});

test('parseUpdateDiff handles EOF marker', function (): void {
    $parser = new DiffParser();
    $input = "line 1\nline 2\nline 3";
    $diff = [' line 1', ' line 2', '-line 3', '+line 3 updated', '*** End of File'];
    $parsed = $parser->parseUpdateDiff($diff, $input);
    expect(count($parsed->chunks))->toBe(1);
    expect($parsed->chunks[0]->origIndex)->toBe(2);
});

test('parseUpdateDiff throws on invalid line prefix', function (): void {
    $parser = new DiffParser();
    $input = "line 1\nline 2";
    $diff = [' line 1', 'Xinvalid line'];
    expect(fn () => $parser->parseUpdateDiff($diff, $input))
        ->toThrow(InvalidDiffException::class, 'Invalid Line');
});

test('parseUpdateDiff throws on invalid section marker', function (): void {
    $parser = new DiffParser();
    $input = "line 1";
    $diff = [' line 1', '*** Invalid Marker'];
    expect(fn () => $parser->parseUpdateDiff($diff, $input))
        ->toThrow(InvalidDiffException::class, 'Invalid Line');
});
