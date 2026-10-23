<?php
/**
 * Public frontend Schema.org JSON-LD output renderer with caching.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class SchemaOutput
{
    public const CACHE_PREFIX = 'answermesh_schema_';

    /**
     * Register hooks.
     */
    public function init(): void
    {
        if (is_admin() || is_feed()) {
            return;
        }

        add_action('wp_head', [$this, 'render_schema'], 2);
        add_action('save_post', [$this, 'invalidate_post_cache']);
    }

    /**
     * Invalidate transient cache for a modified post.
     */
    public function invalidate_post_cache(int $post_id): void
    {
        delete_transient(self::CACHE_PREFIX . $post_id);
    }

    /**
     * Render cached or generated JSON-LD script tag.
     */
    public function render_schema(): void
    {
        if (Compatibility::is_schema_handled_by_third_party()) {
            return;
        }

        $settings = Settings::get_instance();
        if (!$settings->get('general', 'enable_schema', true)) {
            return;
        }

        $post_id = is_singular() ? (int) get_the_ID() : 0;
        $cache_key = self::CACHE_PREFIX . ($post_id ?: 'global');

        $json_ld = get_transient($cache_key);

        if ($json_ld === false) {
            $graph = Schema::build_graph();
            $json_ld = wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($json_ld) {
                // Cache for 24 hours.
                set_transient($cache_key, $json_ld, DAY_IN_SECONDS);
            }
        }

        if (!empty($json_ld)) {
            $brand_name = class_exists('AnswerMesh\WhiteLabel') ? WhiteLabel::get_plugin_name() : 'AnswerMesh';
            $hide_credits = class_exists('AnswerMesh\WhiteLabel') && WhiteLabel::is_credits_hidden();

            if (!$hide_credits) {
                printf("\n<!-- %s Structured Data / Schema.org JSON-LD -->\n", esc_html($brand_name));
            }
            printf("<script type=\"application/ld+json\" class=\"answermesh-schema-graph\">\n%s\n</script>\n", $json_ld); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            if (!$hide_credits) {
                printf("<!-- /%s Structured Data -->\n\n", esc_html($brand_name));
            }
        }
    }
}
