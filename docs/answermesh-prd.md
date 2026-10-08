# AnswerMesh — Product Requirements Document (PRD)

**Version:** 1.0  
**Status:** Draft / MVP Planning  
**Product Type:** WordPress Plugin  
**Product Name:** AnswerMesh  
**Category:** AI Search Visibility, AEO, GEO & SEO for WordPress  
**Tagline:** Make your website understandable, trustworthy, and cite-ready for AI search.

> **Naming decision:** AnswerGraph was considered because it strongly matched the product concept, but current web research shows AnswerGraph is already used by multiple AI-search visibility products. AnswerMesh is therefore used as the product working name for this PRD. Domain, WordPress.org slug, and trademark availability must be validated before public launch.

---

## 1. Executive Summary

AnswerMesh is a WordPress SEO and AI-search optimization plugin designed to help websites become easier for traditional search engines and AI answer engines to understand, trust, extract information from, and reference.

The product combines:

- Traditional technical SEO
- Answer Engine Optimization (AEO)
- Generative Engine Optimization (GEO)
- Entity SEO
- Topical authority
- Trust and evidence signals
- AI-assisted content optimization
- Internal linking
- Structured data / Schema.org
- Site knowledge graph
- AI-search visibility monitoring
- Competitor and content-gap analysis

The product is inspired by the evidence-first workflow of BeyondSEO, but is designed specifically as a native WordPress product.

Reference project:

https://github.com/beyondtahir/beyondseo

The key product principle is:

> **Do not optimize only for keywords. Optimize the website so search engines and AI systems can clearly understand the business, entities, topics, answers, evidence, relationships, and expertise represented by the site.**

The plugin must not claim that its score is an official Google, ChatGPT, Gemini, Perplexity, or Microsoft ranking score.

---

# 2. Problem Statement

Traditional WordPress SEO plugins primarily focus on:

- Keywords
- SEO titles
- Meta descriptions
- Sitemaps
- Schema
- Readability
- Basic on-page SEO

These remain important, but AI-powered search introduces additional challenges.

Users increasingly ask AI systems questions such as:

- Who is the best provider for this service?
- Which product should I choose?
- What is the difference between A and B?
- How much does this service cost?
- Which company serves this location?
- Is this business trustworthy?
- What are the pros and cons?
- Who provides this service in Pune?
- Which school is best for this requirement?

For a website to become a useful source for these answers, it needs more than keywords.

It needs:

1. Clear business identity
2. Strong entities
3. Explicit answers
4. Structured information
5. Supporting evidence
6. Author expertise
7. Topical coverage
8. Internal relationships
9. Consistent facts
10. Machine-readable content
11. Strong technical foundations
12. Regular content maintenance

AnswerMesh addresses these requirements from inside WordPress.

---

# 3. Product Vision

## Vision

Build the leading WordPress plugin for **AI Search Visibility**.

The plugin should become the control center where website owners can understand:

> "How well does my website communicate with search engines and AI systems?"

And then:

> "What should I fix first?"

And finally:

> "Did the changes improve the website?"

---

# 4. Product Mission

AnswerMesh should transform SEO from:

> Keyword optimization

into:

> **Evidence-backed search visibility optimization.**

The plugin should provide actionable recommendations rather than overwhelming users with hundreds of technical warnings.

---

# 5. Target Users

## Primary Users

### 5.1 Small Business Owners

Examples:

- Doctors
- Lawyers
- Real estate companies
- Schools
- Consultants
- Local businesses
- Agencies
- Restaurants
- Service businesses

Needs:

- Better search visibility
- Better local visibility
- More leads
- Easy AI recommendations

---

### 5.2 SEO Professionals

Needs:

- Site audits
- AI visibility analysis
- Content gaps
- Entity analysis
- Internal linking
- Schema
- Competitor analysis
- Reporting

---

### 5.3 WordPress Agencies

Needs:

- Manage multiple websites
- Standardized SEO workflow
- Client reports
- AI content assistance
- Site health monitoring

---

### 5.4 Content Teams

Needs:

- Content optimization
- Topic clusters
- Question discovery
- Content briefs
- AI-assisted rewriting
- Content freshness monitoring

---

### 5.5 E-commerce Stores

Needs:

- Product schema
- Category optimization
- Product entity optimization
- Comparison content
- Buying questions
- Product attributes
- AI-readable product information

WooCommerce support should be part of the roadmap.

---

# 6. Product Goals

## Primary Goals

1. Improve technical SEO foundations.
2. Improve AI-answer readiness.
3. Improve entity clarity.
4. Improve topical authority.
5. Improve trust and evidence signals.
6. Help users identify content gaps.
7. Provide AI-assisted fixes.
8. Generate accurate structured data.
9. Improve internal linking.
10. Track changes over time.
11. Measure observed AI-search visibility where technically possible.

---

# 7. Non-Goals

The product must NOT:

- Guarantee Google rankings.
- Guarantee ChatGPT citations.
- Guarantee Gemini citations.
- Guarantee Perplexity citations.
- Claim to know proprietary search-engine ranking algorithms.
- Automatically create spam pages.
- Automatically publish large amounts of AI content.
- Generate fake reviews.
- Generate fake backlinks.
- Manipulate search results.
- Claim that an internal score is an official search-engine score.

