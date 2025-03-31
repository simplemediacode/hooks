<?php

namespace SimpleMediaCode\Hooks;

/**
 * Static facade for the hooks system.
 * Provides a simple global access point to hook functionality.
 */
class Hooks
{
    /**
     * The singleton instance of the hooks manager.
     *
     * @var HooksInterface|null
     */
    private static ?HooksInterface $instance = null;

    /**
     * Get the hooks manager instance.
     *
     * @return HooksInterface
     */
    public static function getInstance(): HooksInterface
    {
        if (self::$instance === null) {
            self::$instance = new HooksManager();
        }
        
        return self::$instance;
    }
    
    /**
     * Set a custom hooks manager implementation.
     *
     * @param HooksInterface $manager
     * @return void
     */
    public static function setInstance(HooksInterface $manager): void
    {
        self::$instance = $manager;
    }
    
    /**
     * Add a callback to a filter hook.
     *
     * @param string   $tag           The name of the filter hook.
     * @param callable $callback      The callback to run.
     * @param int      $priority      Optional. Priority of execution. Default 10.
     * @param int      $accepted_args Optional. Number of arguments the callback accepts. Default 1.
     * @return bool Always returns true.
     */
    public static function addFilter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return self::getInstance()->addHook($tag, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a callback to an action hook.
     *
     * @param string   $tag           The name of the action hook.
     * @param callable $callback      The callback to run.
     * @param int      $priority      Optional. Priority of execution. Default 10.
     * @param int      $accepted_args Optional. Number of arguments the callback accepts. Default 1.
     * @return bool Always returns true.
     */
    public static function addAction(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return self::getInstance()->addHook($tag, $callback, $priority, $accepted_args);
    }
    
    /**
     * Apply filters to a value.
     *
     * @param string $tag  The name of the filter hook.
     * @param mixed  $value The value to filter.
     * @param mixed  ...$args Additional arguments to pass to callbacks.
     * @return mixed The filtered value.
     */
    public static function applyFilters(string $tag, mixed $value, ...$args): mixed
    {
        return self::getInstance()->executeHook($tag, $value, $args);
    }
    
    /**
     * Execute actions.
     *
     * @param string $tag The name of the action hook.
     * @param mixed  ...$args Arguments to pass to callbacks.
     * @return void
     */
    public static function doAction(string $tag, ...$args): void
    {
        self::getInstance()->executeHook($tag, null, $args);
    }
    
    /**
     * Remove a callback from a hook.
     *
     * @param string   $tag      The name of the hook.
     * @param callable $callback The callback to remove.
     * @param int      $priority Optional. Priority of the callback. Default 10.
     * @return bool Whether the function existed before it was removed.
     */
    public static function removeHook(string $tag, callable $callback, int $priority = 10): bool
    {
        return self::getInstance()->removeHook($tag, $callback, $priority);
    }
    
    /**
     * Check if hook has a specific callback.
     *
     * @param string        $tag      The name of the hook.
     * @param callable|null $callback Optional. The callback to check for.
     * @return bool|int False if the callback doesn't exist, the priority if it does.
     */
    public static function hasHook(string $tag, ?callable $callback = null): bool|int
    {
        return self::getInstance()->hasHook($tag, $callback);
    }
    
    /**
     * Get the number of times an action has been executed.
     *
     * @param string $tag The name of the action hook.
     * @return int The count.
     */
    public static function didAction(string $tag): int
    {
        return self::getInstance()->getHookCount($tag);
    }
    
    /**
     * Check if a hook is currently being processed.
     *
     * @param string|null $tag Optional. The hook to check. Default null.
     * @return bool Whether the hook is being processed.
     */
    public static function doingHook(?string $tag = null): bool
    {
        return self::getInstance()->isExecutingHook($tag);
    }
    
    /**
     * Get the name of the current hook being processed.
     *
     * @return string|null The name of the hook or null if no hook is being processed.
     */
    public static function currentHook(): ?string
    {
        return self::getInstance()->getCurrentHook();
    }
}