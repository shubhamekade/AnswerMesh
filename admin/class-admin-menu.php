<?php
/**
 * Admin menu registration and page view handlers.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class AdminMenu
{
    public const MENU_SLUG = 'answermesh';

    /**
     * Register hooks.
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu_pages']);
    }

    /**
     * Add admin menu entries.
     */
    public function register_menu_pages(): void
    {
        $capability = 'manage_options';

        // Main Menu: Dashboard.
        add_menu_page(
            __('AnswerMesh — AI Search Visibility', 'answermesh'),
            __('AnswerMesh', 'answermesh'),
            $capability,
            self::MENU_SLUG,
            [$this, 'render_app_container'],
            'dashicons-networking',
            30
        );

        // Submenu: Dashboard (renaming default first submenu).
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard — AnswerMesh', 'answermesh'),
            __('Dashboard', 'answermesh'),
            $capability,
            self::MENU_SLUG,
            [$this, 'render_app_container']
        );

        // Submenu: Business Knowledge.
        add_submenu_page(
            self::MENU_SLUG,
            __('Business Knowledge Base — AnswerMesh', 'answermesh'),
            __('Knowledge Base', 'answermesh'),
            $capability,
            self::MENU_SLUG . '-knowledge',
            [$this, 'render_app_container']
        );

        // Submenu: Page Inventory & Analysis.
        add_submenu_page(
            self::MENU_SLUG,
            __('Page Analysis — AnswerMesh', 'answermesh'),
            __('Page Analysis', 'answermesh'),
            $capability,
            self::MENU_SLUG . '-pages',
            [$this, 'render_app_container']
        );

        // Submenu: Settings.
        add_submenu_page(
            self::MENU_SLUG,
            __('Settings — AnswerMesh', 'answermesh'),
            __('Settings', 'answermesh'),
            $capability,
            self::MENU_SLUG . '-settings',
            [$this, 'render_app_container']
        );
    }

    /**
     * Render common mount container for React admin app.
     */
    public function render_app_container(): void
    {
        ?>
        <div class="wrap answermesh-admin-wrap">
            <div id="answermesh-admin-root">
                <div class="answermesh-loading-state" style="padding: 40px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    <h2><?php esc_html_e('Loading AnswerMesh...', 'answermesh'); ?></h2>
                    <p style="color: #646970;"><?php esc_html_e('Initializing AI Search Visibility workspace.', 'answermesh'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
