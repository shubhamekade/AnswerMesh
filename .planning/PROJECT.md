# Project: AnswerMesh

**Type:** WordPress Plugin  
**Version:** 1.0 (MVP)  
**Status:** Initialized  
**Category:** AI Search Visibility, AEO, GEO & SEO for WordPress  
**Tagline:** Make your website understandable, trustworthy, and cite-ready for AI search.  
**Reference Document:** [answermesh-prd.md](file:///d:/Projects/Wordpress%20Plugins/AnswerMesh/docs/answermesh-prd.md)

---

## 1. Executive Summary & Vision

AnswerMesh is a WordPress SEO and AI-search optimization plugin designed to help websites become easier for traditional search engines and AI answer engines (ChatGPT, Google AI Overviews/Gemini, Perplexity, Copilot) to understand, trust, extract information from, and reference.

The product transforms SEO from pure keyword optimization into **evidence-backed search visibility optimization**, combining:
- Traditional technical SEO
- Answer Engine Optimization (AEO)
- Generative Engine Optimization (GEO)
- Entity SEO & Site Knowledge Graph
- Topical Authority & Content Clusters
- Trust & Evidence Signals
- AI-Assisted Content Optimization (Human-in-the-loop preview/approval)
- Structured Data (Schema.org JSON-LD)
- Competitor & Content Gap Analysis
- AI-Search Visibility Monitoring

### Core Product Principle
> **Do not optimize only for keywords. Optimize the website so search engines and AI systems can clearly understand the business, entities, topics, answers, evidence, relationships, and expertise represented by the site.**

The plugin will **never** claim its score is an official search engine ranking score or guarantee citations.

---

## 2. Five Optimization Layers

1. **Layer 1 — SEO (Technical & On-Page):** Titles, meta descriptions, headings hierarchy, canonicals, robots, indexability, sitemaps, open graph, breadcrumbs.
2. **Layer 2 — AEO (Answer Engine Optimization):** Question coverage, direct concise answers, FAQ blocks, clear intent resolution, answer placement.
3. **Layer 3 — GEO (Generative Engine Optimization):** Semantic coverage, citation readiness, topical completeness, factual consistency, machine-readable structured info.
4. **Layer 4 — Entity:** Structured representations of Organization, Person, Services, Products, Locations, and their interconnected relationships.
5. **Layer 5 — Trust:** Author credentials, business identity, verifiable evidence, source citations, publication/update timestamps, review sources.

---

## 3. Technology Stack & Architecture

- **Platform:** WordPress 6.x+, PHP 8.1+
- **Database:** MySQL 8.0+ / MariaDB with custom tables for high-volume analytics (`wp_aiseo_scores`, `wp_aiseo_entities`, `wp_aiseo_questions`, `wp_aiseo_recommendations`, `wp_aiseo_visibility`).
- **Admin UI & Editor:** React built with `@wordpress/scripts`, `@wordpress/element`, `@wordpress/components`, WordPress REST API, `@wordpress/api-fetch`.
- **Block Editor Integration:** Gutenberg sidebar panel + filter hooks for classic editor and visual builders (Elementor/Divi compatible via post meta and `the_content` hooks).
- **AI Engine:** Pluggable server-side provider abstraction (`AIProviderInterface`) supporting OpenAI, Google Gemini, Anthropic, OpenRouter, with secure server-side encrypted credentials and offline/no-AI fallback.
- **Frontend Performance:** Zero external HTTP/AI requests on frontend render; cached Schema and metadata; sub-50ms target execution overhead.
- **Compatibility:** Detection and collision avoidance for existing SEO plugins (Yoast SEO, Rank Math, AIOSEO) with configurable source of truth.

---

## 4. MVP Core Goals (Milestones 1–7)

1. **Milestone 1 — Foundation:** Plugin bootstrap, activation hooks, custom tables migration, settings management, REST API endpoints, React admin shell.
2. **Milestone 2 — SEO & Schema Engine:** Metadata generation, Schema JSON-LD builder (Organization, LocalBusiness, Person, Article, Service, FAQPage, etc.), conflict detector, XML sitemaps, compatibility mode.
3. **Milestone 3 — Content & Entity Analyzer:** Content parser, heading hierarchy audit, question discovery, entity extraction, internal link analyzer, trust signal evaluator, bounded crawler (50/500/5000 limits).
4. **Milestone 4 — Diagnostic Scoring System:** Diagnostic scoring algorithm (SEO 20%, AEO 20%, GEO 20%, Entity 15%, Trust 15%, Content 10%), Evidence model (Finding, Evidence, Impact, Recommendation, Priority, Confidence, Acceptance Check).
5. **Milestone 5 — AI Intelligence Services:** Server-side AI provider abstraction (OpenAI, Gemini), safe prompt constructor with business knowledge context, AI content recommendations, diff generation, offline fallback.
6. **Milestone 6 — Admin Dashboard & Editor Sidebar:** React dashboard with score gauge, priority recommendations list, Gutenberg sidebar panel, preview-and-approval diff workflow before applying changes.
7. **Milestone 7 — Verification & Hardening:** PHPUnit test suite, REST permission checks, security sanitization, performance profiling, documentation.

---

## 5. Non-Goals & Safety Constraints

- No ranking guarantees or citation promises.
- No automated mass publishing or unapproved content alterations.
- Never hallucinate or invent business facts, awards, reviews, or statistics.
- No API keys exposed client-side in browser JavaScript.
- No frontend AI execution or external network calls during public visitor requests.
- No unlimited crawl loops from within WordPress execution threads.
