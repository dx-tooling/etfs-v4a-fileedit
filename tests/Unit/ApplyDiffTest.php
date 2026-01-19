<?php

declare(strict_types=1);

namespace V4AFileEdit\Tests\Unit;

use V4AFileEdit\ApplyDiff;
use V4AFileEdit\Exception\InvalidContextException;
use V4AFileEdit\Exception\InvalidDiffException;
use V4AFileEdit\Exception\OverlappingChunkException;

test('applyDiff in create mode with simple content', function (): void {
    $applyDiff = new ApplyDiff();
    $diff = "+line 1\n+line 2\n+line 3";
    $result = $applyDiff->applyDiff('', $diff, 'create');
    expect($result)->toBe("line 1\nline 2\nline 3");
});

test('applyDiff in create mode with empty lines', function (): void {
    $applyDiff = new ApplyDiff();
    $diff = "+line 1\n+\n+line 3";
    $result = $applyDiff->applyDiff('', $diff, 'create');
    expect($result)->toBe("line 1\n\nline 3");
});

test('applyDiff in create mode throws on invalid line', function (): void {
    $applyDiff = new ApplyDiff();
    $diff = "+line 1\n-line 2\n+line 3";
    expect(fn () => $applyDiff->applyDiff('', $diff, 'create'))
        ->toThrow(InvalidDiffException::class, 'Invalid Add File Line');
});

test('applyDiff in default mode with simple update', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    $diff = " line 1\n-line 2\n+line 2 updated\n line 3";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff in default mode with multiple chunks', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3\nline 4";
    $diff = " line 1\n-line 2\n+line 2a\n line 3\n-line 4\n+line 4a";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2a\nline 3\nline 4a");
});

test('applyDiff in default mode with anchor', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3\nline 4";
    $diff = "@@ line 2\n-line 2\n+line 2 updated\n line 3";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2 updated\nline 3\nline 4");
});

test('applyDiff in default mode with bare anchor', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    $diff = "@@\n line 1\n-line 2\n+line 2 updated\n line 3";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff handles EOF marker', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    $diff = " line 1\n line 2\n-line 3\n+line 3 updated\n*** End of File";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2\nline 3 updated");
});

test('applyDiff throws on overlapping chunks', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    // This would create overlapping chunks if parser doesn't handle it correctly
    // We'll test with a diff that would cause this
    $diff = " line 1\n-line 2\n+line 2a\n line 2\n-line 3\n+line 3a";
    // This should fail because the second chunk overlaps with the first
    expect(fn () => $applyDiff->applyDiff($input, $diff))
        ->toThrow(InvalidContextException::class);
});

test('applyDiff throws on invalid context', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    $diff = " line 999\n-line 2\n+line 2 updated";
    expect(fn () => $applyDiff->applyDiff($input, $diff))
        ->toThrow(InvalidContextException::class);
});

test('applyDiff handles Windows line endings', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\r\nline 2\r\nline 3";
    $diff = " line 1\r\n-line 2\r\n+line 2 updated\r\n line 3";
    $result = $applyDiff->applyDiff($input, $diff);
    // Result should have Unix line endings
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff handles mixed line endings in diff', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    $diff = " line 1\r\n-line 2\r\n+line 2 updated\n line 3";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff with insertion only', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    $diff = " line 1\n+line 1.5\n line 2";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 1.5\nline 2\nline 3");
});

test('applyDiff with deletion only', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3";
    $diff = " line 1\n-line 2\n line 3";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 3");
});

test('applyDiff with multiple insertions in one chunk', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2";
    $diff = " line 1\n+line 1a\n+line 1b\n line 2";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 1a\nline 1b\nline 2");
});

test('applyDiff with multiple deletions in one chunk', function (): void {
    $applyDiff = new ApplyDiff();
    $input = "line 1\nline 2\nline 3\nline 4";
    $diff = " line 1\n-line 2\n-line 3\n line 4";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 4");
});

test('applyDiff handles empty input in create mode', function (): void {
    $applyDiff = new ApplyDiff();
    $diff = "+";
    $result = $applyDiff->applyDiff('', $diff, 'create');
    expect($result)->toBe('');
});

test('applyDiff handles empty input in default mode', function (): void {
    $applyDiff = new ApplyDiff();
    $input = '';
    $diff = "+line 1";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1");
});
