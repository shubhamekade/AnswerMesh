<?php
/**
 * Third-party SEO plugin detection and compatibility manager.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Compatibility
{
    /**
     * Detect active third-party SEO plugins.
     *
     * @return array<string, string>
     */
    public static function get_active_seo_plugins(): array
    {
        $plugins = [];

        // Yoast SEO.
        if (defined('WPSEO_VERSION')) {
            $plugins['yoast'] = 'Yoast SEO ' . WPSEO_VERSION;
        }

        // Rank Math.
        if (defined('RANK_MATH_VERSION')) {
            $plugins['rank_math'] = 'Rank Math SEO ' . RANK_MATH_VERSION;
        }

        // All in One SEO.
        if (defined('AIOSEO_VERSION')) {
            $plugins['aioseo'] = 'All in One SEO ' . AIOSEO_VERSION;
        }

        // The SEO Framework.
        if (defined('THE_SEO_FRAMEWORK_VERSION')) {
            $plugins['the_seo_framework'] = 'The SEO Framework ' . THE_SEO_FRAMEWORK_VERSION;
        }

        // SEOPress.
        if (defined('SEOPRESS_VERSION')) {
            $plugins['seopress'] = 'SEOPress ' . SEOPRESS_VERSION;
        }

        return $plugins;
    }

    /**
     * Check if any third-party SEO plugin is active.
     */
    public static function has_third_party_seo(): bool
    {
        return !empty(self::get_active_seo_plugins());
    }

    /**
     * Check whether metadata rendering should be yielded to third-party plugin.
     */
    public static function is_meta_handled_by_third_party(): bool
    {
        $settings = Settings::get_instance();
        $mode = (string) $settings->get('seo', 'compatibility_mode', 'auto');

        if ($mode === 'passthrough') {
            return true;
        }

        if ($mode === 'override') {
            return false;
        }

        // In 'auto' mode: yield metadata output if a recognized third-party SEO plugin is present.
        return self::has_third_party_seo();
    }

    /**
     * Check whether schema rendering should be yielded to third-party plugin.
     */
    public static function is_schema_handled_by_third_party(): bool
    {
        $settings = Settings::get_instance();
        $mode = (string) $settings->get('seo', 'compatibility_mode', 'auto');

        if ($mode === 'passthrough') {
            return true;
        }

        // In both 'auto' and 'override', AnswerMesh handles Schema unless explicitly disabled.
        return false;
    }
}