---

# 8. Core Product Concept

The plugin is based on five major optimization layers.

## Layer 1 — SEO

Technical search optimization.

## Layer 2 — AEO

Answer Engine Optimization.

Make pages directly answer user questions.

## Layer 3 — GEO

Generative Engine Optimization.

Make information understandable and useful to AI-powered search systems.

## Layer 4 — Entity

Help systems understand:

- Who
- What
- Where
- When
- Relationships

## Layer 5 — Trust

Provide:

- Author information
- Credentials
- Sources
- Evidence
- Business information
- Dates
- References
- Reviews
- Experience

---

# 9. Product Architecture

```text
                         WordPress Website
                                |
                                v
                     +----------------------+
                     |   AnswerMesh      |
                     |       Plugin         |
                     +----------+-----------+
                                |
          +---------------------+---------------------+
          |                     |                     |
          v                     v                     v
   SEO Engine             AI Engine             Entity Engine
          |                     |                     |
          v                     v                     v
   Technical SEO          Content AI          Knowledge Graph
          |                     |                     |
          +---------------------+---------------------+
                                |
                                v
                       Analysis / Scoring
                                |
              +-----------------+------------------+
              |                 |                  |
              v                 v                  v
          Dashboard         Recommendations    AI Actions
                                |
                                v
                            Verification
```

---

# 10. WordPress Architecture

The plugin should remain WordPress-native.

Recommended structure:

```text
answermesh/
│
├── answermesh.php
│
├── includes/
│   ├── class-plugin.php
│   ├── class-settings.php
│   ├── class-post-analyzer.php
│   ├── class-site-analyzer.php
│   ├── class-schema.php
│   ├── class-meta.php
│   ├── class-sitemap.php
│   ├── class-entity.php
│   ├── class-ai.php
│   ├── class-content-engine.php
│   ├── class-internal-links.php
│   ├── class-score.php
│   ├── class-audit.php
│   └── class-rest-api.php
│
├── admin/
│   ├── dashboard.php
│   ├── pages.php
│   ├── ai-visibility.php
│   ├── entities.php
│   ├── content.php
│   ├── competitors.php
│   ├── settings.php
│   └── assets/
│
├── public/
│   ├── class-meta-output.php
│   ├── class-schema-output.php
│   ├── class-breadcrumbs.php
│   └── class-sitemap.php
│
├── src/
│   └── React/
│
├── languages/
│
├── templates/
│
├── tests/
│
└── uninstall.php
```

---

# 11. Technology Stack

## WordPress

- WordPress 6.x+
- PHP 8.1+
- MySQL 8+ / MariaDB compatible
- WordPress REST API
- WordPress Settings API
- WordPress Metadata API
- WP Cron

## Admin UI

Recommended:

- React
- WordPress components
- WordPress REST API
- @wordpress/data
- @wordpress/api-fetch

Avoid unnecessary external UI frameworks in the first version.

## AI

Provider abstraction:

```php
interface AIProviderInterface
{
    public function generate(string $prompt, array $context);
    public function analyze(string $content, array $context);
}
```

Initial providers:

- OpenAI
- Google Gemini
- Anthropic
- OpenRouter

Future:

- Ollama
- Local models
- Custom API

---

# 12. AI Provider Architecture

Users should be able to select:

```text
AI Provider
-----------------
OpenAI
Gemini
Anthropic
OpenRouter
Custom
```

Settings:

- Provider
- API key
- Model
- Temperature
- Maximum tokens
- Content language

API keys must never be exposed to browser JavaScript.

API calls should happen server-side.

---

# 13. Dashboard

The main dashboard should show:

```text
AI SEARCH SEO
------------------------------------------------

AI SEARCH VISIBILITY

             78 / 100
                GOOD

SEO       ████████████████░░ 86
AEO       ██████████████░░░░ 74
GEO       █████████████░░░░░ 70
ENTITY    ███████████████░░░ 83
TRUST     █████████████░░░░░ 72

------------------------------------------------

TOP PRIORITIES

1. Improve Organization Entity
2. Add answers to 8 important questions
3. Add author expertise information
4. Strengthen internal links
5. Fix missing Service schema

[ Analyze Website ] [ Fix Issues ]
```

---

# 14. Scoring System

AnswerMesh should have an internal diagnostic scoring system.

It must be clearly labeled as:

> AnswerMesh Visibility Score

It must NOT be presented as an official Google or AI-engine score.

## Score

```text
Overall =

SEO              20%
AEO              20%
GEO              20%
Entity           15%
Trust            15%
Content          10%
```

Alternative configurable weighting may be added later.

---

# 15. SEO Score

Evaluate:

- Title
- Meta description
- H1
- Heading hierarchy
- Canonical
- Robots
- Indexability
- Sitemap
- Internal links
- External links
- Image alt text
- Open Graph
- URL structure
- Duplicate metadata
- Broken links
- Schema
- Breadcrumbs

---

