<?php
/**
 * Plugin Name: Custom Meta Box Builder
 * Description: Create custom meta boxes with modern PHP architecture.
 * Version: 2.0
 * Author: Your Name
 */

 defined('ABSPATH') || exit;

 require_once __DIR__ . '/vendor/autoload.php';
 
 use CMB\Core\Plugin;
 
 $plugin = new Plugin();
 $plugin->boot();
