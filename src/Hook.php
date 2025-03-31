<?php

namespace SimpleMediaCode\Hooks;

/**
 * Core class that implements the hook functionality.
 */
final class Hook implements \Iterator, \ArrayAccess
{
    /**
     * Hook callbacks.
     *
     * @var array
     */
    public array $callbacks = [];

    /**
     * The priority keys of actively running iterations of a hook.
     *
     * @var array
     */
    private array $iterations = [];

    /**
     * The current priority of actively running iterations of a hook.
     *
     * @var array
     */
    private array $currentPriority = [];

    /**
     * Number of levels this hook can be recursively called.
     *
     * @var int
     */
    private int $nestingLevel = 0;

    /**
     * Flag for if we're current doing an action, rather than a filter.
     *
     * @var bool
     */
    private bool $doingAction = false;

    /**
     * Hooks a function or method to a specific filter action.
     *
     * @param string   $tag          The name of the hook.
     * @param callable $callback     The callback to be run.
     * @param int      $priority     The order in which the callbacks execute.
     * @param int      $acceptedArgs The number of arguments the callback accepts.
     */
    public function addHook(string $tag, callable $callback, int $priority, int $acceptedArgs): void
    {
        $idx = $this->buildUniqueId($tag, $callback, $priority);

        $priorityExisted = isset($this->callbacks[$priority]);

        $this->callbacks[$priority][$idx] = [
            'function'      => $callback,
            'accepted_args' => $acceptedArgs,
        ];

        // If we're adding a new priority to the list, put them back in sorted order.
        if (!$priorityExisted && count($this->callbacks) > 1) {
            ksort($this->callbacks, SORT_NUMERIC);
        }

        if ($this->nestingLevel > 0) {
            $this->resortActiveIterations($priority, $priorityExisted);
        }
    }

    /**
     * Handles resetting callback priority keys mid-iteration.
     *
     * @param bool|int $newPriority     The priority of the new filter being added.
     * @param bool     $priorityExisted Flag for whether the priority already existed.
     */
    private function resortActiveIterations($newPriority = false, bool $priorityExisted = false): void
    {
        $newPriorities = array_keys($this->callbacks);

        // If there are no remaining hooks, clear out all running iterations.
        if (!$newPriorities) {
            foreach ($this->iterations as $index => $iteration) {
                $this->iterations[$index] = $newPriorities;
            }
            return;
        }

        $min = min($newPriorities);
        foreach ($this->iterations as $index => &$iteration) {
            $current = current($iteration);
            // If we're already at the end of this iteration, just leave the array pointer where it is.
            if (false === $current) {
                continue;
            }

            $iteration = $newPriorities;

            if ($current < $min) {
                array_unshift($iteration, $current);
                continue;
            }

            while (current($iteration) < $current) {
                if (false === next($iteration)) {
                    break;
                }
            }

            // If we have a new priority that didn't exist, but ::applyFilters() or ::doAction() thinks it's the current priority...
            if ($newPriority === $this->currentPriority[$index] && !$priorityExisted) {
                /*
                 * ...and the new priority is the same as what $this->iterations thinks is the previous
                 * priority, we need to move back to it.
                 */
                if (false === current($iteration)) {
                    // If we've already moved off the end of the array, go back to the last element.
                    $prev = end($iteration);
                } else {
                    // Otherwise, just go back to the previous element.
                    $prev = prev($iteration);
                }
                if (false === $prev) {
                    // Start of the array. Reset, and go about our day.
                    reset($iteration);
                } elseif ($newPriority !== $prev) {
                    // Previous wasn't the same. Move forward again.
                    next($iteration);
                }
            }
        }
        unset($iteration);
    }

    /**
     * Removes a callback from a specific hook.
     *
     * @param string   $tag      The hook name.
     * @param callable $callback The callback to remove.
     * @param int      $priority The priority of the callback.
     * @return bool Whether the callback existed before it was removed.
     */
    public function removeHook(string $tag, callable $callback, int $priority): bool
    {
        $functionKey = $this->buildUniqueId($tag, $callback, $priority);

        $exists = isset($this->callbacks[$priority][$functionKey]);
        if ($exists) {
            unset($this->callbacks[$priority][$functionKey]);
            if (!$this->callbacks[$priority]) {
                unset($this->callbacks[$priority]);
                if ($this->nestingLevel > 0) {
                    $this->resortActiveIterations();
                }
            }
        }
        return $exists;
    }

