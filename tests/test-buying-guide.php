<?php
/**
 * Test Suite: Autonomous Buying Guide & Comparison Generator
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\BuyingGuideEngine;

class TestBuyingGuide
{
    /**
     * Run all Buying Guide suite assertions.
     *
     * @return array<string, mixed>
     */
    public static function run(): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'errors' => []];

        $assert = function (bool $condition, string $msg) use (&$results) {
            if ($condition) {
                $results['passed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $msg;
            }
        };

        // Prepare mock category products
        $p1 = (object)[
            'ID'            => 601,
            'post_title'    => 'Budget Mechanical Keyboard',
            'post_content'  => 'Affordable clicky keyboard with blue switches.',
            'post_excerpt'  => 'Entry level mechanical keyboard.',
            'post_type'     => 'product',
            'post_status'   => 'publish',
        ];
        $p2 = (object)[
            'ID'            => 602,
            'post_title'    => 'Pro Wireless Split Keyboard',
            'post_content'  => 'Ergonomic split keyboard for developers and writers.',
            'post_excerpt'  => 'Flagship wireless split layout.',
            'post_type'     => 'product',
            'post_status'   => 'publish',
        ];
        $p3 = (object)[
            'ID'            => 603,
            'post_title'    => 'Popular Gaming TKL Keyboard',
            'post_content'  => 'Fast optical switches with RGB lighting.',
            'post_excerpt'  => 'Tenkeyless high refresh keyboard.',
            'post_type'     => 'product',
            'post_status'   => 'publish',
        ];

        $GLOBALS['mock_category_products'] = [$p1, $p2, $p3];

        $GLOBALS['mock_postmeta'][601] = [
            '_price'             => ['49.00'],
            '_wc_average_rating' => ['4.1'],
            '_wc_review_count'   => ['8'],
            '_stock_status'      => ['instock'],
        ];

        $GLOBALS['mock_postmeta'][602] = [
            '_price'             => ['249.00'],
            '_wc_average_rating' => ['4.9'],
            '_wc_review_count'   => ['35'],
            '_stock_status'      => ['instock'],
        ];

        $GLOBALS['mock_postmeta'][603] = [
            '_price'             => ['99.00'],
            '_wc_average_rating' => ['4.4'],
            '_wc_review_count'   => ['80'],
            '_stock_status'      => ['instock'],
        ];

        // 1. Matrix generation with category
        $matrix = BuyingGuideEngine::generate_comparison_matrix('keyboards', 5);
        $assert(!empty($matrix), 'generate_comparison_matrix should return a non-empty array');
        $assert($matrix['count'] === 3, "Matrix product count should be 3, got {$matrix['count']}");
        $assert($matrix['category_slug'] === 'keyboards', 'Matrix slug should match requested category');
        $assert(!empty($matrix['factors']), 'Matrix should include buying criteria factors');
        $assert(!empty($matrix['summary']), 'Matrix should include human-readable summary');

        // Check badge assignments
        $products = $matrix['products'];
        $best_value = null;
        $top_rated = null;
        $popular = null;

        foreach ($products as $prod) {
            if ($prod['id'] === 601) $best_value = $prod;
            if ($prod['id'] === 602) $top_rated = $prod;
            if ($prod['id'] === 603) $popular = $prod;
        }

        $assert($best_value !== null && $best_value['badge'] === 'Best Value', 'Cheapest product should receive "Best Value" badge');
        $assert($top_rated !== null && $top_rated['badge'] === 'Top Rated', 'Highest rating product should receive "Top Rated" badge');
        $assert($popular !== null && $popular['badge'] === 'Most Popular', 'Most reviewed product should receive "Most Popular" badge');

        // 2. Render HTML Table
        $html = BuyingGuideEngine::render_html($matrix, 'Custom Keyboard Guide');
        $assert(str_contains($html, 'Custom Keyboard Guide'), 'HTML must contain custom title');
        $assert(str_contains($html, 'answermesh-comparison-table'), 'HTML must contain comparison table');
        $assert(str_contains($html, 'Best Value'), 'HTML must render Best Value badge');
        $assert(str_contains($html, '$49.00'), 'HTML must render formatted product prices');
        $assert(str_contains($html, 'Key Factors to Consider'), 'HTML must render guide factors block');

        // 3. Shortcode execution
        $shortcode_out = BuyingGuideEngine::render_shortcode(['category' => 'keyboards', 'limit' => 3]);
        $assert(str_contains($shortcode_out, 'answermesh-buying-guide-container'), 'Shortcode should output container');

        $empty_shortcode = BuyingGuideEngine::render_shortcode(['category' => '']);
        $assert($empty_shortcode === '', 'Shortcode with empty category should return empty string');

        // 4. Zero Checkout Impact (NFR-M4-01)
        $_SERVER['REQUEST_URI'] = '/cart/';
        $checkout_matrix = BuyingGuideEngine::generate_comparison_matrix('keyboards', 5);
        $assert(empty($checkout_matrix), 'Buying guide must return empty on cart context');
        unset($_SERVER['REQUEST_URI']);

        return $results;
    }
}
