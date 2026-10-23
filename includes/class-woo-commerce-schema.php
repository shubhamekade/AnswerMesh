<?php
/**
 * WooCommerce Structured Data & Product JSON-LD Schema Generator.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class WooCommerceSchema
{
    /**
     * Check if WooCommerce is installed and active.
     */
    public static function is_woocommerce_active(): bool
    {
        if (class_exists('WooCommerce') || function_exists('wc_get_product')) {
            return true;
        }

        // Check active plugins option fallback
        $active_plugins = apply_filters('active_plugins', (array) get_option('active_plugins', []));
        foreach ($active_plugins as $plugin) {
            if (str_contains((string) $plugin, 'woocommerce.php')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify that the current execution is safe for commerce schema generation.
     * Guarantees zero overhead/interference on checkout, cart, or payment webhooks.
     */
    public static function is_safe_commerce_context(): bool
    {
        // Check if WooCommerce conditional tags exist and are active
        if (function_exists('is_cart') && is_cart()) {
            return false;
        }

        if (function_exists('is_checkout') && is_checkout()) {
            return false;
        }

        if (function_exists('is_checkout_pay_page') && is_checkout_pay_page()) {
            return false;
        }

        // Avoid execution during background cart/checkout AJAX or webhooks
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            $action = sanitize_text_field($_REQUEST['action'] ?? '');
            if (str_contains($action, 'woocommerce_checkout') || str_contains($action, 'woocommerce_add_to_cart')) {
                return false;
            }
        }

        // Check URI patterns if available in server globals
        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        if (!empty($uri)) {
            if (str_contains($uri, '/checkout') || str_contains($uri, '/cart') || str_contains($uri, 'wc-api=')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build comprehensive Schema.org Product JSON-LD for a given product ID.
     *
     * @param int $product_id Product post ID.
     * @return array<string, mixed> Structured Product schema or empty array.
     */
    public static function build_product_schema(int $product_id): array
    {
        if (!self::is_safe_commerce_context()) {
            return [];
        }

        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'product') {
            return [];
        }

        $canonical = Meta::get_canonical_url($product_id);
        $title = Meta::get_title($product_id) ?: $post->post_title;
        $description = Meta::get_description($product_id) ?: wp_strip_all_tags($post->post_excerpt ?: $post->post_content);

        // Retrieve product via WC helper if available
        $product = null;
        if (function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
        }

        // Base price, currency, SKU, availability
        $price = '0.00';
        $currency = 'USD';
        $in_stock = true;
        $sku = '';
        $average_rating = 0.0;
        $review_count = 0;
        $brand_name = (string) get_bloginfo('name');
        $gtin = '';

        if ($product && is_object($product)) {
            $price = method_exists($product, 'get_price') ? (string) $product->get_price() : '0.00';
            $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD';
            $in_stock = method_exists($product, 'is_in_stock') ? (bool) $product->is_in_stock() : true;
            $sku = method_exists($product, 'get_sku') ? (string) $product->get_sku() : '';
            $average_rating = method_exists($product, 'get_average_rating') ? (float) $product->get_average_rating() : 0.0;
            $review_count = method_exists($product, 'get_review_count') ? (int) $product->get_review_count() : 0;

            // Brand attribute lookup
            if (method_exists($product, 'get_attribute')) {
                $attr_brand = $product->get_attribute('brand');
                if (!empty($attr_brand)) {
                    $brand_name = $attr_brand;
                }
                $attr_gtin = $product->get_attribute('gtin') ?: $product->get_attribute('barcode');
                if (!empty($attr_gtin)) {
                    $gtin = $attr_gtin;
                }
            }
        } else {
            // Postmeta fallback for testing or lightweight mock
            $meta_price = get_post_meta($product_id, '_price', true);
            if ($meta_price !== '') {
                $price = (string) $meta_price;
            }
            $meta_sku = get_post_meta($product_id, '_sku', true);
            if ($meta_sku !== '') {
                $sku = (string) $meta_sku;
            }
            $stock_status = get_post_meta($product_id, '_stock_status', true);
            if ($stock_status === 'outofstock') {
                $in_stock = false;
            }
            $meta_rating = get_post_meta($product_id, '_wc_average_rating', true);
            if ($meta_rating) {
                $average_rating = (float) $meta_rating;
            }
            $meta_reviews = get_post_meta($product_id, '_wc_review_count', true);
            if ($meta_reviews) {
                $review_count = (int) $meta_reviews;
            }
        }

        $home_url = trailingslashit(home_url());

        // Construct Offer
        $offer = [
            '@type'                  => 'Offer',
            '@id'                    => $canonical . '#offer',
            'url'                    => $canonical,
            'price'                  => number_format((float) $price, 2, '.', ''),
            'priceCurrency'          => $currency,
            'priceValidUntil'        => gmdate('Y-12-31', strtotime('+1 year')),
            'availability'           => $in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition'          => 'https://schema.org/NewCondition',
            'seller'                 => [
                '@type' => 'Organization',
                'name'  => get_bloginfo('name'),
                'url'   => $home_url,
            ],
            'hasMerchantReturnPolicy' => [
                '@type'                 => 'MerchantReturnPolicy',
                'applicableCountry'     => 'US',
                'returnPolicyCategory'  => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays'    => 30,
                'returnMethod'          => 'https://schema.org/ReturnByMail',
                'returnFees'            => 'https://schema.org/FreeReturn',
            ],
            'shippingDetails'        => [
                '@type'               => 'OfferShippingDetails',
                'shippingRate'        => [
                    '@type'    => 'MonetaryAmount',
                    'value'    => '0.00',
                    'currency' => $currency,
                ],
                'shippingDestination' => [
                    '@type'          => 'DefinedRegion',
                    'addressCountry' => 'US',
                ],
                'deliveryTime'        => [
                    '@type'        => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type'    => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 1,
                        'unitCode' => 'DAY',
                    ],
                    'transitTime'  => [
                        '@type'    => 'QuantitativeValue',
                        'minValue' => 2,
                        'maxValue' => 5,
                        'unitCode' => 'DAY',
                    ],
                ],
            ],
        ];

        $schema = [
            '@type'            => 'Product',
            '@id'              => $canonical . '#product',
            'name'             => $title,
            'description'      => $description,
            'mainEntityOfPage' => $canonical,
            'inLanguage'       => get_locale(),
            'brand'            => [
                '@type' => 'Brand',
                'name'  => $brand_name,
            ],
            'offers'           => $offer,
        ];

        if (!empty($sku)) {
            $schema['sku'] = $sku;
        }

        if (!empty($gtin)) {
            $schema['gtin'] = $gtin;
        }

        // Image
        if (function_exists('has_post_thumbnail') && has_post_thumbnail($product_id)) {
            $thumb_id = get_post_thumbnail_id($product_id);
            $img_src = function_exists('wp_get_attachment_image_src') ? wp_get_attachment_image_src($thumb_id, 'full') : false;
            if ($img_src && !empty($img_src[0])) {
                $schema['image'] = esc_url_raw($img_src[0]);
            }
        }

        // Aggregate Rating
        if ($review_count > 0 && $average_rating > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => number_format($average_rating, 1, '.', ''),
                'reviewCount' => $review_count,
            ];
        }

        return apply_filters('answermesh_woocommerce_product_schema', $schema, $product_id);
    }
}
