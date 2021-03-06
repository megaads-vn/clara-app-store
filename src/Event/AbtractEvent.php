<?php

namespace Megaads\Clara\Event;

abstract class AbtractEvent
{
    /**
     * Holds the event listeners.
     *
     * @var array
     */
    protected $listeners = null;

    public function __construct()
    {
        $this->listeners = collect([]);
    }

    /**
     * Adds a listener.
     *
     * @param string $hook      Hook name
     * @param mixed  $callback  Function to execute
     * @param int    $priority  Priority of the action
     * @param int    $arguments Number of arguments to accept
     */
    public function listen($hook, $callback, $priority = 20, $arguments = 1)
    {
        $this->listeners->push([
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'arguments' => $arguments,
        ]);
        return $this;
    }

    /**
     * Removes a listener.
     *
     * @param string $hook     Hook name
     * @param mixed  $callback Function to execute
     * @param int    $priority Priority of the action
     */
    public function remove($hook, $callback, $priority = 20)
    {
        if ($this->listeners) {
            $this->listeners->where('hook', $hook)
                ->filter(function ($listener) use ($callback) {
                    if ($callback instanceof \Closure) {
                        return (new HashedCallable($callback))->is($listener['callback']);
                    }
                    return $callback === $listener['callback'];
                })
                ->where('priority', $priority)
                ->each(function ($listener, $key) {
                    $this->listeners->forget($key);
                });
        }
    }

    /**
     * Remove all listeners with given hook in collection. If no hook, clear all listeners.
     *
     * @param string $hook Hook name
     */
    public function removeAll($hook = null)
    {
        if ($hook) {
            if ($this->listeners) {
                $this->listeners->where('hook', $hook)->each(function ($listener, $key) {
                    $this->listeners->forget($key);
                });
            }
        } else {
            // no hook was specified, so clear entire collection
            $this->listeners = collect([]);
        }
    }

    /**
     * Gets a sorted list of all listeners.
     *
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners->sortByDesc('priority');
    }

    /**
     * Gets the function.
     *
     * @param mixed $callback Callback
     *
     * @return mixed A closure, an array if "class@method" or a string if "function_name"
     */
    protected function getFunction($callback)
    {
        if (is_callable($callback) || (is_string($callback) && strpos($callback, '@'))) {
            return $callback;
        } else {
            throw new \Exception('$callback is not a Callable', 1);
        }
    }

    /**
     * Fires a new action.
     *
     * @param string $action Name of action
     * @param array  $args   Arguments passed to the action
     */
    abstract public function fire($action, $args);
}
