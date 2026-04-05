<?php
namespace CMB\Core\Traits;

/**
 * Multi-language field support (8.3).
 *
 * Provides per-locale value storage and retrieval for fields
 * configured with 'multilingual' => true.
 *
 * Usage in field config:
 *   'multilingual' => true,
 *   'locales' => ['en', 'fr', 'es'],  // Optional; defaults to WP locale list
 *
 * Values are stored as: {field_id}_{locale} (e.g., title_en, title_fr)
 */
trait MultiLanguageTrait {
    /**
     * Get the current locale.
     */
    protected function getCurrentLocale(): string {
        if (function_exists('get_locale')) {
            $locale = get_locale();
            // Normalize: en_US → en
            return substr($locale, 0, 2);
        }
        return 'en';
    }

    /**
     * Get configured locales for a multilingual field.
     */
    protected function getFieldLocales(array $field): array {
        if (!empty($field['locales']) && is_array($field['locales'])) {
            return $field['locales'];
        }
        // Default set
        return ['en'];
    }

    /**
     * Get the meta key for a specific locale.
     */
    protected function getLocalizedKey(string $fieldId, string $locale): string {
        return $fieldId . '_' . $locale;
    }

    /**
     * Check if a field is multilingual.
     */
    protected function isMultilingual(array $field): bool {
        return !empty($field['multilingual']);
    }

    /**
     * Render language tabs for a multilingual field.
     */
    protected function renderLanguageTabs(string $fieldId, array $locales, string $currentLocale): string {
        $output = '<div class="cmb-lang-tabs" data-field="' . esc_attr($fieldId) . '">';
        $output .= '<ul class="cmb-lang-tab-nav">';
        foreach ($locales as $locale) {
            $active = ($locale === $currentLocale) ? ' cmb-lang-tab-active' : '';
            $output .= '<li class="cmb-lang-tab-item' . $active . '" data-lang="' . esc_attr($locale) . '">';
            $output .= '<a href="#">' . strtoupper(esc_html($locale)) . '</a>';
            $output .= '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    protected function closeLanguageTabs(): string {
        return '</div>';
    }
}
