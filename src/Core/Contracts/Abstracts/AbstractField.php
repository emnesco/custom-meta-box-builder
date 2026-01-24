<?php
namespace CMB\Core\Contracts\Abstracts;

use CMB\Core\Contracts\FieldInterface;

abstract class AbstractField implements FieldInterface {
    protected array $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function getId(): string {
        return $this->config['id'] ?? '';
    }

    public function getLabel(): string {
        return $this->config['label'] ?? '';
    }

    public function getValue() {
        return $this->config['value'] ?? [];
    }
}
