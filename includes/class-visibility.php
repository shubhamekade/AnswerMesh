<?php
/**
 * AI Search Visibility data model and query manager.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Visibility
{
    /**
     * Get table name.
     */
    public static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'aiseo_visibility';
    }

    /**
     * Record a new AI search observation.
     *
     * @param array<string, mixed> $data
     * @return int|false ID of inserted record, or false on failure.
     */
    public static function record_observation(array $data): int|false
    {
        global $wpdb;

        $table = self::table_name();

        $query = sanitize_text_field($data['query'] ?? '');
        if (empty($query)) {
            return false;
        }

        $platform = sanitize_key($data['platform'] ?? 'chatgpt');
        $observed = !empty($data['observed']) ? 1 : 0;
        $mention = !empty($data['mention']) ? 1 : 0;
        $position_context = sanitize_textarea_field($data['position_context'] ?? '');
        $source_url = esc_url_raw($data['source_url'] ?? '');

        $competitors = $data['competitors_json'] ?? [];
        if (is_array($competitors)) {
            $competitors_json = wp_json_encode($competitors);
        } elseif (is_string($competitors)) {
            $competitors_json = $competitors;
        } else {
            $competitors_json = '[]';
        }

        $captured_at = !empty($data['captured_at']) 
            ? sanitize_text_field($data['captured_at']) 
            : current_time('mysql');

        $inserted = $wpdb->insert(
            $table,
            [
                'query' => $query,
                'platform' => $platform,
                'observed' => $observed,
                'mention' => $mention,
                'position_context' => $position_context,
                'source_url' => $source_url,
                'competitors_json' => $competitors_json,
                'captured_at' => $captured_at,
            ],
            [
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($inserted === false) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get observation records with optional filters.
     *
     * @param array<string, mixed> $args
     * @return array<int, array<string, mixed>>
     */
    public static function get_observations(array $args = []): array
    {
        global $wpdb;

        $table = self::table_name();
        $where = ['1=1'];
        $params = [];

        if (!empty($args['platform']) && $args['platform'] !== 'all') {
            $where[] = 'platform = %s';
            $params[] = sanitize_key($args['platform']);
        }

        if (!empty($args['query'])) {
            $where[] = 'query LIKE %s';
            $params[] = '%' . $wpdb->esc_like(sanitize_text_field($args['query'])) . '%';
        }

        if (isset($args['observed']) && $args['observed'] !== '' && $args['observed'] !== null) {
            $where[] = 'observed = %d';
            $params[] = !empty($args['observed']) ? 1 : 0;
        }

        if (!empty($args['from_date'])) {
            $where[] = 'captured_at >= %s';
            $params[] = sanitize_text_field($args['from_date']);
        }

        if (!empty($args['to_date'])) {
            $where[] = 'captured_at <= %s';
            $params[] = sanitize_text_field($args['to_date']);
        }

        $limit = isset($args['limit']) ? max(1, min(200, (int) $args['limit'])) : 50;
        $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

        $orderby = 'captured_at';
        $order = 'DESC';
        if (!empty($args['order']) && strtoupper((string) $args['order']) === 'ASC') {
            $order = 'ASC';
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        $prepared = $wpdb->prepare($sql, ...$params);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        return array_map([self::class, 'hydrate_row'], $rows);
    }

    /**
     * Get total count of matching observations.
     *
     * @param array<string, mixed> $args
     * @return int
     */
    public static function get_total_count(array $args = []): int
    {
        global $wpdb;

        $table = self::table_name();
        $where = ['1=1'];
        $params = [];

        if (!empty($args['platform']) && $args['platform'] !== 'all') {
            $where[] = 'platform = %s';
            $params[] = sanitize_key($args['platform']);
        }

        if (!empty($args['query'])) {
            $where[] = 'query LIKE %s';
            $params[] = '%' . $wpdb->esc_like(sanitize_text_field($args['query'])) . '%';
        }

        if (isset($args['observed']) && $args['observed'] !== '' && $args['observed'] !== null) {
            $where[] = 'observed = %d';
            $params[] = !empty($args['observed']) ? 1 : 0;
        }

        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get high-level summary metrics.
     *
     * @param int $days Time window in days.
     * @return array<string, mixed>
     */
    public static function get_summary(int $days = 30): array
    {
        global $wpdb;

        $table = self::table_name();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * 86400));

        $sql = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_checks,
                SUM(observed) as total_observed,
                SUM(mention) as total_mentions,
                SUM(CASE WHEN source_url != '' THEN 1 ELSE 0 END) as total_citations
            FROM {$table}
            WHERE captured_at >= %s",
            $cutoff
        );

        $row = $wpdb->get_row($sql, ARRAY_A) ?: [];

        $total_checks = (int) ($row['total_checks'] ?? 0);
        $total_observed = (int) ($row['total_observed'] ?? 0);
        $total_mentions = (int) ($row['total_mentions'] ?? 0);
        $total_citations = (int) ($row['total_citations'] ?? 0);

        $citation_rate = $total_checks > 0 ? round(($total_citations / $total_checks) * 100, 1) : 0.0;
        $mention_rate = $total_checks > 0 ? round(($total_mentions / $total_checks) * 100, 1) : 0.0;
        $observed_rate = $total_checks > 0 ? round(($total_observed / $total_checks) * 100, 1) : 0.0;

        // Platform breakdown
        $platforms_sql = $wpdb->prepare(
            "SELECT 
                platform,
                COUNT(*) as count,
                SUM(observed) as observed,
                SUM(mention) as mentions,
                SUM(CASE WHEN source_url != '' THEN 1 ELSE 0 END) as citations
            FROM {$table}
            WHERE captured_at >= %s
            GROUP BY platform",
            $cutoff
        );

        $platform_rows = $wpdb->get_results($platforms_sql, ARRAY_A) ?: [];
        $platforms = [
            'chatgpt' => ['name' => 'ChatGPT Search', 'checks' => 0, 'observed' => 0, 'citations' => 0, 'rate' => 0.0],
            'gemini' => ['name' => 'Google Gemini', 'checks' => 0, 'observed' => 0, 'citations' => 0, 'rate' => 0.0],
            'perplexity' => ['name' => 'Perplexity AI', 'checks' => 0, 'observed' => 0, 'citations' => 0, 'rate' => 0.0],
            'copilot' => ['name' => 'Microsoft Copilot', 'checks' => 0, 'observed' => 0, 'citations' => 0, 'rate' => 0.0],
        ];

        foreach ($platform_rows as $p_row) {
            $key = $p_row['platform'];
            $p_count = (int) $p_row['count'];
            $p_obs = (int) $p_row['observed'];
            $p_cit = (int) $p_row['citations'];
            $rate = $p_count > 0 ? round(($p_obs / $p_count) * 100, 1) : 0.0;

            if (isset($platforms[$key])) {
                $platforms[$key]['checks'] = $p_count;
                $platforms[$key]['observed'] = $p_obs;
                $platforms[$key]['citations'] = $p_cit;
                $platforms[$key]['rate'] = $rate;
            } else {
                $platforms[$key] = [
                    'name' => ucfirst($key),
                    'checks' => $p_count,
                    'observed' => $p_obs,
                    'citations' => $p_cit,
                    'rate' => $rate,
                ];
            }
        }

        return [
            'time_window_days' => $days,
            'total_checks' => $total_checks,
            'total_observed' => $total_observed,
            'total_mentions' => $total_mentions,
            'total_citations' => $total_citations,
            'citation_rate' => $citation_rate,
            'mention_rate' => $mention_rate,
            'observed_rate' => $observed_rate,
            'platforms' => $platforms,
        ];
    }

    /**
     * Delete records older than specified number of days (Retention pruning).
     *
     * @param int $days Retention threshold in days (defaults to 90).
     * @return int Number of rows deleted.
     */
    public static function prune_old_records(int $days = 90): int
    {
        global $wpdb;

        $table = self::table_name();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * 86400));

        $deleted = $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table} WHERE captured_at < %s", $cutoff)
        );

        return is_numeric($deleted) ? (int) $deleted : 0;
    }

    /**
     * Delete an observation by ID.
     */
    public static function delete_observation(int $id): bool
    {
        global $wpdb;
        $table = self::table_name();
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);
        return $result !== false && $result > 0;
    }

    /**
     * Hydrate a row from the database.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected static function hydrate_row(array $row): array
    {
        $competitors = [];
        if (!empty($row['competitors_json'])) {
            $decoded = json_decode((string) $row['competitors_json'], true);
            if (is_array($decoded)) {
                $competitors = $decoded;
            }
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'query' => (string) ($row['query'] ?? ''),
            'platform' => (string) ($row['platform'] ?? ''),
            'observed' => !empty($row['observed']),
            'mention' => !empty($row['mention']),
            'position_context' => (string) ($row['position_context'] ?? ''),
            'source_url' => (string) ($row['source_url'] ?? ''),
            'competitors' => $competitors,
            'captured_at' => (string) ($row['captured_at'] ?? ''),
        ];
    }
}
