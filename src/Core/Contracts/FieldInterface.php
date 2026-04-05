<?php
/**
 * Interface that all field types must implement.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core\Contracts;

interface FieldInterface {
    public function render(): string;
    public function sanitize(mixed $value): mixed;
    public function getValue(): mixed;
    public function validate(mixed $value): array;
    public function getType(): string;
    public function getId(): string;
    public function getConfig(): array;
    public function enqueueAssets(): void;
}
