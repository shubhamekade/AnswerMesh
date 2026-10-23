<?php
/**
 * WordPress Multisite Network Manager & Policy Inheritance Engine.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class MultiSiteManager
{
    public const NETWORK_OPTION_KEY = 'answermesh_network_settings';

    /**
     * Check if running on WordPress Multisite.
     */
    public static function is_multisite(): bool
    {
        return function_exists('is_multisite') && is_multisite();
    }

    /**
     * Check if plugin is network activated.
     */
    public static function is_network_activated(): bool
    {
        if (!self::is_multisite()) {
            return false;
        }

        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return function_exists('is_plugin_active_for_network') && is_plugin_active_for_network(ANSWERMESH_BASENAME);
    }

    /**
     * Retrieve network-wide configuration settings.
     *
     * @return array<string, mixed>
     */
    public static function get_network_settings(): array
    {
        $defaults = [
            'allow_site_overrides' => true,
            'force_schema'         => true,
            'default_ai_provider'  => 'openai',
            'retention_days'       => 90,
            'sync_api_keys'        => false,
            'network_brand_name'   => '',
        ];

        if (!self::is_multisite()) {
            return $defaults;
        }

        $stored = function_exists('get_site_option') ? get_site_option(self::NETWORK_OPTION_KEY, []) : [];
        if (!is_array($stored)) {
            $stored = [];
        }

        return array_merge($defaults, $stored);
    }

    /**
     * Update network-wide settings (Super Admin only).
     *
     * @param array<string, mixed> $settings New network settings.
     * @return bool
     */
    public static function update_network_settings(array $settings): bool
    {
        if (!self::is_multisite()) {
            return false;
        }

        $current = self::get_network_settings();
        $updated = array_merge($current, $settings);

        if (function_exists('update_site_option')) {
            return update_site_option(self::NETWORK_OPTION_KEY, $updated);
        }

        return false;
    }

    /**
     * Check if child sites are permitted to override settings.
     */
    public static function allows_site_overrides(): bool
    {
        if (!self::is_multisite()) {
            return true;
        }

        $settings = self::get_network_settings();
        return (bool) ($settings['allow_site_overrides'] ?? true);
    }

    /**
     * Resolve an effective configuration value respecting network policy.
     *
     * @param string $group   Settings group.
     * @param string $key     Key name.
     * @param mixed  $default Fallback value.
     * @return mixed Effective setting.
     */
    public static function get_effective_setting(string $group, string $key, mixed $default = null): mixed
    {
        $network = self::get_network_settings();
        $site_settings = Settings::get_instance();

        // If network forbids per-site overrides, enforce network value if present
        if (!self::allows_site_overrides() && isset($network[$key])) {
            return $network[$key];
        }

        // Otherwise check local site setting first
        $local_val = $site_settings->get($group, $key, null);
        if ($local_val !== null) {
            return $local_val;
        }

        // Fallback to network setting if defined
        if (isset($network[$key])) {
            return $network[$key];
        }

        return $default;
    }
}
