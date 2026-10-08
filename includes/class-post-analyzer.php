<?php
/**
 * Post content parser and semantic structure analyzer.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class PostAnalyzer
{
    /**
     * Analyze post content and semantic structure.
     *
     * @param int $post_id Post ID.
     * @return array<string, mixed>
     */
    public static function analyze_post(int $post_id): array
    {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $raw_html = (string) apply_filters('the_content', $post->post_content);
        $clean_text = wp_strip_all_tags($raw_html);

        // Word count.
        $word_count = str_word_count($clean_text);
        $read_time_minutes = (int) ceil($word_count / 200);

        // Heading hierarchy extraction.
        $headings = self::extract_headings($raw_html);
        $heading_audit = self::audit_headings($headings, $post->post_title);

        // Images extraction.
        $images = self::extract_images($raw_html);

        // Links extraction.
        $links = self::extract_links($raw_html);

        // Paragraph readability.
        $paragraphs = self::audit_paragraphs($raw_html);

        return [
            'post_id'           => $post_id,
            'word_count'        => $word_count,
            'read_time_minutes' => $read_time_minutes,
            'thin_content'      => ($word_count < 300),
            'headings'          => $headings,
            'heading_audit'     => $heading_audit,
            'images'            => $images,
            'links'             => $links,
            'paragraphs'        => $paragraphs,
            'analyzed_at'       => current_time('mysql'),
        ];
    }

    /**
     * Extract all headings H1–H6 with level and text.
     *
     * @return array<int, array{level: int, tag: string, text: string}>
     */
    public static function extract_headings(string $html): array
    {
        $headings = [];
        preg_match_all('/<(h[1-6])[^>]*>(.*?)<\/\1>/is', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            $level = (int) substr($tag, 1);
            $text = trim(wp_strip_all_tags($match[2]));

            if ($text !== '') {
                $headings[] = [
                    'level' => $level,
                    'tag'   => $tag,
                    'text'  => $text,
                ];
            }
        }

        return $headings;
    }

    /**
     * Audit heading hierarchy for SEO & GEO compliance.
     *
     * @param array<int, array{level: int, tag: string, text: string}> $headings
     * @return array{h1_count: int, has_single_h1: bool, level_skips: array<int, string>, issues: array<int, string>}
     */
    public static function audit_headings(array $headings, string $post_title = ''): array
    {
        $h1_count = 0;
        $level_skips = [];
        $issues = [];
        $prev_level = 1; // Content assumed to start under page H1.

        foreach ($headings as $h) {
            if ($h['level'] === 1) {
                $h1_count++;
            }

            // Detect heading level skips (e.g. H2 -> H4).
            if ($h['level'] > $prev_level + 1 && $prev_level > 0) {
                $level_skips[] = sprintf('H%d to H%d', $prev_level, $h['level']);
                $issues[] = sprintf(
                    /* translators: 1: Previous level, 2: Current level */
                    __('Heading level skipped from H%1$d to H%2$d without intermediate sub-heading.', 'answermesh'),
                    $prev_level,
                    $h['level']
                );
            }
            $prev_level = $h['level'];
        }

        // Standard post template provides page title H1; body should have 0 or 1 H1.
        if ($h1_count > 1) {
            $issues[] = __('Multiple H1 tags detected in post body. Only one primary H1 should exist per page.', 'answermesh');
        }

        return [
            'h1_count'      => $h1_count,
            'has_single_h1' => ($h1_count <= 1),
            'level_skips'   => $level_skips,
            'issues'        => $issues,
        ];
    }

    /**
     * Extract images and assess alt text coverage.
     *
     * @return array{total_images: int, missing_alt: int, alt_coverage_percent: int, items: array}
     */
    public static function extract_images(string $html): array
    {
        $items = [];
        $missing_alt = 0;

        preg_match_all('/<img\s+[^>]*>/is', $html, $matches);

        foreach ($matches[0] as $img_tag) {
            $src = '';
            $alt = '';

            if (preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $img_tag, $src_m)) {
                $src = $src_m[1];
            }
            if (preg_match('/alt=[\'"]([^\'"]*)[\'"]/i', $img_tag, $alt_m)) {
                $alt = trim($alt_m[1]);
            }

            $has_alt = ($alt !== '');
            if (!$has_alt) {
                $missing_alt++;
            }

            $items[] = [
                'src'     => $src,
                'alt'     => $alt,
                'has_alt' => $has_alt,
            ];
        }

        $total = count($items);
        $coverage = $total > 0 ? (int) round((($total - $missing_alt) / $total) * 100) : 100;

        return [
            'total_images'         => $total,
            'missing_alt'          => $missing_alt,
            'alt_coverage_percent' => $coverage,
            'items'                => $items,
        ];
    }

    /**
     * Extract links separated into internal and external.
     *
     * @return array{total_links: int, internal_count: int, external_count: int, internal: array, external: array}
     */
    public static function extract_links(string $html): array
    {
        $internal = [];
        $external = [];
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

        preg_match_all('/<a\s+[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $href = trim($match[1]);
            $text = trim(wp_strip_all_tags($match[2]));

            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            $link_host = wp_parse_url($href, PHP_URL_HOST);

            $entry = [
                'href'   => $href,
                'anchor' => $text,
            ];

            if (empty($link_host) || $link_host === $site_host) {
                $internal[] = $entry;
            } else {
                $external[] = $entry;
            }
        }

        return [
            'total_links'    => count($internal) + count($external),
            'internal_count' => count($internal),
            'external_count' => count($external),
            'internal'       => $internal,
            'external'       => $external,
        ];
    }

    /**
     * Audit paragraph lengths for readability.
     *
     * @return array{total_paragraphs: int, long_paragraphs_count: int, issues: array<int, string>}
     */
    public static function audit_paragraphs(string $html): array
    {
        $long_count = 0;
        $issues = [];

        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $matches);

        foreach ($matches[1] as $p) {
            $words = str_word_count(wp_strip_all_tags($p));
            if ($words > 120) {
                $long_count++;
            }
        }

        if ($long_count > 0) {
            $issues[] = sprintf(
                /* translators: %d: count of long paragraphs */
                __('%d paragraphs exceed 120 words. Consider breaking them into shorter answer blocks.', 'answermesh'),
                $long_count
            );
        }

        return [
            'total_paragraphs'      => count($matches[1]),
            'long_paragraphs_count' => $long_count,
            'issues'                => $issues,
        ];
    }
}
