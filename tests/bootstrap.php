<?php

declare(strict_types=1);

// Patchwork must be loaded BEFORE any WP function stubs are defined
// so that Brain\Monkey can intercept them.
require_once dirname(__DIR__) . '/vendor/antecedent/patchwork/Patchwork.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Constants expected by the plugin
if (! defined('DOING_AUTOSAVE')) {
    define('DOING_AUTOSAVE', false);
}
if (! defined('WP_USE_THEMES')) {
    define('WP_USE_THEMES', false);
}
if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wp/');
}
