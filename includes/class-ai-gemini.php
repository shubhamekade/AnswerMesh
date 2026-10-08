<?php
/**
 * Google Gemini Provider Adapter.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

class AIGemini implements AIProviderInterface
{
    private string $api_key;
    private string $default_model;

    public function __construct(string $api_key, string $default_model = 'gemini-1.5-flash')
    {
        $this->api_key = $api_key;
        $this->default_model = $default_model ?: 'gemini-1.5-flash';
    }

    /**
     * Generate content via Google Gemini REST API.
     */
    public function generate(string $prompt, array $options = []): array
    {
        if (empty($this->api_key)) {
            return ['success' => false, 'content' => '', 'error' => __('Gemini API key is missing.', 'answermesh')];
        }

        $model = $options['model'] ?? $this->default_model;
        $temperature = (float) ($options['temperature'] ?? 0.3);
        $max_tokens = (int) ($options['max_tokens'] ?? 1500);

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($model),
            rawurlencode($this->api_key)
        );

        $body = [
            'contents'         => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => $max_tokens,
            ],
        ];

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
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

        if ($status !== 200 || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $msg = $data['error']['message'] ?? sprintf(__('Gemini API error (HTTP %d).', 'answermesh'), $status);
            return [
                'success' => false,
                'content' => '',
                'error'   => $msg,
            ];
        }

        return [
            'success' => true,
            'content' => trim((string) $data['candidates'][0]['content']['parts'][0]['text']),
            'model'   => $model,
        ];
    }

    /**
     * Analyze text with Gemini.
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
        $res = $this->generate('Respond with "ok".', ['max_tokens' => 10]);
        if ($res['success']) {
            return ['success' => true, 'message' => __('Google Gemini connection successful.', 'answermesh')];
        }
        return ['success' => false, 'message' => $res['error'] ?? __('Google Gemini connection failed.', 'answermesh')];
    }
}
