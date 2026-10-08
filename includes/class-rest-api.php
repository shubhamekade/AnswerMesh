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

        // GET /aiseo/v1/entities
        register_rest_route(self::NAMESPACE, '/entities', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_entities'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // POST /aiseo/v1/entities
        register_rest_route(self::NAMESPACE, '/entities', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'save_entity'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // DELETE /aiseo/v1/entities/{id}
        register_rest_route(self::NAMESPACE, '/entities/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_entity'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // GET /aiseo/v1/questions
        register_rest_route(self::NAMESPACE, '/questions', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_questions'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // GET /aiseo/v1/posts/{id}/analysis
        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)/analysis', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_post_analysis'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // POST /aiseo/v1/crawl/start
        register_rest_route(self::NAMESPACE, '/crawl/start', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'start_crawl'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // GET /aiseo/v1/crawl/status
        register_rest_route(self::NAMESPACE, '/crawl/status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_crawl_status'],
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

    /**
     * GET /entities handler.
     */
    public function get_entities(WP_REST_Request $request): WP_REST_Response
    {
        $type = $request->get_param('type');
        $entities = Entity::get_all($type ? sanitize_key($type) : null);

        return new WP_REST_Response([
            'success' => true,
            'data'    => $entities,
        ], 200);
    }

    /**
     * POST /entities handler.
     */
    public function save_entity(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params();
        if (!is_array($params) || empty($params['name'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Entity name is required.', 'answermesh'),
            ], 400);
        }

        $id = !empty($params['id']) ? (int) $params['id'] : 0;
        $saved_id = Entity::save($params, $id);

        return new WP_REST_Response([
            'success' => true,
            'data'    => Entity::get_by_id($saved_id),
            'message' => __('Entity saved successfully.', 'answermesh'),
        ], 200);
    }

    /**
     * DELETE /entities/{id} handler.
     */
    public function delete_entity(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $deleted = Entity::delete($id);

        return new WP_REST_Response([
            'success' => $deleted,
            'message' => $deleted ? __('Entity deleted.', 'answermesh') : __('Failed to delete entity.', 'answermesh'),
        ], $deleted ? 200 : 404);
    }

    /**
     * GET /questions handler.
     */
    public function get_questions(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_questions';

        $post_id = (int) $request->get_param('post_id');
        if ($post_id > 0) {
            $questions = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d ORDER BY created_at DESC", $post_id),
                ARRAY_A
            );
        } else {
            $questions = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100", ARRAY_A);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $questions ?: [],
        ], 200);
    }

    /**
     * GET /posts/{id}/analysis handler.
     */
    public function get_post_analysis(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = (int) $request->get_param('id');
        $post = get_post($post_id);
        if (!$post) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Post not found.', 'answermesh'),
            ], 404);
        }

        $content_audit = PostAnalyzer::analyze_post($post_id);
        $trust_audit = TrustAnalyzer::evaluate_post($post_id);
        $questions = QuestionEngine::extract_questions_from_post($post_id, (string) $post->post_content);

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'post_id'   => $post_id,
                'title'     => Meta::get_title($post_id),
                'content'   => $content_audit,
                'trust'     => $trust_audit,
                'questions' => $questions,
            ],
        ], 200);
    }

    /**
     * POST /crawl/start handler.
     */
    public function start_crawl(WP_REST_Request $request): WP_REST_Response
    {
        $limit = (int) ($request->get_param('limit') ?: 50);
        $started = SiteAnalyzer::start_crawl($limit);

        return new WP_REST_Response([
            'success' => $started,
            'data'    => SiteAnalyzer::get_crawl_status(),
            'message' => $started ? __('Site crawl initiated.', 'answermesh') : __('Could not start crawl.', 'answermesh'),
        ], 200);
    }

    /**
     * GET /crawl/status handler.
     */
    public function get_crawl_status(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data'    => SiteAnalyzer::get_crawl_status(),
        ], 200);
    }
}
