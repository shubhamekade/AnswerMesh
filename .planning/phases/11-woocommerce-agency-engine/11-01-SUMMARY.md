# Summary 11-01: WooCommerce Schema & Product Question Extraction Engine

**Phase:** 11 — WooCommerce Structured Commerce & Agency Engine  
**Plan ID:** 11-01  
**Status:** Complete  
**Date Completed:** 2026-10-23  

---

## 1. What Was Delivered
1. **`AnswerMesh\WooCommerceSchema` (`includes/class-woo-commerce-schema.php`):**
   - Active WooCommerce detection (`class_exists`, `function_exists`, and active plugin fallback).
   - Context protection guard (`is_safe_commerce_context()`): checks `is_cart()`, `is_checkout()`, `is_checkout_pay_page()`, AJAX actions, and URI patterns to enforce zero execution overhead on checkout/cart/webhooks (NFR-M4-01).
   - Generates Schema.org `Product` JSON-LD graph with `Offer` (`price`, `priceCurrency`, `availability`), `hasMerchantReturnPolicy` (30-day return window, FreeReturn), `shippingDetails` (`OfferShippingDetails` with transit/handling windows), and `AggregateRating` (REQ-M4-01).
2. **`AnswerMesh\ProductQuestionEngine` (`includes/class-product-question-engine.php`):**
   - Extracts product dimensions (length, width, height, weight), pricing, stock availability, specifications, return policy, and shipping terms (REQ-M4-02).
   - Synthesizes high-intent question-and-answer pairs for generative AI commerce engines.
   - Generates Schema.org `FAQPage` JSON-LD graph with nested `Question` and `acceptedAnswer` nodes.
   - Accessible HTML accordion renderer `render_qa_html()` for frontend presentation.
3. **Schema Graph & REST API Integration:**
   - Updated `Schema::build_graph()` and `Schema::build_post_schema()` to detect singular product requests and embed product structured data and FAQ entities.
   - Registered REST endpoints:
     - `GET /aiseo/v1/commerce/status`
     - `GET /aiseo/v1/commerce/product/{id}/schema`
     - `GET /aiseo/v1/commerce/product/{id}/qa`
4. **Automated Unit Testing:**
   - Created `tests/test-woocommerce.php` with 30 assertions validating schema structure, offer properties, return policies, shipping specs, Q&A synthesis, FAQ schema, and cart/checkout bypass safety.
   - Added `TestWooCommerce` to `tests/runner.php`. All 244 test suite assertions pass.

---

## 2. Verification
- `php tests/runner.php` executed cleanly with 244/244 passing assertions.
