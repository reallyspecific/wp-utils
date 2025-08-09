<?php

namespace ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Testing;

use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Mock_WP;
class EventStack
{
    protected array $actions = ['hooks' => [], 'history' => []];
    protected array $filters = ['hooks' => [], 'history' => []];
    protected Mock_WP $mock;
    public function __construct(Mock_WP $mock)
    {
        $this->mock = $mock;
    }
    public function get_function_callbacks(): array
    {
        return ['do_action' => [$this, 'do_action'], 'did_action' => [$this, 'did_action'], 'add_action' => [$this, 'add_action'], 'has_action' => [$this, 'has_action'], 'remove_action' => [$this, 'remove_action'], 'apply_filters' => [$this, 'apply_filters'], 'add_filter' => [$this, 'add_filter'], 'has_filter' => [$this, 'has_filter'], 'remove_filter' => [$this, 'remove_filter']];
    }
    public function add_hook(&$hooks, $event_name, $callback, $priority = 10)
    {
        $hooks['hooks'][$event_name] = [...$hooks['hooks'][$event_name] ?? [], $priority => [...$hooks['hooks'][$event_name][$priority] ?? [], $callback]];
    }
    public function remove_hook(&$hooks, $event_name, $priority = null, $callback = null)
    {
        if (is_null($callback) && is_null($priority)) {
            $hooks['hooks'][$event_name] = [];
        } elseif (is_null($callback)) {
            $hooks['hooks'][$event_name][$priority] = [];
        } else {
            $hooks['hooks'][$event_name][$priority] = array_filter($hooks['hooks'][$event_name][$priority], function ($hook_callback) use ($callback) {
                return $hook_callback !== $callback;
            });
        }
    }
    protected function do_hook(&$hooks, $event_name, ...$args)
    {
        $priorities = array_keys($hooks['hooks'][$event_name] ?? []);
        foreach ($priorities as $priority) {
            // check to make sure it hasn't been removed by another callback
            if (!isset($hooks['hooks'][$event_name][$priority])) {
                continue;
            }
            $callbacks = $hooks['hooks'][$event_name][$priority];
            foreach ($callbacks as $callback) {
                // check to make sure it hasn't been removed by another callback
                if (!in_array($callback, $hooks['hooks'][$event_name][$priority], \true)) {
                    continue;
                }
                $hooks['history'][] = ['hook' => $event_name, 'priority' => $priority, 'callback' => $callback, 'args' => $args];
            }
        }
    }
    public function did_hook(&$hooks, $event_name): bool
    {
        $found = array_filter($hooks['history'], function ($hook) use ($event_name) {
            return $hook['hook'] === $event_name;
        });
        return !empty($found);
    }
    public function has_hook(&$hooks, $event_name, $callback = null): bool
    {
        if (is_null($callback)) {
            return isset($hooks['hooks'][$event_name]);
        } else {
            $contained = array_filter($hooks['hooks'][$event_name], function ($hook) use ($callback) {
                return in_array($callback, $hook, \true);
            });
            return !empty($contained);
        }
    }
    public function do_action($event_name, ...$args)
    {
        $this->do_hook($this->actions, $event_name, ...$args);
    }
    public function did_action($event_name)
    {
        $this->did_hook($this->actions, $event_name);
    }
    public function add_action($event_name, $callback, $priority = 10)
    {
        $this->add_hook($this->actions, $event_name, $callback, $priority);
    }
    public function has_action($event_name)
    {
        return $this->has_hook($this->actions, $event_name);
    }
    public function remove_action($event_name, $callback = null, $priority = null)
    {
        $this->remove_hook($this->actions, $event_name, $priority, $callback);
    }
    public function add_filter($filter_name, $callback, $priority = 10)
    {
        $this->add_hook($this->filters, $filter_name, $callback, $priority);
    }
    public function apply_filters($filter_name, $value, ...$args)
    {
        $this->do_hook($this->filters, $filter_name, $value, ...$args);
        return $value;
    }
    public function has_filter($filter_name)
    {
        return $this->has_hook($this->filters, $filter_name);
    }
    public function remove_filter($filter_name, $callback = null, $priority = null)
    {
        $this->remove_hook($this->filters, $filter_name, $priority, $callback);
    }
}
