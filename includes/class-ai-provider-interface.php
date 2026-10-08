<?php
/**
 * AI Provider interface contract.
 *
 * @package AnswerMesh
 */

declare(strict_types=1);

namespace AnswerMesh;

defined('ABSPATH') || exit;

interface AIProviderInterface
{
    /**
     * Generate text from prompt.
     *
     * @param string $prompt Prompt string.
     * @param array<string, mixed> $options Provider options (model, temperature, max_tokens).
     * @return array{success: bool, content: string, error?: string, model?: string}
     */
    public function generate(string $prompt, array $options = []): array;

    /**
     * Analyze text content with structured context.
     *
     * @param string $text Content to analyze.
     * @param array<string, mixed> $context Verified business and entity context.
     * @return array{success: bool, result: array, error?: string}
     */
    public function analyze(string $text, array $context = []): array;

    /**
     * Test connection credentials.
     *
     * @return array{success: bool, message: string}
     */
    public function test_connection(): array;
}
