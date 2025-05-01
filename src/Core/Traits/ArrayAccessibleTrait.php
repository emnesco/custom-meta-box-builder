<?php
namespace CMB\Core\Traits;

trait ArrayAccessibleTrait {
    protected array $config = [];

    public function __get(string $key) {
        return $this->config[$key] ?? null;
    }

    public function __isset(string $key): bool {
        return isset($this->config[$key]);
    }
}