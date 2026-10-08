<?php
/**
 * AI Provider Factory and Execution Coordinator.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class AI
{
    private static ?AIProviderInterface $cached_provider = null;

    /**
     * Resolve active AI provider instance based on settings.
     */
    public static function get_active_provider(): ?AIProviderInterface
    {
        if (self::$cached_provider !== null) {
            return self::$cached_provider;
        }

        $settings = Settings::get_instance();
        $provider_name = (string) $settings->get('ai', 'provider', 'none');
        $api_key = $settings->get_decrypted_api_key();
        $model = (string) $settings->get('ai', 'model', '');

        if ($provider_name === 'none' || empty($api_key)) {
            return null;
        }

        switch ($provider_name) {
            case 'openai':
                self::$cached_provider = new AIOpenAI($api_key, $model ?: 'gpt-4o-mini');
                break;
            case 'gemini':
                self::$cached_provider = new AIGemini($api_key, $model ?: 'gemini-1.5-flash');
                break;
            default:
                self::$cached_provider = null;
                break;
        }

        return self::$cached_provider;
    }

    /**
     * Check if AI features are configured and ready.
     */
    public static function is_ai_enabled(): bool
    {
        $settings = Settings::get_instance();
        $provider = $settings->get('ai', 'provider', 'none');
        $has_key = !empty($settings->get_decrypted_api_key());

        return ($provider !== 'none' && $has_key);
    }

    /**
     * Test connection for current settings without saving.
     */
    public static function test_connection(string $provider, string $api_key, string $model = ''): array
    {
        if (empty($api_key)) {
            return ['success' => false, 'message' => __('API key is required to test connection.', 'answermesh')];
        }

        switch ($provider) {
            case 'openai':
                $adapter = new AIOpenAI($api_key, $model ?: 'gpt-4o-mini');
                return $adapter->test_connection();
            case 'gemini':
                $adapter = new AIGemini($api_key, $model ?: 'gemini-1.5-flash');
                return $adapter->test_connection();
            default:
                return ['success' => false, 'message' => sprintf(__('Unsupported provider "%s".', 'answermesh'), $provider)];
        }
    }
}
