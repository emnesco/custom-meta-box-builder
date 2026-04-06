<?php
declare(strict_types=1);

/**
 * User profile meta field registration, rendering, and save logic.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractMetaManager;
use CMB\Core\RenderContext\UserContext;
use CMB\Core\Storage\StorageInterface;
use CMB\Core\Storage\UserMetaStorage;

class UserMetaManager extends AbstractMetaManager {
    private array $fields = [];

    public function __construct( ?StorageInterface $storage = null ) {
        $this->storage = $storage ?? new UserMetaStorage();
    }

    public function add(array $fields): void {
        $this->fields = array_merge($this->fields, $fields);
    }

    public function register(): void {
        add_action('show_user_profile', [$this, 'renderFields']);
        add_action('edit_user_profile', [$this, 'renderFields']);
        add_action('personal_options_update', [$this, 'saveFields']);
        add_action('edit_user_profile_update', [$this, 'saveFields']);
    }

    public function renderFields($user): void {
        if (empty($this->fields)) return;

        $context       = new UserContext((int) $user->ID, $this->storage);
        $fieldRenderer = new FieldRenderer($context);

        echo '<h2>Additional Information</h2>';
        echo '<table class="form-table">';

        wp_nonce_field('cmb_user_save', 'cmb_user_nonce');

        foreach ($this->fields as $field) {
            echo '<tr>';
            echo '<th><label for="cmb-' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label></th>';
            echo '<td>';
            echo $fieldRenderer->render($field);
            echo '</td></tr>';
        }

        echo '</table>';
    }

    public function saveFields(int $userId): void {
        if (!isset($_POST['cmb_user_nonce']) || !wp_verify_nonce($_POST['cmb_user_nonce'], 'cmb_user_save')) {
            return;
        }
        if (!current_user_can('edit_user', $userId)) {
            return;
        }

        $this->saveFieldSet($this->fields, $userId, 'user');
    }
}