# 16. AEO Score

Evaluate:

- Question coverage
- Direct answers
- FAQ coverage
- Answer clarity
- Answer placement
- Content structure
- Question intent
- Conciseness
- Supporting evidence

Example:

```text
AEO SCORE: 72

Questions detected: 18
Answered: 13
Weak answers: 3
Missing: 2
```

---

# 17. GEO Score

Evaluate:

- Content clarity
- Entity relationships
- Semantic coverage
- Topic completeness
- Source quality
- Factual consistency
- Content structure
- Citation readiness
- Author information
- Business information

---

# 18. Entity Score

Evaluate:

### Organization

- Name
- Logo
- URL
- Description
- Address
- Phone
- Email
- Social profiles

### Person

- Name
- Job title
- Bio
- Credentials
- Author profile
- SameAs

### Services

- Name
- Description
- Provider
- Area served

### Products

- Name
- Brand
- Description
- SKU
- Price
- Availability

### Locations

- Address
- City
- Region
- Country
- Geo coordinates

---

# 19. Trust Score

Evaluate:

- Author
- Author bio
- Credentials
- Business identity
- Contact information
- About page
- References
- External sources
- Original research
- Review sources
- Publication dates
- Updated dates
- Supporting evidence

---

# 20. AI Answer Readiness

Each page should receive an answer-readiness analysis.

Example:

```text
PAGE:
Laravel Development Services

AI ANSWER READINESS

✓ Clearly identifies service
✓ Explains target customer
✓ Provides service details
✓ Has supporting content

⚠ Pricing information missing
⚠ No delivery timeline
⚠ No comparison section
⚠ Limited evidence of experience
```

---

# 21. AI Question Discovery

The system should generate questions from:

- Page topic
- Search intent
- Existing headings
- Existing FAQ
- Site content
- Business profile
- Products
- Services
- Locations
- User-provided topics

Question categories:

```text
Informational
Commercial
Transactional
Navigational
Comparison
Local
Problem-solving
```

Example:

```text
Topic: WordPress Development

Questions:

What is WordPress development?
How much does WordPress development cost?
How long does a WordPress website take?
WordPress vs Shopify?
Who provides WordPress development in Pune?
How do I choose a WordPress developer?
```

---

# 22. Question Coverage

Dashboard:

```text
QUESTION COVERAGE

50 target questions

Covered       28
Weak          12
Missing       10

Coverage: 56%
```

Each question should link to:

- Existing page
- Recommended page
- Recommended section

---

# 23. AI Content Assistant

Actions:

```text
Improve for AI Search
Improve SEO
Improve AEO
Improve GEO
Generate Answer
Generate FAQ
Generate Comparison
Improve Entity Clarity
Generate Schema
Generate Internal Links
Refresh Content
```

The system must show proposed changes before saving.

---

# 24. Content Optimization

The AI analyzer should inspect:

- Intent
- Topic
- Entity
- Questions
- Supporting facts
- Content structure
- Missing sections
- Internal links
- External references
- Schema
- Author information

Output:

```text
CONTENT HEALTH: 81

Strengths:
✓ Strong topical relevance
✓ Good heading structure
✓ Good answer coverage

Problems:
⚠ Missing comparison section
⚠ Weak author information
⚠ Missing evidence

Recommended actions:
1. Add comparison section
2. Add author bio
3. Add supporting sources
```

---

# 25. Topic Cluster Engine

The plugin should identify topic relationships.

Example:

```text
Laravel
|
+-- Laravel Development
|   |
|   +-- Laravel API Development
|   +-- Laravel Ecommerce
|   +-- Laravel SaaS
|
+-- Laravel Security
|
+-- Laravel Performance
|
+-- Laravel Deployment
|
+-- Laravel vs WordPress
```

The system should identify:

- Pillar topics
- Supporting topics
- Missing topics
- Orphan pages
- Weak clusters
- Internal-link opportunities

---

# 26. Entity Knowledge Graph

The plugin should maintain a structured internal representation of website entities.

Example:

```text
Organization
    |
    +-- Person
    |
    +-- Service
    |
    +-- Product
    |
    +-- Location
    |
    +-- Website
    |
    +-- Social Profiles
```

Entity relationships should be used to improve:

- Schema
- Internal links
- Content recommendations
- AI prompts
- Content consistency

---

# 27. Business Knowledge Base

Admin screen:

```text
BUSINESS KNOWLEDGE

Business Name
Business Description
Industry
Services
Products
Locations
Target Customers
USP
Founder
Team
Experience
Certifications
Awards
Contact Details
Social Profiles
```

The AI must use this information as verified business context.

The AI must not invent missing facts.

---

# 28. Factual Consistency

The plugin should compare business facts across pages.

Example:

```text
BUSINESS NAME

Homepage:
ABC Technologies

About:
ABC Technology

Contact:
ABC Technologies Pvt Ltd

⚠ Possible entity inconsistency
```

Another example:

```text
Experience

Homepage:
10 years

About:
8 years

⚠ Conflicting business information
```

---

# 29. Schema Engine

Automatically generate Schema.org JSON-LD.

