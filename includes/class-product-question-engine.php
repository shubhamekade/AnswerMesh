<?php
/**
 * Product Question Extraction & Answering Engine for Generative Commerce.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class ProductQuestionEngine
{
    /**
     * Extract product attributes and generate structured Q&A pairs.
     *
     * @param int $product_id Product post ID.
     * @return array<int, array<string, string>> Array of question/answer definitions.
     */
    public static function generate_questions(int $product_id): array
    {
        if (!WooCommerceSchema::is_safe_commerce_context()) {
            return [];
        }

        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'product') {
            return [];
        }

        $title = $post->post_title;
        $questions = [];

        // Fetch WC product if available
        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

        // 1. Availability and Pricing Q&A
        $price = '0.00';
        $currency = 'USD';
        $in_stock = true;
        if ($product && is_object($product)) {
            $price = method_exists($product, 'get_price') ? (string) $product->get_price() : '0.00';
            $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD';
            $in_stock = method_exists($product, 'is_in_stock') ? (bool) $product->is_in_stock() : true;
        } else {
            $meta_price = get_post_meta($product_id, '_price', true);
            if ($meta_price !== '') {
                $price = (string) $meta_price;
            }
            $stock_status = get_post_meta($product_id, '_stock_status', true);
            if ($stock_status === 'outofstock') {
                $in_stock = false;
            }
        }

        $stock_status_str = $in_stock ? 'in stock and ready to ship' : 'currently out of stock';
        $questions[] = [
            'category' => 'availability',
            'question' => sprintf(__('Is %s currently in stock and how much does it cost?', 'answermesh'), $title),
            'answer'   => sprintf(
                __('%s is priced at %s %s and is %s.', 'answermesh'),
                $title,
                $currency,
                number_format((float) $price, 2),
                $stock_status_str
            ),
        ];

        // 2. Dimensions & Weight Q&A
        $weight = '';
        $dimensions = '';
        if ($product && is_object($product)) {
            if (method_exists($product, 'has_weight') && $product->has_weight()) {
                $weight = $product->get_weight() . ' ' . (function_exists('get_option') ? get_option('woocommerce_weight_unit', 'kg') : 'kg');
            }
            if (method_exists($product, 'has_dimensions') && $product->has_dimensions()) {
                $dim_unit = function_exists('get_option') ? get_option('woocommerce_dimension_unit', 'cm') : 'cm';
                $dimensions = sprintf(
                    '%s x %s x %s %s',
                    $product->get_length(),
                    $product->get_width(),
                    $product->get_height(),
                    $dim_unit
                );
            }
        } else {
            $w = get_post_meta($product_id, '_weight', true);
            if ($w) $weight = $w . ' kg';
            $l = get_post_meta($product_id, '_length', true);
            $wi = get_post_meta($product_id, '_width', true);
            $h = get_post_meta($product_id, '_height', true);
            if ($l && $wi && $h) {
                $dimensions = "{$l} x {$wi} x {$h} cm";
            }
        }

        if (!empty($weight) || !empty($dimensions)) {
            $parts = [];
            if (!empty($dimensions)) {
                $parts[] = sprintf(__('dimensions of %s', 'answermesh'), $dimensions);
            }
            if (!empty($weight)) {
                $parts[] = sprintf(__('a weight of %s', 'answermesh'), $weight);
            }
            $questions[] = [
                'category' => 'specifications',
                'question' => sprintf(__('What are the dimensions and weight of %s?', 'answermesh'), $title),
                'answer'   => sprintf(__('%s has %s.', 'answermesh'), $title, implode(' and ', $parts)),
            ];
        }

        // 3. Custom Attributes (e.g. Material, Color, Brand)
        if ($product && is_object($product) && method_exists($product, 'get_attributes')) {
            $attributes = $product->get_attributes();
            $specs = [];
            foreach ($attributes as $attr_name => $attr_val) {
                $name = wc_attribute_label($attr_name);
                $val = is_object($attr_val) && method_exists($attr_val, 'get_options') ? implode(', ', $attr_val->get_options()) : (string) $attr_val;
                if (!empty($name) && !empty($val)) {
                    $specs[] = "{$name}: {$val}";
                }
            }
            if (!empty($specs)) {
                $questions[] = [
                    'category' => 'specifications',
                    'question' => sprintf(__('What specifications and materials are used in %s?', 'answermesh'), $title),
                    'answer'   => sprintf(__('%s features the following specifications: %s.', 'answermesh'), $title, implode('; ', $specs)),
                ];
            }
        }

        // 4. Return Policy & Warranty
        $questions[] = [
            'category' => 'policy',
            'question' => sprintf(__('What is the return policy for %s?', 'answermesh'), $title),
            'answer'   => sprintf(
                __('We offer a hassle-free 30-day return policy for %s. Items must be in original condition and return shipping is covered.', 'answermesh'),
                $title
            ),
        ];

        // 5. Shipping & Handling
        $questions[] = [
            'category' => 'shipping',
            'question' => sprintf(__('How fast will %s ship and what are the delivery options?', 'answermesh'), $title),
            'answer'   => sprintf(
                __('Orders for %s are processed within 24 hours. Standard shipping typically delivers in 2 to 5 business days with tracking provided.', 'answermesh'),
                $title
            ),
        ];

        return apply_filters('answermesh_product_questions', $questions, $product_id);
    }

    /**
     * Build Schema.org FAQPage JSON-LD graph block for product questions.
     *
     * @param int $product_id Product post ID.
     * @return array<string, mixed> Schema FAQPage or empty.
     */
    public static function build_faq_schema(int $product_id): array
    {
        $qa_list = self::generate_questions($product_id);
        if (empty($qa_list)) {
            return [];
        }

        $canonical = Meta::get_canonical_url($product_id);
        $entities = [];

        foreach ($qa_list as $item) {
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $item['answer'],
                ],
            ];
        }

        return [
            '@type'            => 'FAQPage',
            '@id'              => $canonical . '#faq',
            'isPartOf'         => ['@id' => $canonical . '#product'],
            'mainEntity'       => $entities,
        ];
    }

    /**
     * Render clean HTML snippet for product questions.
     *
     * @param int $product_id Product post ID.
     * @return string Rendered HTML.
     */
    public static function render_qa_html(int $product_id): string
    {
        $qa_list = self::generate_questions($product_id);
        if (empty($qa_list)) {
            return '';
        }

        $html = '<div class="answermesh-product-qa">';
        $html .= '<h3>' . esc_html__('Frequently Asked Questions', 'answermesh') . '</h3>';
        $html .= '<div class="answermesh-qa-accordion">';

        foreach ($qa_list as $item) {
            $html .= '<details class="answermesh-qa-item">';
            $html .= '<summary class="answermesh-qa-question">' . esc_html($item['question']) . '</summary>';
            $html .= '<div class="answermesh-qa-answer"><p>' . esc_html($item['answer']) . '</p></div>';
            $html .= '</details>';
        }

        $html .= '</div></div>';
        return $html;
    }
}
