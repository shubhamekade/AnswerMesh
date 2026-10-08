<?php
/**
 * Plugin Name:       AnswerMesh
 * Plugin URI:        https://answermesh.com
 * Description:       AI Search Visibility, AEO, GEO, and Technical SEO optimization for WordPress.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            AnswerMesh Team
 * Author URI:        https://answermesh.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       answermesh
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

// Version constants.
define('ANSWERMESH_VERSION', '1.0.0');
define('ANSWERMESH_DB_VERSION', '1.0.0');
define('ANSWERMESH_FILE', __FILE__);
define('ANSWERMESH_DIR', plugin_dir_path(__FILE__));
define('ANSWERMESH_URL', plugin_dir_url(__FILE__));
define('ANSWERMESH_BASENAME', plugin_basename(__FILE__));

// PHP version verification.
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    add_action('admin_notices', function () {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('AnswerMesh requires PHP 8.1 or higher to run. Please update your PHP version.', 'answermesh')
        );
    });
    return;
}

// Autoloader.
spl_autoload_register(function (string $class) {
    $prefix = 'AnswerMesh\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts);

    // Format class file name: ClassName -> class-class-name.php
    $class_file = 'class-' . strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $class_name)) . '.php';

    $path_parts = array_map(function ($part) {
        return strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $part));
    }, $parts);

    $subdir = empty($path_parts) ? 'includes' : 'includes/' . implode('/', $path_parts);
    $full_path = ANSWERMESH_DIR . $subdir . '/' . $class_file;

    if (file_exists($full_path)) {
        require_once $full_path;
        return;
    }

    // Check admin directory fallback.
    $admin_path = ANSWERMESH_DIR . 'admin/' . $class_file;
    if (file_exists($admin_path)) {
        require_once $admin_path;
        return;
    }

    // Check public directory fallback.
    $public_path = ANSWERMESH_DIR . 'public/' . $class_file;
    if (file_exists($public_path)) {
        require_once $public_path;
    }
});

// Registration lifecycle hooks.
register_activation_hook(ANSWERMESH_FILE, [Installer::class, 'activate']);
register_deactivation_hook(ANSWERMESH_FILE, [Installer::class, 'deactivate']);

// Bootstrap container.
add_action('plugins_loaded', function () {
    Plugin::get_instance()->init();
});