Supported initial types:

- WebSite
- Organization
- LocalBusiness
- Person
- Article
- BlogPosting
- WebPage
- Service
- Product
- BreadcrumbList
- FAQPage
- Review
- Event

Schema should be generated from verified site information.

Users should be able to inspect generated JSON-LD.

---

# 30. Schema Conflict Detection

Detect:

- Multiple Organization entities
- Conflicting names
- Conflicting URLs
- Duplicate schema
- Invalid JSON-LD
- Missing required properties
- Conflicting sameAs
- Conflicting addresses

---

# 31. Internal Linking AI

Analyze the complete site's content graph.

Recommend:

```text
Current page:
WordPress Development

Recommended:

→ WooCommerce Development
→ WordPress Plugin Development
→ Custom WordPress Development
→ WordPress Maintenance
```

Each recommendation should contain:

- Source page
- Target page
- Suggested anchor
- Reason
- Confidence

---

# 32. Internal Link Health

Identify:

- Orphan pages
- Pages with few internal links
- Overlinked pages
- Important pages with weak authority flow
- Broken internal links
- Irrelevant anchors

---

# 33. Citation Readiness

A page should be evaluated for whether it contains information that could be confidently referenced.

Checks:

- Specific answers
- Clear claims
- Supporting sources
- Author
- Organization
- Dates
- Statistics
- Evidence
- Original research

Example:

```text
CITATION READINESS: 67

✓ Clear factual answer
✓ Author identified
⚠ Statistical claim lacks source
⚠ Two factual claims need evidence
```

This is a diagnostic concept, not a prediction of AI citations.

---

# 34. Content Freshness

Track:

- Published date
- Modified date
- Outdated references
- Old statistics
- Old product/service information
- Broken links
- Missing current information

Categories:

```text
Healthy
Needs Update
Outdated
Critical
```

---

# 35. Content Decay Dashboard

Example:

```text
CONTENT HEALTH

120 pages

Healthy          72
Needs Update     31
Outdated         12
Critical          5
```

---

# 36. AI Search Query Planner

Users enter a business/topic.

The system generates:

```text
COMMERCIAL

best Laravel developer Pune
Laravel development company Pune

INFORMATIONAL

what is Laravel development?
how much does Laravel development cost?

COMPARISON

Laravel vs WordPress
Laravel vs Node.js

TRANSACTIONAL

hire Laravel developer
hire Laravel agency Pune

LOCAL

Laravel developers near me
Laravel company in Pune
```

The system should group queries by intent.

---

# 37. Query Coverage

For each query:

```text
Query:
Best Laravel development company in Pune

Status:
Weak

Best matching page:
/laravel-development/

Problem:
Page does not clearly establish Pune service area.

Recommendation:
Add verified service-area information.
```

---

# 38. Competitor Analysis

Users can add competitors manually.

Example:

```text
YOU
Competitor A
Competitor B
Competitor C
```

Compare:

- Topic coverage
- Entity clarity
- Answer coverage
- Content depth
- Schema
- Internal linking
- Trust signals
- Service coverage
- Location coverage

The system must distinguish:

- Direct competitors
- Search competitors
- Aspirational benchmarks

---

# 39. Competitor Content Gap

Example:

```text
Competitor A covers:

✓ Pricing
✓ Implementation timeline
✓ Comparison
✓ FAQ
✓ Case studies

Your site:

✓ Pricing
✗ Implementation timeline
✗ Comparison
✓ FAQ
✗ Case studies

Opportunity:
Create implementation timeline section.
Create comparison page.
Add case study evidence.
```

---

# 40. AI Visibility Monitoring

Premium feature.

Track user-defined queries over time.

Potential platforms:

- Google Search / AI features where observable
- ChatGPT
- Gemini
- Perplexity
- Microsoft Copilot

The system should store:

- Query
- Date
- Platform
- Observed answer
- Mention status
- Mention context
- Referenced URL
- Referenced competitors

Important:

Visibility measurements are observations from supported environments. They are not universal rankings.

---

# 41. Visibility History

Example:

```text
QUERY:
best web development company Pune

October 1:
Not observed

October 15:
Brand mentioned

October 30:
Brand mentioned with website URL
```

Chart:

```text
AI Visibility
100 |
 80 |                  ●
 60 |             ●
 40 |        ●
 20 |
  0 +-----------------------
      Oct 1  Oct 15  Oct 30
```

---

# 42. SEO Audit

The audit engine should inspect:

### Technical

- HTTPS
- Canonical
- Robots
- Sitemap
- Indexability
- Redirects
- Broken links
- Status codes

### On-page

- Titles
- Meta descriptions
- Headings
- Images
- Content
- Links

### Structured data

- JSON-LD
- Schema types
- Conflicts
- Missing properties

### Content

- Thin pages
- Duplicate pages
- Missing questions
- Topic gaps
- Content freshness

---

# 43. Website Crawl

MVP crawl should be bounded.

Default:

- Homepage
- Important pages
- Posts
- Pages
- Categories
- Sitemap URLs

Do not perform an unlimited crawl from WordPress.

Recommended initial limit:

