# Decoupled WordPress Hooks (filters and actions)

Simple copy of WordPress' [WP_Hook class](https://github.com/WordPress/WordPress/blob/3cee52b3622cd6eab054db09074f220270a09243/wp-includes/class-wp-hook.php) with some adjustments to use outside of [WordPress](https://wordpress.org).

**No dependencies**!
## Why?

Because I like these WordPress "filters" and they "cost" almost nothing. And sine I use them in other projects it's easier to make them public anyways.

## Usage

Via **composer**

`composer require simplemediacode/hooks`

and then

`use SimpleMediaCode\Hooks\WP_Hook;`

See [ActionHooks.php](./example/ActionHooks.php) in `example` folder (which is autoloaded too). Or wrap in your own solution. 
I use them inside classes and/or in helper functions.
_Should_ be compatible with WordPress (works on my machine). "Tested" with PHP 8.2.22.

## Changelog

Read at [CHANGELOG.md](./CHANGELOG.md).

## Links

More about how to use WordPress hooks (filters and actions) read at [wordpress.org: "WP_Hook: Next Generation Actions and Filters"](https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/). Instead of `$wp_*` here use `$wphook_*` for compatiblity.

---

## Thanks to WordPress team and collaborators

Most of job done by [WordPress team and collaborators](https://github.com/WordPress/WordPress)

## License
This library is released under the GLP-2 license. See the complete license in the bundled [LICENSE](./LICENSE) file.