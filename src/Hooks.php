<?php

namespace Simplemediacode\Hooks;

/**
 * Static facade for the hooks system.
 * 
 * Provides convenient static access to hook functionality.
 */
class Hooks {
    /** @var HooksInterface|null Singleton instance */
    private static ?HooksInterface $instance = null;
    
    /**
     * Get the hooks instance.
     * 
     * @return HooksInterface The singleton hooks instance.
     */
    public static function getInstance(): HooksInterface {
        if (self::$instance === null) {
            self::$instance = new HooksManager();
        }
        
        return self::$instance;
    }
    
    /**
     * Set a custom hooks instance.
     * 
     * @param HooksInterface $instance The hooks implementation to use.
     * 
     * @return void
     */
    public static function setInstance(HooksInterface $instance): void {
        self::$instance = $instance;
    }
    
    /**
     * Add a filter hook.
     * 
     * @param string   $hookName     The name of the filter hook.
     * @param callable $callback     The callback function to be executed.
     * @param int      $priority     Optional. The priority. Default 10.
     * @param int      $acceptedArgs Optional. The number of arguments the function accepts. Default 1.
     * 
     * @return bool True on success, false on failure.
     */
    public static function addFilter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool {
        return self::getInstance()->add($hookName, $callback, $priority, $acceptedArgs);
    }
    
    /**
     * Add an action hook.
     * 
     * @param string   $hookName     The name of the action hook.
     * @param callable $callback     The callback function to be executed.
     * @param int      $priority     Optional. The priority. Default 10.
     * @param int      $acceptedArgs Optional. The number of arguments the function accepts. Default 1.
     * 
     * @return bool True on success, false on failure.
     */
    public static function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool {
        return self::getInstance()->add($hookName, $callback, $priority, $acceptedArgs);
    }
    
    /**
     * Apply filters to a value.
     * 
     * @param string $hookName The name of the filter hook.
     * @param mixed  $value    The value to filter.
     * @param mixed  ...$args  Additional arguments to pass to callbacks.
     * 
     * @return mixed The filtered value.
     */
    public static function applyFilters(string $hookName, mixed $value, ...$args): mixed {
        return self::getInstance()->filter($hookName, $value, ...$args);
    }
    
    /**
     * Execute an action.
     * 
     * @param string $hookName The name of the action hook.
     * @param mixed  ...$args  Arguments to pass to callbacks.
     * 
     * @return void
     */
    public static function doAction(string $hookName, ...$args): void {
        self::getInstance()->action($hookName, ...$args);
    }
    
    /**
     * Remove a hook.
     * 
     * @param string   $hookName The name of the hook.
     * @param callable $callback The callback to be removed.
     * @param int      $priority The priority of the callback. Default 10.
     * 
     * @return bool True if removed, false if not found.
     */
    public static function removeHook(string $hookName, callable $callback, int $priority = 10): bool {
        return self::getInstance()->remove($hookName, $callback, $priority);
    }
    
    /**
     * Check if a hook exists.
     * 
     * @param string        $hookName The name of the hook to check.
     * @param callable|null $callback Optional. The callback to check for. Default null.
     * 
     * @return bool|int If $callback is null, returns boolean for whether any callbacks exist.
     *                  If $callback is specified, returns priority if found, or false if not found.
     */
    public static function hasHook(string $hookName, ?callable $callback = null): bool|int {
        return self::getInstance()->has($hookName, $callback);
    }
    
    /**
     * Get action execution count.
     * 
     * @param string $hookName The name of the hook to check.
     * 
     * @return int The number of times the hook has been executed.
     */
    public static function didAction(string $hookName): int {
        return self::getInstance()->count($hookName);
    }
    
    /**
     * Check if a hook is currently executing.
     * 
     * @param string|null $hookName Optional. The name of the hook to check. Default null.
     * 
     * @return bool True if the hook is currently executing.
     */
    public static function doingHook(?string $hookName = null): bool {
        return self::getInstance()->isExecuting($hookName);
    }
    
    /**
     * Get the current hook being executed.
     * 
     * @return string|null The name of the current hook, or null if no hook is being executed.
     */
    public static function currentHook(): ?string {
        return self::getInstance()->current();
    }
}