```text
Free:
50 pages

Pro:
500 pages

Agency:
5,000 pages
```

Cloud crawling can be introduced later.

---

# 44. Evidence Model

Every recommendation should have:

```text
Observation
Evidence
Impact
Recommendation
Priority
Confidence
Acceptance Check
```

Example:

```text
Finding:
Missing Organization schema

Evidence:
Homepage contains organization information
but no Organization JSON-LD was detected.

Impact:
Machine-readable entity identity is incomplete.

Recommendation:
Add Organization schema using verified business information.

Priority:
High

Confidence:
High

Acceptance:
Organization JSON-LD exists on homepage and
contains consistent name, URL and logo.
```

---

# 45. Recommendation Priority

Levels:

```text
CRITICAL
HIGH
MEDIUM
LOW
INFO
```

Priority should consider:

```text
Business impact
SEO impact
AI-search impact
Implementation effort
Confidence
```

---

# 46. Recommendation Engine

Recommendations should be actionable.

Bad:

> Improve content.

Good:

> Add a 50–100 word direct answer below the H1 explaining what Laravel development is, who the service is for, and the main deliverables.

---

# 47. AI Safety Rules

The AI must:

- Never invent business facts.
- Never invent credentials.
- Never invent awards.
- Never invent statistics.
- Never invent customer reviews.
- Never invent case studies.
- Clearly mark assumptions.
- Preserve verified business facts.
- Show proposed content before publishing.

---

# 48. User Approval Workflow

AI changes should use:

```text
Analyze
   ↓
Recommend
   ↓
Preview
   ↓
User Approval
   ↓
Apply
   ↓
Verify
```

Never silently rewrite the entire website.

---

# 49. WordPress Editor Integration

For Gutenberg:

A sidebar panel:

```text
AI SEARCH SEO

Score: 84

SEO       91
AEO       79
GEO       82
Entity    88
Trust     73

[Analyze]

Recommendations
----------------
⚠ Add author expertise
⚠ Add comparison section
✓ Schema valid

AI Actions
----------------
[Improve Answer]
[Generate FAQ]
[Improve Content]
[Internal Links]
```

---

# 50. Elementor / Builder Compatibility

The plugin should primarily use:

- `the_content`
- Post metadata
- WordPress REST API
- WordPress hooks
- Front-end DOM-independent metadata

Do not make Gutenberg the only supported editor.

---

# 51. Settings

## General

- Enable plugin
- Enable analysis
- Enable schema
- Enable sitemap
- Enable Open Graph

## AI

- Provider
- API key
- Model
- Language
- AI usage limits

## SEO

- Title templates
- Meta templates
- Canonical settings
- Robots

## Schema

- Organization
- Person
- Local Business
- Social profiles

## Content

- AI writing preferences
- Brand voice
- Target audience

## Privacy

- AI data processing
- Usage analytics
- Data retention

---

# 52. REST API

Recommended routes:

```text
GET  /wp-json/aiseo/v1/dashboard

GET  /wp-json/aiseo/v1/posts/{id}/analysis

POST /wp-json/aiseo/v1/posts/{id}/analyze

POST /wp-json/aiseo/v1/posts/{id}/optimize

POST /wp-json/aiseo/v1/posts/{id}/generate-answer

POST /wp-json/aiseo/v1/posts/{id}/generate-faq

GET  /wp-json/aiseo/v1/entities

POST /wp-json/aiseo/v1/entities

GET  /wp-json/aiseo/v1/questions

GET  /wp-json/aiseo/v1/recommendations

POST /wp-json/aiseo/v1/recommendations/{id}/apply

GET  /wp-json/aiseo/v1/visibility
```

All routes must use WordPress authentication and capability checks.

---

# 53. Database Design

Use custom tables for analytics and large datasets.

## wp_aiseo_scores

```text
id
post_id
seo_score
aeo_score
geo_score
entity_score
trust_score
overall_score
created_at
updated_at
```

## wp_aiseo_entities

```text
id
type
name
description
url
schema_type
data_json
created_at
updated_at
```

## wp_aiseo_questions

```text
id
question
intent
topic
post_id
status
confidence
created_at
```

## wp_aiseo_recommendations

```text
id
post_id
type
priority
title
description
evidence
confidence
status
created_at
updated_at
```

## wp_aiseo_visibility

```text
id
query
platform
observed
mention
position_context
source_url
competitors_json
captured_at
```

---

# 54. WordPress Post Metadata

Post-specific lightweight settings may use:

```text
_aiseo_focus_topic
_aiseo_primary_entity
_aiseo_author_entity
_aiseo_manual_description
_aiseo_score
_aiseo_no_ai
_aiseo_noindex
```

Do not store large AI analysis documents in postmeta.

---

# 55. Performance Requirements

The plugin must not significantly slow down front-end requests.

Requirements:

- No AI API request during page render.
- No external HTTP requests during normal page rendering.
- Cache generated analysis.
- Use WP Cron for scheduled analysis.
- Lazy-load admin assets.
- Avoid loading React on front-end.
- Cache schema output.
- Use transients/object cache where appropriate.

