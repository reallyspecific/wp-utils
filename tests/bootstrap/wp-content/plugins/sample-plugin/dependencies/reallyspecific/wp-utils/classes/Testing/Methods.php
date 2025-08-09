<?php

namespace ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Testing;

use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Mock_WP;
class Methods
{
    protected array $methods = [];
    protected Mock_WP $mock;
    public function __construct(Mock_WP $mock)
    {
        $this->mock = $mock;
    }
    public function add_function($function_name, $callback, $register = \true): void
    {
        $this->methods[$function_name] = ['history' => [], 'callback' => $callback, 'registered' => \false];
        if ($register) {
            $this->register_function($function_name);
        }
    }
    public function add_functions($methods, $register = \false): void
    {
        foreach ($methods as $function_name => $callback) {
            $this->add_function($function_name, $callback, $register);
        }
    }
    public function execute_function($function_name, ...$args): mixed
    {
        $this->methods[$function_name]['history'][] = ['trace' => debug_backtrace(), 'args' => $args];
        if (is_callable($this->methods[$function_name]['callback'])) {
            return call_user_func_array($this->methods[$function_name]['callback'], $args);
        } else {
            return $this->methods[$function_name]['callback'];
        }
    }
    public function register_function(string $function_name)
    {
        if (function_exists($function_name)) {
            return \false;
        }
        $parts = explode('\\', $function_name);
        $name = array_pop($parts);
        $namespace = empty($parts) ? '' : 'namespace ' . implode('\\', $parts) . ';' . \PHP_EOL;
        $declaration = <<<EOF
        \t{$namespace}
        \tfunction {$name}(...\$args) {
        \t\treturn \\ReallySpecific\\Utils\\Mock_WP::handle_function( '{$function_name}', ...\$args);
        \t}
        EOF;
        eval($declaration);
        $this->methods[$function_name]['registered'] = \true;
        return \true;
    }
    public function register()
    {
        foreach ($this->methods as $function_name => $function) {
            if ($function['registered']) {
                continue;
            }
            $this->register_function($function_name);
        }
    }
}
