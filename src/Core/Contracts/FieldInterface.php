<?php
namespace CMB\Core\Contracts;

interface FieldInterface {
    public function render(): string;
    public function sanitize($value);
    public function getValue(int $postId);
}