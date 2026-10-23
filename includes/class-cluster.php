<?php
/**
 * Topic Cluster data model and database manager.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Cluster
{
    /**
     * Get table name.
     */
    public static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'aiseo_clusters';
    }

    /**
     * Retrieve all topic clusters.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_all(): array
    {
        global $wpdb;
        $table = self::table_name();

        $rows = $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY name ASC",
            ARRAY_A
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map([self::class, 'format_cluster'], $rows);
    }

    /**
     * Retrieve cluster by ID.
     *
     * @param int $id Cluster ID.
     * @return array<string, mixed>|null
     */
    public static function get(int $id): ?array
    {
        global $wpdb;
        $table = self::table_name();

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        return $row ? self::format_cluster($row) : null;
    }

    /**
     * Find cluster that contains a specific post ID.
     *
     * @param int $post_id WordPress post ID.
     * @return array<string, mixed>|null
     */
    public static function get_for_post(int $post_id): ?array
    {
        global $wpdb;
        $table = self::table_name();

        // 1. Check if it is the pillar
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE pillar_post_id = %d LIMIT 1", $post_id),
            ARRAY_A
        );

        if ($row) {
            return self::format_cluster($row);
        }

        // 2. Check if present in posts_json
        $clusters = self::get_all();
        foreach ($clusters as $cluster) {
            $posts = $cluster['posts'] ?? [];
            foreach ($posts as $post) {
                if ((int)($post['post_id'] ?? 0) === $post_id) {
                    return $cluster;
                }
            }
        }

        return null;
    }

    /**
     * Create a new topic cluster.
     *
     * @param array<string, mixed> $data Cluster payload.
     * @return int Created cluster ID or 0 on failure.
     */
    public static function create(array $data): int
    {
        global $wpdb;
        $table = self::table_name();

        $name = sanitize_text_field($data['name'] ?? '');
        $slug = sanitize_title($data['slug'] ?? $name);
        $pillar_id = (int)($data['pillar_post_id'] ?? 0);
        $posts = is_array($data['posts'] ?? null) ? $data['posts'] : [];
        $entities = is_array($data['entities'] ?? null) ? $data['entities'] : [];
        $coverage_score = max(0, min(100, (int)($data['coverage_score'] ?? 0)));
        $subtopics = is_array($data['subtopics'] ?? null) ? $data['subtopics'] : [];
        $now = current_time('mysql');

        $result = $wpdb->insert(
            $table,
            [
                'name'           => $name,
                'slug'           => $slug,
                'pillar_post_id' => $pillar_id,
                'posts_json'     => wp_json_encode($posts),
                'entities_json'  => wp_json_encode($entities),
                'coverage_score' => $coverage_score,
                'subtopics_json' => wp_json_encode($subtopics),
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            ['%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        return $result ? (int)$wpdb->insert_id : 0;
    }

    /**
     * Update an existing cluster.
     *
     * @param int                  $id   Cluster ID.
     * @param array<string, mixed> $data Update payload.
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = self::table_name();

        $fields = [];
        $format = [];

        if (isset($data['name'])) {
            $fields['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        if (isset($data['slug'])) {
            $fields['slug'] = sanitize_title($data['slug']);
            $format[] = '%s';
        }
        if (isset($data['pillar_post_id'])) {
            $fields['pillar_post_id'] = (int)$data['pillar_post_id'];
            $format[] = '%d';
        }
        if (isset($data['posts'])) {
            $fields['posts_json'] = wp_json_encode($data['posts']);
            $format[] = '%s';
        }
        if (isset($data['entities'])) {
            $fields['entities_json'] = wp_json_encode($data['entities']);
            $format[] = '%s';
        }
        if (isset($data['coverage_score'])) {
            $fields['coverage_score'] = max(0, min(100, (int)$data['coverage_score']));
            $format[] = '%d';
        }
        if (isset($data['subtopics'])) {
            $fields['subtopics_json'] = wp_json_encode($data['subtopics']);
            $format[] = '%s';
        }

        if (empty($fields)) {
            return false;
        }

        $fields['updated_at'] = current_time('mysql');
        $format[] = '%s';

        $updated = $wpdb->update(
            $table,
            $fields,
            ['id' => $id],
            $format,
            ['%d']
        );

        return $updated !== false;
    }

    /**
     * Delete cluster by ID.
     *
     * @param int $id Cluster ID.
     * @return bool
     */
    public static function delete(int $id): bool
    {
        global $wpdb;
        $table = self::table_name();

        $deleted = $wpdb->delete(
            $table,
            ['id' => $id],
            ['%d']
        );

        return (bool)$deleted;
    }

    /**
     * Assign list of post IDs to a cluster.
     *
     * @param int        $cluster_id Cluster ID.
     * @param array<int> $post_ids   Post IDs to assign.
     * @return bool
     */
    public static function assign_posts(int $cluster_id, array $post_ids): bool
    {
        $cluster = self::get($cluster_id);
        if (!$cluster) {
            return false;
        }

        $existing_posts = $cluster['posts'] ?? [];
        $existing_ids = array_column($existing_posts, 'post_id');

        foreach ($post_ids as $pid) {
            $pid = (int)$pid;
            if ($pid > 0 && !in_array($pid, $existing_ids, true)) {
                $existing_posts[] = [
                    'post_id' => $pid,
                    'role'    => 'cluster_support',
                    'title'   => get_the_title($pid),
                ];
                $existing_ids[] = $pid;
            }
        }

        return self::update($cluster_id, ['posts' => $existing_posts]);
    }

    /**
     * Remove a post from cluster.
     *
     * @param int $cluster_id Cluster ID.
     * @param int $post_id    Post ID to detach.
     * @return bool
     */
    public static function remove_post(int $cluster_id, int $post_id): bool
    {
        $cluster = self::get($cluster_id);
        if (!$cluster) {
            return false;
        }

        $posts = array_filter($cluster['posts'] ?? [], function ($item) use ($post_id) {
            return (int)($item['post_id'] ?? 0) !== $post_id;
        });

        return self::update($cluster_id, ['posts' => array_values($posts)]);
    }

    /**
     * Recalculate coverage score for a cluster based on member metrics.
     *
     * @param int $cluster_id Cluster ID.
     * @return int Computed score (0-100).
     */
    public static function calculate_cluster_score(int $cluster_id): int
    {
        $cluster = self::get($cluster_id);
        if (!$cluster) {
            return 0;
        }

        $posts = $cluster['posts'] ?? [];
        $has_pillar = ($cluster['pillar_post_id'] > 0);
        $post_count = count($posts);

        // Score formula:
        // Pillar assigned: 30 pts
        // Member posts count: up to 30 pts (6+ posts = 30 pts)
        // Subtopics covered: up to 20 pts
        // Average post overall score: up to 20 pts
        $score = 0;
        if ($has_pillar) {
            $score += 30;
        }

        $score += (int)min(30, $post_count * 5);

        $subtopics = $cluster['subtopics'] ?? [];
        if (!empty($subtopics)) {
            $score += (int)min(20, count($subtopics) * 5);
        }

        // Average post score
        if ($post_count > 0) {
            global $wpdb;
            $table_scores = $wpdb->prefix . 'aiseo_scores';
            $ids = array_map(fn($p) => (int)$p['post_id'], $posts);
            if ($has_pillar) {
                $ids[] = (int)$cluster['pillar_post_id'];
            }
            $ids = array_unique($ids);
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            
            $avg = (float)$wpdb->get_var(
                $wpdb->prepare(
                    "SELECT AVG(overall_score) FROM {$table_scores} WHERE post_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    ...$ids
                )
            );
            $score += (int)round(($avg / 100) * 20);
        }

        $final_score = max(0, min(100, $score));
        self::update($cluster_id, ['coverage_score' => $final_score]);

        return $final_score;
    }

    /**
     * Format raw database row to typed array.
     *
     * @param array<string, mixed> $row Raw database row.
     * @return array<string, mixed>
     */
    private static function format_cluster(array $row): array
    {
        $pillar_id = (int)($row['pillar_post_id'] ?? 0);
        $pillar_title = $pillar_id > 0 ? get_the_title($pillar_id) : '';

        return [
            'id'             => (int)$row['id'],
            'name'           => (string)$row['name'],
            'slug'           => (string)$row['slug'],
            'pillar_post_id' => $pillar_id,
            'pillar_title'   => $pillar_title,
            'posts'          => json_decode((string)($row['posts_json'] ?? '[]'), true) ?: [],
            'entities'       => json_decode((string)($row['entities_json'] ?? '[]'), true) ?: [],
            'coverage_score' => (int)($row['coverage_score'] ?? 0),
            'subtopics'      => json_decode((string)($row['subtopics_json'] ?? '[]'), true) ?: [],
            'created_at'     => (string)$row['created_at'],
            'updated_at'     => (string)$row['updated_at'],
        ];
    }
}
