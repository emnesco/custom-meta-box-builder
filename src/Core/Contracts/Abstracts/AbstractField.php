<?php

namespace CMB\Core\Contracts\Abstracts;

use CMB\Core\Contracts\FieldInterface;

/**
 * Class AbstractField
 * * Provides a base implementation for configuration-driven fields.
 * * @package CMB\Core\Contracts\Abstracts
 */
abstract class AbstractField implements FieldInterface {

    /**
     * Internal configuration storage.
     * @var array
     */
    protected array $config;

    /**
     * AbstractField constructor.
     * * @param array $config Configuration array containing field settings.
     */
    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * Retrieves the field name.
     * * @return string The field name or an empty string if not set.
     */
    public function getName(): string {
        return $this->config['name'] ?? '';
    }

    /**
     * Retrieves the field unique ID.
     * * @return string The field ID or an empty string if not set.
     */
    public function getId(): string {
        return $this->config['id'] ?? '';
    }

    /**
     * Retrieves the field display label.
     * * @return string The field label or an empty string if not set.
     */
    public function getLabel(): string {
        return $this->config['label'] ?? '';
    }

    /**
     * Resolves the current value of the field.
     * * If a value is explicitly set in config, it is returned. 
     * Otherwise, it returns an empty array for group/repeater types 
     * or null for standard fields.
     * * @return mixed Array for collections, mixed value, or null.
     */
    public function getValue() {
        // Return existing value if it is provided and not empty
        if (!empty($this->config['value'])) {
            return $this->config['value'];
        }

        /**
         * Determine the fallback return type.
         * A "collection" is defined as a group field or a field marked as repeatable.
         */
        $isCollection = (
            ($this->config['type'] ?? '') === 'group' ||
            (isset($this->config['repeat']) && $this->config['repeat'] ?? false) === true
        );

        return $isCollection ? [] : null;
    }

    /**
     * Renders extra HTML attributes from the 'attributes' config key.
     * Supports any key-value pairs; values are escaped with esc_attr().
     *
     * @return string Space-prefixed attribute string, or empty string.
     */
    protected function renderAttributes(): string
    {
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
}