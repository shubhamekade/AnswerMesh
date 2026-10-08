<?php
/**
 * Public frontend head renderer for SEO metadata and OpenGraph tags.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class MetaOutput
{
    /**
     * Register frontend hooks.
     */
    public function init(): void
    {
        // Don't render in admin or feeds.
        if (is_admin() || is_feed()) {
            return;
        }

        // Title filter.
        add_filter('pre_get_document_title', [$this, 'filter_title'], 15);

        // Frontend head tags.
        add_action('wp_head', [$this, 'render_meta_tags'], 1);
    }

    /**
     * Filter document title tag.
     */
    public function filter_title(string $title): string
    {
        if (Compatibility::is_meta_handled_by_third_party()) {
            return $title;
        }

        $custom_title = Meta::get_title();
        if (!empty($custom_title)) {
            $settings = Settings::get_instance();
            $sep = (string) $settings->get('seo', 'title_separator', '-');
            if (!is_front_page()) {
                return sprintf('%s %s %s', $custom_title, $sep, get_bloginfo('name'));
            }
            return $custom_title;
        }

        return $title;
    }

    /**
     * Render HTML meta tags into <head>.
     */
    public function render_meta_tags(): void
    {
        if (Compatibility::is_meta_handled_by_third_party()) {
            return;
        }

        $settings = Settings::get_instance();

        // Check if plugin or SEO features are enabled.
        if (!$settings->get('general', 'enable_plugin', true)) {
            return;
        }

        echo "\n<!-- AnswerMesh SEO & AI Visibility Tags -->\n";

        // Description.
        $description = Meta::get_description();
        if (!empty($description)) {
            printf("<meta name=\"description\" content=\"%s\" />\n", esc_attr($description));
        }

        // Robots.
        $robots = Meta::get_robots();
        if (!empty($robots['string'])) {
            printf("<meta name=\"robots\" content=\"%s\" />\n", esc_attr($robots['string']));
        }

        // Canonical.
        if ($settings->get('seo', 'canonical_enabled', true)) {
            $canonical = Meta::get_canonical_url();
            if (!empty($canonical)) {
                printf("<link rel=\"canonical\" href=\"%s\" />\n", esc_url($canonical));
            }
        }

        // OpenGraph & Twitter Cards.
        if ($settings->get('general', 'enable_og', true)) {
            $og = Meta::get_og_data();
            printf("<meta property=\"og:title\" content=\"%s\" />\n", esc_attr($og['title']));
            if (!empty($og['description'])) {
                printf("<meta property=\"og:description\" content=\"%s\" />\n", esc_attr($og['description']));
            }
            printf("<meta property=\"og:url\" content=\"%s\" />\n", esc_url($og['url']));
            printf("<meta property=\"og:type\" content=\"%s\" />\n", esc_attr($og['type']));
            printf("<meta property=\"og:site_name\" content=\"%s\" />\n", esc_attr($og['site_name']));
            if (!empty($og['image'])) {
                printf("<meta property=\"og:image\" content=\"%s\" />\n", esc_url($og['image']));
            }

            // Twitter Card.
            echo "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
            printf("<meta name=\"twitter:title\" content=\"%s\" />\n", esc_attr($og['title']));
            if (!empty($og['description'])) {
                printf("<meta name=\"twitter:description\" content=\"%s\" />\n", esc_attr($og['description']));
            }
            if (!empty($og['image'])) {
                printf("<meta name=\"twitter:image\" content=\"%s\" />\n", esc_url($og['image']));
            }
        }

        echo "<!-- /AnswerMesh SEO Tags -->\n\n";
    }
}
