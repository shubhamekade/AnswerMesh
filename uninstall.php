<?php
/**
 * Plugin cleanup on uninstall.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('WP_UNINSTALL_PLUGIN') || exit;

// Check if user elected to delete all plugin data.
$delete_data = get_option('answermesh_delete_data_on_uninstall', false);

if ($delete_data) {
    // 1. Drop tables.
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'aiseo_scores',
        $wpdb->prefix . 'aiseo_entities',
        $wpdb->prefix . 'aiseo_questions',
        $wpdb->prefix . 'aiseo_recommendations',
        $wpdb->prefix . 'aiseo_visibility',
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table};"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    // 2. Delete options.
    delete_option('answermesh_settings');
    delete_option('answermesh_db_version');
    delete_option('answermesh_installed_at');
    delete_option('answermesh_delete_data_on_uninstall');

    // 3. Clear transients.
    delete_transient('answermesh_flush_rewrites');
}
