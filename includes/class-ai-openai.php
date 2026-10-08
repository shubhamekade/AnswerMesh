<?php
/**
 * OpenAI Provider Adapter.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class AIOpenAI implements AIProviderInterface
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private string $api_key;
    private string $default_model;

    public function __construct(string $api_key, string $default_model = 'gpt-4o-mini')
    {
        $this->api_key = $api_key;
        $this->default_model = $default_model ?: 'gpt-4o-mini';
    }

    /**
     * Generate content via OpenAI Chat Completions API.
     */
    public function generate(string $prompt, array $options = []): array
    {
        if (empty($this->api_key)) {
            return ['success' => false, 'content' => '', 'error' => __('OpenAI API key is missing.', 'answermesh')];
        }

        $model = $options['model'] ?? $this->default_model;
        $temperature = (float) ($options['temperature'] ?? 0.3);
        $max_tokens = (int) ($options['max_tokens'] ?? 1500);

        $body = [
            'model'       => $model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are an expert AI Search Visibility and SEO assistant for WordPress. Follow verified business facts strictly. Never hallucinate facts.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $max_tokens,
        ];

        $response = wp_remote_post(self::ENDPOINT, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => (string) wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'content' => '',
                'error'   => $response->get_error_message(),
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        $data = json_decode($raw_body, true);

        if ($status !== 200 || !isset($data['choices'][0]['message']['content'])) {
            $msg = $data['error']['message'] ?? sprintf(__('OpenAI API error (HTTP %d).', 'answermesh'), $status);
            return [
                'success' => false,
                'content' => '',
                'error'   => $msg,
            ];
        }

        return [
            'success' => true,
            'content' => trim((string) $data['choices'][0]['message']['content']),
            'model'   => $model,
        ];
    }

    /**
     * Analyze text with OpenAI.
     */
    public function analyze(string $text, array $context = []): array
    {
        $prompt = sprintf(
            "Analyze the following content for AI Search Visibility (GEO/AEO). Return JSON with keys: 'strengths', 'weaknesses', 'recommendations'.\n\nContent:\n%s",
            $text
        );

        $res = $this->generate($prompt, ['temperature' => 0.2]);
        if (!$res['success']) {
            return ['success' => false, 'result' => [], 'error' => $res['error'] ?? ''];
        }

        $decoded = json_decode($res['content'], true);
        return [
            'success' => true,
            'result'  => is_array($decoded) ? $decoded : ['raw' => $res['content']],
        ];
    }

    /**
     * Test connection credentials.
     */
    public function test_connection(): array
    {
        $res = $this->generate('Say "connection_ok".', ['max_tokens' => 10]);
        if ($res['success']) {
            return ['success' => true, 'message' => __('OpenAI connection successful.', 'answermesh')];
        }
        return ['success' => false, 'message' => $res['error'] ?? __('OpenAI connection failed.', 'answermesh')];
    }
}
