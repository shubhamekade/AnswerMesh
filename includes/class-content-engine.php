<?php
/**
 * AI Content Assistant and Diff Proposal Engine.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class ContentEngine
{
    /**
     * Generate an answer block proposal for a post.
     *
     * @param int $post_id Post ID.
     * @param string $question Question to answer.
     * @return array<string, mixed>
     */
    public static function generate_answer_block(int $post_id, string $question): array
    {
        $provider = AI::get_active_provider();
        if (!$provider) {
            return [
                'success' => false,
                'error'   => __('AI Provider is not configured or disabled.', 'answermesh'),
            ];
        }

        $post = get_post($post_id);
        if (!$post) {
            return ['success' => false, 'error' => __('Post not found.', 'answermesh')];
        }

        $excerpt = mb_strimwidth(wp_strip_all_tags((string) $post->post_content), 0, 1000);
        $prompt = PromptBuilder::build_answer_prompt($question, $excerpt, $post_id);

        $res = $provider->generate($prompt, ['temperature' => 0.2]);
        if (!$res['success']) {
            return $res;
        }

        $generated_html = $res['content'];

        return [
            'success'  => true,
            'type'     => 'append_answer_block',
            'question' => $question,
            'original' => '',
            'proposed' => $generated_html,
            'diff'     => [
                'type'     => 'append',
                'target'   => 'post_content',
                'addition' => $generated_html,
            ],
            'reason'   => sprintf(__('Adds direct answer block for: "%s"', 'answermesh'), $question),
        ];
    }

    /**
     * Generate structured FAQ block proposal for a post.
     *
     * @param int $post_id Post ID.
     * @param int $count Number of FAQs.
     * @return array<string, mixed>
     */
    public static function generate_faq(int $post_id, int $count = 3): array
    {
        $provider = AI::get_active_provider();
        if (!$provider) {
            return [
                'success' => false,
                'error'   => __('AI Provider is not configured or disabled.', 'answermesh'),
            ];
        }

        $post = get_post($post_id);
        if (!$post) {
            return ['success' => false, 'error' => __('Post not found.', 'answermesh')];
        }

        $excerpt = mb_strimwidth(wp_strip_all_tags((string) $post->post_content), 0, 1500);
        $prompt = PromptBuilder::build_faq_prompt($post->post_title, $excerpt, $count);

        $res = $provider->generate($prompt, ['temperature' => 0.2]);
        if (!$res['success']) {
            return $res;
        }

        // Parse JSON or format into clean HTML block.
        $faqs = json_decode($res['content'], true);
        $faq_html = "<div class=\"answermesh-faq-section\">\n<h3>" . esc_html__('Frequently Asked Questions', 'answermesh') . "</h3>\n";

        if (is_array($faqs)) {
            foreach ($faqs as $item) {
                $q = esc_html($item['question'] ?? '');
                $a = esc_html($item['answer'] ?? '');
                $faq_html .= "<div class=\"answermesh-faq-item\">\n  <h4>{$q}</h4>\n  <p>{$a}</p>\n</div>\n";
            }
        } else {
            $faq_html .= $res['content'];
        }
        $faq_html .= "</div>\n";

        return [
            'success'  => true,
            'type'     => 'append_faq',
            'original' => '',
            'proposed' => $faq_html,
            'items'    => is_array($faqs) ? $faqs : [],
            'diff'     => [
                'type'     => 'append',
                'target'   => 'post_content',
                'addition' => $faq_html,
            ],
            'reason'   => sprintf(__('Adds %d topical FAQs with structured answers.', 'answermesh'), $count),
        ];
    }

    /**
     * Apply an approved diff proposal to a post.
     *
     * @param int $post_id Post ID.
     * @param string $action_type Type of modification.
     * @param string $content Content to append or replace.
     * @return bool
     */
    public static function apply_proposal(int $post_id, string $action_type, string $content): bool
    {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        // Verify capability.
        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }

        $current_content = (string) $post->post_content;

        switch ($action_type) {
            case 'append_answer_block':
            case 'append_faq':
            case 'append':
                $new_content = $current_content . "\n\n" . $content;
                break;
            case 'replace':
                $new_content = $content;
                break;
            default:
                $new_content = $current_content . "\n\n" . $content;
                break;
        }

        $updated = wp_update_post([
            'ID'           => $post_id,
            'post_content' => $new_content,
        ]);

        if (is_wp_error($updated) || $updated === 0) {
            return false;
        }

        // Re-score post after application.
        Score::compute_and_save_post_score($post_id);

        return true;
    }
}
