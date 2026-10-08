<?php
/**
 * Context-rich prompt builder with strict anti-hallucination guardrails.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class PromptBuilder
{
    /**
     * Build answer block prompt.
     */
    public static function build_answer_prompt(string $question, string $post_content, int $post_id = 0): string
    {
        $context = Entity::get_verified_context();
        $settings = Settings::get_instance();
        $tone = (string) $settings->get('brand_voice', 'tone', 'professional');
        $instructions = (string) $settings->get('brand_voice', 'custom_instructions', '');

        return <<<PROMPT
You are an expert AI Answer Engine Optimization (AEO) assistant.
Write a clear, direct, concise answer for the question below.

QUESTION:
{$question}

TARGET AUDIENCE & VOICE:
- Tone: {$tone}
- Instructions: {$instructions}

VERIFIED BUSINESS KNOWLEDGE BASE (Use ONLY verified facts below. DO NOT invent credentials, statistics, awards, or fake testimonials):
{$context}

PAGE CONTENT EXCERPT:
{$post_content}

REQUIREMENTS:
1. Provide a direct 40–80 word answer block directly answering the question.
2. Include 2–3 supporting bullet points if applicable.
3. Be cite-ready: clear, factual, unambiguous.
4. Output in clean HTML format:
<div class="answermesh-answer-block">
  <p><strong>[Direct Answer Summary]</strong></p>
  <p>[Elaboration based strictly on verified facts]</p>
</div>
PROMPT;
    }

    /**
     * Build FAQ generation prompt.
     */
    public static function build_faq_prompt(string $post_title, string $post_content, int $count = 3): string
    {
        $context = Entity::get_verified_context();

        return <<<PROMPT
You are an expert SEO and AEO assistant.
Generate {$count} high-intent FAQ questions and answers relevant to the page topic.

TOPIC: {$post_title}

VERIFIED BUSINESS FACTS:
{$context}

PAGE CONTENT:
{$post_content}

RULES:
1. Address real user search questions (Informational, Comparison, Commercial, Local).
2. Keep answers concise (40–70 words each).
3. Ground answers strictly in verified business information or provided content.
4. Return valid JSON array with objects containing 'question' and 'answer':
[
  {"question": "...", "answer": "..."},
  ...
]
PROMPT;
    }

    /**
     * Build content improvement prompt.
     */
    public static function build_improvement_prompt(string $content, string $instruction): string
    {
        $context = Entity::get_verified_context();

        return <<<PROMPT
You are an expert Generative Engine Optimization (GEO) content editor.
Rewrite the provided content section following the instructions below while preserving factual integrity.

SPECIFIC INSTRUCTION:
{$instruction}

VERIFIED FACTS:
{$context}

ORIGINAL SECTION:
{$content}

RULES:
1. Improve clarity, machine extractability, and direct answer precision.
2. Never invent unverified facts, statistics, or credentials.
3. Return only the revised HTML/text content without conversational preamble.
PROMPT;
    }
}
