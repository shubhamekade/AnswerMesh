<?php
/**
 * HTML Breadcrumb navigation generator.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Breadcrumbs
{
    /**
     * Render HTML breadcrumbs navigation trail.
     */
    public static function render(): string
    {
        if (is_front_page()) {
            return '';
        }

        $items = [];
        $items[] = sprintf('<a href="%s">%s</a>', esc_url(home_url('/')), esc_html__('Home', 'answermesh'));

        if (is_singular()) {
            $post_id = (int) get_the_ID();
            $ancestors = get_post_ancestors($post_id);

            if (!empty($ancestors)) {
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor_id) {
                    $items[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(get_permalink($ancestor_id)),
                        esc_html(get_the_title($ancestor_id))
                    );
                }
            } elseif (is_singular('post')) {
                $categories = get_the_category($post_id);
                if (!empty($categories)) {
                    $first_cat = $categories[0];
                    $items[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(get_category_link($first_cat->term_id)),
                        esc_html($first_cat->name)
                    );
                }
            }

            $items[] = sprintf('<span class="current">%s</span>', esc_html(get_the_title($post_id)));
        } elseif (is_archive()) {
            $items[] = sprintf('<span class="current">%s</span>', esc_html(get_the_archive_title()));
        } elseif (is_search()) {
            $items[] = sprintf(
                '<span class="current">%s "%s"</span>',
                esc_html__('Search results for', 'answermesh'),
                esc_html(get_search_query())
            );
        }

        return sprintf(
            '<nav class="answermesh-breadcrumbs" aria-label="%s">%s</nav>',
            esc_attr__('Breadcrumb', 'answermesh'),
            implode(' <span class="sep">&raquo;</span> ', $items)
        );
    }
}
