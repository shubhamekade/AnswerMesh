# Roadmap: AnswerMesh

**Product:** AnswerMesh WordPress Plugin  
**Version:** 1.0 (MVP)  
**Methodology:** GSD (Phased Wave Implementation)  
**Reference Document:** [answermesh-prd.md](file:///d:/Projects/Wordpress%20Plugins/AnswerMesh/docs/answermesh-prd.md) | [REQUIREMENTS.md](file:///d:/Projects/Wordpress%20Plugins/AnswerMesh/.planning/REQUIREMENTS.md)

---

## Milestone 1: AnswerMesh MVP (Phases 1–7)

### Phase 1: Plugin Foundation & Core Architecture
- **Goal:** Establish WordPress plugin lifecycle, custom database architecture, settings management, secure REST API controller, and modern asset build pipeline.
- **Deliverables:**
  - `answermesh.php` main bootstrap file with hooks and autoloader.
  - `includes/class-plugin.php` dependency container and lifecycle orchestrator.
  - Custom DB tables migration runner for `wp_aiseo_scores`, `wp_aiseo_entities`, `wp_aiseo_questions`, `wp_aiseo_recommendations`, `wp_aiseo_visibility`.
  - Settings API wrapper for General, AI, SEO, Schema, and Privacy.
  - REST API base controller registering `/wp-json/aiseo/v1/` routes with capability checks.
  - Setup Wizard skeleton and build environment configured with `@wordpress/scripts`.
- **Plans:** `01-01` Plugin Bootstrap & Database Schema, `01-02` Settings & Security Subsystem, `01-03` REST API Foundation & Asset Pipeline.

---

### Phase 2: Technical SEO Engine & Structured Data
- **Goal:** Implement native on-page metadata generation, Schema.org JSON-LD builder, conflict detection, XML sitemaps, and third-party SEO plugin compatibility.
- **Deliverables:**
  - Metadata generator for titles, meta descriptions, canonicals, robots, and OpenGraph.
  - Schema JSON-LD builder supporting `WebSite`, `Organization`, `LocalBusiness`, `Person`, `Article`, `Service`, `FAQPage`, `BreadcrumbList`.
  - Schema conflict detector detecting duplicates and conflicting entity definitions.
  - XML sitemap provider integrated with WordPress core sitemaps.
  - Third-party SEO plugin detector (Yoast, Rank Math, AIOSEO) with source-of-truth toggles.
- **Plans:** `02-01` Metadata & Frontend Tag Injection, `02-02` Schema.org JSON-LD Engine & Conflict Detector, `02-03` Sitemaps & Third-Party Compatibility Layer.

---

### Phase 3: Content Analyzer & Entity Extraction
- **Goal:** Develop robust post and page content analyzer extracting semantic structure, user questions, entities, and trust signals with bounded site crawl capabilities.
- **Deliverables:**
  - Content parser inspecting heading hierarchy, word count, readability, link density, and images.
  - Business Knowledge Base manager storing verified company facts, founders, offerings, and locations.
  - Question discovery module identifying questions, categorizing intent, and mapping answer coverage.
  - Entity extractor cross-referencing content mentions with verified business knowledge.
  - Trust and citation readiness evaluator inspecting author credentials, citations, and evidence.
  - Bounded crawler (50/500/5000 limits) utilizing WP Cron or chunked batch processing.
- **Plans:** `03-01` Content Parser & Semantic Structure Inspector, `03-02` Question Discovery & Entity Extractor, `03-03` Trust Evaluator & Bounded Crawler.

---

### Phase 4: Multi-Layer Diagnostic Scoring System
- **Goal:** Implement the AnswerMesh Visibility Score and the Evidence Model recommendation engine.
- **Deliverables:**
  - Individual score evaluators: SEO (20%), AEO (20%), GEO (20%), Entity (15%), Trust (15%), Content (10%).
  - Composite 0–100 AnswerMesh Visibility Score calculator with diagnostic rating levels.
  - Evidence Model recommendation generator producing structured findings with `Observation`, `Evidence`, `Impact`, `Recommendation`, `Priority`, `Confidence`, and `Acceptance Check`.
  - Priority sorting engine ranking actions by business impact, AI visibility gain, and implementation ease.
- **Plans:** `04-01` Layer Scoring Calculators, `04-02` Composite Visibility Score & Rating Engine, `04-03` Evidence Model Recommendation Generator.

---

### Phase 5: AI Intelligence Services & Provider Abstraction
- **Goal:** Build the pluggable AI provider architecture with secure server-side execution, verified context injection, and human-in-the-loop preview.
- **Deliverables:**
  - `AIProviderInterface` defining `generate()` and `analyze()` contracts.
  - OpenAI adapter (GPT-4o / GPT-4o-mini) and Google Gemini adapter (Gemini 1.5/2.0).
  - Secure credential storage with symmetric encryption for API keys.
  - Prompt builder fusing verified business knowledge, page context, brand voice, and anti-hallucination rules.
  - Diff generation and proposal formatter for answers, FAQs, schema corrections, and content improvements.
  - Graceful offline / no-AI fallback mode maintaining all rule-based analyses.
- **Plans:** `05-01` Provider Interface & Credential Encryption, `05-02` OpenAI & Gemini Adapters with Fallback Handling, `05-03` Prompt Engineering & Diff Generation Pipeline.

---

### Phase 6: Admin Dashboard & Editor Experience
- **Goal:** Create the React-powered Admin Dashboard and Gutenberg block editor sidebar for seamless user management and approval.
- **Deliverables:**
  - React Admin Dashboard built with `@wordpress/components`:
    - Overall visibility score radial gauge and layer breakdown meters.
    - Priority recommendations feed with action triggers.
    - Business Knowledge Base editor.
    - Post inventory table with filterable scores and quick analysis.
  - Gutenberg Editor Sidebar Plugin:
    - Real-time score display.
    - Detected questions and coverage flags.
    - Action buttons: Generate Answer, Generate FAQ, Fix Entity Clarity.
    - Interactive diff preview and approval modal.
  - Setup Wizard interactive walkthrough for first-time configuration.
- **Plans:** `06-01` React Admin App Shell & Score Dashboard, `06-02` Recommendations Feed & Knowledge Base UI, `06-03` Gutenberg Sidebar & Diff Approval Modal.

---

### Phase 7: Verification, Security Hardening & MVP Polish
- **Goal:** Validate end-to-end functionality, conduct comprehensive security audits, benchmark frontend performance, and complete documentation.
- **Deliverables:**
  - Automated test suite: PHPUnit tests for score algorithms, schema output, REST endpoints, and security checks.
  - Security audit verifying nonces, SQL escaping, input sanitization, capability checks, and credential protection.
  - Performance profiling ensuring frontend overhead remains strictly below 50ms.
  - Complete user guide and developer documentation.
- **Plans:** `07-01` PHPUnit Test Suites & Test Harness, `07-02` Security Audit & Performance Benchmarking, `07-03` Release Packaging & Verification Gate.

---

## Future Milestones (Post-MVP)

### Milestone 2: Topic Clusters, Content Decay & Internal Links (Phase 8–9)
- Automated pillar-to-cluster taxonomy and gap identification.
- Content freshness tracking and decay alerts.
- Site-wide internal link graph analysis with anchor recommendations.
- Competitor content gap comparison matrix.

### Milestone 3: Observed AI Search Visibility Monitoring (Phase 10)
- Scheduled query tracking across ChatGPT, Gemini, Perplexity, and Copilot.
- Brand mention, citation, and competitor observation history.
- Historical trend analytics and automated PDF/CSV client reports.

### Milestone 4: WooCommerce & Agency Engine (Phase 11)
- WooCommerce product schema, attribute-based buying guide generator.
- Multi-site WordPress multisite management.
- White-label agency reporting and centralized API connector.
