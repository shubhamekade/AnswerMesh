<?php
/**
 * SEO metadata manager and helper.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Meta
{
    public const META_TITLE = '_aiseo_title';
    public const META_DESCRIPTION = '_aiseo_description';
    public const META_CANONICAL = '_aiseo_canonical';
    public const META_ROBOTS_NOINDEX = '_aiseo_noindex';
    public const META_ROBOTS_NOFOLLOW = '_aiseo_nofollow';
    public const META_OG_IMAGE = '_aiseo_og_image';
    public const META_PRIMARY_ENTITY = '_aiseo_primary_entity';
    public const META_FOCUS_TOPIC = '_aiseo_focus_topic';

    /**
     * Register post meta keys with WordPress.
     */
    public function init(): void
    {
        $post_types = get_post_types(['public' => true]);

        foreach ($post_types as $post_type) {
            register_post_meta($post_type, self::META_TITLE, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => [$this, 'check_post_meta_cap'],
            ]);

            register_post_meta($post_type, self::META_DESCRIPTION, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'auth_callback'     => [$this, 'check_post_meta_cap'],
            ]);

            register_post_meta($post_type, self::META_CANONICAL, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'auth_callback'     => [$this, 'check_post_meta_cap'],
            ]);

            register_post_meta($post_type, self::META_ROBOTS_NOINDEX, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'auth_callback'     => [$this, 'check_post_meta_cap'],
            ]);

            register_post_meta($post_type, self::META_ROBOTS_NOFOLLOW, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'auth_callback'     => [$this, 'check_post_meta_cap'],
            ]);

            register_post_meta($post_type, self::META_OG_IMAGE, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'auth_callback'     => [$this, 'check_post_meta_cap'],
            ]);
        }
    }

    /**
     * Permission callback for editing post metadata.
     */
    public function check_post_meta_cap(bool $allowed, string $meta_key, int $post_id, int $user_id, string $cap, array $caps): bool
    {
        return current_user_can('edit_post', $post_id);
    }

    /**
     * Get computed page title with fallbacks.
     */
    public static function get_title(int $post_id = 0): string
    {
        if ($post_id <= 0 && is_singular()) {
            $post_id = (int) get_the_ID();
        }

        if ($post_id > 0) {
            $custom_title = (string) get_post_meta($post_id, self::META_TITLE, true);
            if (!empty($custom_title)) {
                return $custom_title;
            }
            $post = get_post($post_id);
            if ($post) {
                return apply_filters('the_title', $post->post_title, $post_id);
            }
        }

        return get_bloginfo('name');
    }

    /**
     * Get computed meta description with fallbacks.
     */
    public static function get_description(int $post_id = 0): string
    {
        if ($post_id <= 0 && is_singular()) {
            $post_id = (int) get_the_ID();
        }

        if ($post_id > 0) {
            $custom_desc = (string) get_post_meta($post_id, self::META_DESCRIPTION, true);
            if (!empty($custom_desc)) {
                return $custom_desc;
            }

            $post = get_post($post_id);
            if ($post) {
                if (!empty($post->post_excerpt)) {
                    return wp_strip_all_tags($post->post_excerpt);
                }
                // Strip tags, truncate to 160 characters.
                $clean_text = wp_strip_all_tags($post->post_content);
                $clean_text = (string) preg_replace('/\s+/', ' ', $clean_text);
                return mb_strimwidth(trim($clean_text), 0, 160, '...');
            }
        }

        if (is_front_page() || is_home()) {
            return (string) get_bloginfo('description');
        }

        return '';
    }

    /**
     * Get canonical URL.
     */
    public static function get_canonical_url(int $post_id = 0): string
    {
        if ($post_id <= 0 && is_singular()) {
            $post_id = (int) get_the_ID();
        }

        if ($post_id > 0) {
            $custom_canonical = (string) get_post_meta($post_id, self::META_CANONICAL, true);
            if (!empty($custom_canonical)) {
                return esc_url_raw($custom_canonical);
            }
            $permalink = get_permalink($post_id);
            if ($permalink) {
                return esc_url_raw($permalink);
            }
        }

        return esc_url_raw(home_url('/'));
    }

    /**
     * Get robots directives.
     *
     * @return array{noindex: bool, nofollow: bool, string: string}
     */
    public static function get_robots(int $post_id = 0): array
    {
        if ($post_id <= 0 && is_singular()) {
            $post_id = (int) get_the_ID();
        }

        // Global check: WordPress discourage search engines.
        $blog_public = (int) get_option('blog_public', 1);
        if ($blog_public === 0) {
            return ['noindex' => true, 'nofollow' => true, 'string' => 'noindex, nofollow'];
        }

        $noindex = false;
        $nofollow = false;

        if ($post_id > 0) {
            $noindex = (bool) get_post_meta($post_id, self::META_ROBOTS_NOINDEX, true);
            $nofollow = (bool) get_post_meta($post_id, self::META_ROBOTS_NOFOLLOW, true);
        }

        $parts = [];
        $parts[] = $noindex ? 'noindex' : 'index';
        $parts[] = $nofollow ? 'nofollow' : 'follow';

        return [
            'noindex'  => $noindex,
            'nofollow' => $nofollow,
            'string'   => implode(', ', $parts),
        ];
    }

    /**
     * Get OpenGraph data array.
     */
    public static function get_og_data(int $post_id = 0): array
    {
        if ($post_id <= 0 && is_singular()) {
            $post_id = (int) get_the_ID();
        }

        $title = self::get_title($post_id);
        $description = self::get_description($post_id);
        $url = self::get_canonical_url($post_id);
        $type = (is_singular('post')) ? 'article' : 'website';

        $image = '';
        if ($post_id > 0) {
            $custom_image = (string) get_post_meta($post_id, self::META_OG_IMAGE, true);
            if (!empty($custom_image)) {
                $image = $custom_image;
            } elseif (has_post_thumbnail($post_id)) {
                $thumb = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'large');
                if ($thumb) {
                    $image = $thumb[0];
                }
            }
        }

        // Fallback to site logo if configured.
        if (empty($image)) {
            $settings = Settings::get_instance();
            $image = (string) $settings->get('schema', 'logo_url', '');
        }

        return [
            'title'       => $title,
            'description' => $description,
            'url'         => $url,
            'type'        => $type,
            'site_name'   => get_bloginfo('name'),
            'image'       => $image,
        ];
    }
}
