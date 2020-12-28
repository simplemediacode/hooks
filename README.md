# Decoupled WordPress Hooks (filters and actions)

Simple copy of WordPress' [WP_Hook class](https://github.com/WordPress/WordPress/blob/3cee52b3622cd6eab054db09074f220270a09243/wp-includes/class-wp-hook.php) with some adjustments to use outside of [WordPress](https://wordpress.org).

**No dependencies**!

## Usage

Via **composer**

`composer require simplemediacode/hooks`

and then

`use SimpleMediaCode\Hooks\WP_Hook;`

See [ActionHooks.php](./example/ActionHooks.php) in example folder. Or wrap in your own solution. 
I use them inside classes and/or in helper functions.
_Should_ be compatible with WordPress (works on my machine). "Tested" with PHP 7.4 and PHP 8.0.
## Why?

Because I like these WordPress "filters" and they "cost" almost nothing. And sine I use them in other projects it's easier to make them public anyways.

---

Most of job done by [WordPress team and collaborators](https://github.com/WordPress/WordPress)