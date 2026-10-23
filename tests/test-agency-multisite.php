<?php
/**
 * Test Suite: MultiSite Management & Agency White-Label Hub
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\MultiSiteManager;
use AnswerMesh\WhiteLabel;
use AnswerMesh\AgencyReport;

class TestAgencyMultiSite
{
    /**
     * Run all Agency & MultiSite suite assertions.
     *
     * @return array<string, mixed>
     */
    public static function run(): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'errors' => []];

        $assert = function (bool $condition, string $msg) use (&$results) {
            if ($condition) {
                $results['passed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $msg;
            }
        };

        WhiteLabel::init();

        // 1. MultiSite Manager Defaults
        $assert(is_bool(MultiSiteManager::is_multisite()), 'is_multisite should return boolean');
        $net_settings = MultiSiteManager::get_network_settings();
        $assert(is_array($net_settings), 'get_network_settings should return an array');
        $assert(isset($net_settings['allow_site_overrides']), 'network settings must contain allow_site_overrides');
        $assert(MultiSiteManager::allows_site_overrides() === true, 'default allows_site_overrides must be true');

        $eff_val = MultiSiteManager::get_effective_setting('general', 'force_schema', true);
        $assert($eff_val === true, 'get_effective_setting should resolve default or fallback value');

        // 2. White-Label Branding Default Configuration
        $default_config = WhiteLabel::get_config();
        $assert(is_array($default_config), 'get_config should return array');
        $assert($default_config['enabled'] === false, 'white label default must be disabled');
        $assert(WhiteLabel::get_plugin_name() === 'AnswerMesh', 'default plugin name must be AnswerMesh');
        $assert(WhiteLabel::is_credits_hidden() === false, 'default branding credits must be visible');

        // 3. Save White-Label Rebranding
        $custom_config = [
            'enabled'               => true,
            'plugin_name'           => 'Apex AEO Suite',
            'support_url'           => 'https://agency.example.com/support',
            'agency_name'           => 'Apex Digital Agency',
            'agency_logo_url'       => 'https://agency.example.com/logo.png',
            'hide_branding_credits' => true,
        ];
        $saved = WhiteLabel::save_config($custom_config);
        $assert($saved === true, 'WhiteLabel::save_config should return true');

        // 4. Verify Active Rebranding Overrides
        $assert(WhiteLabel::get_plugin_name() === 'Apex AEO Suite', 'get_plugin_name should return custom name');
        $assert(WhiteLabel::get_support_url() === 'https://agency.example.com/support', 'get_support_url should return custom support URL');
        $assert(WhiteLabel::is_credits_hidden() === true, 'is_credits_hidden should return true when enabled');

        $branding = WhiteLabel::get_branding();
        $assert($branding['is_white_labeled'] === true, 'get_branding is_white_labeled must be true');
        $assert($branding['agency_name'] === 'Apex Digital Agency', 'agency_name must match saved configuration');

        $filtered_name = apply_filters('answermesh_brand_name', 'AnswerMesh');
        $assert($filtered_name === 'Apex AEO Suite', 'answermesh_brand_name filter must reflect white-label name');

        // 5. Agency Client Report Generation (REQ-M4-06)
        $report_data = AgencyReport::compile_report_data();
        $assert(is_array($report_data), 'compile_report_data must return an array');
        $assert(isset($report_data['meta'], $report_data['executive_scores']), 'report must contain meta and executive scores');
        $assert($report_data['meta']['agency_name'] === 'Apex Digital Agency', 'report meta should reflect agency name');

        // Check grades
        $assert(AgencyReport::score_to_grade(95) === 'A+', 'Score 95 should yield A+');
        $assert(AgencyReport::score_to_grade(85) === 'A', 'Score 85 should yield A');
        $assert(AgencyReport::score_to_grade(75) === 'B', 'Score 75 should yield B');
        $assert(AgencyReport::score_to_grade(65) === 'C', 'Score 65 should yield C');
        $assert(AgencyReport::score_to_grade(45) === 'F', 'Score 45 should yield F');

        $scores = $report_data['executive_scores'];
        $assert(isset($scores['overall']['grade']), 'overall score must have a grade');
        $assert(isset($scores['aeo']['grade']), 'aeo score must have a grade');
        $assert(isset($scores['geo']['grade']), 'geo score must have a grade');

        // 6. Printable HTML Output
        $printable_html = AgencyReport::render_printable_html($report_data);
        $assert(!empty($printable_html), 'render_printable_html must return HTML string');
        $assert(str_contains($printable_html, '<!DOCTYPE html>'), 'Printable HTML must be valid document');
        $assert(str_contains($printable_html, 'Apex Digital Agency'), 'Printable report must feature agency branding');
        $assert(str_contains($printable_html, 'Executive Scores') || str_contains($printable_html, 'Overall Visibility'), 'Printable report must display visibility scores');
        $assert(str_contains($printable_html, 'Observed AI Search Visibility'), 'Printable report must contain AI search section');

        return $results;
    }
}
