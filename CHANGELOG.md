# Changelog

## 2.1.0

### Changes
- Refactored code to follow DRY and KISS principles
- Removed redundant `Hook` class in favor of direct implementation in `HooksManager`
- Standardized method naming across the codebase
- Improved method documentation with comprehensive PHPDoc comments
- Optimized hook callback execution
- Fixed interface implementation consistency

## 2.0.1

### Changes
- Changed namespace vendor name from `SimpeMediaCode` to `Simplemediacode`.


## 2.0.0

un-WordPress-ed
### Breaking Changes
- Complete refactoring to modern PHP architecture
- Renamed `WP_Hook` to `Hook`
- Introduced `HooksInterface` and `HooksManager`
- Removed global variables and functions
- Added static `Hooks` facade for backward compatibility
- Improved type safety with PHP 8+ return types

### Bug Fixes
- Added missing method definitions to `HooksInterface`: `getHookCount()`, `isExecutingHook()`, and `getCurrentHook()`
- Fixed consistency between interface methods and their implementations

## 1.0.8
### Changes
- PHP 8.3 and 8.4 support tags

## 1.0.7
### Changes
- Better compitability with PHP8
- Moves forward to the next element in `next` method.

## 1.0.6 (unpublished)

- `composer.json` update: authors.
- updated [README.md](./README.md): removing "repositories" note

## 1.0.5 - 1.0.2

 - composer tags, validation
 - do not load helper file. Implementation is up to Developer.
 
## 1.0.0 - 1.0.1

**initial**

- remove dependency from internal WordPress filesystem
- renaming `$wp_`* to `$wphook_`* to avoid conflict with WordPress 
- renaming `_*()` functions to "normal"
    - Example: `_wp_filter_build_unique_id()` => `wp_filter_build_unique_id()`