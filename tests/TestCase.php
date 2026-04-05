<?php

declare(strict_types=1);

namespace Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for all unit tests in this suite.
 *
 * Sets up Brain\Monkey before each test and tears it down afterwards so that
 * WordPress function mocks are isolated per test.
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub the most commonly-used WordPress escaping / sanitization
        // functions so that field render() calls don't fail when Brain\Monkey
        // has not been given explicit expectations for them.
        Monkey\Functions\stubs([
            'esc_attr'             => static fn(string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8'),
            'esc_html'             => static fn(string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8'),
            'esc_textarea'         => static fn(string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8'),
            'esc_url'              => static fn(string $u): string => filter_var($u, FILTER_SANITIZE_URL) ?: '',
            'esc_url_raw'          => static fn(string $u): string => filter_var($u, FILTER_SANITIZE_URL) ?: '',
            'sanitize_text_field'  => static fn($v): string => strip_tags((string) $v),
            'sanitize_email'       => static fn(string $e): string => filter_var($e, FILTER_SANITIZE_EMAIL),
            'absint'               => static fn($v): int => abs((int) $v),
            'selected'             => static function ($sel, $cur = true, bool $echo = true): string {
                return $sel == $cur ? 'selected="selected"' : '';
            },
            'checked'              => static function ($chk, $cur = true, bool $echo = true): string {
                return $chk == $cur ? 'checked="checked"' : '';
            },
            // Meta functions are NOT stubbed here so that tests can set
            // expectations via Functions\expect(). Stub them in individual
            // tests if needed via Functions\stubs().
            'get_users'            => static fn(): array => [],
            'get_posts'            => static fn(): array => [],
            'get_terms'            => static fn(): array => [],
            'map_deep'             => static function ($value, callable $cb): mixed {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $value[$k] = map_deep($v, $cb);
                    }
                    return $value;
                }
                return call_user_func($cb, $value);
            },
            'wp_kses_post'         => static fn($d): mixed => $d,
            'apply_filters'        => static fn(string $tag, $value) => $value,
            'do_action'            => static fn() => null,
            '_doing_it_wrong'      => static fn() => null,
            'is_wp_error'          => static fn(): bool => false,
        ]);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