    /**
     * Checks if a specific hook has been registered.
     *
     * @param string        $tag      The name of the hook.
     * @param callable|bool $callback The callback to check for.
     * @return bool|int The priority of that hook or false if not attached.
     */
    public function hasHook(string $tag = '', $callback = false): bool|int
    {
        if (false === $callback) {
            return $this->hasHooks();
        }

        $functionKey = $this->buildUniqueId($tag, $callback, false);
        if (!$functionKey) {
            return false;
        }

        foreach ($this->callbacks as $priority => $callbacks) {
            if (isset($callbacks[$functionKey])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Checks if any callbacks have been registered for this hook.
     *
     * @return bool True if callbacks exist, false otherwise.
     */
    public function hasHooks(): bool
    {
        foreach ($this->callbacks as $callbacks) {
            if ($callbacks) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes all callbacks from the current hook.
     *
     * @param int|bool $priority Optional. Priority to remove. Default false.
     */
    public function removeAllHooks($priority = false): void
    {
        if (!$this->callbacks) {
            return;
        }

        if (false === $priority) {
            $this->callbacks = [];
        } elseif (isset($this->callbacks[$priority])) {
            unset($this->callbacks[$priority]);
        }

        if ($this->nestingLevel > 0) {
            $this->resortActiveIterations();
        }
    }

    /**
     * Calls the callback functions that have been added to a filter hook.
     *
     * @param mixed $value The value to filter.
     * @param array $args  Additional parameters to pass to callbacks.
     * @return mixed The filtered value.
     */
    public function applyFilters($value, array $args): mixed
    {
        if (!$this->callbacks) {
            return $value;
        }

        $nestingLevel = $this->nestingLevel++;

        $this->iterations[$nestingLevel] = array_keys($this->callbacks);
        $numArgs = count($args);

        do {
            $this->currentPriority[$nestingLevel] = current($this->iterations[$nestingLevel]);
            $priority = $this->currentPriority[$nestingLevel];

            foreach ($this->callbacks[$priority] as $callback) {
                if (!$this->doingAction) {
                    $args[0] = $value;
                }

                // Avoid the array_slice() if possible.
                if (0 == $callback['accepted_args']) {
                    $value = call_user_func($callback['function']);
                } elseif ($callback['accepted_args'] >= $numArgs) {
                    $value = call_user_func_array($callback['function'], $args);
                } else {
                    $value = call_user_func_array(
                        $callback['function'],
                        array_slice($args, 0, (int) $callback['accepted_args'])
                    );
                }
            }
        } while (false !== next($this->iterations[$nestingLevel]));

        unset($this->iterations[$nestingLevel]);
        unset($this->currentPriority[$nestingLevel]);

        $this->nestingLevel--;

        return $value;
    }

    /**
     * Calls the callback functions that have been added to an action hook.
     *
     * @param array $args Parameters to pass to callbacks.
     */
    public function doAction(array $args): void
    {
        $this->doingAction = true;
        $this->applyFilters('', $args);

        // If there are recursive calls to the current action, we haven't finished it until we get to the last one.
        if (!$this->nestingLevel) {
            $this->doingAction = false;
        }
    }

    /**
     * Processes the functions hooked into the 'all' hook.
     *
     * @param array $args Arguments to pass to callbacks. Passed by reference.
     */
    public function doAllHook(array &$args): void
    {
        $nestingLevel = $this->nestingLevel++;
        $this->iterations[$nestingLevel] = array_keys($this->callbacks);

        do {
            $priority = current($this->iterations[$nestingLevel]);
            foreach ($this->callbacks[$priority] as $callback) {
                call_user_func_array($callback['function'], $args);
            }
        } while (false !== next($this->iterations[$nestingLevel]));

        unset($this->iterations[$nestingLevel]);
        $this->nestingLevel--;
    }

    /**
     * Return the current priority level of the running iteration.
     *
     * @return int|false Current priority or false if not running.
     */
    public function currentPriority(): int|false
    {
        if (false === current($this->iterations)) {
            return false;
        }

        return current(current($this->iterations));
    }

    /**
     * Build Unique ID for storage and retrieval.
     *
     * @param string   $tag      The hook name.
     * @param callable $callback The callback function.
     * @param int|bool $priority The priority of the callback.
     * @return string Unique function ID for usage as array key.
     */
    public function buildUniqueId(string $tag, callable $callback, $priority): string
    {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_object($callback)) {
            // Closures are currently implemented as objects.
            $callback = array($callback, '');
        } else {
            $callback = (array) $callback;
        }

        if (is_object($callback[0])) {
            // Object class calling.
            return spl_object_hash($callback[0]) . $callback[1];
        } elseif (is_string($callback[0])) {
            // Static calling.
            return $callback[0] . '::' . $callback[1];
        }

        return '';
    }

    /**
     * Determines whether an offset value exists.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->callbacks[$offset]);
    }

    /**
     * Retrieves a value at a specified offset.
     */
    public function offsetGet($offset): mixed
    {
        return isset($this->callbacks[$offset]) ? $this->callbacks[$offset] : null;
    }

    /**
     * Sets a value at a specified offset.
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->callbacks[] = $value;
        } else {
            $this->callbacks[$offset] = $value;
        }
    }

    /**
     * Unsets a specified offset.
     */
    public function offsetUnset($offset): void
    {
        unset($this->callbacks[$offset]);
    }

    /**
     * Returns the current element.
     */
    public function current(): mixed
    {
        return current($this->callbacks);
    }

    /**
     * Moves forward to the next element.
     */
    public function next(): void
    {
        next($this->callbacks);
    }

    /**
     * Returns the key of the current element.
     */
    public function key(): mixed
    {
        return key($this->callbacks);
    }

    /**
     * Checks if current position is valid.
     */
    public function valid(): bool
    {
        return key($this->callbacks) !== null;
    }

    /**
     * Rewinds the Iterator to the first element.
     */
    public function rewind(): void
    {
        reset($this->callbacks);
    }
}