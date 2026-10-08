<?php
/**
 * REST API controller for AnswerMesh endpoints.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

defined('ABSPATH') || exit;

class RestApi
{
    public const NAMESPACE = 'aiseo/v1';

    /**
     * Register hooks.
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes.
     */
    public function register_routes(): void
    {
        // GET /aiseo/v1/status
        register_rest_route(self::NAMESPACE, '/status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // GET /aiseo/v1/settings
        register_rest_route(self::NAMESPACE, '/settings', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_settings'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // POST /aiseo/v1/settings
        register_rest_route(self::NAMESPACE, '/settings', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'update_settings'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // GET /aiseo/v1/dashboard
        register_rest_route(self::NAMESPACE, '/dashboard', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Permission callback: requires manage_options.
     */
    public function check_admin_permission(): bool|WP_Error
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to access AnswerMesh settings.', 'answermesh'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * GET /status handler.
     */
    public function get_status(WP_REST_Request $request): WP_REST_Response
    {
        $settings = Settings::get_instance();
        $table_status = Database::check_tables();

        $data = [
            'version'         => ANSWERMESH_VERSION,
            'db_version'      => get_option(Database::OPTION_NAME, 'none'),
            'php_version'     => PHP_VERSION,
            'wp_version'      => get_bloginfo('version'),
            'ai_provider'     => $settings->get('ai', 'provider', 'none'),
            'has_api_key'     => !empty($settings->get_decrypted_api_key()),
            'tables'          => $table_status,
            'installed_at'    => get_option('answermesh_installed_at', ''),
            'timestamp'       => current_time('mysql'),
        ];

        return new WP_REST_Response([
            'success' => true,
            'data'    => $data,
        ], 200);
    }

    /**
     * GET /settings handler.
     */
    public function get_settings(WP_REST_Request $request): WP_REST_Response
    {
        $settings = Settings::get_instance();
        return new WP_REST_Response([
            'success' => true,
            'data'    => $settings->get_safe_payload(),
        ], 200);
    }

    /**
     * POST /settings handler.
     */
    public function update_settings(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Invalid JSON payload provided.', 'answermesh'),
            ], 400);
        }

        $settings = Settings::get_instance();

        foreach ($params as $group => $fields) {
            if (is_array($fields)) {
                $settings->set((string) $group, $fields);
            }
        }

        $saved = $settings->save();

        return new WP_REST_Response([
            'success' => $saved,
            'data'    => $settings->get_safe_payload(),
            'message' => __('Settings successfully saved.', 'answermesh'),
        ], 200);
    }

    /**
     * GET /dashboard skeleton handler.
     */
    public function get_dashboard(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $scores_table = $wpdb->prefix . 'aiseo_scores';
        $rec_table = $wpdb->prefix . 'aiseo_recommendations';

        // Fetch site-level scores (post_id = 0).
        $site_scores = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$scores_table} WHERE post_id = 0 ORDER BY updated_at DESC LIMIT 1"),
            ARRAY_A
        );

        if (!$site_scores) {
            $site_scores = [
                'overall_score' => 0,
                'seo_score'     => 0,
                'aeo_score'     => 0,
                'geo_score'     => 0,
                'entity_score'  => 0,
                'trust_score'   => 0,
                'status'        => 'uncalibrated',
            ];
        }

        // Fetch count of pending recommendations.
        $pending_recommendations_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$rec_table} WHERE status = 'pending'"
        );

        // Fetch total indexed posts count.
        $total_posts = (int) wp_count_posts('post')->publish + (int) wp_count_posts('page')->publish;

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'scores'               => $site_scores,
                'pending_actions'      => $pending_recommendations_count,
                'analyzed_pages_count' => 0,
                'total_posts_count'    => $total_posts,
            ],
        ], 200);
    }
}
