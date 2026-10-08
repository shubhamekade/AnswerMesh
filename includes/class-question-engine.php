<?php
/**
 * Question discovery and answer coverage engine.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class QuestionEngine
{
    /**
     * Interrogative starter patterns.
     */
    private const QUESTION_PATTERNS = [
        '/\b(what|why|how|who|where|when|which|is|are|can|should|does|do|will)\b.*\?/i',
    ];

    /**
     * Extract questions from post content and update database table.
     *
     * @param int $post_id Post ID.
     * @param string $content Post content HTML.
     * @return array<int, array{question: string, intent: string, status: string, confidence: string}>
     */
    public static function extract_questions_from_post(int $post_id, string $content): array
    {
        $questions = [];

        // 1. Scan headings for questions.
        preg_match_all('/<(h[2-4])[^>]*>(.*?)<\/\1>/is', $content, $headings_m, PREG_SET_ORDER);
        foreach ($headings_m as $h) {
            $text = trim(wp_strip_all_tags($h[2]));
            if (self::is_question($text)) {
                $intent = self::classify_intent($text);
                $status = self::evaluate_answer_coverage($text, $content);
                $questions[] = [
                    'question'   => $text,
                    'intent'     => $intent,
                    'status'     => $status,
                    'confidence' => 'high',
                ];
            }
        }

        // 2. Scan sentences ending with '?' in body paragraphs.
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $para_m);
        foreach ($para_m[1] as $para) {
            $plain = wp_strip_all_tags($para);
            $sentences = preg_split('/(?<=[.?!])\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY);
            if (!is_array($sentences)) {
                continue;
            }

            foreach ($sentences as $sentence) {
                $trimmed = trim($sentence);
                if (str_ends_with($trimmed, '?') && strlen($trimmed) > 15) {
                    $intent = self::classify_intent($trimmed);
                    $status = self::evaluate_answer_coverage($trimmed, $content);
                    $questions[] = [
                        'question'   => $trimmed,
                        'intent'     => $intent,
                        'status'     => $status,
                        'confidence' => 'medium',
                    ];
                }
            }
        }

        // Deduplicate.
        $unique = [];
        $final_list = [];
        foreach ($questions as $q) {
            $normalized = strtolower(trim($q['question']));
            if (!isset($unique[$normalized])) {
                $unique[$normalized] = true;
                $final_list[] = $q;
            }
        }

        // Sync with wp_aiseo_questions table.
        self::sync_to_db($post_id, $final_list);

        return $final_list;
    }

    /**
     * Test if a text string is a question.
     */
    public static function is_question(string $text): bool
    {
        $trimmed = trim($text);
        if (str_ends_with($trimmed, '?')) {
            return true;
        }

        foreach (self::QUESTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $trimmed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Classify question intent.
     */
    public static function classify_intent(string $question): string
    {
        $lower = strtolower($question);

        if (preg_match('/\b(vs|versus|difference|compare|better|or)\b/', $lower)) {
            return 'comparison';
        }

        if (preg_match('/\b(cost|price|how much|fee|rate|quote|pricing|inexpensive|cheap)\b/', $lower)) {
            return 'commercial';
        }

        if (preg_match('/\b(buy|hire|order|contact|schedule|book|purchase|near me)\b/', $lower)) {
            return 'transactional';
        }

        if (preg_match('/\b(near me|in [a-z]+|location|address|city)\b/', $lower)) {
            return 'local';
        }

        return 'informational';
    }

    /**
     * Evaluate answer coverage within content following the question.
     */
    public static function evaluate_answer_coverage(string $question, string $content): string
    {
        $pos = mb_stripos($content, $question);
        if ($pos === false) {
            return 'missing';
        }

        // Extract 300 characters following the question.
        $after = mb_substr($content, $pos + mb_strlen($question), 300);
        $clean_after = trim(wp_strip_all_tags($after));

        $words = str_word_count($clean_after);
        if ($words >= 25) {
            return 'answered';
        }
        if ($words >= 8) {
            return 'weak';
        }

        return 'missing';
    }

    /**
     * Sync discovered questions to wp_aiseo_questions.
     *
     * @param array<int, array{question: string, intent: string, status: string, confidence: string}> $questions
     */
    private static function sync_to_db(int $post_id, array $questions): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_questions';

        // Remove previous questions for this post.
        $wpdb->delete($table, ['post_id' => $post_id]);

        foreach ($questions as $q) {
            $wpdb->insert($table, [
                'question'   => sanitize_text_field($q['question']),
                'intent'     => sanitize_key($q['intent']),
                'topic'      => '',
                'post_id'    => $post_id,
                'status'     => sanitize_key($q['status']),
                'confidence' => sanitize_key($q['confidence']),
                'created_at' => current_time('mysql'),
            ]);
        }
    }
}
