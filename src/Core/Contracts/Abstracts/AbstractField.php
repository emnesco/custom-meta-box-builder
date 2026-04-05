<?php

namespace CMB\Core\Contracts\Abstracts;

use CMB\Core\Contracts\FieldInterface;

abstract class AbstractField implements FieldInterface {

    protected array $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function getName(): string {
        return $this->config['name'] ?? '';
    }

    public function getId(): string {
        return $this->config['id'] ?? '';
    }

    public function getLabel(): string {
        return $this->config['label'] ?? '';
    }

    public function getValue(): mixed {
        if (!empty($this->config['value'])) {
            return $this->config['value'];
        }

        // Return default value if set
        if (isset($this->config['default'])) {
            return $this->config['default'];
        }

        $isCollection = (
            ($this->config['type'] ?? '') === 'group' ||
            ($this->config['repeat'] ?? false) === true
        );

        return $isCollection ? [] : null;
    }

    /**
     * Validate a value against field rules. Returns array of error messages (empty = valid).
     */
    public function validate(mixed $value): array {
        $errors = [];
        $rules = $this->config['validate'] ?? [];

        if (!empty($this->config['required']) && !in_array('required', $rules, true)) {
            array_unshift($rules, 'required');
        }

        foreach ($rules as $rule) {
            $parts = explode(':', $rule, 2);
            $ruleName = $parts[0];
            $ruleParam = $parts[1] ?? null;
            $label = $this->getLabel() ?: $this->getId();

            switch ($ruleName) {
                case 'required':
                    if ($value === '' || $value === null || $value === []) {
                        $errors[] = sprintf('%s is required.', $label);
                    }
                    break;
                case 'email':
                    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = sprintf('%s must be a valid email address.', $label);
                    }
                    break;
                case 'url':
                    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[] = sprintf('%s must be a valid URL.', $label);
                    }
                    break;
                case 'min':
                    if ($ruleParam !== null && strlen((string)$value) < (int)$ruleParam) {
                        $errors[] = sprintf('%s must be at least %s characters.', $label, $ruleParam);
                    }
                    break;
                case 'max':
                    if ($ruleParam !== null && strlen((string)$value) > (int)$ruleParam) {
                        $errors[] = sprintf('%s must be no more than %s characters.', $label, $ruleParam);
                    }
                    break;
                case 'numeric':
                    if ($value !== '' && !is_numeric($value)) {
                        $errors[] = sprintf('%s must be a number.', $label);
                    }
                    break;
                case 'pattern':
                    if ($ruleParam !== null && $value !== '' && !preg_match('/' . $ruleParam . '/', (string)$value)) {
                        $errors[] = sprintf('%s format is invalid.', $label);
                    }
                    break;
            }
        }

        return $errors;
    }

    protected function renderAttributes(): string {
        $attrs = $this->config['attributes'] ?? [];
        if (empty($attrs) || !is_array($attrs)) {
            return '';
        }
        $html = '';
        foreach ($attrs as $attr => $val) {
            $html .= ' ' . esc_attr($attr) . '="' . esc_attr((string) $val) . '"';
        }
        return $html;
    }

    protected function isRequired(): bool {
        return !empty($this->config['required']);
    }

    protected function requiredAttr(): string {
        return $this->isRequired() ? ' required' : '';
    }
}
