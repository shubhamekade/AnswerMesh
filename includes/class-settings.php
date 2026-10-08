<?php
/**
 * Settings manager with partitioned namespaces and automatic API key encryption.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class Settings
{
    public const OPTION_NAME = 'answermesh_settings';

    private static ?Settings $instance = null;
    private array $data = [];

    /**
     * Get singleton instance.
     */
    public static function get_instance(): Settings
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->load();
    }

    /**
     * Default options schema.
     */
    public static function get_defaults(): array
    {
        return [
            'general' => [
                'enable_plugin' => true,
                'enable_analysis' => true,
                'enable_schema' => true,
                'enable_sitemap' => true,
                'enable_og' => true,
                'delete_data_on_uninstall' => false,
            ],
            'ai' => [
                'provider' => 'none', // none, openai, gemini, anthropic, openrouter
                'api_key' => '',      // stored encrypted
                'model' => 'gpt-4o-mini',
                'language' => 'en',
                'temperature' => 0.3,
                'max_tokens' => 1500,
            ],
            'seo' => [
                'title_separator' => '-',
                'default_robots' => 'index, follow',
                'canonical_enabled' => true,
                'compatibility_mode' => 'auto', // auto, override, passthrough
            ],
            'schema' => [
                'site_type' => 'Organization',
                'business_name' => get_bloginfo('name'),
                'logo_url' => '',
                'social_profiles' => [],
            ],
            'brand_voice' => [
                'tone' => 'professional', // professional, friendly, technical, expert, conversational
                'custom_instructions' => '',
                'target_audience' => '',
            ],
            'privacy' => [
                'allow_ai_processing' => false,
                'allow_telemetry' => false,
            ],
        ];
    }

    /**
     * Load settings from database merged with defaults.
     */
    public function load(): void
    {
        $stored = get_option(self::OPTION_NAME, []);
        $defaults = self::get_defaults();

        $this->data = [];
        foreach ($defaults as $group => $fields) {
            $this->data[$group] = array_merge($fields, $stored[$group] ?? []);
        }
    }

    /**
     * Ensure defaults are stored in database.
     */
    public function ensure_defaults(): void
    {
        if (!get_option(self::OPTION_NAME)) {
            update_option(self::OPTION_NAME, self::get_defaults());
        }
    }

    /**
     * Retrieve a setting value.
     */
    public function get(string $group, ?string $key = null, mixed $default = null): mixed
    {
        if (!isset($this->data[$group])) {
            return $default;
        }

        if ($key === null) {
            return $this->data[$group];
        }

        return $this->data[$group][$key] ?? $default;
    }

    /**
     * Retrieve decrypted API key for internal server-side use.
     */
    public function get_decrypted_api_key(): string
    {
        $encrypted = $this->get('ai', 'api_key', '');
        if (empty($encrypted)) {
            return '';
        }
        return Encryption::decrypt((string) $encrypted);
    }

    /**
     * Set a setting value or entire group.
     */
    public function set(string $group, array|string $key_or_values, mixed $value = null): void
    {
        if (!isset($this->data[$group])) {
            $this->data[$group] = [];
        }

        if (is_array($key_or_values)) {
            foreach ($key_or_values as $k => $v) {
                $this->set_single($group, (string) $k, $v);
            }
        } else {
            $this->set_single($group, $key_or_values, $value);
        }
    }

    /**
     * Set a single field with sanitization and encryption rules.
     */
    private function set_single(string $group, string $key, mixed $value): void
    {
        // Special handling for API key: encrypt if changed and not already ciphertext.
        if ($group === 'ai' && $key === 'api_key') {
            $str_value = trim((string) $value);
            // Ignore masked string submissions.
            if (str_contains($str_value, '••••••••')) {
                return;
            }
            if ($str_value !== '') {
                $value = Encryption::encrypt($str_value);
            } else {
                $value = '';
            }
        }

        $this->data[$group][$key] = $value;
    }

    /**
     * Save settings to database.
     */
    public function save(): bool
    {
        // Sync delete_data_on_uninstall option.
        $delete_flag = !empty($this->data['general']['delete_data_on_uninstall']);
        update_option('answermesh_delete_data_on_uninstall', $delete_flag);

        return update_option(self::OPTION_NAME, $this->data);
    }

    /**
     * Return safe settings payload for admin UI (masks sensitive keys).
     */
    public function get_safe_payload(): array
    {
        $copy = $this->data;
        $raw_key = $this->get_decrypted_api_key();
        $copy['ai']['api_key'] = Encryption::mask_key($raw_key);
        $copy['ai']['has_api_key'] = !empty($raw_key);
        return $copy;
    }
}
