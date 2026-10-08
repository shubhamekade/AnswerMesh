# Requirements: AnswerMesh (v1.0 MVP)

**Status:** Approved  
**Source Document:** [docs/answermesh-prd.md](file:///d:/Projects/Wordpress%20Plugins/AnswerMesh/docs/answermesh-prd.md)  
**Target Platform:** WordPress 6.x+, PHP 8.1+

---

## 1. Functional Requirements (MVP)

### 1.1 Foundation & Core Architecture
- **REQ-F01 (Bootstrap & Lifecycle):** Plugin must register activation, deactivation, and uninstall hooks cleanly. Activation must provision custom database tables; deactivation preserves data; uninstall provides optional clean removal.
- **REQ-F02 (Database Schema):** Provide versioned database migrations creating 5 custom tables with prefix `$wpdb->prefix`:
  - `wp_aiseo_scores`: per-post and site-level scores across 5 layers.
  - `wp_aiseo_entities`: entities (Organization, Person, Service, Product, Location) with structured JSON payload.
  - `wp_aiseo_questions`: extracted/discovered questions with intent, status (covered/weak/missing), and target post.
  - `wp_aiseo_recommendations`: actionable recommendations with evidence model attributes.
  - `wp_aiseo_visibility`: query tracking entries for observed AI visibility.
- **REQ-F03 (Settings Management):** Settings API for General options, AI provider configuration, SEO defaults, Schema options, Brand Voice guidelines, and Privacy/telemetry preferences.
- **REQ-F04 (REST API Controller):** Secure REST API under namespace `/wp-json/aiseo/v1/` with permission callbacks (`manage_options` for admin, `edit_posts` for editorial features):
  - `GET /dashboard`: overview metrics, score breakdown, top priorities.
  - `GET /posts/{id}/analysis`: detailed layer scores and audit issues for a post.
  - `POST /posts/{id}/analyze`: trigger on-demand page analysis.
  - `POST /posts/{id}/optimize`: request AI-assisted improvements.
  - `POST /posts/{id}/generate-answer`: generate direct answer block for a specific question.
  - `POST /posts/{id}/generate-faq`: generate structured FAQ pairs.
  - `GET/POST /entities`: retrieve and store verified business knowledge entities.
  - `GET /questions`: list site questions and coverage statuses.
  - `GET /recommendations`: list pending recommendations with filters.
  - `POST /recommendations/{id}/apply`: apply an approved recommendation.
- **REQ-F05 (Setup Wizard):** First-run onboarding wizard collecting core business identity (Name, Type, Offerings, Service Areas, Target Audience) and connecting initial AI provider.

### 1.2 Technical SEO & Structured Data Engine
- **REQ-F06 (Metadata Management):** Output optimized SEO titles, meta descriptions, canonical URLs, robots directives (`index`/`noindex`, `follow`/`nofollow`), and OpenGraph tags on public frontend requests.
- **REQ-F07 (Schema.org JSON-LD Generator):** Automatically generate valid Schema.org JSON-LD scripts based on verified site data:
  - Global schemas: `WebSite`, `Organization`, `LocalBusiness`, `Person`.
  - Contextual schemas: `Article`, `BlogPosting`, `WebPage`, `Service`, `Product`, `BreadcrumbList`, `FAQPage`.
- **REQ-F08 (Schema Conflict Detection):** Detect schema duplication, multiple conflicting Organization entities, invalid syntax, or missing mandatory properties.
- **REQ-F09 (XML Sitemap & Breadcrumbs):** Generate clean, search-engine-compliant XML sitemaps and breadcrumb trails with Schema integration.
- **REQ-F10 (SEO Plugin Compatibility Layer):** Detect active third-party SEO plugins (Yoast, Rank Math, All in One SEO). When detected, offer compatibility mode allowing user to choose source of truth for metadata vs. schema, suppressing duplicate tag outputs.

### 1.3 Content & Entity Analyzer
- **REQ-F11 (DOM & Content Parsing):** Parse post content to extract headings hierarchy (H1–H6), word counts, readability, image alt attributes, internal and external links.
- **REQ-F12 (Question Discovery & Coverage):** Identify user questions answered or implied within content, categorize by intent (Informational, Commercial, Transactional, Navigational, Comparison, Local), and assess answer clarity (Answered, Weak, Missing).
- **REQ-F13 (Entity Extraction & Knowledge Base):** Map content mentions against verified business knowledge (founder, team, services, locations, USPs), tracking entity relationships and consistency.
- **REQ-F14 (Trust & Citation Readiness Check):** Detect author bios, verifiable credentials, publication/update timestamps, citations, external evidence links, and statistical claims.
- **REQ-F15 (Bounded Site Crawler):** Execute background crawls using WP Cron or chunked REST batches with strict boundaries (Free: 50 pages, Pro: 500 pages, Agency: 5,000 pages).

### 1.4 Diagnostic Scoring & Evidence Engine
- **REQ-F16 (AnswerMesh Visibility Score):** Calculate 0–100 diagnostic score weighted as:
  - Technical SEO: 20%
  - AEO (Answer Engine Optimization): 20%
  - GEO (Generative Engine Optimization): 20%
  - Entity Clarity: 15%
  - Trust Signals: 15%
  - Content Quality: 10%
- **REQ-F17 (Evidence Model Recommendations):** Every diagnostic issue must generate a structured recommendation:
  - `Observation`: What is detected.
  - `Evidence`: Specific snippet/data backing the finding.
  - `Impact`: Why it matters for AI search and users.
  - `Recommendation`: Precise, actionable next step.
  - `Priority`: `CRITICAL`, `HIGH`, `MEDIUM`, `LOW`, `INFO`.
  - `Confidence`: `High`, `Medium`, `Low`.
  - `Acceptance Check`: Verifiable condition confirming the fix.

### 1.5 AI Intelligence & Safe Content Assistant
- **REQ-F18 (Provider Abstraction Interface):** Core `AIProviderInterface` decoupling business logic from external LLMs.
- **REQ-F19 (Initial Adapters):** Implement native adapters for OpenAI (GPT-4o / GPT-4o-mini) and Google Gemini (Gemini 1.5 / 2.0 Flash / Pro) with server-side API key encryption.
- **REQ-F20 (Context-Rich Prompt Construction):** Build prompts incorporating verified Business Knowledge, page context, target audience, brand voice, and strict anti-hallucination instructions ("Never invent facts, statistics, credentials, or reviews").
- **REQ-F21 (Human-in-the-Loop Preview & Apply):** All AI-generated content (answers, FAQs, meta improvements, schema fixes) must present a visual diff preview. Changes are only committed to post content/meta upon explicit user approval.
- **REQ-F22 (Offline / No-AI Fallback):** Full technical SEO, rule-based AEO/GEO analysis, and Schema generation must work without an active AI API key.

### 1.6 Admin Interface & Editor Integration
- **REQ-F23 (React Admin Dashboard):** Native React dashboard rendered in `admin.php?page=answermesh` using `@wordpress/components`:
  - Overall Visibility Score gauge and 5 layer break-down bars.
  - Priority Action List with inline filter and action triggers.
  - Business Knowledge Base management tab.
  - Page inventory analysis table.
- **REQ-F24 (Gutenberg Block Editor Sidebar):** Register a sidebar plugin via `wp.plugins.registerPlugin` showing live page scores, detected questions, schema preview, and AI action buttons (Generate Answer, Generate FAQ, Enhance Entity Clarity).

---

## 2. Non-Functional Requirements (NFR)

- **NFR-01 (Security & Sanitization):**
  - All user inputs sanitized with `sanitize_text_field`, `wp_kses_post`, etc.
  - All database queries prepared via `$wpdb->prepare()`.
  - Nonces verified on all form submissions and REST API requests.
  - Strict capability enforcement (`manage_options`, `edit_posts`).
  - API keys encrypted at rest; never transmitted or exposed in front-end script locals.
- **NFR-02 (Performance):**
  - Public front-end execution overhead strictly under 50ms.
  - Zero external HTTP or AI calls during public frontend page loads.
  - Transients / object caching utilized for Schema JSON-LD and analysis outputs.
  - Admin JavaScript assets loaded strictly on AnswerMesh admin pages and post edit screens.
- **NFR-03 (Compatibility):**
  - WordPress 6.0+ through current latest release.
  - PHP 8.1, 8.2, and 8.3 compatibility with zero deprecation notices.
  - Compatibility with Gutenberg, Classic Editor, Elementor, and major theme frameworks.
- **NFR-04 (Privacy & Telemetry):**
  - Explicit opt-in toggles for sending content to AI providers and anonymous telemetry.
  - Clear data retention disclosure.
  - Complete data wipe capability on plugin uninstall.
- **NFR-05 (Code Quality & Testing):**
  - PSR-12 / WordPress Coding Standards (WPCS) compliance.
  - Unit and integration test coverage via PHPUnit.
  - Jest / React Testing Library tests for frontend components where applicable.

---

## 3. Future Roadmap Requirements (Post-MVP)

- **Phase 2 (Topic Clusters & Content Decay):** Automated pillar-to-cluster taxonomy mapping, orphan page discovery, content freshness tracking, internal linking graph engine, competitor content gap matrix.
- **Phase 3 (Observed AI Visibility Monitoring):** Scheduled query monitoring for ChatGPT, Gemini, Perplexity, and Copilot; historical observation charts; mention and citation tracking.
- **Phase 4 (WooCommerce & Multi-site Agency):** WooCommerce product schema, attribute-based answer generation, multi-site management dashboard, client PDF white-label reports, cloud crawler.
