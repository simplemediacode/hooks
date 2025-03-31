<?php

namespace Simplemediacode\Hooks;

/**
 * Interface for the hooks system.
 */
interface HooksInterface
{
    /**
     * Add a callback to a hook.
     *
     * @param string   $tag           The name of the hook.
     * @param callable $callback      The callback function.
     * @param int      $priority      Optional. Priority of the callback. Default 10.
     * @param int      $accepted_args Optional. Number of arguments the callback accepts. Default 1.
     * @return bool Always returns true.
     */
    public function addHook(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool;
    
    /**
     * Remove a callback from a hook.
     *
     * @param string   $tag           The name of the hook.
     * @param callable $callback      The callback to remove.
     * @param int      $priority      Optional. Priority of the callback. Default 10.
     * @return bool Whether the function existed before it was removed.
     */
    public function removeHook(string $tag, callable $callback, int $priority = 10): bool;
    
    /**
     * Check if a hook has any callbacks registered.
     *
     * @param string        $tag           The name of the hook.
     * @param callable|null $callback      Optional. Specific callback to check for. Default null.
     * @return bool|int False if no callbacks are registered, true if callbacks exist, 
     *                  or the priority of the specific callback if it exists.
     */
    public function hasHook(string $tag, ?callable $callback = null): bool|int;
    
    /**
     * Execute hooks registered for a specific tag.
     *
     * @param string $tag  The name of the hook.
     * @param mixed  $args Arguments to pass to callbacks.
     * @return mixed Value after all hooks are applied (for filters) or void (for actions).
     */
    public function executeHook(string $tag, mixed $value = null, array $args = []): mixed;
    
    /**
     * Remove all callbacks from a hook.
     *
     * @param string    $tag      The name of the hook.
     * @param int|false $priority Optional. Priority to remove. Default false (all priorities).
     * @return bool Always returns true.
     */
    public function removeAllHooks(string $tag, $priority = false): bool;
    
    /**
     * Get the number of times an action has been executed.
     *
     * @param string $tag The name of the action hook.
     * @return int The count.
     */
    public function getHookCount(string $tag): int;
    
    /**
     * Check if a hook is currently being processed.
     *
     * @param string|null $tag Optional. The hook to check. Default null.
     * @return bool Whether the hook is being processed.
     */
    public function isExecutingHook(?string $tag = null): bool;
    
    /**
     * Get the name of the current hook being processed.
     *
     * @return string|null The name of the hook or null if no hook is being processed.
     */
    public function getCurrentHook(): ?string;
}