<?php
/**
 * Test Suite: WooCommerce Structured Commerce & Product Question Extraction Engine
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\WooCommerceSchema;
use AnswerMesh\ProductQuestionEngine;
use AnswerMesh\Schema;

class TestWooCommerce
{
    /**
     * Run all WooCommerce suite assertions.
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

        // Setup mock product post
        $GLOBALS['mock_post_product'] = (object)[
            'ID'            => 501,
            'post_title'    => 'Ergonomic Wireless Keyboard',
            'post_content'  => 'Premium mechanical keyboard with Bluetooth and 2.4GHz connectivity.',
            'post_excerpt'  => 'Compact ergonomic wireless keyboard.',
            'post_date'     => '2026-03-01 10:00:00',
            'post_date_gmt' => '2026-03-01 10:00:00',
            'post_modified' => '2026-03-02 10:00:00',
            'post_author'   => 1,
            'post_type'     => 'product',
            'post_status'   => 'publish',
        ];

        // Store postmeta for product 501
        $GLOBALS['mock_postmeta'][501] = [
            '_price'             => ['129.99'],
            '_sku'               => ['KB-ERG-500'],
            '_stock_status'      => ['instock'],
            '_weight'            => ['0.85'],
            '_length'            => ['35'],
            '_width'             => ['14'],
            '_height'            => ['3'],
            '_wc_average_rating' => ['4.7'],
            '_wc_review_count'   => ['42'],
        ];

        // 1. Activation & Safe Context Check
        $assert(is_bool(WooCommerceSchema::is_woocommerce_active()), 'is_woocommerce_active() should return a boolean');
        $assert(WooCommerceSchema::is_safe_commerce_context() === true, 'Standard context should be safe for commerce');

        // 2. Non-product schema handling
        $empty_schema = WooCommerceSchema::build_product_schema(1); // Standard post
        $assert(empty($empty_schema), 'build_product_schema for non-product post should return empty');

        // 3. Product Schema Generation
        $schema = WooCommerceSchema::build_product_schema(501);
        $assert(!empty($schema), 'build_product_schema should return schema array for product');
        $assert(($schema['@type'] ?? '') === 'Product', 'Schema @type must be Product');
        $assert(($schema['name'] ?? '') === 'Ergonomic Wireless Keyboard', 'Schema name must match product title');
        $assert(($schema['sku'] ?? '') === 'KB-ERG-500', 'Schema SKU must match product SKU');

        // Offer validation
        $offer = $schema['offers'] ?? [];
        $assert(($offer['@type'] ?? '') === 'Offer', 'Offer @type must be Offer');
        $assert(($offer['price'] ?? '') === '129.99', 'Offer price must match 129.99');
        $assert(($offer['availability'] ?? '') === 'https://schema.org/InStock', 'Offer availability must be InStock');
        
        // Return Policy validation
        $return_policy = $offer['hasMerchantReturnPolicy'] ?? [];
        $assert(($return_policy['@type'] ?? '') === 'MerchantReturnPolicy', 'Return policy @type must be MerchantReturnPolicy');
        $assert(($return_policy['merchantReturnDays'] ?? 0) === 30, 'Return window must be 30 days');

        // Shipping Details validation
        $shipping = $offer['shippingDetails'] ?? [];
        $assert(($shipping['@type'] ?? '') === 'OfferShippingDetails', 'Shipping details @type must be OfferShippingDetails');

        // Aggregate Rating validation
        $rating = $schema['aggregateRating'] ?? [];
        $assert(($rating['@type'] ?? '') === 'AggregateRating', 'AggregateRating @type must be AggregateRating');
        $assert(($rating['ratingValue'] ?? '') === '4.7', 'Rating value must be 4.7');
        $assert(($rating['reviewCount'] ?? 0) === 42, 'Review count must be 42');

        // 4. Zero Checkout Impact (NFR-M4-01)
        $_SERVER['REQUEST_URI'] = '/checkout/';
        $assert(WooCommerceSchema::is_safe_commerce_context() === false, 'Checkout context should NOT be safe');
        $checkout_schema = WooCommerceSchema::build_product_schema(501);
        $assert(empty($checkout_schema), 'Schema generation on checkout must return empty');
        unset($_SERVER['REQUEST_URI']);

        // 5. Product Question Answering Engine
        $questions = ProductQuestionEngine::generate_questions(501);
        $assert(is_array($questions), 'generate_questions should return an array');
        $assert(count($questions) >= 4, 'generate_questions should produce at least 4 Q&A pairs');

        $categories = array_column($questions, 'category');
        $assert(in_array('availability', $categories, true), 'Q&A must include availability question');
        $assert(in_array('specifications', $categories, true), 'Q&A must include specifications question');
        $assert(in_array('policy', $categories, true), 'Q&A must include return policy question');
        $assert(in_array('shipping', $categories, true), 'Q&A must include shipping question');

        // Check dimension answers
        $spec_q = null;
        foreach ($questions as $q) {
            if ($q['category'] === 'specifications') {
                $spec_q = $q;
                break;
            }
        }
        $assert($spec_q !== null && str_contains($spec_q['answer'], '0.85 kg'), 'Specification Q&A should include weight');

        // 6. FAQPage Schema Generation
        $faq_schema = ProductQuestionEngine::build_faq_schema(501);
        $assert(($faq_schema['@type'] ?? '') === 'FAQPage', 'FAQ schema @type must be FAQPage');
        $assert(count($faq_schema['mainEntity'] ?? []) >= 4, 'FAQ schema should contain question entities');

        // 7. Q&A HTML Rendering
        $qa_html = ProductQuestionEngine::render_qa_html(501);
        $assert(str_contains($qa_html, 'answermesh-product-qa'), 'Rendered HTML should have container class');
        $assert(str_contains($qa_html, 'Frequently Asked Questions'), 'Rendered HTML should have heading');

        // 8. General Schema Graph Integration
        $graph_schema = Schema::build_post_schema(501);
        $assert(($graph_schema['@type'] ?? '') === 'Product', 'Schema::build_post_schema for product should return Product schema');

        return $results;
    }
}
