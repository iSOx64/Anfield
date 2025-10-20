<?php

declare(strict_types=1);

namespace App\Core;

class Recaptcha
{
    public static function verify(
        ?string $token,
        string $expectedAction,
        float $threshold = 0.5,
        ?string $remoteIp = null
    ): bool {
        $secret = Config::get('RECAPTCHA_SECRET_KEY');
        if (!$secret || !$token) {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($remoteIp) {
            $payload['remoteip'] = $remoteIp;
        }

        $body = http_build_query($payload);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                    . "Content-Length: " . strlen($body) . "\r\n",
                'content' => $body,
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify',
            false,
            $context
        );

        if ($response === false) {
            return false;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($response, true);
        if (($data['success'] ?? false) !== true) {
            return false;
        }

        if (!empty($data['action']) && $data['action'] !== $expectedAction) {
            return false;
        }

        $score = isset($data['score']) ? (float) $data['score'] : 0.0;
        return $score >= $threshold;
    }
}
