# Migration Plan: PSR-14 for Version 3

## Short Answer

Yes, this package can support PSR-14 in version 3 while remaining primarily a WordPress-style hooks API.

The v3 direction is additive: keep the current action/filter API as the main developer experience, then expose PSR-14 interfaces for interoperability with frameworks and packages that expect standard event dispatching.

## Resolved Decisions

These decisions are now fixed for the v3 proposal:

- Minimum PHP version: PHP `8.3+`.
- Static facade: keep `Hooks` fully supported in v3 without deprecation.
- Primary API: keep the current WordPress-style hooks API as the main package API.
- PSR-14 dispatch: `HooksManager` should implement `Psr\EventDispatcher\EventDispatcherInterface` directly.
- Listener provider: use a separate provider class, exposed through `HooksManager`.
- Listener registration: add a convenience `listen()` method for PSR-14 event-object listeners.
- Parent/interface matching: supported for PSR-14 event listeners.
- String hook names: remain first-class through generic named `ActionEvent` and `FilterEvent` objects.
- `all` hook: remains supported for both `doAction()` and `applyFilters()`.
- Stoppable events: only user-defined events that implement `StoppableEventInterface` are stoppable initially.
- Documentation filename: use `docs/migration-psr-14.md`.

## References

- PHP-FIG PSR-14: https://www.php-fig.org/psr/psr-14/
- PHP-FIG PSR-14 meta document: https://www.php-fig.org/psr/psr-14/meta/
- `psr/event-dispatcher` package: https://packagist.org/packages/psr/event-dispatcher

## Current Package Shape

Current files inspected:

- `src/Hooks.php`
- `src/HooksInterface.php`
- `src/HooksManager.php`
- `composer.json`
- `README.md`
- `CHANGELOG.md`

Current version: `2.1.2`

Current dependencies: none, except PHP itself.

Current PHP support: `~8.0|~8.1|~8.2|~8.3|~8.4|~8.5`

The main implementation is `Simplemediacode\Hooks\HooksManager`, which implements `HooksInterface` and stores callbacks by:

```text
hook name -> priority -> callback id -> callback metadata
```

The important current behaviors are:

- `add()` registers both action and filter callbacks.
- `remove()` removes a callback by hook name, callback identity, and priority.
- `removeAll()` removes all callbacks for a hook or one priority.
- `has()` checks whether callbacks exist.
- `filter()` and `applyFilters()` pass a value through callbacks and use each callback return value as the next value.
- `action()` and `doAction()` execute callbacks for side effects.
- the special `all` hook receives the hook name plus the original arguments.
- `count()` tracks action execution count.
- `current()` and `isExecuting()` track hook execution stack state.

## Version 3 Direction

Version 3 should remain primarily a WordPress-style hooks package while adding PSR-14 interfaces for interoperability.

The package should keep:

- `Hooks` as the static facade
- `HooksInterface` as the package-native dependency injection contract
- `HooksManager` as the main implementation
- string hook names as first-class identifiers
- priorities with lower numbers running earlier
- `acceptedArgs`
- action hooks
- filter hooks
- the `all` hook
- execution state helpers such as `didAction()`, `doingHook()`, and `currentHook()`

The package should add:

- `psr/event-dispatcher` as a dependency
- PSR-14 event objects
- direct `dispatch(object $event)` support on `HooksManager`
- a separate listener provider exposed through `HooksManager`
- `listen(string $eventClass, callable $listener, int $priority = 10): bool` for event-object listeners
- parent class and interface listener matching

This gives framework users a standard integration point without making existing users migrate away from the hook API.

## Proposed Composer Changes

For v3, update Composer requirements to:

```json
{
  "require": {
    "php": "^8.3",
    "psr/event-dispatcher": "^1.0"
  }
}
```

Using `^8.3` keeps the major-version boundary below PHP 9 while allowing current and future PHP 8.x versions.

## PSR-14 Compatibility Gap

The largest mismatch is filter return values.

In this package:

```php
$content = Hooks::applyFilters('content', 'Hello World');
```

A filter callback returns the new value:

```php
fn (string $content): string => strtoupper($content)
```

In PSR-14, listener return values are ignored. A listener changes state by mutating the event object:

```php
function (ContentFilterEvent $event): void {
    $event->setValue(strtoupper($event->value()));
}
```

Version 3 should keep return-value chaining for `applyFilters()`. PSR-14 listeners should use mutable event objects.

## Proposed Architecture

### HooksManager

`HooksManager` remains the main implementation and should directly implement:

```php
Simplemediacode\Hooks\HooksInterface
Psr\EventDispatcher\EventDispatcherInterface
```

Primary responsibilities:

- preserve current hook registration and execution behavior
- expose `dispatch(object $event): object`
- expose a listener provider for PSR-14 integration
- provide `listen()` as a convenience method for event-object listeners
- track current hook/action state for the package-native API

### Listener Provider

Create a separate provider, for example:

