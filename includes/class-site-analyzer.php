<?php
/**
 * Bounded site crawler and batch audit manager.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class SiteAnalyzer
{
    public const OPTION_PROGRESS = 'answermesh_crawl_progress';
    public const CRON_HOOK = 'answermesh_crawl_batch';

    /**
     * Register cron hook.
     */
    public function init(): void
    {
        add_action(self::CRON_HOOK, [$this, 'process_cron_batch']);
    }

    /**
     * Start bounded background crawl.
     *
     * @param int $limit Maximum pages to crawl (50 default, max 5000).
     */
    public static function start_crawl(int $limit = 50): bool
    {
        $limit = max(5, min(5000, $limit));

        // Get publish post IDs.
        $query = new \WP_Query([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        $post_ids = $query->posts;
        if (empty($post_ids)) {
            return false;
        }

        $progress = [
            'status'      => 'running',
            'total'       => count($post_ids),
            'processed'   => 0,
            'pending_ids' => $post_ids,
            'started_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ];

        update_option(self::OPTION_PROGRESS, $progress);

        // Schedule first batch if not already scheduled.
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 2, self::CRON_HOOK);
        }

        return true;
    }

    /**
     * Process a batch of posts.
     */
    public static function process_batch(int $batch_size = 5): array
    {
        $progress = get_option(self::OPTION_PROGRESS);
        if (!$progress || $progress['status'] !== 'running' || empty($progress['pending_ids'])) {
            return $progress ?: ['status' => 'idle'];
        }

        $pending = $progress['pending_ids'];
        $to_process = array_splice($pending, 0, $batch_size);

        foreach ($to_process as $post_id) {
            // Run content analysis.
            $content_audit = PostAnalyzer::analyze_post((int) $post_id);

            // Extract questions.
            $post = get_post($post_id);
            if ($post) {
                QuestionEngine::extract_questions_from_post((int) $post_id, (string) $post->post_content);
            }

            // Trust analysis.
            TrustAnalyzer::evaluate_post((int) $post_id);

            $progress['processed']++;
        }

        $progress['pending_ids'] = $pending;
        $progress['updated_at'] = current_time('mysql');

        if (empty($pending)) {
            $progress['status'] = 'completed';
        }

        update_option(self::OPTION_PROGRESS, $progress);

        // Schedule next batch if still running.
        if ($progress['status'] === 'running' && !wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 5, self::CRON_HOOK);
        }

        return $progress;
    }

    /**
     * Cron callback handler.
     */
    public function process_cron_batch(): void
    {
        self::process_batch(5);
    }

    /**
     * Get current crawler progress.
     */
    public static function get_crawl_status(): array
    {
        $progress = get_option(self::OPTION_PROGRESS);
        if (!$progress) {
            return [
                'status'    => 'idle',
                'total'     => 0,
                'processed' => 0,
            ];
        }
        return [
            'status'     => $progress['status'],
            'total'      => $progress['total'],
            'processed'  => $progress['processed'],
            'started_at' => $progress['started_at'] ?? '',
            'updated_at' => $progress['updated_at'] ?? '',
        ];
    }
}
