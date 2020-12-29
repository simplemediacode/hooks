# Changelog

## 1.0.6 (unpublished)

- `composer.json` update: authors.
- updated [README.md](./README.md): removing "repositories" note

## 1.0.5 - 1.0.2

 - composer tags, validation
 - do not load helper file. Implementation is up to Developer.
 
## 1.0.0 - 1.0.1

**initial**

- remove dependency from internal WordPress filesystem;
- renaming `$wp_`* to `$wphook_`* to avoid conflict wirth WordPress; 
- renaming `_*()` functions to "noraml". 
    - Example: `_wp_filter_build_unique_id()` => `wp_filter_build_unique_id()`