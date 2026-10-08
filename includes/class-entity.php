<?php
/**
 * Entity knowledge base and business context manager.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Entity
{
    public const TYPE_ORGANIZATION = 'organization';
    public const TYPE_PERSON = 'person';
    public const TYPE_SERVICE = 'service';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_LOCATION = 'location';

    /**
     * Get all entities or filtered by type.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_all(?string $type = null): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_entities';

        if ($type !== null) {
            $rows = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$table} WHERE type = %s ORDER BY name ASC", $type),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY type ASC, name ASC", ARRAY_A);
        }

        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            if (!empty($row['data_json'])) {
                $row['data'] = json_decode((string) $row['data_json'], true) ?: [];
            } else {
                $row['data'] = [];
            }
        }

        return $rows;
    }

    /**
     * Get a single entity by ID.
     */
    public static function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_entities';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $row['data'] = !empty($row['data_json']) ? (json_decode((string) $row['data_json'], true) ?: []) : [];
        return $row;
    }

    /**
     * Insert or update an entity.
     *
     * @param array{type: string, name: string, description?: string, url?: string, schema_type?: string, data?: array} $data
     */
    public static function save(array $data, int $id = 0): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_entities';

        $payload = [
            'type'        => sanitize_key($data['type'] ?? self::TYPE_ORGANIZATION),
            'name'        => sanitize_text_field($data['name'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'url'         => esc_url_raw($data['url'] ?? ''),
            'schema_type' => sanitize_text_field($data['schema_type'] ?? 'Thing'),
            'data_json'   => wp_json_encode($data['data'] ?? []),
            'updated_at'  => current_time('mysql'),
        ];

        if ($id > 0) {
            $wpdb->update($table, $payload, ['id' => $id]);
            return $id;
        }

        $payload['created_at'] = current_time('mysql');
        $wpdb->insert($table, $payload);
        return (int) $wpdb->insert_id;
    }

    /**
     * Delete an entity by ID.
     */
    public static function delete(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_entities';
        return (bool) $wpdb->delete($table, ['id' => $id]);
    }

    /**
     * Build formatted verified business context string for AI engine prompts.
     */
    public static function get_verified_context(): string
    {
        $entities = self::get_all();
        if (empty($entities)) {
            $settings = Settings::get_instance();
            return sprintf(
                "Business: %s\nSite Description: %s",
                get_bloginfo('name'),
                $settings->get('schema', 'business_name', get_bloginfo('name'))
            );
        }

        $lines = ["VERIFIED BUSINESS ENTITIES:"];

        foreach ($entities as $e) {
            $lines[] = sprintf(
                "- [%s] %s: %s (URL: %s)",
                strtoupper((string) $e['type']),
                $e['name'],
                $e['description'] ?: 'None provided',
                $e['url'] ?: 'N/A'
            );
        }

        return implode("\n", $lines);
    }
}
