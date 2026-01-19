# PHP V4A File Edit

A modern, composer-installable PHP library that implements the V4A diff application algorithm from OpenAI's apply_patch tool. This library allows you to parse and apply V4A-formatted diffs to text files, supporting both create and update modes.

## Features

- **V4A Diff Parsing**: Full support for V4A diff format with context hunks, anchors, and section terminators
- **Create Mode**: Parse diffs for new files (all lines prefixed with `+`)
- **Update Mode**: Apply diffs to existing files with context matching
- **Fuzzy Matching**: Intelligent context matching with exact, rstrip, and strip matching
- **EOF Handling**: Support for end-of-file markers
- **Type Safe**: PHPStan level 10 compliance
- **Well Tested**: Comprehensive unit test coverage

## Requirements

- PHP 8.4 or higher
- Composer

## Installation

```bash
composer require enterprise-tooling-for-symfony/v4a-fileedit
```

## Usage

### Basic Example

```php
<?php

use V4AFileEdit\ApplyDiff;

$applyDiff = new ApplyDiff();

// Update mode (default)
$original = "line 1\nline 2\nline 3";
$diff = " line 1\n-line 2\n+line 2 updated\n line 3";
$result = $applyDiff->applyDiff($original, $diff);
// Result: "line 1\nline 2 updated\nline 3"

// Create mode
$diff = "+line 1\n+line 2\n+line 3";
$result = $applyDiff->applyDiff('', $diff, 'create');
// Result: "line 1\nline 2\nline 3"
```

### Update Mode with Anchors

```php
$original = "line 1\nline 2\nline 3\nline 4";
$diff = "@@ line 2\n-line 2\n+line 2 updated\n line 3";
$result = $applyDiff->applyDiff($original, $diff);
// Result: "line 1\nline 2 updated\nline 3\nline 4"
```

### Create Mode

```php
$diff = "+#!/usr/bin/env php\n+<?php\n+\n+echo 'Hello, World!';\n";
$result = $applyDiff->applyDiff('', $diff, 'create');
// Result: "#!/usr/bin/env php\n<?php\n\necho 'Hello, World!';\n"
```

### Handling EOF Markers

```php
$original = "line 1\nline 2\nline 3";
$diff = " line 1\n line 2\n-line 3\n+line 3 updated\n*** End of File";
$result = $applyDiff->applyDiff($original, $diff);
// Result: "line 1\nline 2\nline 3 updated"
```

## Development Setup

This project uses mise-en-place for tool management and Docker Compose for a deterministic development environment.

### Prerequisites

- Docker Desktop
- mise-en-place (https://mise.jdx.dev)

**Note**: You only need mise and Docker on your host machine. PHP, Node.js, and all other tools run inside the Docker container.

### Setup

1. Clone the repository:

    ```bash
    git clone <repository-url>
    cd php-v4a-fileedit
    ```

2. Trust the mise configuration:

    ```bash
    mise trust
    ```

3. Install dependencies (runs in an ephemeral container):

    ```bash
    mise run in-app-container composer install
    mise run in-app-container mise trust
    ```

4. Run quality checks:

    ```bash
    mise run quality
    ```

5. Run tests:
    ```bash
    mise run tests
    ```

**Note**: Containers are created ephemerally for each command. There's no need to start or stop containers manually - they're created on-demand and automatically removed after execution.

### Available Commands

- `mise run quality` - Run all quality tools (PHP CS Fixer, Prettier, PHPStan)
- `mise run quality --check-violations` - Check for violations without fixing
- `mise run tests` - Run the test suite

### Docker Container

The Docker container provides a consistent PHP 8.4 CLI environment with:

- PHP 8.4 with required extensions (mbstring, pcntl, bcmath, intl, zip)
- Composer
- mise-en-place (for managing tools inside the container)
- Node.js 24 (pre-installed via mise during Docker build to avoid download overhead)

Containers are created **ephemerally** for each command execution - they're created on-demand, run the command, and are automatically removed afterward. This ensures a clean, consistent environment for every execution without needing to manage container lifecycle.

All development commands run inside ephemeral containers via mise tasks. To execute commands directly in an ephemeral container:

```bash
mise run in-app-container <command>
```

## V4A Diff Format

The library supports the V4A diff format as documented by OpenAI:

### Update Mode Format

```
 line 1 (context)
-line 2 (deletion)
+line 2 updated (insertion)
 line 3 (context)
```

### Create Mode Format

```
+line 1
+line 2
+line 3
```

### Special Markers

- `@@ <anchor>` - Anchor marker for positioning
- `@@` - Bare anchor marker
- `*** End Patch` - End of patch marker
- `*** End of File` - End of file marker
- `*** Update File:` - Update file marker
- `*** Delete File:` - Delete file marker
- `*** Add File:` - Add file marker

## Error Handling

The library throws specific exceptions for different error conditions:

- `V4AFileEdit\Exception\InvalidDiffException` - Invalid diff format
- `V4AFileEdit\Exception\InvalidContextException` - Context not found in input
- `V4AFileEdit\Exception\OverlappingChunkException` - Overlapping chunks detected

## Testing

The library includes comprehensive unit tests using Pest. Run tests with:

```bash
mise run tests
```

Or directly:

```bash
php vendor/bin/pest
```

## Code Quality

The project enforces strict code quality standards:

- **PHP CS Fixer**: Symfony coding standards with custom rules
- **PHPStan**: Level 10 (maximum strictness) with 100% type coverage (return, param, property, constant)
- **Prettier**: Code formatting for JSON, YAML, Markdown files

Run all quality checks:

```bash
mise run quality
```

## License

MIT

## Contributing

Contributions are welcome! Please ensure that:

1. All tests pass (`mise run tests`)
2. Code quality checks pass (`mise run quality`)
3. Code follows the project's coding standards

## References

- [OpenAI V4A Apply Patch Documentation](https://platform.openai.com/docs/guides/tools-apply-patch)
- [Python Reference Implementation](https://github.com/openai/openai-agents-python/blob/main/src/agents/apply_diff.py)