Target:

> Front-end plugin overhead should normally be below 50ms on a typical cached WordPress page.

---

# 56. Security

Follow WordPress security standards.

Required:

- Nonces
- Capability checks
- Sanitization
- Escaping
- Prepared SQL
- REST permission callbacks
- API-key encryption/protection
- No API key exposed to JavaScript
- No arbitrary remote code execution
- Restricted file operations

Required capability:

```text
manage_options
```

Some editorial features may use:

```text
edit_posts
edit_post
```

---

# 57. Privacy

The plugin should clearly disclose:

- What content is sent to AI providers.
- Which provider receives the data.
- Whether AI provider retention applies.
- What analytics are stored.
- How users can delete plugin data.

Provide:

```text
Send content to AI provider:
[Yes / No]

Store AI analysis:
[Yes / No]

Anonymous usage analytics:
[Yes / No]
```

---

# 58. Multilingual Support

The plugin should support:

- English
- Hindi
- Marathi
- Other WordPress translation locales

AI content generation should allow output language selection.

Example:

```text
Content language:
English
Hindi
Marathi
Gujarati
Tamil
Custom
```

The product should not assume English-only search behavior.

---

# 59. WooCommerce Roadmap

Future support:

- Product schema
- Product attributes
- Product questions
- Buying guides
- Product comparisons
- Category optimization
- Product entity graph
- Review analysis
- Availability
- Price
- Brand
- SKU

---

# 60. Pricing Model

Recommended:

## Free

- Basic SEO
- Basic schema
- Basic page score
- 10 AI analyses/month
- Basic AI Search Score

## Pro — $49/year or monthly equivalent

- Unlimited local analysis
- AEO/GEO
- Entity management
- AI content assistant
- Internal link suggestions
- Topic clusters
- Content freshness
- Advanced schema

## Business — $149/year or monthly equivalent

- Competitor analysis
- Advanced audits
- AI query planning
- Visibility tracking
- More AI usage
- Multiple sites

## Agency

- Multiple websites
- Client reports
- White labeling
- Central management
- API
- Higher limits

Pricing should be validated through market research before launch.

---

# 61. MVP Scope

## MVP must include

### Dashboard

- Overall score
- SEO score
- AEO score
- GEO score
- Entity score
- Trust score
- Priority recommendations

### Page Analysis

- SEO metadata
- Content analysis
- Questions
- Entity
- Schema
- Internal links
- Trust signals

### AI

- Analyze page
- Improve content
- Generate answer
- Generate FAQ
- Generate schema
- Generate internal links

### Business Knowledge

- Organization
- Services
- Locations
- Founder
- Social profiles

### Technical

- Schema
- Sitemap
- Canonical
- Robots
- Open Graph

---

# 62. Phase 2

- Topic clusters
- Content gaps
- Competitor analysis
- Content decay
- Advanced internal linking
- AI query planner
- Citation readiness
- Advanced entity graph

---

# 63. Phase 3

- AI visibility monitoring
- ChatGPT observations
- Gemini observations
- Perplexity observations
- Historical visibility
- Competitor visibility
- Automated reports

---

# 64. Phase 4

- WooCommerce intelligence
- Agency dashboard
- White labeling
- Cloud crawler
- Multi-site management
- API
- Advanced reporting

---

# 65. Development Milestones

## Milestone 1 — Foundation

- Plugin bootstrap
- Admin menu
- Settings
- Database
- REST API
- React admin app

## Milestone 2 — SEO Engine

- Metadata
- Canonical
- Robots
- OpenGraph
- Sitemap
- Schema

## Milestone 3 — Analyzer

- Content extraction
- Heading analysis
- Question extraction
- Entity extraction
- Internal link analysis
- Trust analysis

## Milestone 4 — Scoring

- SEO score
- AEO score
- GEO score
- Entity score
- Trust score
- Overall score

## Milestone 5 — AI

- Provider abstraction
- OpenAI
- Gemini
- AI analysis
- AI recommendations
- AI content tools

## Milestone 6 — Editor

- Gutenberg sidebar
- AI actions
- Preview
- Apply changes

## Milestone 7 — Testing

- Unit tests
- REST API tests
- Security testing
- WordPress compatibility
- Performance testing

---

# 66. Acceptance Criteria

## Dashboard

- Dashboard loads without PHP errors.
- Scores are calculated from stored analysis.
- Recommendations show priority.
- User can navigate to affected pages.

## Page Analysis

- Plugin can analyze posts and pages.
- Analysis identifies title, meta, headings and schema.
- Questions can be detected.
- Entity information can be detected.
- Internal links can be analyzed.

## AI

- Provider can be configured.
- API keys are not exposed client-side.
- AI responses are stored safely.
- AI never automatically publishes content.

## Schema

- Valid JSON-LD is generated.
- Duplicate/conflicting schema is detected.
- User can preview schema.

## Security

- Unauthorized users cannot access admin actions.
- REST endpoints require permissions.
- Inputs are sanitized.
- SQL uses prepared statements.

## Performance

- No AI requests occur during frontend rendering.
- Admin scripts load only where needed.
- Schema is cached.

