# Modern PHP Hooks System

A lightweight, dependency-free implementation of an action and filter hook system inspired by WordPress hooks but designed for modern PHP applications.

## Features

- **Modern PHP Support**: Fully typed, supports PHP 8.0+
- **Dependency Injection Ready**: Use the `HooksInterface` in your classes
- **Static Facade**: Convenient static methods for quick integration
- **No Dependencies**: Lightweight implementation with zero dependencies
- **WordPress Inspired**: Familiar API if you're coming from WordPress

## Installation

Via **composer**:

```bash
composer require simplemediacode/hooks
```

## Usage

### Using the Static Facade

```php
use Simplemediacode\Hooks\Hooks;

// Add a filter
Hooks::addFilter('content', function($content) {
    return strtoupper($content);
});

// Apply a filter
$content = Hooks::applyFilters('content', 'Hello World'); // Returns "HELLO WORLD"

// Add an action
Hooks::addAction('save_post', function($postId) {
    // Do something when a post is saved
    echo "Post {$postId} was saved!";
});

// Execute an action
Hooks::doAction('save_post', 123);
```

### Using Dependency Injection

```php
use Simplemediacode\Hooks\HooksInterface;

class MyClass
{
    private ?HooksInterface $hooks;

    public function __construct(
        ?HooksInterface $hooks = null
    ) {
        $this->hooks = $hooks;
    }
    
    public function processContent(string $content): string
    {
        // If hooks are available, filter the content
        if ($this->hooks) {
            $content = $this->hooks->executeHook('content', $content);
        }
        
        return $content;
    }
}
```

### Extending with Custom Implementations

You can implement your own version of `HooksInterface` to provide custom hook functionality:

```php
$customHooks = new MyCustomHooksImplementation();
Hooks::setInstance($customHooks);
```

## Migration from WP_Hook

This package is a complete rewrite of the original WordPress hook system. Key differences:

- Renamed `WP_Hook` to `Hook`
- Introduced proper interfaces for better type safety
- Added dependency injection support
- Replaced global variables with proper class properties
- Improved naming conventions and method signatures

## Changelog

Read at [CHANGELOG.md](./CHANGELOG.md).

## License
This library is released under the GPL-2.0 license. See the complete license in the bundled [LICENSE](./LICENSE) file.