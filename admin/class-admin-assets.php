<?php
/**
 * Admin asset enqueuer and script localization.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class AdminAssets
{
    /**
     * Register hooks.
     */
    public function init(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    /**
     * Enqueue CSS/JS only on AnswerMesh screens.
     */
    public function enqueue_assets(string $hook): void
    {
        // Enqueue only on pages containing 'answermesh'.
        if (!str_contains($hook, 'answermesh')) {
            return;
        }

        $asset_file = ANSWERMESH_DIR . 'build/index.asset.php';

        if (file_exists($asset_file)) {
            $asset = require $asset_file;
            wp_enqueue_script(
                'answermesh-admin',
                ANSWERMESH_URL . 'build/index.js',
                $asset['dependencies'] ?? ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'],
                $asset['version'] ?? ANSWERMESH_VERSION,
                true
            );

            if (file_exists(ANSWERMESH_DIR . 'build/index.css')) {
                wp_enqueue_style(
                    'answermesh-admin-style',
                    ANSWERMESH_URL . 'build/index.css',
                    ['wp-components'],
                    $asset['version'] ?? ANSWERMESH_VERSION
                );
            }
        }

        // Localize configuration.
        wp_localize_script('answermesh-admin', 'answermeshSettings', [
            'root'        => esc_url_raw(rest_url('aiseo/v1/')),
            'nonce'       => wp_create_nonce('wp_rest'),
            'version'     => ANSWERMESH_VERSION,
            'currentHook' => $hook,
        ]);
    }

    /**
     * Enqueue block editor sidebar script for Gutenberg screens.
     */
    public function enqueue_editor_assets(): void
    {
        $gutenberg_asset = ANSWERMESH_DIR . 'build/gutenberg.asset.php';
        $deps = ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks', 'wp-i18n', 'wp-api-fetch'];
        $version = ANSWERMESH_VERSION;

        if (file_exists($gutenberg_asset)) {
            $asset = require $gutenberg_asset;
            $deps = $asset['dependencies'] ?? $deps;
            $version = $asset['version'] ?? $version;
        }

        if (file_exists(ANSWERMESH_DIR . 'build/gutenberg.js')) {
            wp_enqueue_script(
                'answermesh-gutenberg-sidebar',
                ANSWERMESH_URL . 'build/gutenberg.js',
                $deps,
                $version,
                true
            );

            if (file_exists(ANSWERMESH_DIR . 'build/index.css')) {
                wp_enqueue_style(
                    'answermesh-gutenberg-style',
                    ANSWERMESH_URL . 'build/index.css',
                    ['wp-components', 'wp-edit-post'],
                    $version
                );
            }

            wp_localize_script('answermesh-gutenberg-sidebar', 'answermeshSettings', [
                'root'    => esc_url_raw(rest_url('aiseo/v1/')),
                'nonce'   => wp_create_nonce('wp_rest'),
                'version' => ANSWERMESH_VERSION,
            ]);
        }
    }
}
