<?php

namespace App\Services;

class AuditSanitizer
{
    /**
     * Recursively sanitize data, redacting all passwords, API keys, tokens, and secrets.
     */
    public function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                if ($this->isSensitiveKey((string) $key)) {
                    $cleaned[$key] = '[REDACTED]';
                } else {
                    $cleaned[$key] = $this->sanitize($value);
                }
            }

            return $cleaned;
        }

        if (is_string($data)) {
            return $this->sanitizeString($data);
        }

        return $data;
    }

    /**
     * Scrub embedded secrets, tokens, and keys from raw text using regex patterns.
     */
    public function sanitizeString(string $text): string
    {
        $patterns = config('privacy.string_patterns', [
            '/bot(\d{5,}:[A-Za-z0-9_-]{20,})/i' => 'bot[REDACTED_TELEGRAM_TOKEN]',
            '/(sk|rk)_(live|test)_[0-9a-zA-Z]{16,}/' => '$1_$2_[REDACTED_STRIPE_KEY]',
            '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i' => 'Bearer [REDACTED_BEARER_TOKEN]',
            '/([?&](?:key|api_key|secret|secret_key|token|bot_token|password)=)[^&\s]+/i' => '$1[REDACTED]',
        ]);

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Anonymize an IP address by masking the host part (compliant with privacy standards).
     */
    public function anonymizeIp(?string $ip): ?string
    {
        if (empty($ip)) {
            return null;
        }

        $ip = trim($ip);

        // IPv4: mask the last octet (e.g. 192.168.1.150 -> 192.168.1.0)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        // IPv6: mask the last 64 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            if (count($parts) > 4) {
                return implode(':', array_slice($parts, 0, 4)).'::';
            }

            return '::';
        }

        return $ip;
    }

    /**
     * Determine if an array key corresponds to a sensitive secret.
     */
    public function isSensitiveKey(string $key): bool
    {
        $normalizedKey = strtolower(str_replace(['-', ' '], '_', trim($key)));

        $sensitiveList = config('privacy.sensitive_keys', [
            'password',
            'secret',
            'token',
            'api_key',
            'bot_token',
            'cvv',
            'cvc',
            'card_number',
            'pan',
            'session_id',
            'remember_token',
            'wifi_password',
            'two_factor_secret',
        ]);

        foreach ($sensitiveList as $sensitive) {
            if ($normalizedKey === $sensitive || str_contains($normalizedKey, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
