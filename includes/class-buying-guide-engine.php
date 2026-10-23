<?php
/**
 * Autonomous Buying Guide & Product Comparison Generator.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class BuyingGuideEngine
{
    /**
     * Hook shortcode.
     */
    public static function init(): void
    {
        add_shortcode('answermesh_buying_guide', [self::class, 'render_shortcode']);
    }

    /**
     * Retrieve products in a category and build a comparison matrix.
     *
     * @param string|int $category Category slug or term ID.
     * @param int        $limit    Max products to compare (default 5, max 15).
     * @return array<string, mixed> Comparison matrix data.
     */
    public static function generate_comparison_matrix(string|int $category, int $limit = 5): array
    {
        if (!WooCommerceSchema::is_safe_commerce_context()) {
            return [];
        }

        $limit = max(2, min(15, $limit));
        $category_name = is_numeric($category) ? 'Category #' . $category : ucwords(str_replace(['-', '_'], ' ', (string) $category));
        $category_slug = (string) $category;

        // Fetch category term if WP taxonomy is available
        if (function_exists('get_term_by') && !is_numeric($category)) {
            $term = get_term_by('slug', (string) $category, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $category_name = $term->name;
                $category_slug = $term->slug;
            }
        } elseif (function_exists('get_term') && is_numeric($category)) {
            $term = get_term((int) $category, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $category_name = $term->name;
                $category_slug = $term->slug;
            }
        }

        // Query products
        $product_posts = [];
        if (function_exists('get_posts')) {
            $args = [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'orderby'        => 'meta_value_num',
                'meta_key'       => '_wc_average_rating',
                'order'          => 'DESC',
            ];

            if (!empty($category_slug)) {
                $args['tax_query'] = [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => is_numeric($category) ? 'term_id' : 'slug',
                        'terms'    => $category,
                    ],
                ];
            }

            $product_posts = get_posts($args);
        }

        // Fallback for test environments without DB taxonomy
        if (empty($product_posts) && !empty($GLOBALS['mock_category_products'])) {
            $product_posts = array_slice($GLOBALS['mock_category_products'], 0, $limit);
        }

        if (empty($product_posts)) {
            return [
                'category_name' => $category_name,
                'category_slug' => $category_slug,
                'count'         => 0,
                'products'      => [],
                'guide'         => [],
            ];
        }

        $items = [];
        $lowest_price = PHP_FLOAT_MAX;
        $highest_rating = -1.0;
        $most_reviews = -1;
        $best_value_id = 0;
        $top_rated_id = 0;
        $popular_id = 0;

        foreach ($product_posts as $post) {
            $pid = (int) $post->ID;
            $title = $post->post_title;
            $permalink = function_exists('get_permalink') ? get_permalink($pid) : 'http://example.com/product/' . $pid;
            $image_url = '';
            if (function_exists('get_the_post_thumbnail_url')) {
                $image_url = get_the_post_thumbnail_url($pid, 'medium') ?: '';
            }

            // Extract price and ratings
            $price = 0.0;
            $rating = 0.0;
            $reviews = 0;
            $in_stock = true;
            $weight = '';

            if (function_exists('wc_get_product')) {
                $wc_p = wc_get_product($pid);
                if ($wc_p && is_object($wc_p)) {
                    $price = method_exists($wc_p, 'get_price') ? (float) $wc_p->get_price() : 0.0;
                    $rating = method_exists($wc_p, 'get_average_rating') ? (float) $wc_p->get_average_rating() : 0.0;
                    $reviews = method_exists($wc_p, 'get_review_count') ? (int) $wc_p->get_review_count() : 0;
                    $in_stock = method_exists($wc_p, 'is_in_stock') ? (bool) $wc_p->is_in_stock() : true;
                }
            } else {
                $price = (float) get_post_meta($pid, '_price', true);
                $rating = (float) get_post_meta($pid, '_wc_average_rating', true);
                $reviews = (int) get_post_meta($pid, '_wc_review_count', true);
                $in_stock = get_post_meta($pid, '_stock_status', true) !== 'outofstock';
                $weight = (string) get_post_meta($pid, '_weight', true);
            }

            if ($price > 0 && $price < $lowest_price) {
                $lowest_price = $price;
                $best_value_id = $pid;
            }

            if ($rating > $highest_rating) {
                $highest_rating = $rating;
                $top_rated_id = $pid;
            }

            if ($reviews > $most_reviews) {
                $most_reviews = $reviews;
                $popular_id = $pid;
            }

            $items[] = [
                'id'           => $pid,
                'title'        => $title,
                'permalink'    => $permalink,
                'image_url'    => $image_url,
                'price'        => $price,
                'price_fmt'    => '$' . number_format($price, 2),
                'rating'       => $rating,
                'review_count' => $reviews,
                'in_stock'     => $in_stock,
                'badge'        => '',
            ];
        }

        // Assign badges
        foreach ($items as &$item) {
            if ($item['id'] === $top_rated_id && $item['rating'] >= 4.0) {
                $item['badge'] = __('Top Rated', 'answermesh');
            } elseif ($item['id'] === $best_value_id) {
                $item['badge'] = __('Best Value', 'answermesh');
            } elseif ($item['id'] === $popular_id && $item['review_count'] >= 10) {
                $item['badge'] = __('Most Popular', 'answermesh');
            }
        }
        unset($item);

        // Buying Guide Key Factors & Criteria
        $guide_factors = [
            [
                'factor'      => __('Performance & Features', 'answermesh'),
                'description' => sprintf(__('Compare specifications to ensure the product meets your intended use case in %s.', 'answermesh'), $category_name),
            ],
            [
                'factor'      => __('Price-to-Value Ratio', 'answermesh'),
                'description' => sprintf(__('Current category options range from %s up to premium configurations.', 'answermesh'), '$' . number_format($lowest_price === PHP_FLOAT_MAX ? 0 : $lowest_price, 2)),
            ],
            [
                'factor'      => __('Customer Satisfaction', 'answermesh'),
                'description' => __('Products with verified reviews above 4.5 stars consistently deliver higher reliability and build quality.', 'answermesh'),
            ],
        ];

        return [
            'category_name' => $category_name,
            'category_slug' => $category_slug,
            'count'         => count($items),
            'products'      => $items,
            'factors'       => $guide_factors,
            'summary'       => sprintf(
                __('Our automated comparative analysis of %d %s products highlights the best choices across budget, user satisfaction, and performance tiers.', 'answermesh'),
                count($items),
                $category_name
            ),
        ];
    }

    /**
     * Render HTML table for product comparison.
     *
     * @param array<string, mixed> $matrix Output from generate_comparison_matrix.
     * @param string               $title  Optional custom heading.
     * @return string Rendered HTML.
     */
    public static function render_html(array $matrix, string $title = ''): string
    {
        if (empty($matrix) || empty($matrix['products'])) {
            return '';
        }

        $heading = !empty($title) ? $title : sprintf(__('Comprehensive Buying Guide & Comparison: %s', 'answermesh'), $matrix['category_name']);

        $html = '<div class="answermesh-buying-guide-container">';
        $html .= '<div class="answermesh-guide-header">';
        $html .= '<h2 class="answermesh-guide-title">' . esc_html($heading) . '</h2>';
        $html .= '<p class="answermesh-guide-summary">' . esc_html($matrix['summary'] ?? '') . '</p>';
        $html .= '</div>';

        // Comparison Table
        $html .= '<div class="answermesh-table-responsive">';
        $html .= '<table class="answermesh-comparison-table">';
        $html .= '<thead><tr>';
        $html .= '<th>' . esc_html__('Product', 'answermesh') . '</th>';
        $html .= '<th>' . esc_html__('Price', 'answermesh') . '</th>';
        $html .= '<th>' . esc_html__('Rating', 'answermesh') . '</th>';
        $html .= '<th>' . esc_html__('Availability', 'answermesh') . '</th>';
        $html .= '<th>' . esc_html__('Action', 'answermesh') . '</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($matrix['products'] as $p) {
            $badge_html = !empty($p['badge']) ? '<span class="answermesh-badge">' . esc_html($p['badge']) . '</span> ' : '';
            $status_class = $p['in_stock'] ? 'answermesh-stock-in' : 'answermesh-stock-out';
            $status_text = $p['in_stock'] ? __('In Stock', 'answermesh') : __('Out of Stock', 'answermesh');

            $html .= '<tr>';
            $html .= '<td class="answermesh-td-title">' . $badge_html . '<strong>' . esc_html($p['title']) . '</strong></td>';
            $html .= '<td class="answermesh-td-price">' . esc_html($p['price_fmt']) . '</td>';
            $html .= '<td class="answermesh-td-rating">★ ' . number_format((float) $p['rating'], 1) . ' <small>(' . (int) $p['review_count'] . ')</small></td>';
            $html .= '<td class="answermesh-td-stock"><span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span></td>';
            $html .= '<td class="answermesh-td-cta"><a href="' . esc_url($p['permalink']) . '" class="button answermesh-btn-view" rel="nofollow">' . esc_html__('View Details', 'answermesh') . '</a></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        // Buying Guide Key Factors
        if (!empty($matrix['factors'])) {
            $html .= '<div class="answermesh-buying-factors">';
            $html .= '<h3>' . esc_html__('Key Factors to Consider', 'answermesh') . '</h3>';
            $html .= '<ul class="answermesh-factors-list">';
            foreach ($matrix['factors'] as $factor) {
                $html .= '<li><strong>' . esc_html($factor['factor']) . ':</strong> ' . esc_html($factor['description']) . '</li>';
            }
            $html .= '</ul></div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Shortcode handler for [answermesh_buying_guide category="slug" limit="5" title="..."].
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public static function render_shortcode($atts): string
    {
        $parsed = shortcode_atts([
            'category' => '',
            'limit'    => 5,
            'title'    => '',
        ], $atts, 'answermesh_buying_guide');

        $category = sanitize_text_field((string) ($parsed['category'] ?? ''));
        $limit = (int) ($parsed['limit'] ?? 5);
        $title = sanitize_text_field((string) ($parsed['title'] ?? ''));

        if (empty($category)) {
            return '';
        }

        $matrix = self::generate_comparison_matrix($category, $limit);
        return self::render_html($matrix, $title);
    }
}
