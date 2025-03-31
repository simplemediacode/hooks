<?php
namespace Simplemediacode\Hooks;

/**
 * Main implementation of the HooksInterface.
 */
class HooksManager implements HooksInterface
{
    /**
     * Storage for all hook instances.
     *
     * @var array<string, Hook>
     */
    protected array $hooks = [];
    
    /**
     * Storage for the number of times each action was triggered.
     *
     * @var array<string, int>
     */
    protected array $actionCounts = [];
    
    /**
     * Stores the list of current hooks being executed.
     * 
     * @var string[]
     */
    protected array $currentHooks = [];

    /**
     * Add a callback to a hook.
     * 
     * @param string   $tag           The name of the hook.
     * @param callable $callback      The callback function.
     * @param int      $priority      Optional. Priority of the callback. Default 10.
     * @param int      $accepted_args Optional. Number of arguments the callback accepts. Default 1.
     * @return bool Always returns true.
     */
    public function addHook(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        if (!isset($this->hooks[$tag])) {
            $this->hooks[$tag] = new Hook();
        }
        
        $this->hooks[$tag]->addHook($tag, $callback, $priority, $accepted_args);
        return true;
    }

    /**
     * Remove a callback from a hook.
     * 
     * @param string   $tag           The name of the hook.
     * @param callable $callback      The callback to remove.
     * @param int      $priority      Optional. Priority of the callback. Default 10.
     * @return bool Whether the function existed before it was removed.
     */
    public function removeHook(string $tag, callable $callback, int $priority = 10): bool
    {
        $removed = false;
        
        if (isset($this->hooks[$tag])) {
            $removed = $this->hooks[$tag]->removeHook($tag, $callback, $priority);
            
            if (!$this->hooks[$tag]->hasHooks()) {
                unset($this->hooks[$tag]);
            }
        }
        
        return $removed;
    }

    /**
     * Check if a hook has any callbacks registered.
     * 
     * @param string        $tag           The name of the hook.
     * @param callable|null $callback      Optional. Specific callback to check for. Default null.
     * @return bool|int False if no callbacks are registered, true if callbacks exist, 
     *                  or the priority of the specific callback if it exists.
     */
    public function hasHook(string $tag, ?callable $callback = null): bool|int
    {
        if (!isset($this->hooks[$tag])) {
            return false;
        }
        
        return $this->hooks[$tag]->hasHook($tag, $callback);
    }

    /**
     * Execute hooks registered for a specific tag.
     *
     * @param string $tag   The name of the hook.
     * @param mixed  $value The value to filter (for filters) or null (for actions).
     * @param array  $args  Arguments to pass to callbacks.
     * @return mixed Value after all hooks are applied (for filters) or original value (for actions).
     */
    public function executeHook(string $tag, mixed $value = null, array $args = []): mixed
    {
        // Track the hooks being processed
        $this->currentHooks[] = $tag;
        
        // For actions, increment the counter
        if ($value === null) {
            if (!isset($this->actionCounts[$tag])) {
                $this->actionCounts[$tag] = 1;
            } else {
                $this->actionCounts[$tag]++;
            }
        }
        
        // Process 'all' hooks first, if they exist
        if (isset($this->hooks['all'])) {
            // Clone the args and add the tag as the first argument
            $allArgs = array_merge([$tag], $args);
            $this->hooks['all']->doAllHook($allArgs);
        }
        
        // If no hooks for this tag, return the value unchanged
        if (!isset($this->hooks[$tag])) {
            array_pop($this->currentHooks);
            return $value;
        }
        
        // For filters, include $value as the first argument
        if ($value !== null) {
            $args = array_merge([$value], $args);
            $result = $this->hooks[$tag]->applyFilters($value, $args);
        } else {
            // For actions, just execute the callbacks
            $this->hooks[$tag]->doAction($args);
            $result = $value; // Actions return void, but our interface returns mixed
        }
        
        array_pop($this->currentHooks);
        return $result;
    }

    /**
     * Remove all callbacks from a hook.
     *
     * @param string    $tag      The name of the hook.
     * @param int|false $priority Optional. Priority to remove. Default false (all priorities).
     * @return bool Always returns true.
     */
    public function removeAllHooks(string $tag, $priority = false): bool
    {
        if (isset($this->hooks[$tag])) {
            $this->hooks[$tag]->removeAllHooks($priority);
            
            if ($priority === false || !$this->hooks[$tag]->hasHooks()) {
                unset($this->hooks[$tag]);
            }
        }
        
        return true;
    }
    
    /**
     * Check if a specific hook is currently being executed.
     *
     * @param string|null $tag Optional. The hook name to check. Null to check if any hook is being executed.
     * @return bool Whether the hook is being executed.
     */
    public function isExecutingHook(?string $tag = null): bool
    {
        if ($tag === null) {
            return !empty($this->currentHooks);
        }
        
        return in_array($tag, $this->currentHooks, true);
    }
    
    /**
     * Get the name of the currently executing hook.
     *
     * @return string|null The name of the current hook or null if no hook is executing.
     */
    public function getCurrentHook(): ?string
    {
        return end($this->currentHooks) ?: null;
    }

    /**
     * Get the number of times an action has been executed.
     *
     * @param string $tag The name of the action hook.
     * @return int The count.
     */
    public function getHookCount(string $tag): int
    {
        return $this->actionCounts[$tag] ?? 0;
    }
}