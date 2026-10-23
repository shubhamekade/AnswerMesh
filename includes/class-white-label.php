<?php
/**
 * Agency White-Label Branding & Rebranding Manager.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class WhiteLabel
{
    public const OPTION_KEY = 'answermesh_white_label';

    /**
     * Initialize white-label filters and branding overrides.
     */
    public static function init(): void
    {
        add_filter('answermesh_brand_name', [self::class, 'filter_brand_name']);
        add_filter('answermesh_support_url', [self::class, 'filter_support_url']);
    }

    /**
     * Retrieve white-label configuration settings.
     *
     * @return array<string, mixed>
     */
    public static function get_config(): array
    {
        $defaults = [
            'enabled'               => false,
            'plugin_name'           => 'AnswerMesh',
            'plugin_icon_url'       => '',
            'support_url'           => 'https://answermesh.com/support',
            'agency_name'           => '',
            'agency_logo_url'       => '',
            'hide_branding_credits' => false,
        ];

        $stored = get_option(self::OPTION_KEY, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        return array_merge($defaults, $stored);
    }

    /**
     * Save white-label configuration settings.
     *
     * @param array<string, mixed> $data New configuration.
     * @return bool
     */
    public static function save_config(array $data): bool
    {
        $current = self::get_config();

        $updated = [
            'enabled'               => !empty($data['enabled']),
            'plugin_name'           => !empty($data['plugin_name']) ? sanitize_text_field((string) $data['plugin_name']) : 'AnswerMesh',
            'plugin_icon_url'       => !empty($data['plugin_icon_url']) ? esc_url_raw((string) $data['plugin_icon_url']) : '',
            'support_url'           => !empty($data['support_url']) ? esc_url_raw((string) $data['support_url']) : 'https://answermesh.com/support',
            'agency_name'           => !empty($data['agency_name']) ? sanitize_text_field((string) $data['agency_name']) : '',
            'agency_logo_url'       => !empty($data['agency_logo_url']) ? esc_url_raw((string) $data['agency_logo_url']) : '',
            'hide_branding_credits' => !empty($data['hide_branding_credits']),
        ];

        return update_option(self::OPTION_KEY, $updated);
    }

    /**
     * Get active brand name.
     */
    public static function get_plugin_name(): string
    {
        $config = self::get_config();
        if ($config['enabled'] && !empty($config['plugin_name'])) {
            return (string) $config['plugin_name'];
        }
        return 'AnswerMesh';
    }

    /**
     * Get active support URL.
     */
    public static function get_support_url(): string
    {
        $config = self::get_config();
        if ($config['enabled'] && !empty($config['support_url'])) {
            return (string) $config['support_url'];
        }
        return 'https://answermesh.com/support';
    }

    /**
     * Check if AnswerMesh branding credits should be suppressed.
     */
    public static function is_credits_hidden(): bool
    {
        $config = self::get_config();
        return (bool) ($config['enabled'] && $config['hide_branding_credits']);
    }

    /**
     * Get branding package for admin UI.
     *
     * @return array<string, mixed>
     */
    public static function get_branding(): array
    {
        $config = self::get_config();

        return [
            'is_white_labeled'      => (bool) $config['enabled'],
            'plugin_name'           => self::get_plugin_name(),
            'plugin_icon_url'       => (string) $config['plugin_icon_url'],
            'support_url'           => self::get_support_url(),
            'agency_name'           => (string) ($config['agency_name'] ?: self::get_plugin_name()),
            'agency_logo_url'       => (string) $config['agency_logo_url'],
            'hide_branding_credits' => self::is_credits_hidden(),
        ];
    }

    /**
     * Filter callback for brand name.
     */
    public static function filter_brand_name(string $default = 'AnswerMesh'): string
    {
        return self::get_plugin_name();
    }

    /**
     * Filter callback for support URL.
     */
    public static function filter_support_url(string $default = 'https://answermesh.com/support'): string
    {
        return self::get_support_url();
    }
}