```php
Simplemediacode\Hooks\Psr14\ListenerProvider
```

It should implement:

```php
Psr\EventDispatcher\ListenerProviderInterface
```

Responsibilities:

- store event-object listeners by event class, parent class, or interface
- preserve numeric priority ordering
- preserve insertion order for listeners with the same priority
- return listeners for exact event class matches
- return listeners registered for parent classes
- return listeners registered for implemented interfaces

`HooksManager` should own or compose this provider and expose it through a method such as:

```php
public function getListenerProvider(): ListenerProviderInterface;
```

### listen() Convenience Method

Add a convenience method for PSR-14 listeners:

```php
public function listen(string $eventClass, callable $listener, int $priority = 10): bool;
```

Example:

```php
$hooks->listen(PostSavedEvent::class, function (PostSavedEvent $event): void {
    // react to the event
});
```

This avoids forcing users to manually fetch the provider for normal use.

### Event Dispatcher

`HooksManager::dispatch()` should follow PSR-14 behavior:

- accept an event object
- ask the provider for listeners
- call listeners synchronously in provider order
- return the same event object
- ignore listener return values
- stop before the next listener if the event implements `StoppableEventInterface` and reports stopped propagation
- let listener exceptions bubble to the caller

Built-in `ActionEvent` and `FilterEvent` do not need to be stoppable initially. User-defined event classes can opt into stopping by implementing `Psr\EventDispatcher\StoppableEventInterface`.

## Proposed Event Objects

### ActionEvent

Generic named action event used by `doAction()`.

```php
final class ActionEvent
{
    public function __construct(
        private string $name,
        private array $arguments = []
    ) {}

    public function name(): string {}
    public function arguments(): array {}
}
```

`doAction()` remains the primary action API. Internally, it may create `ActionEvent` and dispatch it through the PSR-14 path.

### FilterEvent

Generic named filter event used by `applyFilters()`.

```php
final class FilterEvent
{
    public function __construct(
        private string $name,
        private mixed $value,
        private array $arguments = []
    ) {}

    public function name(): string {}
    public function value(): mixed {}
    public function setValue(mixed $value): void {}
    public function arguments(): array {}
}
```

`applyFilters()` remains the primary filter API. Internally, it may create `FilterEvent`, dispatch it, then return `$event->value()`.

### NamedEventInterface

A small interface can make named hook events easier to identify:

```php
interface NamedEventInterface
{
    public function name(): string;
}
```

Both `ActionEvent` and `FilterEvent` should implement it.

## How Current Calls Would Work

### Current Filter API

Existing v2 style remains valid:

```php
Hooks::addFilter('content', fn ($content) => strtoupper($content));

$content = Hooks::applyFilters('content', 'Hello World');
```

Expected v3 flow:

1. `applyFilters('content', 'Hello World')` creates `FilterEvent`.
2. `FilterEvent` stores the name `content`, value `Hello World`, and extra arguments.
3. The hook dispatch path runs registered string-hook callbacks.
4. Each filter callback return value becomes the next filter value.
5. The `all` hook also runs with the hook name and original arguments.
6. `applyFilters()` returns the final value.

### Current Action API

Existing v2 style remains valid:

```php
Hooks::addAction('save_post', function ($postId): void {
    echo "Post {$postId} was saved!";
});

Hooks::doAction('save_post', 123);
```

Expected v3 flow:

1. `doAction('save_post', 123)` creates `ActionEvent`.
2. The hook dispatch path runs registered string-hook callbacks.
3. Hook listener wrappers unwrap `$event->arguments()`.
4. Action callbacks run with the expected positional arguments.
5. The `all` hook also runs with the hook name and original arguments.
6. `didAction('save_post')` is incremented.

## PSR-14 Usage

### Event Listener Registration

```php
$hooks = new HooksManager();

$hooks->listen(PostSavedEvent::class, function (PostSavedEvent $event): void {
    echo "Post {$event->postId()} was saved!";
});
```

### Event Dispatch

```php
$event = $hooks->dispatch(new PostSavedEvent(123));
```

### Framework Integration

Consumers that expect PSR-14 can depend on the standard interface:

```php
use Psr\EventDispatcher\EventDispatcherInterface;

final class SavePostHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function save(int $postId): void
    {
        $this->dispatcher->dispatch(new PostSavedEvent($postId));
    }
}
```

`HooksManager` can be passed because it implements `EventDispatcherInterface` directly.

## Step-by-Step Migration Plan

### Step 1: Raise the Platform Requirement

Set PHP to `^8.3` in `composer.json`.

This is a major version, so dropping PHP 8.0, 8.1, and 8.2 is acceptable if v3 intentionally targets a cleaner modern baseline.

### Step 2: Add PSR-14 Interfaces

Add `psr/event-dispatcher` to Composer.

Do not import a third-party implementation. This package should remain lightweight and provide its own dispatcher/provider behavior.

### Step 3: Add Event Objects

Add:

