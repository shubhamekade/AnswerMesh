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

        // GET /aiseo/v1/recommendations
        register_rest_route(self::NAMESPACE, '/recommendations', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_recommendations'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // POST /aiseo/v1/posts/{id}/analyze
        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)/analyze', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'trigger_post_analyze'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // POST /aiseo/v1/ai/test-connection
        register_rest_route(self::NAMESPACE, '/ai/test-connection', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'test_ai_connection'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // POST /aiseo/v1/posts/{id}/generate-answer
        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)/generate-answer', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'generate_answer'],
            'permission_callback' => [$this, 'check_editor_permission'],
        ]);

        // POST /aiseo/v1/posts/{id}/generate-faq
        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)/generate-faq', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'generate_faq'],
            'permission_callback' => [$this, 'check_editor_permission'],
        ]);

        // POST /aiseo/v1/posts/{id}/apply-diff
        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)/apply-diff', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'apply_diff'],
            'permission_callback' => [$this, 'check_editor_permission'],
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
     * Permission callback: requires edit_posts.
     */
    public function check_editor_permission(): bool|WP_Error
    {
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to edit content.', 'answermesh'),
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
     * GET /dashboard handler.
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
            $site_scores = Score::compute_and_save_site_score();
        } else {
            $site_scores['label'] = Score::get_rating_label((int) $site_scores['overall_score']);
        }

        // Fetch count of pending recommendations.
        $pending_recommendations_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$rec_table} WHERE status = 'pending'"
        );

        // Fetch top 5 priorities.
        $top_priorities = RecommendationEngine::get_top_priorities(5);

        // Fetch analyzed posts count.
        $analyzed_count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$scores_table} WHERE post_id > 0"
        );

        // Fetch total indexed posts count.
        $total_posts = (int) wp_count_posts('post')->publish + (int) wp_count_posts('page')->publish;

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'scores'               => $site_scores,
                'pending_actions'      => $pending_recommendations_count,
                'top_priorities'       => $top_priorities,
                'analyzed_pages_count' => $analyzed_count,
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

    /**
     * GET /recommendations handler.
     */
    public function get_recommendations(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aiseo_recommendations';

        $post_id = (int) $request->get_param('post_id');
        $priority = $request->get_param('priority');

        $where = ["status = 'pending'"];
        $params = [];

        if ($post_id > 0) {
            $where[] = "post_id = %d";
            $params[] = $post_id;
        }
        if (!empty($priority)) {
            $where[] = "priority = %s";
            $params[] = sanitize_key($priority);
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY 
            CASE priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END ASC, created_at DESC LIMIT 100";

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $results ?: [],
        ], 200);
    }

    /**
     * POST /posts/{id}/analyze handler.
     */
    public function trigger_post_analyze(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = (int) $request->get_param('id');
        $post = get_post($post_id);
        if (!$post) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Post not found.', 'answermesh'),
            ], 404);
        }

        $scores = Score::compute_and_save_post_score($post_id);

        return new WP_REST_Response([
            'success' => true,
            'data'    => $scores,
            'message' => __('Post successfully analyzed and scored.', 'answermesh'),
        ], 200);
    }

    /**
     * POST /ai/test-connection handler.
     */
    public function test_ai_connection(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params() ?: [];
        $provider = sanitize_key($params['provider'] ?? '');
        $api_key = trim((string) ($params['api_key'] ?? ''));
        $model = sanitize_text_field($params['model'] ?? '');

        // If api_key masked, pull saved decrypted key.
        if (str_contains($api_key, '••••••••') || empty($api_key)) {
            $api_key = Settings::get_instance()->get_decrypted_api_key();
        }

        $result = AI::test_connection($provider, $api_key, $model);

        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /posts/{id}/generate-answer handler.
     */
    public function generate_answer(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = (int) $request->get_param('id');
        $params = $request->get_json_params() ?: [];
        $question = sanitize_text_field($params['question'] ?? '');

        if (empty($question)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => __('Question parameter is required.', 'answermesh'),
            ], 400);
        }

        $proposal = ContentEngine::generate_answer_block($post_id, $question);

        return new WP_REST_Response($proposal, $proposal['success'] ? 200 : 400);
    }

    /**
     * POST /posts/{id}/generate-faq handler.
     */
    public function generate_faq(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = (int) $request->get_param('id');
        $params = $request->get_json_params() ?: [];
        $count = max(1, min(10, (int) ($params['count'] ?? 3)));

        $proposal = ContentEngine::generate_faq($post_id, $count);

        return new WP_REST_Response($proposal, $proposal['success'] ? 200 : 400);
    }

    /**
     * POST /posts/{id}/apply-diff handler.
     */
    public function apply_diff(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = (int) $request->get_param('id');
        $params = $request->get_json_params() ?: [];
        $action_type = sanitize_key($params['type'] ?? 'append');
        $content = (string) ($params['content'] ?? '');

        if (empty($content)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Content cannot be empty.', 'answermesh'),
            ], 400);
        }

        $applied = ContentEngine::apply_proposal($post_id, $action_type, $content);

        return new WP_REST_Response([
            'success' => $applied,
            'message' => $applied ? __('Proposal successfully applied.', 'answermesh') : __('Failed to apply proposal.', 'answermesh'),
        ], $applied ? 200 : 500);
    }
}
