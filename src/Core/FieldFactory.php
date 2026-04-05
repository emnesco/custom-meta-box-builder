<?php
/**
 * Factory for creating field instances from type strings with extensible type registry.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;

use CMB\Core\Contracts\FieldInterface;

/**
 * Centralized factory for creating field instances.
 *
 * Handles custom registered types and built-in namespace mapping.
 */
class FieldFactory {
    private static array $customTypes = [];

    /** Built-in type aliases for non-standard naming conventions. */
    private static array $typeAliases = [
        'flexible_content' => \CMB\Fields\FlexibleContentField::class,
        'checkbox_list'    => \CMB\Fields\Checkbox_listField::class,
    ];

    /**
     * Register a custom field type class.
     */
    public static function registerType( string $type, string $className ): void {
        if ( ! class_exists( $className ) ) {
            if ( function_exists( '_doing_it_wrong' ) ) {
                _doing_it_wrong( __METHOD__, sprintf( 'Class "%s" does not exist. See https://developer.wordpress.org/plugins/ for guidance on custom field types.', $className ), '2.1' );
            }
            return;
        }
        if ( ! is_subclass_of( $className, FieldInterface::class ) ) {
            if ( function_exists( '_doing_it_wrong' ) ) {
                _doing_it_wrong( __METHOD__, sprintf( 'Class "%s" must implement FieldInterface. Custom field classes must implement CMB\\Core\\Contracts\\FieldInterface.', $className ), '2.1' );
            }
            return;
        }
        self::$customTypes[ $type ] = $className;
    }

    /**
     * Resolve the class name for a field type.
     *
     * @return string|null The fully-qualified class name, or null if not found.
     */
    public static function resolveClass( string $type ): ?string {
        // Custom registered types take precedence
        if ( isset( self::$customTypes[ $type ] ) ) {
            return self::$customTypes[ $type ];
        }

        // Built-in aliases for non-standard naming
        if ( isset( self::$typeAliases[ $type ] ) ) {
            return self::$typeAliases[ $type ];
        }

        $fieldClass = 'CMB\\Fields\\' . ucfirst( $type ) . 'Field';
        if ( ! class_exists( $fieldClass ) ) {
            return null;
        }
        return $fieldClass;
    }

    /**
     * Create a field instance from a type and config array.
     *
     * @return FieldInterface|null The field instance, or null if type is unknown.
     */
    public static function create( string $type, array $config ): ?FieldInterface {
        $className = self::resolveClass( $type );
        if ( $className === null ) {
            return null;
        }

        return new $className( $config );
    }

    /**
     * Get all registered custom types.
     */
    public static function getCustomTypes(): array {
        return self::$customTypes;
    }
}