---

# 67. Quality Principles

Every recommendation should answer:

1. What is wrong?
2. Where is it wrong?
3. Why does it matter?
4. What should I do?
5. How difficult is it?
6. How confident are we?
7. How can I verify it?

Example:

```text
Issue:
Service page does not clearly explain who the service is for.

Why:
AI systems and users may have difficulty identifying the intended audience.

Action:
Add a short "Who this service is for" section.

Priority:
Medium

Confidence:
High

Verification:
Section exists and explicitly identifies target customer types.
```

---

# 68. Product Differentiation

AnswerMesh should differentiate itself from traditional SEO plugins through:

### Traditional SEO

```text
Keyword
Title
Meta
Schema
Sitemap
```

### AnswerMesh

```text
Entity
Question
Answer
Evidence
Trust
Topic
Relationships
Context
Knowledge Graph
AI visibility
```

The key differentiation is:

> **Understand the website as a knowledge system, not just a collection of keywords.**

---

# 69. Competitive Positioning

The product should not attempt to replace every feature of established SEO plugins immediately.

Instead:

```text
Yoast / Rank Math
        |
        | Traditional SEO
        v
+----------------------+
|   AnswerMesh      |
+----------------------+
        |
        +-- AEO
        +-- GEO
        +-- Entity
        +-- Trust
        +-- AI Content
        +-- Knowledge Graph
        +-- AI Visibility
```

Integration/compatibility with existing SEO plugins should be considered.

The plugin should detect if Yoast, Rank Math, AIOSEO or another SEO plugin is active and avoid generating duplicate metadata.

---

# 70. Existing SEO Plugin Compatibility

The plugin should support a compatibility mode.

If another SEO plugin is active:

```text
SEO metadata:
Use existing plugin

Schema:
AnswerMesh

AI Analysis:
AnswerMesh

AI Visibility:
AnswerMesh
```

The user can choose the source of truth.

Never blindly output duplicate:

- Title
- Description
- Canonical
- Robots
- Schema

---

# 71. Reporting

Generate a report containing:

```text
Executive Summary

Overall Score

SEO
AEO
GEO
Entity
Trust

Top 10 Problems

Top Opportunities

Content Gaps

Question Coverage

Entity Issues

Schema Issues

Internal Link Opportunities

AI Recommendations

Before/After Score
```

Future formats:

- HTML
- PDF
- CSV
- JSON

---

# 72. Observability

The plugin should record:

- Analysis start
- Analysis completion
- AI provider
- AI model
- Token usage where available
- Errors
- Recommendation creation
- Recommendation approval
- Recommendation application
- Verification

Never log API keys or sensitive content unnecessarily.

---

# 73. Error Handling

AI failures must not break WordPress.

Example:

```text
AI analysis unavailable.

Reason:
Provider timeout.

Your existing SEO functionality is unaffected.

[Retry]
```

The plugin must fail gracefully.

---

# 74. Offline / No-AI Mode

Core SEO functionality must work without an AI provider.

```text
AI disabled

SEO:
✓ Active

Schema:
✓ Active

Sitemap:
✓ Active

AEO:
Basic rules available

GEO:
Basic rules available

AI generation:
Disabled
```

This is important for reliability.

---

# 75. Data Migration

Future versions must support:

- Database versioning
- Upgrade migrations
- Safe rollback
- Plugin deactivation
- Uninstall cleanup

Uninstall should ask/allow:

```text
Delete all AnswerMesh data?
```

Do not automatically delete data on deactivation.

---

# 76. Testing Strategy

## Unit Testing

Use PHPUnit.

Test:

- Score calculation
- Schema generation
- Metadata
- Entity parsing
- Question extraction
- Recommendations

## Integration Testing

Test:

- WordPress REST API
- Gutenberg
- Sitemap
- Schema
- AI provider adapters

## Security Testing

Test:

- CSRF
- XSS
- SQL injection
- Privilege escalation
- REST permission bypass
- API-key exposure

## Performance Testing

Test:

- 100 posts
- 1,000 posts
- 10,000 posts
- Large WooCommerce stores

---

# 77. Success Metrics

## Product Metrics

- Number of active installations
- Weekly active users
- AI analyses per site
- Recommendations accepted
- Recommendations applied
- Returning users
- Pro conversion rate

## SEO Metrics

Track observed changes where integrations allow:

- Indexed pages
- Organic impressions
- Organic clicks
- Query coverage
- AI visibility observations
- Content health

Do not claim causation unless supported by evidence.

---

# 78. Recommended MVP User Journey

```text
Install Plugin
      |
      v
Setup Wizard
      |
      v
Enter Business Information
      |
      v
Connect AI Provider
      |
      v
Analyze Website
      |
      v
AI Search Score
      |
      v
Top 10 Recommendations
      |
      v
Analyze Important Pages
      |
      v
AI Suggestions
      |
      v
Preview Changes
      |
      v
Approve
      |
      v
Apply
      |
      v
Verify
```

---

# 79. Setup Wizard

Step 1:

```text
Tell us about your business
```

Step 2:

```text
What do you offer?
```

