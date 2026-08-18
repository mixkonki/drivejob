<?php

namespace Drivejob\Services\AI;

use Exception;

/**
 * Πελάτης για το Anthropic API (Claude) — Πακέτο 4.
 *
 * Επιστρέφει απαντήσεις σε σχήμα συμβατό με το OpenAI
 * (choices[0].message.content + usage.prompt_tokens/completion_tokens)
 * ώστε ο υπάρχων κώδικας parsing και ο AIUsageGuard να δουλεύουν αναλλοίωτοι.
 *
 * Ρύθμιση: ANTHROPIC_API_KEY στο .env
 *          ai_provider = "anthropic" στον πίνακα ai_configuration
 *          anthropic_model (προαιρετικά, default: claude-haiku-4-5)
 */
class AnthropicClient
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';
    private int $timeout;

    public function __construct(int $timeout = 60)
    {
        $this->apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
        $this->timeout = $timeout;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Κλήση Messages API. Επιστρέφει OpenAI-συμβατό array.
     */
    public function chat(string $prompt, string $model, int $maxTokens = 2000): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('Anthropic API key is not configured (ANTHROPIC_API_KEY στο .env)');
        }

        $data = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_error($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception('Anthropic API Error: ' . $err);
        }
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode !== 200) {
            $msg = $decoded['error']['message'] ?? $response;
            throw new Exception("Anthropic API returned HTTP {$httpCode}: {$msg}");
        }

        $content = '';
        foreach (($decoded['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $content .= $block['text'];
            }
        }
        if ($content === '') {
            throw new Exception('Invalid Anthropic response format');
        }

        // Μετατροπή σε OpenAI-συμβατό σχήμα
        return [
            'choices' => [
                ['message' => ['content' => $content]],
            ],
            'usage' => [
                'prompt_tokens' => (int) ($decoded['usage']['input_tokens'] ?? 0),
                'completion_tokens' => (int) ($decoded['usage']['output_tokens'] ?? 0),
            ],
            'model' => $decoded['model'] ?? $model,
        ];
    }
}
