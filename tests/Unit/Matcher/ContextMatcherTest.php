<?php

declare(strict_types=1);

namespace V4AFileEdit\Tests\Unit\Matcher;

use V4AFileEdit\Matcher\ContextMatcher;

test('findContext with exact match', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2', 'line 3'];
    $context = ['line 2'];
    $result = $matcher->findContext($lines, $context, 0, false);
    expect($result->newIndex)->toBe(1);
    expect($result->fuzz)->toBe(0);
});

test('findContext with rstrip match', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2  ', 'line 3'];
    $context = ['line 2'];
    $result = $matcher->findContext($lines, $context, 0, false);
    expect($result->newIndex)->toBe(1);
    expect($result->fuzz)->toBe(1);
});

test('findContext with strip match', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', '  line 2  ', 'line 3'];
    $context = ['line 2'];
    $result = $matcher->findContext($lines, $context, 0, false);
    expect($result->newIndex)->toBe(1);
    expect($result->fuzz)->toBe(100);
});

test('findContext with no match', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2', 'line 3'];
    $context = ['line 999'];
    $result = $matcher->findContext($lines, $context, 0, false);
    expect($result->newIndex)->toBe(-1);
});

test('findContext with empty context', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2'];
    $context = [];
    $result = $matcher->findContext($lines, $context, 5, false);
    expect($result->newIndex)->toBe(5);
    expect($result->fuzz)->toBe(0);
});

test('findContext with EOF marker', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2', 'line 3'];
    $context = ['line 3'];
    $result = $matcher->findContext($lines, $context, 0, true);
    expect($result->newIndex)->toBe(2);
    expect($result->fuzz)->toBe(0);
});

test('findContext with EOF and fallback', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2'];
    $context = ['line 3'];
    $result = $matcher->findContext($lines, $context, 0, true);
    // Should return fallback with high fuzz
    expect($result->fuzz)->toBeGreaterThan(1000);
});

test('findContext with multiple context lines', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2', 'line 3', 'line 4'];
    $context = ['line 2', 'line 3'];
    $result = $matcher->findContext($lines, $context, 0, false);
    expect($result->newIndex)->toBe(1);
    expect($result->fuzz)->toBe(0);
});

test('findContext starts from given index', function (): void {
    $matcher = new ContextMatcher();
    $lines = ['line 1', 'line 2', 'line 1', 'line 2'];
    $context = ['line 1'];
    $result = $matcher->findContext($lines, $context, 2, false);
    expect($result->newIndex)->toBe(2);
});
