<?php
/**
 * Plugin activation and deactivation handler.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Installer
{
    /**
     * Run on plugin activation.
     */
    public static function activate(): void
    {
        // 1. Create/migrate custom database tables.
        Database::create_tables();

        // 2. Set default options if first activation.
        Settings::get_instance()->ensure_defaults();

        // 3. Mark activation timestamp.
        if (!get_option('answermesh_installed_at')) {
            update_option('answermesh_installed_at', current_time('mysql'));
        }

        // 4. Set rewrite flush transient if needed later.
        set_transient('answermesh_flush_rewrites', 1, 30);
    }

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate(): void
    {
        // Deactivation preserves tables and settings.
        // Clear transients.
        delete_transient('answermesh_flush_rewrites');
    }
}
