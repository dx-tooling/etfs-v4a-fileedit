<?php

declare(strict_types=1);

namespace V4AFileEdit\Tests\Unit;

use V4AFileEdit\ApplyDiff;
use V4AFileEdit\Exception\InvalidContextException;
use V4AFileEdit\Exception\InvalidDiffException;

test('applyDiff in create mode with simple content', function (): void {
    $applyDiff = new ApplyDiff();
    $diff      = "+line 1\n+line 2\n+line 3";
    $result    = $applyDiff->applyDiff('', $diff, 'create');
    expect($result)->toBe("line 1\nline 2\nline 3");
});

test('applyDiff in create mode with empty lines', function (): void {
    $applyDiff = new ApplyDiff();
    $diff      = "+line 1\n+\n+line 3";
    $result    = $applyDiff->applyDiff('', $diff, 'create');
    expect($result)->toBe("line 1\n\nline 3");
});

test('applyDiff in create mode throws on invalid line', function (): void {
    $applyDiff = new ApplyDiff();
    $diff      = "+line 1\n-line 2\n+line 3";
    expect(fn () => $applyDiff->applyDiff('', $diff, 'create'))
        ->toThrow(InvalidDiffException::class, 'Invalid Add File Line');
});

test('applyDiff in default mode with simple update', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    $diff      = " line 1\n-line 2\n+line 2 updated\n line 3";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff in default mode with multiple chunks', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3\nline 4";
    $diff      = " line 1\n-line 2\n+line 2a\n line 3\n-line 4\n+line 4a";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2a\nline 3\nline 4a");
});

test('applyDiff in default mode with anchor', function (): void {
    // Anchor positions cursor AFTER the anchor line.
    // Context matching then starts from that position.
    // The anchor line itself should NOT be part of the context.
    $applyDiff = new ApplyDiff();
    $input     = "header\nanchor line\nline 2\nline 3\nline 4";
    // Anchor to "anchor line", cursor moves after it, then context is "line 2", modify "line 3"
    $diff   = "@@ anchor line\n line 2\n-line 3\n+line 3 updated\n line 4";
    $result = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("header\nanchor line\nline 2\nline 3 updated\nline 4");
});

test('applyDiff in default mode with bare anchor', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    $diff      = "@@\n line 1\n-line 2\n+line 2 updated\n line 3";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff handles EOF marker', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    $diff      = " line 1\n line 2\n-line 3\n+line 3 updated\n*** End of File";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2\nline 3 updated");
});

test('applyDiff throws on overlapping chunks', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    // This would create overlapping chunks if parser doesn't handle it correctly
    // We'll test with a diff that would cause this
    $diff = " line 1\n-line 2\n+line 2a\n line 2\n-line 3\n+line 3a";
    // This should fail because the second chunk overlaps with the first
    expect(fn () => $applyDiff->applyDiff($input, $diff))
        ->toThrow(InvalidContextException::class);
});

test('applyDiff throws on invalid context', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    $diff      = " line 999\n-line 2\n+line 2 updated";
    expect(fn () => $applyDiff->applyDiff($input, $diff))
        ->toThrow(InvalidContextException::class);
});

test('applyDiff handles Windows line endings', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\r\nline 2\r\nline 3";
    $diff      = " line 1\r\n-line 2\r\n+line 2 updated\r\n line 3";
    $result    = $applyDiff->applyDiff($input, $diff);
    // Result should have Unix line endings
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff handles mixed line endings in diff', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    $diff      = " line 1\r\n-line 2\r\n+line 2 updated\n line 3";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 2 updated\nline 3");
});

test('applyDiff with insertion only', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    $diff      = " line 1\n+line 1.5\n line 2";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 1.5\nline 2\nline 3");
});

test('applyDiff with deletion only', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3";
    $diff      = " line 1\n-line 2\n line 3";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 3");
});

test('applyDiff with multiple insertions in one chunk', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2";
    $diff      = " line 1\n+line 1a\n+line 1b\n line 2";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 1a\nline 1b\nline 2");
});

test('applyDiff with multiple deletions in one chunk', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = "line 1\nline 2\nline 3\nline 4";
    $diff      = " line 1\n-line 2\n-line 3\n line 4";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("line 1\nline 4");
});

test('applyDiff handles empty input in create mode', function (): void {
    $applyDiff = new ApplyDiff();
    $diff      = '+';
    $result    = $applyDiff->applyDiff('', $diff, 'create');
    expect($result)->toBe('');
});

test('applyDiff handles empty input in default mode', function (): void {
    $applyDiff = new ApplyDiff();
    $input     = '';
    $diff      = '+line 1';
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe('line 1');
});

test('applyDiff handles multibyte UTF-8 characters', function (): void {
    // Test with various multibyte UTF-8 characters:
    // - Emojis (4 bytes each): 🎉, 🚀
    // - Chinese (3 bytes each): 你好
    // - Accented (2 bytes): café
    $applyDiff = new ApplyDiff();
    $input     = "Hello 🎉\n你好世界\ncafé\nend";
    $diff      = " Hello 🎉\n-你好世界\n+你好宇宙\n café";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("Hello 🎉\n你好宇宙\ncafé\nend");
});

test('applyDiff in create mode with multibyte UTF-8', function (): void {
    $applyDiff = new ApplyDiff();
    $diff      = "+🚀 Rocket\n+日本語テスト\n+Ñoño";
    $result    = $applyDiff->applyDiff('', $diff, 'create');
    expect($result)->toBe("🚀 Rocket\n日本語テスト\nÑoño");
});

test('applyDiff with multibyte anchor', function (): void {
    // Anchor line contains multibyte characters
    $applyDiff = new ApplyDiff();
    $input     = "header\n函数开始\nline A\nline B";
    $diff      = "@@ 函数开始\n line A\n-line B\n+line B 修改";
    $result    = $applyDiff->applyDiff($input, $diff);
    expect($result)->toBe("header\n函数开始\nline A\nline B 修改");
});

test('applyDiff with anchor positions cursor after anchor line', function (): void {
    // This test verifies that after finding an anchor, the cursor is positioned
    // AFTER the anchor line, not AT it.
    //
    // Input: "A\nB\nA\nB" - the pattern "A\nB" repeats twice
    // Index:  0  1  2  3
    //
    // Using @@ anchor "A" should find A at index 0, then cursor should be 1 (AFTER anchor).
    // The context from the diff is ["A", "B"] (context line + deleted line).
    // Context matching starting from cursor=1 should NOT match at 1 (B≠A), so it
    // continues and finds the match at index 2.
    //
    // BUG: If cursor is incorrectly set to 0 (AT anchor instead of AFTER),
    // context matching starts at 0 and matches ["A","B"] at indices 0,1.
    // This causes the chunk to modify the FIRST "B" instead of the SECOND "B".
    $applyDiff = new ApplyDiff();
    $input     = "A\nB\nA\nB";
    // Anchor "A", then context " A" followed by delete/insert of "B"
    // The intent is to modify the SECOND "B" (at index 3), not the first
    $diff   = "@@ A\n A\n-B\n+B modified";
    $result = $applyDiff->applyDiff($input, $diff);
    // Correct: modify second B -> "A\nB\nA\nB modified"
    // Bug: would modify first B -> "A\nB modified\nA\nB"
    expect($result)->toBe("A\nB\nA\nB modified");
});
