<?php
/**
 * Main plugin orchestrator container.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Plugin
{
    private static ?Plugin $instance = null;

    private Settings $settings;
    private Database $database;
    private ?RestApi $rest_api = null;
    private ?AdminMenu $admin_menu = null;
    private ?AdminAssets $admin_assets = null;

    /**
     * Get singleton instance.
     */
    public static function get_instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct()
    {
        $this->settings = Settings::get_instance();
        $this->database = new Database();
    }

    /**
     * Initialize plugin subsystems.
     */
    public function init(): void
    {
        // Check DB version upgrade on admin visits.
        if (is_admin()) {
            $this->check_database_version();
        }

        // Initialize SEO & metadata subsystem.
        $meta = new Meta();
        $meta->init();

        // Initialize frontend metadata output.
        $meta_output = new MetaOutput();
        $meta_output->init();

        // Initialize frontend schema output.
        $schema_output = new SchemaOutput();
        $schema_output->init();

        // Initialize sitemap provider.
        $sitemap = new Sitemap();
        $sitemap->init();

        // Initialize admin menus & assets if in admin area.
        if (is_admin()) {
            $this->admin_menu = new AdminMenu();
            $this->admin_menu->init();

            $this->admin_assets = new AdminAssets();
            $this->admin_assets->init();
        }

        // Initialize REST API.
        $this->rest_api = new RestApi();
        $this->rest_api->init();

        do_action('answermesh_loaded', $this);
    }

    /**
     * Check if database version needs upgrade.
     */
    private function check_database_version(): void
    {
        $current_version = get_option(Database::OPTION_NAME, '0.0.0');
        if (version_compare((string) $current_version, Database::DB_VERSION, '<')) {
            Database::create_tables();
        }
    }

    /**
     * Access settings instance.
     */
    public function get_settings(): Settings
    {
        return $this->settings;
    }

    /**
     * Access database instance.
     */
    public function get_database(): Database
    {
        return $this->database;
    }
}
