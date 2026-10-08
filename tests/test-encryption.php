<?php
/**
 * Test Suite: Encryption & Key Security
 */

declare(strict_types=1);

namespace AnswerMesh\Tests;

use AnswerMesh\Encryption;

class TestEncryption {
    public static function run(): array {
        $results = ['passed' => 0, 'failed' => 0, 'errors' => []];

        $assert = function (bool $condition, string $msg) use (&$results) {
            if ($condition) {
                $results['passed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $msg;
            }
        };

        // Test 1: Encrypt and decrypt roundtrip
        $secret = 'sk-proj-super-secret-api-key-1234567890';
        $encrypted = Encryption::encrypt($secret);
        $assert(!empty($encrypted), 'Encrypted string should not be empty');
        $assert($encrypted !== $secret, 'Encrypted string must not match plaintext');

        $decrypted = Encryption::decrypt($encrypted);
        $assert($decrypted === $secret, 'Decrypted string must match original plaintext exactly');

        // Test 2: Decrypting empty or invalid payload
        $emptyDecrypt = Encryption::decrypt('');
        $assert($emptyDecrypt === '', 'Empty string decryption should return empty');

        $invalidDecrypt = Encryption::decrypt('invalid:payload:format');
        $assert($invalidDecrypt === '', 'Corrupt payload decryption should safely return empty');

        // Test 3: Key masking
        $masked = Encryption::mask_key($secret);
        $assert(str_starts_with($masked, 'sk-'), 'Masked key must preserve beginning 3 characters');
        $assert(str_ends_with($masked, '7890'), 'Masked key must preserve ending 4 characters');
        $assert(str_contains($masked, '••••'), 'Masked key must contain masking bullets');
        $assert(!str_contains($masked, 'super-secret'), 'Masked key must never contain full secret');

        return $results;
    }
}
