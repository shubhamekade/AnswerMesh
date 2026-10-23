<?php
/**
 * Test bootstrap and WordPress mock environment.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

if (!defined('ANSWERMESH_VERSION')) {
    define('ANSWERMESH_VERSION', '1.0.0');
}

if (!defined('ANSWERMESH_DIR')) {
    define('ANSWERMESH_DIR', rtrim(ABSPATH, '/\\') . DIRECTORY_SEPARATOR);
}

if (!defined('ANSWERMESH_URL')) {
    define('ANSWERMESH_URL', 'http://example.com/wp-content/plugins/answermesh/');
}

if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!defined('HOUR_IN_SECONDS')) define('HOUR_IN_SECONDS', 3600);
if (!defined('DAY_IN_SECONDS')) define('DAY_IN_SECONDS', 86400);

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

// In-memory option and postmeta stores
$GLOBALS['mock_options'] = [];
$GLOBALS['mock_postmeta'] = [];
$GLOBALS['mock_transients'] = [];

// Mock $wpdb
class MockWPDB {
    public string $prefix = 'wp_';
    public array $queries = [];
    public int $insert_id = 1;
    public array $table_data = [
        'wp_aiseo_clusters'   => [],
        'wp_aiseo_visibility' => [],
    ];

    public function prepare(string $query, ...$args): string {
        $offset = 0;
        foreach ($args as $arg) {
            $pos = strpos($query, '%', $offset);
            if ($pos === false) break;
            $spec = substr($query, $pos, 2);
            $val = is_numeric($arg) ? (string)$arg : "'" . addslashes((string)$arg) . "'";
            $query = substr_replace($query, $val, $pos, 2);
            $offset = $pos + strlen($val);
        }
        return $query;
    }

    public function query(string $sql) {
        $this->queries[] = $sql;
        if (str_contains($sql, 'DELETE FROM wp_aiseo_visibility') && preg_match("/captured_at < '([^']+)'/", $sql, $m)) {
            $cutoff = $m[1];
            $count = 0;
            foreach ($this->table_data['wp_aiseo_visibility'] as $id => $row) {
                if (($row['captured_at'] ?? '') < $cutoff) {
                    unset($this->table_data['wp_aiseo_visibility'][$id]);
                    $count++;
                }
            }
            return $count;
        }
        return 1;
    }

    public function get_var(string $sql) {
        $this->queries[] = $sql;
        if (str_contains($sql, 'AVG(overall_score)')) {
            return '75';
        }
        if (str_contains($sql, 'COUNT(*) FROM wp_aiseo_visibility')) {
            return (string) count($this->table_data['wp_aiseo_visibility']);
        }
        return null;
    }

    public function get_row(string $sql, $output = OBJECT) {
        $this->queries[] = $sql;
        if (str_contains($sql, 'wp_aiseo_clusters') && preg_match('/WHERE id = (\d+)/', $sql, $m)) {
            $id = (int)$m[1];
            return $this->table_data['wp_aiseo_clusters'][$id] ?? null;
        }
        if (str_contains($sql, 'wp_aiseo_clusters') && preg_match('/WHERE pillar_post_id = (\d+)/', $sql, $m)) {
            $pid = (int)$m[1];
            foreach ($this->table_data['wp_aiseo_clusters'] as $row) {
                if ((int)($row['pillar_post_id'] ?? 0) === $pid) {
                    return $row;
                }
            }
        }
        if (str_contains($sql, 'wp_aiseo_visibility') && str_contains($sql, 'COUNT(*) as total_checks')) {
            $rows = $this->table_data['wp_aiseo_visibility'];
            $total_checks = count($rows);
            $total_observed = 0;
            $total_mentions = 0;
            $total_citations = 0;
            foreach ($rows as $r) {
                if (!empty($r['observed'])) $total_observed++;
                if (!empty($r['mention'])) $total_mentions++;
                if (!empty($r['source_url'])) $total_citations++;
            }
            return [
                'total_checks'    => $total_checks,
                'total_observed'  => $total_observed,
                'total_mentions'  => $total_mentions,
                'total_citations' => $total_citations,
            ];
        }
        return null;
    }

    public function get_results(string $sql, $output = OBJECT) {
        $this->queries[] = $sql;
        if (str_contains($sql, 'wp_aiseo_clusters')) {
            return array_values($this->table_data['wp_aiseo_clusters']);
        }
        if (str_contains($sql, 'wp_aiseo_visibility')) {
            if (str_contains($sql, 'GROUP BY platform')) {
                $grouped = [];
                foreach ($this->table_data['wp_aiseo_visibility'] as $r) {
                    $p = $r['platform'] ?? 'unknown';
                    if (!isset($grouped[$p])) {
                        $grouped[$p] = ['platform' => $p, 'count' => 0, 'observed' => 0, 'mentions' => 0, 'citations' => 0];
                    }
                    $grouped[$p]['count']++;
                    if (!empty($r['observed'])) $grouped[$p]['observed']++;
                    if (!empty($r['mention'])) $grouped[$p]['mentions']++;
                    if (!empty($r['source_url'])) $grouped[$p]['citations']++;
                }
                return array_values($grouped);
            }
            return array_values($this->table_data['wp_aiseo_visibility']);
        }
        return [];
    }

    public function insert(string $table, array $data, $format = null) {
        $this->queries[] = "INSERT INTO $table";
        if ($table === 'wp_aiseo_clusters') {
            $id = $this->insert_id++;
            $data['id'] = $id;
            $this->table_data['wp_aiseo_clusters'][$id] = $data;
            return 1;
        }
        if ($table === 'wp_aiseo_visibility') {
            $id = $this->insert_id++;
            $data['id'] = $id;
            $this->table_data['wp_aiseo_visibility'][$id] = $data;
            return 1;
        }
        return 1;
    }

    public function update(string $table, array $data, array $where, $format = null, $where_format = null) {
        $this->queries[] = "UPDATE $table";
        if ($table === 'wp_aiseo_clusters' && isset($where['id'])) {
            $id = (int)$where['id'];
            if (isset($this->table_data['wp_aiseo_clusters'][$id])) {
                $this->table_data['wp_aiseo_clusters'][$id] = array_merge($this->table_data['wp_aiseo_clusters'][$id], $data);
            }
            return 1;
        }
        return 1;
    }

    public function delete(string $table, array $where, $where_format = null) {
        $this->queries[] = "DELETE FROM $table";
        if ($table === 'wp_aiseo_clusters' && isset($where['id'])) {
            $id = (int)$where['id'];
            unset($this->table_data['wp_aiseo_clusters'][$id]);
            return 1;
        }
        if ($table === 'wp_aiseo_visibility' && isset($where['id'])) {
            $id = (int)$where['id'];
            unset($this->table_data['wp_aiseo_visibility'][$id]);
            return 1;
        }
        return 1;
    }
}

$GLOBALS['wpdb'] = new MockWPDB();

// Mock WP core functions
function get_option(string $key, $default = false) {
    return $GLOBALS['mock_options'][$key] ?? $default;
}

function update_option(string $key, $value): bool {
    $GLOBALS['mock_options'][$key] = $value;
    return true;
}

function get_post_meta(int $post_id, string $key = '', bool $single = false) {
    if (!isset($GLOBALS['mock_postmeta'][$post_id])) return $single ? '' : [];
    if ($key === '') return $GLOBALS['mock_postmeta'][$post_id];
    return $GLOBALS['mock_postmeta'][$post_id][$key] ?? ($single ? '' : []);
}

function update_post_meta(int $post_id, string $key, $value): bool {
    if (!isset($GLOBALS['mock_postmeta'][$post_id])) {
        $GLOBALS['mock_postmeta'][$post_id] = [];
    }
    $GLOBALS['mock_postmeta'][$post_id][$key] = $value;
    return true;
}

function get_post($post = null) {
    if (is_object($post)) return $post;
    return (object)[
        'ID' => (int)$post,
        'post_title' => 'Sample Article Title',
        'post_content' => '<h2>What is AI Search?</h2><p>AI search is an evolution of search engines that synthesizes direct answers.</p>',
        'post_excerpt' => 'Sample excerpt for search test.',
        'post_date' => '2026-01-01 12:00:00',
        'post_date_gmt' => '2026-01-01 12:00:00',
        'post_modified' => '2026-01-02 12:00:00',
        'post_modified_gmt' => '2026-01-02 12:00:00',
        'post_author' => 1,
        'post_type' => 'post',
        'post_status' => 'publish',
    ];
}

function get_post_ancestors($post): array {
    return [];
}

function get_the_category($post_id = false): array {
    return [];
}

function get_category_link($cat): string {
    return 'http://example.com/cat/1';
}

function get_bloginfo(string $show = 'name') {
    if ($show === 'name') return 'AnswerMesh Test Site';
    if ($show === 'description') return 'AI Search Visibility Engine';
    if ($show === 'url') return 'http://example.com';
    return '';
}

function home_url(string $path = ''): string {
    return 'http://example.com' . ($path ? '/' . ltrim($path, '/') : '');
}

function get_permalink($post = 0): string {
    $id = is_object($post) ? ($post->ID ?? 1) : (int)$post;
    return "http://example.com/p/{$id}/";
}

function get_the_title($post = 0): string {
    return 'Sample Article Title';
}

function get_the_excerpt($post = null): string {
    return 'Sample excerpt';
}

function get_the_modified_date($format = '', $post = null): string {
    return '2026-01-02T12:00:00+00:00';
}

function get_the_date($format = '', $post = null): string {
    return '2026-01-01T12:00:00+00:00';
}

function get_the_author_meta($field = '', $user_id = null): string {
    if ($field === 'display_name') return 'Dr. Jane Smith';
    if ($field === 'description') return 'AI and SEO Researcher with 10 years experience.';
    return '';
}

function get_author_posts_url(int $author_id): string {
    return "http://example.com/author/{$author_id}/";
}

function has_post_thumbnail($post = null): bool {
    return false;
}

function get_the_post_thumbnail_url($post = null, $size = 'full'): string {
    return '';
}

function is_singular(): bool {
    return true;
}

function is_front_page(): bool {
    return false;
}

function is_home(): bool {
    return false;
}

function get_queried_object_id(): int {
    return 1;
}

function wp_strip_all_tags(string $text): string {
    return strip_tags($text);
}

function sanitize_text_field(string $str): string {
    return trim(strip_tags($str));
}

function wp_kses_post(string $str): string {
    return $str;
}

function esc_html(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_attr(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_url(string $url): string {
    return filter_var($url, FILTER_SANITIZE_URL) ?: '';
}

function esc_url_raw(string $url): string {
    return filter_var($url, FILTER_SANITIZE_URL) ?: '';
}

function wp_json_encode($data, int $options = 0, int $depth = 512) {
    return json_encode($data, $options, $depth);
}

function wp_create_nonce(string $action = ''): string {
    return md5('nonce_' . $action);
}

function wp_verify_nonce(string $nonce, string $action = ''): bool {
    return $nonce === md5('nonce_' . $action);
}

function current_user_can(string $capability): bool {
    return true;
}

function trailingslashit(string $string): string {
    return rtrim($string, '/\\') . '/';
}

function untrailingslashit(string $string): string {
    return rtrim($string, '/\\');
}

function get_the_ID(): int {
    return 1;
}

function sanitize_key(string $key): string {
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
}

function sanitize_textarea_field(string $str): string {
    return trim(strip_tags($str));
}

function current_time(string $type = 'mysql'): string {
    return date('Y-m-d H:i:s');
}

function get_locale(): string {
    return 'en_US';
}

function get_transient(string $key) {
    return $GLOBALS['mock_transients'][$key] ?? false;
}

function set_transient(string $key, $value, int $expiration = 0): bool {
    $GLOBALS['mock_transients'][$key] = $value;
    return true;
}

function delete_transient(string $key): bool {
    unset($GLOBALS['mock_transients'][$key]);
    return true;
}

function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void {}
function do_action(string $hook, ...$args): void {}
function apply_filters(string $hook, $value, ...$args) { return $value; }
function __(string $text, string $domain = 'default'): string { return $text; }
function esc_html__(string $text, string $domain = 'default'): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr__(string $text, string $domain = 'default'): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html_e(string $text, string $domain = 'default'): void { echo esc_html($text); }

// Autoloader for AnswerMesh classes
spl_autoload_register(function (string $class) {
    $prefix = 'AnswerMesh\\';
    if (!str_starts_with($class, $prefix)) return;

    $relative_class = substr($class, strlen($prefix));
    $file_slug = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '-$0', $relative_class));
    $filename = 'class-' . $file_slug . '.php';

    $paths = [
        ANSWERMESH_DIR . 'includes/' . $filename,
        ANSWERMESH_DIR . 'admin/' . $filename,
        ANSWERMESH_DIR . 'public/' . $filename,
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
