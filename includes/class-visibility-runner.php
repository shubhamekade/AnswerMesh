<?php
/**
 * AI Search Visibility query sampling runner, parser, and index engine.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class VisibilityRunner
{
    /**
     * Supported generative search platforms.
     */
    public const PLATFORMS = [
        'chatgpt'    => 'ChatGPT Search',
        'gemini'     => 'Google Gemini',
        'perplexity' => 'Perplexity AI',
        'copilot'    => 'Microsoft Copilot',
    ];

    /**
     * Run a scheduled query sampling batch (WP-Cron or CLI).
     *
     * @param int $batch_size Maximum queries to sample.
     * @return array<string, mixed>
     */
    public static function run_scheduled_sample(int $batch_size = 5): array
    {
        // Check rate limiting / daily budget cap (NFR-M3-01)
        $today = gmdate('Y-m-d');
        $cap_key = '_aiseo_vis_daily_' . $today;
        $daily_checks = (int) get_transient($cap_key);
        $daily_limit = 50; // Cap at 50 queries/day by default

        if ($daily_checks >= $daily_limit) {
            return [
                'success' => false,
                'message' => 'Daily query sampling cap reached (' . $daily_limit . ').',
                'sampled' => 0,
            ];
        }

        $queries = self::get_candidate_queries($batch_size);
        if (empty($queries)) {
            return [
                'success' => true,
                'message' => 'No candidate queries found to sample.',
                'sampled' => 0,
            ];
        }

        $platforms = array_keys(self::PLATFORMS);
        $total_recorded = 0;
        $results = [];

        foreach ($queries as $query) {
            // Pick a platform or sample across all
            $platform = $platforms[array_rand($platforms)];
            $obs = self::sample_query($query, $platform);
            $results[] = $obs;
            $total_recorded++;
            $daily_checks++;
        }

        set_transient($cap_key, $daily_checks, DAY_IN_SECONDS);

        return [
            'success'        => true,
            'sampled_count'  => $total_recorded,
            'daily_total'    => $daily_checks,
            'results'        => $results,
        ];
    }

    /**
     * Sample a specific query against one or all platforms.
     *
     * @param string $query Target search query.
     * @param string $platform 'all' or specific platform key.
     * @return array<string, mixed>
     */
    public static function sample_query(string $query, string $platform = 'all'): array
    {
        $platforms_to_test = ($platform === 'all')
            ? array_keys(self::PLATFORMS)
            : [sanitize_key($platform)];

        $records = [];

        foreach ($platforms_to_test as $plat) {
            $response_text = self::fetch_or_simulate_response($query, $plat);
            $parsed = self::parse_response($response_text, $query, $plat);

            $id = Visibility::record_observation([
                'query'            => $query,
                'platform'         => $plat,
                'observed'         => $parsed['observed'] ? 1 : 0,
                'mention'          => $parsed['mention'] ? 1 : 0,
                'position_context' => $parsed['position_context'],
                'source_url'       => $parsed['source_url'],
                'competitors_json' => $parsed['competitors'],
                'captured_at'      => current_time('mysql'),
            ]);

            $parsed['id'] = $id;
            $records[$plat] = $parsed;
        }

        return [
            'query'     => $query,
            'platforms' => $records,
        ];
    }

    /**
     * Parse generative search response text for citations, mentions, and competitors.
     *
     * @param string $text Raw or simulated response text.
     * @param string $query The query that produced the response.
     * @param string $platform Platform key.
     * @return array<string, mixed>
     */
    public static function parse_response(string $text, string $query, string $platform): array
    {
        $site_url = home_url();
        $site_domain = strtolower((string) wp_parse_url($site_url, PHP_URL_HOST));
        $site_name = strtolower((string) get_bloginfo('name'));

        $lowered_text = strtolower($text);

        // 1. Citation check: exact URL or domain match
        $observed = false;
        $source_url = '';
        if (!empty($site_domain) && str_contains($lowered_text, $site_domain)) {
            $observed = true;
            // Extract the matching URL from text if possible
            if (preg_match('/https?:\/\/[^\s\)\>\"\'\]]+' . preg_quote($site_domain, '/') . '[^\s\)\>\"\'\]]*/i', $text, $url_match)) {
                $source_url = $url_match[0];
            } else {
                $source_url = $site_url;
            }
        }

        // 2. Brand mention check
        $mention = false;
        if (!empty($site_name) && str_contains($lowered_text, $site_name)) {
            $mention = true;
            $observed = true;
        }

        // 3. Position context snippet
        $position_context = '';
        $match_term = $site_domain ?: $site_name;
        if (!empty($match_term) && ($pos = stripos($text, $match_term)) !== false) {
            $start = max(0, $pos - 80);
            $snippet = substr($text, $start, 180);
            $position_context = '...' . trim($snippet) . '...';
        } elseif (!empty($text)) {
            $position_context = substr($text, 0, 150) . '...';
        }

        // 4. Competitor domain discovery
        $competitors = [];
        $ignored_domains = [
            'wikipedia.org', 'google.com', 'youtube.com', 'twitter.com',
            'x.com', 'facebook.com', 'linkedin.com', 'instagram.com',
            'reddit.com', 'github.com', 'apple.com', 'microsoft.com',
            'cloudflare.com', 'wordpress.org', 'medium.com'
        ];

        if (preg_match_all('/https?:\/\/(?:www\.)?([a-zA-Z0-9-]+\.[a-zA-Z]{2,})[^\s\)\>\"\'\]]*/i', $text, $all_urls)) {
            $domains = array_unique($all_urls[1]);
            foreach ($domains as $domain) {
                $lower_domain = strtolower($domain);
                if ($lower_domain !== $site_domain && !in_array($lower_domain, $ignored_domains, true)) {
                    $competitors[] = [
                        'domain' => $lower_domain,
                        'name'   => ucfirst(explode('.', $lower_domain)[0]),
                    ];
                }
            }
        }

        return [
            'query'            => $query,
            'platform'         => $platform,
            'observed'         => $observed,
            'mention'          => $mention,
            'position_context' => $position_context,
            'source_url'       => $source_url,
            'competitors'      => array_slice($competitors, 0, 5),
        ];
    }

    /**
     * Compute composite AI Visibility Index (0–100) and historical trend.
     *
     * @param int $days Time window in days.
     * @return array<string, mixed>
     */
    public static function calculate_visibility_index(int $days = 30): array
    {
        $summary = Visibility::get_summary($days);

        $observed_rate = $summary['observed_rate'] ?? 0.0;
        $citation_rate = $summary['citation_rate'] ?? 0.0;
        $mention_rate = $summary['mention_rate'] ?? 0.0;

        // Weights: Platform Presence (40%), Direct Citations (40%), Mentions (20%)
        $score = ($observed_rate * 0.40) + ($citation_rate * 0.40) + ($mention_rate * 0.20);
        $score = (int) round(min(100, max(0, $score)));

        $grade = 'Nascent';
        if ($score >= 80) {
            $grade = 'Dominant';
        } elseif ($score >= 60) {
            $grade = 'Strong';
        } elseif ($score >= 40) {
            $grade = 'Moderate';
        } elseif ($score >= 20) {
            $grade = 'Developing';
        }

        return [
            'score'            => $score,
            'grade'            => $grade,
            'time_window_days' => $days,
            'observed_rate'    => $observed_rate,
            'citation_rate'    => $citation_rate,
            'mention_rate'     => $mention_rate,
            'total_checks'     => $summary['total_checks'] ?? 0,
            'platforms'        => $summary['platforms'] ?? [],
        ];
    }

    /**
     * Get top observed competitor domains and citation counts.
     *
     * @param int $limit Number of top competitors.
     * @param int $days Time window.
     * @return array<int, array<string, mixed>>
     */
    public static function get_top_competitors(int $limit = 10, int $days = 30): array
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * 86400));
        $observations = Visibility::get_observations([
            'from_date' => $cutoff,
            'limit'     => 200,
        ]);

        $competitor_counts = [];

        foreach ($observations as $obs) {
            if (!empty($obs['competitors']) && is_array($obs['competitors'])) {
                foreach ($obs['competitors'] as $comp) {
                    $domain = $comp['domain'] ?? '';
                    if (!empty($domain)) {
                        if (!isset($competitor_counts[$domain])) {
                            $competitor_counts[$domain] = [
                                'domain' => $domain,
                                'name'   => $comp['name'] ?? ucfirst(explode('.', $domain)[0]),
                                'count'  => 0,
                            ];
                        }
                        $competitor_counts[$domain]['count']++;
                    }
                }
            }
        }

        usort($competitor_counts, fn($a, $b) => $b['count'] <=> $a['count']);

        $total_mentions = array_sum(array_column($competitor_counts, 'count'));
        $top = array_slice($competitor_counts, 0, $limit);

        foreach ($top as &$item) {
            $item['share_percent'] = $total_mentions > 0 
                ? round(($item['count'] / $total_mentions) * 100, 1) 
                : 0.0;
        }

        return $top;
    }

    /**
     * Extract candidate focus queries from questions and postmeta.
     *
     * @param int $limit
     * @return array<int, string>
     */
    public static function get_candidate_queries(int $limit = 5): array
    {
        global $wpdb;

        $queries = [];

        // 1. Check questions table
        $table_questions = $wpdb->prefix . 'aiseo_questions';
        $q_rows = $wpdb->get_col(
            "SELECT question FROM {$table_questions} WHERE question != '' ORDER BY id DESC LIMIT 10"
        );
        if (is_array($q_rows)) {
            $queries = array_merge($queries, $q_rows);
        }

        // 2. Check postmeta focus topics
        $meta_rows = $wpdb->get_col(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_aiseo_focus_topic' AND meta_value != '' LIMIT 10"
        );
        if (is_array($meta_rows)) {
            $queries = array_merge($queries, $meta_rows);
        }

        // 3. Fallback to site title / tagline
        if (empty($queries)) {
            $site_name = get_bloginfo('name');
            $tagline = get_bloginfo('description');
            if (!empty($site_name)) {
                $queries[] = 'Best solutions for ' . $site_name;
            }
            if (!empty($tagline)) {
                $queries[] = $tagline;
            }
        }

        $queries = array_values(array_unique(array_filter($queries)));
        shuffle($queries);

        return array_slice($queries, 0, $limit);
    }

    /**
     * Fetch live response from AI provider or deterministic fallback simulation.
     *
     * @param string $query
     * @param string $platform
     * @return string
     */
    protected static function fetch_or_simulate_response(string $query, string $platform): string
    {
        $site_name = get_bloginfo('name') ?: 'Our Website';
        $site_url = home_url();
        $site_domain = (string) wp_parse_url($site_url, PHP_URL_HOST);

        // Check if live AI connection available
        $ai = new AI();
        if ($ai->is_available()) {
            $prompt = "You are simulating a search engine answer on {$platform}. For the query '{$query}', provide a 3-sentence informational summary. Mention 2 top industry authorities with URLs and, if genuinely relevant to the query, include {$site_name} ({$site_url}).";
            $result = $ai->generate($prompt, ['temperature' => 0.4]);
            if ($result['success'] && !empty($result['text'])) {
                return (string) $result['text'];
            }
        }

        // Deterministic simulation based on site content & query hash (NFR-M3-02)
        $hash = crc32($query . $platform);
        $is_observed = ($hash % 3 === 0); // ~33% simulated observation rate

        $simulated_competitors = [
            'https://techradar.com/best-guide',
            'https://hubspot.com/resources/article',
            'https://forbes.com/advisor/insights',
            'https://pcmag.com/picks/top-solutions',
        ];
        $comp1 = $simulated_competitors[$hash % count($simulated_competitors)];
        $comp2 = $simulated_competitors[($hash + 1) % count($simulated_competitors)];

        if ($is_observed) {
            return "According to leading sources including {$site_name} ({$site_url}/guide), {$query} represents a key strategy. Leading industry analyses from {$comp1} and {$comp2} confirm that structured implementation improves reliability.";
        }

        return "When evaluating {$query}, key recommendations are highlighted by {$comp1} and {$comp2}. Comprehensive benchmarks suggest focusing on clarity, domain authority, and structured data standards.";
    }
}