- `Event\NamedEventInterface`
- `Event\ActionEvent`
- `Event\FilterEvent`

Do not make built-in named events stoppable initially.

### Step 4: Add the Listener Provider

Add `Psr14\ListenerProvider` implementing `ListenerProviderInterface`.

It should support:

- exact class registration
- parent class matching
- interface matching
- priority sorting
- insertion-order stability within the same priority

### Step 5: Add listen()

Add `listen()` to `HooksManager` for event-object listener registration.

This method should delegate to the provider.

### Step 6: Add dispatch()

Make `HooksManager` implement `EventDispatcherInterface` directly.

`dispatch()` should use the provider, honor stoppable user-defined events, ignore listener return values, and return the original event object.

### Step 7: Bridge Named Hooks to Events

Update internal action/filter execution so named hooks can be represented as `ActionEvent` and `FilterEvent` where useful.

The public behavior of `addAction()`, `addFilter()`, `doAction()`, and `applyFilters()` should remain stable.

### Step 8: Preserve the all Hook

Keep the `all` hook for both actions and filters.

For current-style callbacks, `all` receives:

```text
hook name, then original hook arguments
```

Its return value should be ignored.

### Step 9: Keep acceptedArgs

Keep `acceptedArgs` for current-style action/filter callbacks.

Do not apply `acceptedArgs` to native PSR-14 listeners. They receive one event object.

### Step 10: Update Documentation

Update `README.md` in this order:

1. current WordPress-style filter and action usage
2. dependency injection with `HooksInterface`
3. PSR-14 event listener registration with `listen()`
4. PSR-14 event dispatching
5. mutable filter-style events
6. user-defined stoppable events
7. migration notes from v2

## Proposed File Layout

```text
src/
  Hooks.php
  HooksInterface.php
  HooksManager.php
  Event/
    ActionEvent.php
    FilterEvent.php
    NamedEventInterface.php
  Psr14/
    ListenerProvider.php
```

`Hooks.php` remains the static facade. `HooksManager` remains the main implementation. `Psr14\ListenerProvider` is separate but exposed through `HooksManager`.

A separate `Psr14\EventDispatcher` class is not needed because `HooksManager` implements `EventDispatcherInterface` directly.

## Testing Plan

Add tests before or during the v3 implementation.

Important test cases:

- `HooksManager` implements `HooksInterface`
- `HooksManager` implements `EventDispatcherInterface`
- `dispatch()` returns the same event object it receives
- PSR-14 listeners run synchronously
- PSR-14 listener return values are ignored
- priority order is preserved
- same-priority listeners run in insertion order
- parent class listeners match child event objects
- interface listeners match implementing event objects
- user-defined stoppable events stop before the next listener
- listener exceptions bubble and prevent later listeners
- `listen()` registers listeners through the provider
- `applyFilters()` returns the final filtered value
- `doAction()` passes variadic arguments correctly
- `acceptedArgs` still slices current-style callback arguments
- `all` runs for `doAction()`
- `all` runs for `applyFilters()`
- `currentHook()` and `doingHook()` work during hook dispatch
- `didAction()` counts actions

## Risks

### Risk: PSR-14 Users Expect Return Values to Matter

PSR-14 ignores listener return values. Current-style `applyFilters()` can keep using return values, but native PSR-14 listeners must mutate event objects instead.

Mitigation: keep `applyFilters()` as the primary filter API and show clear PSR-14 examples.

### Risk: String Hook Names Do Not Feel Like PSR-14

String names are convenient but less type-safe than event classes.

Mitigation: document string hook names as the primary package style and event classes as the PSR-14 integration style.

### Risk: Static Facade Hides Dependency Injection

The existing `Hooks` facade is convenient, but PSR-14 is usually consumed through dependency injection.

Mitigation: keep the facade fully supported, keep `HooksInterface` for package-native dependency injection, and expose `EventDispatcherInterface` for framework integrations.

### Risk: HooksInterface and EventDispatcherInterface Have Different Roles

`HooksInterface` remains the main package contract, while `EventDispatcherInterface` covers only PSR-14 dispatching.

Mitigation: document both contracts clearly and avoid presenting PSR-14 as a replacement for the hooks API.

## Final Proposal

Version 3 should keep the existing WordPress-style hook model as the primary API and add PSR-14 compatibility around it.

The recommended implementation shape is:

- PHP `8.3+`
- `psr/event-dispatcher` dependency
- `Hooks` facade fully supported
- `HooksInterface` retained
- `HooksManager` retained as the main implementation
- `HooksManager` implements `EventDispatcherInterface` directly
- separate `Psr14\ListenerProvider`, exposed through `HooksManager`
- `listen()` convenience method for PSR-14 event listeners
- `ActionEvent` and `FilterEvent` for named hook bridging
- parent/interface listener matching
- `all` hook preserved for both actions and filters
- `acceptedArgs` preserved for current-style callbacks
- stoppable behavior supported only for user-defined events implementing `StoppableEventInterface`
