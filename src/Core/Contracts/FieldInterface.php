<?php
namespace CMB\Core\Contracts;

interface FieldInterface {
    public function render(): string;
    public function sanitize(mixed $value): mixed;
    public function getValue(): mixed;
    public function validate(mixed $value): array;
}
