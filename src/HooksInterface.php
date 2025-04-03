<?php

namespace Simplemediacode\Hooks;

/**
 * Interface for the hooks system.
 * 
 * Defines the contract for implementing a hooks system with actions and filters.
 */
interface HooksInterface
{
    /**
     * Add a callback to a hook.
     * 
     * @param string   $hookName     The name of the hook to which the callback is added.
     * @param callable $callback     The callback function to be executed when the hook is triggered.
     * @param int      $priority     Optional. The priority at which the function should be executed. Default 10.
     * @param int      $acceptedArgs Optional. The number of arguments the function accepts. Default 1.
     * 
     * @return bool True on success, false on failure.
     */
    public function add(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool;
    
    /**
     * Remove a callback from a hook.
     * 
     * @param string   $hookName The name of the hook to which the callback is attached.
     * @param callable $callback The callback to be removed.
     * @param int      $priority The priority of the callback. Default 10.
     * 
     * @return bool True if the callback was found and removed, false otherwise.
     */
    public function remove(string $hookName, callable $callback, int $priority = 10): bool;
    
    /**
     * Remove all callbacks from a hook.
     * 
     * @param string   $hookName The name of the hook to remove callbacks from.
     * @param int|null $priority Optional. The priority to remove callbacks from. If null, removes all priorities. Default null.
     * 
     * @return bool True if callbacks were removed, false if no callbacks existed.
     */
    public function removeAll(string $hookName, ?int $priority = null): bool;
    
    /**
     * Check if a hook has callbacks registered.
     * 
     * @param string        $hookName The name of the hook to check.
     * @param callable|null $callback Optional. The specific callback to check for. Default null.
     * 
     * @return bool|int If $callback is null, returns boolean whether any callbacks are registered.
     *                  If $callback is specified, returns the priority of the callback if found, or false if not found.
     */
    public function has(string $hookName, ?callable $callback = null): bool|int;
    
    /**
     * Apply a filter hook to a value.
     * 
     * @param string $hookName The name of the filter hook.
     * @param mixed  $value    The value to filter.
     * @param mixed  ...$args  Additional arguments to pass to the callback functions.
     * 
     * @return mixed The filtered value after all hooked callbacks are applied.
     */
    public function filter(string $hookName, mixed $value, ...$args): mixed;
    
    /**
     * Execute an action hook.
     * 
     * @param string $hookName The name of the action hook.
     * @param mixed  ...$args  Arguments to pass to the callbacks.
     * 
     * @return void
     */
    public function action(string $hookName, ...$args): void;
    
    /**
     * Get information about the current hook being executed.
     * 
     * @return string|null The name of the current hook, or null if no hook is being executed.
     */
    public function current(): ?string;
    
    /**
     * Check if a hook is currently being executed.
     * 
     * @param string|null $hookName Optional. The name of the hook to check. If null, checks if any hook is executing. Default null.
     * 
     * @return bool True if the specified hook is executing, or if any hook is executing when no hook specified.
     */
    public function isExecuting(?string $hookName = null): bool;
    
    /**
     * Get the execution count for a hook.
     * 
     * @param string $hookName The name of the hook to check.
     * 
     * @return int The number of times the hook has been executed.
     */
    public function count(string $hookName): int;
}