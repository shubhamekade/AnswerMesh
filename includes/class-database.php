<?php
/**
 * Database schema and migration manager.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Database
{
    public const DB_VERSION = '1.1.0';
    public const OPTION_NAME = 'answermesh_db_version';

    /**
     * Create or upgrade custom tables.
     */
    public static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $table_scores = $wpdb->prefix . 'aiseo_scores';
        $table_entities = $wpdb->prefix . 'aiseo_entities';
        $table_questions = $wpdb->prefix . 'aiseo_questions';
        $table_recommendations = $wpdb->prefix . 'aiseo_recommendations';
        $table_visibility = $wpdb->prefix . 'aiseo_visibility';
        $table_clusters = $wpdb->prefix . 'aiseo_clusters';

        $sql = "
CREATE TABLE {$table_scores} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint(20) unsigned DEFAULT 0,
  seo_score tinyint(3) unsigned NOT NULL DEFAULT 0,
  aeo_score tinyint(3) unsigned NOT NULL DEFAULT 0,
  geo_score tinyint(3) unsigned NOT NULL DEFAULT 0,
  entity_score tinyint(3) unsigned NOT NULL DEFAULT 0,
  trust_score tinyint(3) unsigned NOT NULL DEFAULT 0,
  overall_score tinyint(3) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY post_id (post_id)
) {$charset_collate};

CREATE TABLE {$table_entities} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(64) NOT NULL,
  name varchar(255) NOT NULL,
  description text,
  url varchar(500) DEFAULT '',
  schema_type varchar(64) NOT NULL,
  data_json longtext,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY type (type)
) {$charset_collate};

CREATE TABLE {$table_questions} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  question text NOT NULL,
  intent varchar(32) NOT NULL DEFAULT 'informational',
  topic varchar(128) DEFAULT '',
  post_id bigint(20) unsigned DEFAULT 0,
  status varchar(32) NOT NULL DEFAULT 'missing',
  confidence varchar(16) NOT NULL DEFAULT 'medium',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY post_id (post_id),
  KEY status (status)
) {$charset_collate};

CREATE TABLE {$table_recommendations} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint(20) unsigned DEFAULT 0,
  type varchar(64) NOT NULL,
  priority varchar(16) NOT NULL DEFAULT 'medium',
  title varchar(255) NOT NULL,
  description text,
  evidence text,
  confidence varchar(16) NOT NULL DEFAULT 'medium',
  status varchar(32) NOT NULL DEFAULT 'pending',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY post_id (post_id),
  KEY priority (priority),
  KEY status (status)
) {$charset_collate};

CREATE TABLE {$table_visibility} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  query varchar(255) NOT NULL,
  platform varchar(64) NOT NULL,
  observed tinyint(1) NOT NULL DEFAULT 0,
  mention tinyint(1) NOT NULL DEFAULT 0,
  position_context text,
  source_url varchar(500) DEFAULT '',
  competitors_json longtext,
  captured_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY query (query(191)),
  KEY platform (platform)
) {$charset_collate};

CREATE TABLE {$table_clusters} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  slug varchar(255) NOT NULL,
  pillar_post_id bigint(20) unsigned DEFAULT 0,
  posts_json longtext,
  entities_json text,
  coverage_score tinyint(3) unsigned NOT NULL DEFAULT 0,
  subtopics_json text,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY slug (slug(191)),
  KEY pillar_post_id (pillar_post_id)
) {$charset_collate};
";

        dbDelta($sql);

        update_option(self::OPTION_NAME, self::DB_VERSION);
    }

    /**
     * Check if tables exist.
     *
     * @return array<string, bool>
     */
    public static function check_tables(): array
    {
        global $wpdb;

        $tables = [
            'aiseo_scores',
            'aiseo_entities',
            'aiseo_questions',
            'aiseo_recommendations',
            'aiseo_visibility',
            'aiseo_clusters',
        ];

        $status = [];
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $found = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
            $status[$table] = ($found === $table_name);
        }

        return $status;
    }

    /**
     * Drop all custom tables on complete uninstall.
     */
    public static function drop_tables(): void
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'aiseo_scores',
            $wpdb->prefix . 'aiseo_entities',
            $wpdb->prefix . 'aiseo_questions',
            $wpdb->prefix . 'aiseo_recommendations',
            $wpdb->prefix . 'aiseo_visibility',
            $wpdb->prefix . 'aiseo_clusters',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table};"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        delete_option(self::OPTION_NAME);
    }
}
