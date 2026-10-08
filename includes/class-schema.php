<?php
/**
 * Schema.org JSON-LD graph generator.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Schema
{
    /**
     * Build unified JSON-LD graph for current request.
     *
     * @return array<string, mixed>
     */
    public static function build_graph(): array
    {
        $graph = [];

        // 1. WebSite Schema.
        $website_schema = self::build_website_schema();
        if (!empty($website_schema)) {
            $graph[] = $website_schema;
        }

        // 2. Organization Schema.
        $org_schema = self::build_organization_schema();
        if (!empty($org_schema)) {
            $graph[] = $org_schema;
        }

        // 3. Post / Page / Article Schema if singular.
        if (is_singular()) {
            $post_id = (int) get_the_ID();
            $post_schema = self::build_post_schema($post_id);
            if (!empty($post_schema)) {
                $graph[] = $post_schema;
            }

            // BreadcrumbList.
            $breadcrumbs_schema = self::build_breadcrumbs_schema($post_id);
            if (!empty($breadcrumbs_schema)) {
                $graph[] = $breadcrumbs_schema;
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];
    }

    /**
     * Build WebSite schema with SearchAction.
     */
    public static function build_website_schema(): array
    {
        $home_url = trailingslashit(home_url());

        return [
            '@type'           => 'WebSite',
            '@id'             => $home_url . '#website',
            'url'             => $home_url,
            'name'            => get_bloginfo('name'),
            'description'     => get_bloginfo('description'),
            'publisher'       => ['@id' => $home_url . '#organization'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $home_url . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
            'inLanguage'      => get_locale(),
        ];
    }

    /**
     * Build Organization or LocalBusiness schema.
     */
    public static function build_organization_schema(): array
    {
        $settings = Settings::get_instance();
        $home_url = trailingslashit(home_url());

        $type = (string) $settings->get('schema', 'site_type', 'Organization');
        $name = (string) $settings->get('schema', 'business_name', get_bloginfo('name'));
        $logo = (string) $settings->get('schema', 'logo_url', '');
        $socials = (array) $settings->get('schema', 'social_profiles', []);

        $schema = [
            '@type' => $type,
            '@id'   => $home_url . '#organization',
            'name'  => $name ?: get_bloginfo('name'),
            'url'   => $home_url,
        ];

        if (!empty($logo)) {
            $schema['logo'] = [
                '@type'      => 'ImageObject',
                '@id'        => $home_url . '#logo',
                'inLanguage' => get_locale(),
                'url'        => esc_url_raw($logo),
                'caption'    => $name,
            ];
            $schema['image'] = ['@id' => $home_url . '#logo'];
        }

        if (!empty($socials)) {
            $schema['sameAs'] = array_values(array_filter(array_map('esc_url_raw', $socials)));
        }

        return $schema;
    }

    /**
     * Build Article or WebPage schema.
     */
    public static function build_post_schema(int $post_id): array
    {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $home_url = trailingslashit(home_url());
        $canonical = Meta::get_canonical_url($post_id);
        $title = Meta::get_title($post_id);
        $description = Meta::get_description($post_id);

        $is_post = ($post->post_type === 'post');
        $type = $is_post ? 'BlogPosting' : 'WebPage';

        $schema = [
            '@type'            => $type,
            '@id'              => $canonical . '#' . strtolower($type),
            'isPartOf'         => ['@id' => $home_url . '#website'],
            'author'           => [
                '@type' => 'Person',
                'name'  => get_the_author_meta('display_name', (int) $post->post_author),
                'url'   => get_author_posts_url((int) $post->post_author),
            ],
            'headline'         => $title,
            'description'      => $description,
            'datePublished'    => get_the_date('c', $post),
            'dateModified'     => get_the_modified_date('c', $post),
            'mainEntityOfPage' => $canonical,
            'inLanguage'       => get_locale(),
        ];

        if (has_post_thumbnail($post_id)) {
            $thumb_id = get_post_thumbnail_id($post_id);
            $img_src = wp_get_attachment_image_src($thumb_id, 'full');
            if ($img_src) {
                $schema['image'] = [
                    '@type'  => 'ImageObject',
                    'url'    => $img_src[0],
                    'width'  => $img_src[1],
                    'height' => $img_src[2],
                ];
            }
        }

        if ($is_post) {
            $schema['publisher'] = ['@id' => $home_url . '#organization'];
        }

        return $schema;
    }

    /**
     * Build BreadcrumbList schema.
     */
    public static function build_breadcrumbs_schema(int $post_id): array
    {
        $items = [];
        $position = 1;

        // Position 1: Home
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => __('Home', 'answermesh'),
            'item'     => home_url('/'),
        ];

        // Ancestors if page or hierarchical post type.
        $ancestors = get_post_ancestors($post_id);
        if (!empty($ancestors)) {
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor_id) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => get_the_title($ancestor_id),
                    'item'     => get_permalink($ancestor_id),
                ];
            }
        } elseif (is_singular('post')) {
            $categories = get_the_category($post_id);
            if (!empty($categories)) {
                $first_cat = $categories[0];
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $first_cat->name,
                    'item'     => get_category_link($first_cat->term_id),
                ];
            }
        }

        // Current item.
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => get_the_title($post_id),
            'item'     => get_permalink($post_id),
        ];

        return [
            '@type'           => 'BreadcrumbList',
            '@id'             => get_permalink($post_id) . '#breadcrumb',
            'itemListElement' => $items,
        ];
    }
}
