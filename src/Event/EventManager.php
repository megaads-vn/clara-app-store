<?php

namespace Megaads\Clara\Event;

class EventManager
{
    /**
     * Holds all registered actions.
     *
     */
    protected $action;

    /**
     * Holds all registered views.
     *
     */
    protected $view;

    protected $hookIdx = [];
    protected $variable;

    /**
     * Construct the class.
     */
    public function __construct()
    {
        $this->action = new Action();
        $this->view = new View();
        $this->variable = new Variable();
    }

    /**
     * Get the action instance.
     *
     * @return Megaads\Clara\Event\Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the action instance.
     *
     * @return Megaads\Clara\Event\View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Get the variable instance._
     * 
     * @return Megaads\Clara\Event\Variable
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Add an action listener.
     *
     * @param string $hook      Hook name
     * @param mixed  $callback  Function to execute
     * @param int    $priority  Priority of the action
     * @param int    $arguments Number of arguments to accept
     */
    public function onAction($hook, $callback, $priority = 20, $arguments = 1)
    {
        $this->action->listen($hook, $callback, $priority, $arguments);
    }

    /**
     * Remove an action.
     *
     * @param string $hook     Hook name
     * @param mixed  $callback Function to execute
     * @param int    $priority Priority of the action
     */
    public function removeAction($hook, $callback, $priority = 20)
    {
        $this->action->remove($hook, $callback, $priority);
    }

    /**
     * Remove all actions.
     *
     * @param string $hook Hook name
     */
    public function removeAllActions($hook = null)
    {
        $this->action->removeAll($hook);
    }

    /**
     * Add a view.
     *
     * @param string $hook      Hook name
     * @param mixed  $callback  Function to execute
     * @param int    $priority  Priority of the action
     * @param int    $arguments Number of arguments to accept
     */
    public function onView($hook, $callback, $priority = 20, $arguments = 1)
    {
        $this->view->listen($hook, $callback, $priority, $arguments);
    }
    /**
     * Remove a view.
     *
     * @param string $hook     Hook name
     * @param mixed  $callback Function to execute
     * @param int    $priority Priority of the action
     */
    public function removeView($hook, $callback, $priority = 20)
    {
        $this->view->remove($hook, $callback, $priority);
    }

    /**
     * Remove all views.
     *
     * @param string $hook Hook name
     */
    public function removeAllViews($hook = null)
    {
        $this->view->removeAll($hook);
    }

    /**
     * Set a new action.
     *
     * Actions never return anything. It is merely a way of executing code at a specific time in your code.
     *
     * You can add as many parameters as you'd like.
     *
     * @param string $action     Name of hook
     * @param mixed  $parameter1 A parameter
     * @param mixed  $parameter2 Another parameter
     *
     * @return void
     */
    public function action()
    {
        $args = func_get_args();
        $hook = $args[0];
        unset($args[0]);
        $args = array_values($args);
        $this->action->fire($hook, $args);
    }

    /**
     * Set a new view.
     *
     * Views should always return something. The first parameter will always be the default value.
     *
     * You can add as many parameters as you'd like.
     *
     * @param string $action     Name of hook
     * @param mixed  $value      The original view value
     * @param mixed  $parameter1 A parameter
     * @param mixed  $parameter2 Another parameter
     *
     * @return void
     */
    public function view()
    {
        $args = func_get_args();
        $hook = $args[0];
        if (array_key_exists($hook, $this->hookIdx)) {
            $this->hookIdx[$hook]++;
        } else {
            $this->hookIdx[$hook] = 0;
        }
        $isMultiLayer = null;
        if (count($args) == 3) {
            $isMultiLayer = $args[2];
            unset($args[2]);
        }
        unset($args[0]);
        $args = array_values($args);
        if (!isset($args[0])) {
            $args[0] = [];
        }
        if (is_array($args[0])) {
            $args[0]['view_idx'] = $this->hookIdx[$hook];
        }
        return $this->view->fire($hook, $args, $isMultiLayer);
    }

    /**
     * Add an variable listener.
     *
     * @param string $hook      Hook name
     * @param mixed  $callback  Function to execute
     * @param int    $priority  Priority of the variable
     * @param int    $arguments Number of arguments to accept
     */
    public function onVariable($hook, $callback, $priority = 20, $arguments = 1)
    {
        $this->variable->listen($hook, $callback, $priority, $arguments);
    }

    /**
     * Remove an variable.
     *
     * @param string $hook     Hook name
     * @param mixed  $callback Function to execute
     * @param int    $priority Priority of the variable
     */
    public function removeVariable($hook, $callback, $priority = 20)
    {
        $this->variable->remove($hook, $callback, $priority);
    }

    /**
     * Remove all variables.
     *
     * @param string $hook Hook name
     */
    public function removeAllVariables($hook = null)
    {
        $this->variable->removeAll($hook);
    }

    public function variable() {
        $args = func_get_args();
        $num = func_num_args();
        $hook = $args[0];
        unset($args[0]);
        $default = $args[1];
        $args = array_values($args);
        $result = $this->variable->fire($hook, $args);

        if ($result['status'] == 'successful') {
            return $result['result'];
        } else {
            return $default;
        }
    }
    /**
     * Response an asset url.
     * 
     * @return String
     */
    public function asset($assetPath)
    {        
        return  '/modules/' . $assetPath;
    }
}
