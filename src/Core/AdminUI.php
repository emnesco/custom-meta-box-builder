<?php
namespace CMB\Core;

use CMB\Core\AdminUI\Router;

class AdminUI {
    public static function register(): void {
        Router::register();
    }
}
