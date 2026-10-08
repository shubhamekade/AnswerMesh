<?php
/**
 * XML Sitemap integration and custom provider.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Sitemap
{
    /**
     * Register sitemap hooks.
     */
    public function init(): void
    {
        $settings = Settings::get_instance();
        if (!$settings->get('general', 'enable_sitemap', true)) {
            return;
        }

        // Filter WordPress core post query in sitemaps to exclude noindex.
        add_filter('wp_sitemaps_posts_query_args', [$this, 'filter_posts_query'], 10, 2);

        // Add sitemap index entry if core sitemaps are active.
        add_filter('wp_sitemaps_add_provider', [$this, 'filter_providers'], 10, 2);
    }

    /**
     * Exclude posts marked as noindex from core sitemaps.
     *
     * @param array<string, mixed> $args
     * @param string $post_type
     * @return array<string, mixed>
     */
    public function filter_posts_query(array $args, string $post_type): array
    {
        $meta_query = $args['meta_query'] ?? [];
        if (!is_array($meta_query)) {
            $meta_query = [];
        }

        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => Meta::META_ROBOTS_NOINDEX,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => Meta::META_ROBOTS_NOINDEX,
                'value'   => '1',
                'compare' => '!=',
            ],
        ];

        $args['meta_query'] = $meta_query;
        return $args;
    }

    /**
     * Optional provider filter.
     */
    public function filter_providers(mixed $provider, string $name): mixed
    {
        return $provider;
    }
}
