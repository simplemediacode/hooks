<?php
namespace Simplemediacode\Hooks;

/**
 * Main implementation of the hooks system.
 * 
 * Provides methods to register, manage, and execute hooks (actions and filters).
 */
class HooksManager implements HooksInterface {
    /** @var array<string, array<int, array<string, array>>> Hook storage by name and priority */
    private array $hooks = [];
    
    /** @var array<string, int> Action execution counts */
    private array $actionCounts = [];
    
    /** @var string[] Currently executing hooks */
    private array $currentHooks = [];
    
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
    public function add(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool {
        // Initialize priority level if needed
        $this->hooks[$hookName][$priority] ??= [];
        
        // Generate a unique identifier for this callback
        $id = $this->generateCallbackId($callback);
        
        // Store the callback
        $this->hooks[$hookName][$priority][$id] = [
            'callback' => $callback,
            'args' => $acceptedArgs
        ];
        
        // Sort priorities
        if (isset($this->hooks[$hookName]) && count($this->hooks[$hookName]) > 1) {
            ksort($this->hooks[$hookName], SORT_NUMERIC);
        }
        
        return true;
    }
    
    /**
     * Remove a callback from a hook.
     * 
     * @param string   $hookName The name of the hook to which the callback is attached.
     * @param callable $callback The callback to be removed.
     * @param int      $priority The priority of the callback. Default 10.
     * 
     * @return bool True if the callback was found and removed, false otherwise.
     */
    public function remove(string $hookName, callable $callback, int $priority = 10): bool {
        // If the hook doesn't exist, nothing to remove
        if (!isset($this->hooks[$hookName][$priority])) {
            return false;
        }
        
        $id = $this->generateCallbackId($callback);
        $exists = isset($this->hooks[$hookName][$priority][$id]);
        
        if ($exists) {
            unset($this->hooks[$hookName][$priority][$id]);
            
            // Clean up empty levels
            if (empty($this->hooks[$hookName][$priority])) {
                unset($this->hooks[$hookName][$priority]);
            }
            
            if (empty($this->hooks[$hookName])) {
                unset($this->hooks[$hookName]);
            }
        }
        
        return $exists;
    }
    
    /**
     * Remove all callbacks from a hook.
     * 
     * @param string   $hookName The name of the hook to remove callbacks from.
     * @param int|null $priority Optional. The priority to remove callbacks from. If null, removes all priorities. Default null.
     * 
     * @return bool True if callbacks were removed, false if no callbacks existed.
     */
    public function removeAll(string $hookName, ?int $priority = null): bool {
        if (!isset($this->hooks[$hookName])) {
            return false;
        }
        
        if ($priority === null) {
            unset($this->hooks[$hookName]);
        } else if (isset($this->hooks[$hookName][$priority])) {
            unset($this->hooks[$hookName][$priority]);
            
            if (empty($this->hooks[$hookName])) {
                unset($this->hooks[$hookName]);
            }
        }
        
        return true;
    }
    
    /**
     * Check if a hook has callbacks registered.
     * 
     * @param string        $hookName The name of the hook to check.
     * @param callable|null $callback Optional. The specific callback to check for. Default null.
     * 
     * @return bool|int If $callback is null, returns boolean whether any callbacks are registered.
     *                  If $callback is specified, returns the priority of the callback if found, or false if not found.
     */
    public function has(string $hookName, ?callable $callback = null): bool|int {
        // If the hook doesn't exist, return false
        if (!isset($this->hooks[$hookName])) {
            return false;
        }
        
        // If no specific callback is requested, just confirm hook exists with callbacks
        if ($callback === null) {
            // Return true if any priority has callbacks
            foreach ($this->hooks[$hookName] as $callbacks) {
                if (!empty($callbacks)) {
                    return true;
                }
            }
            return false;
        }
        
        // Check for a specific callback
        $id = $this->generateCallbackId($callback);
        
        foreach ($this->hooks[$hookName] as $priority => $callbacks) {
            if (isset($callbacks[$id])) {
                return $priority;
            }
        }
        
        return false;
    }
    
    /**
     * Apply a filter hook to a value.
     * 
     * @param string $hookName The name of the filter hook.
     * @param mixed  $value    The value to filter.
     * @param mixed  ...$args  Additional arguments to pass to the callback functions.
     * 
     * @return mixed The filtered value after all hooked callbacks are applied.
     */
    public function filter(string $hookName, mixed $value, ...$args): mixed {
        array_unshift($args, $value); // Put value as first arg
        return $this->execute($hookName, true, $args);
    }
    
    /**
     * Apply a filter hook to a value.
     * 
     * @param string $hookName The name of the filter hook.
     * @param mixed  $value    The value to filter.
     * @param mixed  ...$args  Additional arguments to pass to callbacks.
     * 
     * @return mixed The filtered value.
     */
    public function applyFilters(string $hookName, mixed $value, ...$args): mixed {
        return $this->filter($hookName, $value, ...$args);
    }
    
    /**
     * Execute an action hook.
     * 
     * @param string $hookName The name of the action hook.
     * @param mixed  ...$args  Arguments to pass to callbacks.
     * 
     * @return void
     */
    public function doAction(string $hookName, ...$args): void {
        $this->action($hookName, ...$args);
    }
    
    /**
     * Execute an action hook.
     * 
     * @param string $hookName The name of the action hook.
     * @param mixed  ...$args  Arguments to pass to the callbacks.
     * 
     * @return void
     */
    public function action(string $hookName, ...$args): void {
        $this->execute($hookName, false, $args);
    }
    
    /**
     * Internal method to execute hooks.
     * 
     * @param string $hookName The name of the hook to execute.
     * @param bool   $isFilter Whether this is a filter hook or an action hook.
     * @param array  $args     Arguments to pass to the callbacks.
     * 
     * @return mixed For filters, the filtered value; for actions, null.
     */
    private function execute(string $hookName, bool $isFilter, array $args): mixed {
        // Track execution
        $this->currentHooks[] = $hookName;
        $result = $isFilter ? $args[0] : null;
        
        // Count action executions
        if (!$isFilter) {
            $this->actionCounts[$hookName] = ($this->actionCounts[$hookName] ?? 0) + 1;
        }
        
        // Process 'all' hook first if it exists
        if (isset($this->hooks['all'])) {
            $allArgs = array_merge([$hookName], $args);
            $this->processHookCallbacks('all', $allArgs, false);
        }
        
        // If no callbacks for this hook, return the original value
        if (!isset($this->hooks[$hookName])) {
            array_pop($this->currentHooks);
            return $result;
        }
        
        // Process hook callbacks
        if ($isFilter) {
            $result = $this->processHookCallbacks($hookName, $args, true);
        } else {
            $this->processHookCallbacks($hookName, $args, false);
        }
        
        array_pop($this->currentHooks);
        return $result;
    }
    
    /**
     * Process callbacks for a specific hook.
     * 
     * @param string $hookName The name of the hook being processed.
     * @param array  $args     The arguments to pass to callbacks.
     * @param bool   $isFilter Whether this is processing a filter hook.
     * 
     * @return mixed For filters, the filtered value; for actions, null.
     */
    private function processHookCallbacks(string $hookName, array $args, bool $isFilter): mixed {
        if (!isset($this->hooks[$hookName])) {
            return $isFilter ? $args[0] : null;
        }
        
        $result = $isFilter ? $args[0] : null;
        $priorities = array_keys($this->hooks[$hookName]);
        $numArgs = count($args);
        
        foreach ($priorities as $priority) {
            foreach ($this->hooks[$hookName][$priority] as $callback) {
                if ($isFilter) {
                    // For filters, pass at most the number of accepted arguments
                    $acceptedArgs = $callback['args'];
                    
                    if ($acceptedArgs === 0) {
                        $result = call_user_func($callback['callback']);
                    } else if ($acceptedArgs >= $numArgs) {
                        $result = call_user_func_array($callback['callback'], $args);
                    } else {
                        $result = call_user_func_array(
                            $callback['callback'],
                            array_slice($args, 0, $acceptedArgs)
                        );
                    }
                    
                    // Update the first argument for the next callback
                    $args[0] = $result;
                } else {
                    // For actions, just execute with appropriate number of args
                    $acceptedArgs = $callback['args'];
                    
                    if ($acceptedArgs === 0) {
                        call_user_func($callback['callback']);
                    } else if ($acceptedArgs >= $numArgs) {
                        call_user_func_array($callback['callback'], $args);
                    } else {
                        call_user_func_array(
                            $callback['callback'],
                            array_slice($args, 0, $acceptedArgs)
                        );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Get information about the current hook being executed.
     * 
     * @return string|null The name of the current hook, or null if no hook is being executed.
     */
    public function current(): ?string {
        return end($this->currentHooks) ?: null;
    }
    
    /**
     * Check if a hook is currently being executed.
     * 
     * @param string|null $hookName Optional. The name of the hook to check. If null, checks if any hook is executing. Default null.
     * 
     * @return bool True if the specified hook is executing, or if any hook is executing when no hook specified.
     */
    public function isExecuting(?string $hookName = null): bool {
        if ($hookName === null) {
            return !empty($this->currentHooks);
        }
        
        return in_array($hookName, $this->currentHooks, true);
    }
    
    /**
     * Get the execution count for a hook.
     * 
     * @param string $hookName The name of the hook to check.
     * 
     * @return int The number of times the hook has been executed.
     */
    public function count(string $hookName): int {
        return $this->actionCounts[$hookName] ?? 0;
    }
    
    /**
     * Generate a unique ID for a callback.
     * 
     * @param callable $callback The callback for which to generate an ID.
     * 
     * @return string A unique string identifier for the callback.
     */
    private function generateCallbackId(callable $callback): string {
        if (is_string($callback)) {
            return $callback;
        }
        
        if (is_object($callback)) {
            if (is_a($callback, 'Closure')) {
                return spl_object_hash($callback);
            }
            
            // Handle object method calls
            return spl_object_hash($callback) . '::__invoke';
        }
        
        $callback = (array)$callback;
        
        if (is_object($callback[0])) {
            // Object method calls
            return spl_object_hash($callback[0]) . '::' . $callback[1];
        } elseif (is_string($callback[0])) {
            // Static method calls
            return $callback[0] . '::' . $callback[1];
        }
        
        return '';
    }
}