# AnswerMesh — AI Search Visibility, AEO, GEO & Technical SEO

> Ground truth optimization engine for modern search: Generative AI, Answer Engines, and Technical SEO for WordPress.

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Frontend Overhead](https://img.shields.io/badge/Frontend%20Overhead-%3C0.02ms-success.svg)](#performance-benchmarks)

---

## Overview

**AnswerMesh** is a next-generation WordPress plugin built to prepare content for the AI search era. Where traditional SEO plugins focus strictly on keyword meta tags and basic sitemaps, AnswerMesh audits and optimizes your content across the entire modern retrieval spectrum:

1. **AEO (Answer Engine Optimization):** Ensures content delivers direct, quotable answers to user intent queries.
2. **GEO (Generative Engine Optimization):** Structures citations, statistics, and verifiable claims so Perplexity, Gemini, ChatGPT, and Copilot can accurately cite your site.
3. **Entity Clarity:** Anchors your content against a verified Business Knowledge Base using structured Schema.org graphs.
4. **Trust & Citation Signals:** Audits author credentials, publication timestamps, and external evidence links.
5. **Technical SEO:** Delivers high-performance metadata, canonicals, robots controls, and conflict-free JSON-LD schemas.

---

## Key Features

- **Multi-Layer Diagnostic Scoring (0–100):**
  - Technical SEO (20%)
  - Answer Engine Optimization (20%)
  - Generative Engine Optimization (20%)
  - Entity Clarity (15%)
  - Trust & Citation Signals (15%)
  - Content Quality (10%)
- **7-Attribute Evidence Model:** Every audit observation is paired with verifiable evidence, search impact, concrete recommendations, priority level, confidence score, and an acceptance check.
- **Business Knowledge Base:** Centralized entity catalog (Organization, Person, Service, Product, LocalBusiness) injected into Schema `@graph` and LLM prompt context to eliminate hallucination.
- **AI Intelligence Adapters:** Pluggable adapters for OpenAI (`gpt-4o`, `gpt-4o-mini`) and Google Gemini (`gemini-1.5-flash`, `gemini-1.5-pro`) with AES-256-CBC encrypted API key storage.
- **Human-in-the-Loop Diff Approval:** AI-suggested content improvements require explicit review in an interactive diff modal before committing changes.
- **Zero Frontend Overhead:** Tested at ~0.01ms per request. Zero outbound HTTP or external AI requests occur on public page loads.
- **Gutenberg Editor Sidebar:** Real-time visibility score gauge, question discovery monitor, and one-click AI block generation right in the block editor.

---

## Architecture & Codebase Layout

```
answermesh/
├── answermesh.php             # Main bootstrap, autoloader & lifecycle hooks
├── uninstall.php              # Clean database and settings purge
├── admin/
│   ├── class-admin-menu.php   # Admin menu registration & mount containers
│   └── class-admin-assets.php # Admin & Gutenberg asset enqueuer
├── public/
│   ├── class-meta-output.php  # Public <head> metadata output
│   ├── class-schema-output.php# Public Schema.org @graph JSON-LD renderer
│   └── class-breadcrumbs.php  # Semantic breadcrumb trails
├── includes/
│   ├── class-plugin.php       # Core singleton orchestrator container
│   ├── class-database.php     # Custom table schema definitions (5 tables)
│   ├── class-encryption.php   # Symmetric AES-256-CBC cipher & key masking
│   ├── class-settings.php     # Partitioned settings schema & persistence
│   ├── class-rest-api.php     # REST API controller (/wp-json/aiseo/v1/)
│   ├── class-meta.php         # Post metadata management & fallback engine
│   ├── class-schema.php       # Unified Schema.org @graph builder
│   ├── class-schema-conflict.php # Schema conflict & duplicate detector
│   ├── class-compatibility.php# Third-party SEO plugin detection
│   ├── class-sitemap.php      # Sitemaps with noindex exclusions
│   ├── class-post-analyzer.php# DOM parser, heading hierarchy & word counts
│   ├── class-entity.php       # Verified business entity CRUD
│   ├── class-question-engine.php # Question discovery & intent classifier
│   ├── class-trust-analyzer.php  # Author credentials & citation scorer
│   ├── class-site-analyzer.php# Bounded background batch crawler
│   ├── class-score.php        # Multi-layer diagnostic scoring engine
│   ├── class-recommendation-engine.php # 7-attribute Evidence Model engine
│   ├── class-ai-provider-interface.php # AI provider abstraction contract
│   ├── class-ai-openai.php    # Native OpenAI API adapter
│   ├── class-ai-gemini.php    # Native Google Gemini API adapter
│   ├── class-ai.php           # Provider factory & key resolver
│   ├── class-prompt-builder.php # Ground truth prompt builder
│   └── class-content-engine.php # Answer & FAQ block generator
├── src/                       # React admin & Gutenberg sidebar sources
│   ├── index.js               # Admin application mount
│   ├── index.css              # Admin & editor styles
│   ├── components/            # React admin components
│   └── gutenberg/             # Gutenberg sidebar plugin & diff modal
├── build/                     # Compiled production bundles
├── tests/                     # Automated test suites & benchmarks
│   ├── bootstrap.php          # WordPress mock harness
│   ├── runner.php             # Test suite discovery & execution runner
│   ├── audit-security.php     # Static security & SQLi code auditor
│   └── benchmark-performance.php # 1000-cycle performance benchmark
└── package.json               # @wordpress/scripts build toolchain
```

---

## REST API Reference

All endpoints are registered under `/wp-json/aiseo/v1/`:

| Endpoint | Method | Permission | Description |
|---|---|---|---|
| `/status` | `GET` | Public | System status, DB table health, AI configuration |
| `/dashboard` | `GET` | `manage_options` | Site scores, top priorities, crawl coverage stats |
| `/settings` | `GET`, `POST` | `manage_options` | Read and update plugin configuration |
| `/entities` | `GET`, `POST` | `manage_options` | List or create verified business entities |
| `/entities/{id}` | `DELETE` | `manage_options` | Delete a verified entity |
| `/questions` | `GET` | `manage_options` | List site questions and coverage statuses |
| `/recommendations`| `GET` | `manage_options` | Filterable recommendations feed |
| `/crawl/start` | `POST` | `manage_options` | Trigger background batch crawl |
| `/crawl/status` | `GET` | `manage_options` | Get current site crawl status |
| `/posts/{id}/analysis` | `GET` | `edit_posts` | Post scores, question coverage, heading hierarchy |
| `/posts/{id}/analyze` | `POST` | `edit_posts` | Run on-demand audit for a post |
| `/posts/{id}/generate-answer`| `POST` | `edit_posts` | Generate direct answer block |
| `/posts/{id}/generate-faq` | `POST` | `edit_posts` | Generate structured FAQ section |
| `/posts/{id}/apply-diff` | `POST` | `edit_posts` | Commit approved content diff |
| `/ai/test-connection` | `POST` | `manage_options` | Test API key connectivity |

---

## Testing & Quality Verification

Run the automated test suites using PHP CLI:

```bash
# Run unit & subsystem assertion tests
php tests/runner.php

# Run static security and SQL injection audit
php tests/audit-security.php

# Run frontend performance benchmark (1,000 cycles)
php tests/benchmark-performance.php
```

### Performance Benchmarks

- **Frontend Render Overhead:** ~0.0125 ms / request (SLA target: <50 ms).
- **Outbound Frontend Calls:** 0 HTTP requests, 0 external AI calls.
- **Cache Strategy:** Schema graphs and post scores are cached via WordPress transients and postmeta.

---

## Requirements

- **PHP:** 8.1 or higher (PHP 8.2 and 8.3 fully supported).
- **WordPress:** 6.0 or higher.
- **Optional:** OpenAI API Key or Google Gemini API Key for generative content features. (All SEO audits, rule-based heuristics, and Schema generation function in offline mode without an API key).

---

## License

GPL-2.0-or-later
