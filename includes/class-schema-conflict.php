<?php
/**
 * Schema conflict and duplication detector.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class SchemaConflict
{
    /**
     * Inspect HTML content for duplicate or conflicting JSON-LD blocks.
     *
     * @param string $html Rendered page HTML.
     * @return array<int, array{type: string, message: string, severity: string}>
     */
    public static function detect_conflicts(string $html): array
    {
        $issues = [];

        // Extract all ld+json blocks.
        preg_match_all('/<script\s+type=[\'"]application\/ld\+json[\'"][^>]*>(.*?)<\/script>/is', $html, $matches);

        if (empty($matches[1])) {
            return $issues;
        }

        $all_types = [];
        $organization_count = 0;
        $seen_names = [];

        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);
            if (!is_array($data)) {
                $issues[] = [
                    'type'     => 'malformed_json',
                    'message'  => __('Malformed JSON-LD detected on page.', 'answermesh'),
                    'severity' => 'critical',
                ];
                continue;
            }

            $entities = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : [$data];

            foreach ($entities as $entity) {
                if (!is_array($entity) || empty($entity['@type'])) {
                    continue;
                }

                $type = (string) $entity['@type'];
                $all_types[] = $type;

                if (in_array($type, ['Organization', 'LocalBusiness'], true)) {
                    $organization_count++;
                    if (!empty($entity['name'])) {
                        $seen_names[] = (string) $entity['name'];
                    }
                }
            }
        }

        // Flag multiple Organization blocks.
        if ($organization_count > 1) {
            $unique_names = array_unique($seen_names);
            if (count($unique_names) > 1) {
                $issues[] = [
                    'type'     => 'conflicting_organization_entities',
                    'message'  => sprintf(
                        /* translators: %s: Comma-separated organization names */
                        __('Multiple conflicting Organization entities detected: %s.', 'answermesh'),
                        implode(', ', $unique_names)
                    ),
                    'severity' => 'critical',
                ];
            } else {
                $issues[] = [
                    'type'     => 'duplicate_organization_schema',
                    'message'  => __('Multiple duplicate Organization schema declarations detected.', 'answermesh'),
                    'severity' => 'high',
                ];
            }
        }

        return $issues;
    }
}
