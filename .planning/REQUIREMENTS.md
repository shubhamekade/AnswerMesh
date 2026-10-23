# Requirements: AnswerMesh (Milestone 4 — WooCommerce & Agency Engine)

**Status:** Approved  
**Milestone:** Milestone 4 (Phase 11)  
**Reference Document:** [docs/answermesh-prd.md](file:///d:/Projects/Wordpress%20Plugins/AnswerMesh/docs/answermesh-prd.md)  
**Target Platform:** WordPress 6.x+, WooCommerce 8.x+, PHP 8.1+

---

## 1. Functional Requirements (Milestone 4)

### 1.1 WooCommerce Structured Commerce & Agency Engine (Phase 11)
- **REQ-M4-01 (WooCommerce Product Schema & Return Policy):** Automatically generate comprehensive Schema.org `Product` JSON-LD for WooCommerce products, including `Offer`, `priceCurrency`, `price`, `availability`, `MerchantReturnPolicy`, `ShippingDetails`, and `AggregateRating`.
- **REQ-M4-02 (Product Question Answering & Attribute Extraction):** Automatically extract product attributes, dimensions, specifications, and warranty information into high-intent product questions with direct concise answers for generative commerce engines.
- **REQ-M4-03 (Autonomous Buying Guide & Comparison Generator):** Generate structured comparison tables and buying guide content blocks for WooCommerce product categories, comparing key attributes across category products without manual data entry.
- **REQ-M4-04 (Multi-Site Network Management):** Support WordPress Multisite network activation, network-level settings inheritance, and per-site override policies.
- **REQ-M4-05 (Agency White-Label Branding):** Customization controls to rebrand the plugin in WordPress admin: custom plugin name, custom logo/icon, custom support link, and removal of AnswerMesh promotional credits.
- **REQ-M4-06 (Agency Client Report Generator):** 1-Click generation of branded client audit reports summarizing SEO, AEO, GEO scores, topic cluster health, and AI search visibility observations.

---

## 2. Non-Functional Requirements (Milestone 4)

- **NFR-M4-01 (Zero WooCommerce Checkout Impact):** Schema and question generators must never execute on checkout, cart, or payment webhooks.
- **NFR-M4-02 (Graceful Deactivation if WooCommerce Absent):** Automatically detect if WooCommerce is active; deactivate commerce modules silently when inactive without throwing fatal errors.
- **NFR-M4-03 (Automated Unit Testing):** Unit tests verifying WooCommerce schema generation, buying guide data structures, white-label overrides, and multi-site option handling.