Step 3:

```text
Where do you operate?
```

Step 4:

```text
Who are your customers?
```

Step 5:

```text
Connect AI provider
```

Step 6:

```text
Analyze website
```

Step 7:

```text
Your AI Search Score
```

---

# 80. Example Recommendation

```text
HIGH PRIORITY

Missing Organization Entity

Page:
Homepage

Problem:
The website provides company information but does not
clearly expose a consistent Organization entity.

Why it matters:
A clear entity representation helps machine-readable
systems connect the website, brand, organization and
official profiles.

Recommended action:
Configure the Organization entity with verified:
- Business name
- URL
- Logo
- Description
- Social profiles

[Fix with AI]

Confidence:
High
```

---

# 81. Example AI Optimization

Before:

```text
We provide Laravel development services for businesses.
```

AI recommendation:

```text
Our Laravel development service helps businesses build
secure, scalable web applications, APIs, SaaS platforms
and custom business systems using the Laravel PHP framework.
```

The system should only add claims supported by the business knowledge base or verified page content.

---

# 82. Example AI Answer Block

Question:

> What is Laravel development?

Suggested answer:

```text
Laravel development is the process of building web
applications, APIs and backend systems using Laravel,
a PHP framework designed for modern application development.
```

The user must approve generated content before publishing.

---

# 83. AI Prompt Context

AI prompts should include:

```text
Business Profile
+
Page Content
+
Site Entities
+
Verified Facts
+
Target Audience
+
Search Intent
+
Existing Schema
+
Questions
+
Internal Links
+
Brand Voice
```

The system should explicitly instruct the provider:

```text
Do not invent facts.
Use only verified information.
Mark missing information.
```

---

# 84. Brand Voice

Settings:

```text
Professional
Friendly
Technical
Expert
Minimal
Conversational
Custom
```

Users can define:

```text
Brand voice instructions
```

---

# 85. Localization

The plugin should support:

```text
English
Hindi
Marathi
Gujarati
Tamil
Telugu
Bengali
Kannada
Malayalam
Punjabi
Urdu
```

The system should distinguish:

- Content language
- Target market
- Location
- Search language

Language must not automatically determine target market.

---

# 86. Accessibility

Admin UI should follow WordPress accessibility guidelines.

Requirements:

- Keyboard navigation
- ARIA where appropriate
- Color contrast
- Screen reader labels
- Focus states
- No information conveyed by color alone

---

# 87. Licensing

Recommended:

```text
Plugin:
GPL-compatible WordPress license

Cloud service:
Separate commercial service

AI:
User-owned API keys initially
```

This reduces early infrastructure cost.

---

# 88. Open Core Strategy

Potential future model:

### Free plugin

- Technical SEO
- Basic schema
- Basic analysis
- Basic AI Search score

### Premium cloud

- AI analysis
- Competitor research
- AI visibility
- Historical monitoring
- Advanced reports
- Multi-site

This allows the WordPress plugin to gain distribution while monetizing advanced intelligence.

---

# 89. MVP Definition of Done

The MVP is complete when:

- Plugin installs successfully on clean WordPress.
- Plugin activates without errors.
- Setup wizard works.
- Dashboard works.
- Page analyzer works.
- SEO score works.
- AEO score works.
- GEO score works.
- Entity score works.
- Trust score works.
- Schema engine works.
- AI provider abstraction works.
- At least one AI provider works.
- AI suggestions can be previewed.
- Users can approve/reject changes.
- Gutenberg integration works.
- REST endpoints are secured.
- No duplicate metadata is produced with supported SEO plugins.
- Unit tests pass.
- Security tests pass.
- Performance is acceptable.
- Documentation is complete.

---

# 90. Final Product Principle

AnswerMesh should not promise:

> "We will make ChatGPT rank your website."

It should promise:

> **"We help your website become clearer, more structured, more trustworthy, more comprehensive, and easier for search engines and AI systems to understand."**

The product should continuously follow this loop:

```text
UNDERSTAND
    ↓
ANALYZE
    ↓
IDENTIFY GAPS
    ↓
RECOMMEND
    ↓
GENERATE
    ↓
USER APPROVES
    ↓
APPLY
    ↓
VERIFY
    ↓
MEASURE
    ↓
IMPROVE
```

This evidence-first workflow is the core product philosophy and should guide all future features.

---

# 91. Future Vision

Long-term, AnswerMesh should evolve from a WordPress SEO plugin into an **AI Search Visibility Platform**.

The WordPress plugin becomes the website-side agent.

The cloud platform becomes the intelligence layer.

```text
                    AI SEARCH SEO PLATFORM

                            |
             +--------------+--------------+
             |              |              |
          Website        Content        Search
             |              |              |
          WordPress       AI Engine     Visibility
             |              |              |
             +--------------+--------------+
                            |
                     Knowledge Graph
                            |
             +--------------+--------------+
             |              |              |
           Entity         Evidence       Topics
             |              |              |
             +--------------+--------------+
                            |
                    AI Search Visibility
```

The long-term goal is:

> **Become the operating system for website visibility in AI-powered search.**
