<?php

namespace App\Widgets;

abstract class BaseWidget
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    abstract public function render(): string;

    protected function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
