<?php

namespace App\Services\Security;

use InvalidArgumentException;

class CssSanitizer
{
    public const MAX_CSS_LENGTH = 10000; // 10 KB

    /**
     * Dangerous tokens and patterns that must never appear in custom CSS.
     */
    protected const DANGEROUS_PATTERNS = [
        // Tag breakout
        '/<\/?style[^>]*>/i',
        '/<\/?script[^>]*>/i',
        '/<\/?(?:iframe|object|embed|applet|meta|link|base|form|svg|img)[^>]*>/i',

        // JavaScript schemes & execution
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/data\s*:\s*image\/svg\+xml/i',
        '/expression\s*\(/i',
        '/-moz-binding\s*:/i',
        '/behavior\s*:/i',

        // External style import & HTML comments
        '/@import/i',
        '/<!--/i',
        '/-->/i',
    ];

    /**
     * Sanitize custom CSS string to guarantee it cannot execute JavaScript or break out into HTML.
     *
     * @throws InvalidArgumentException
     */
    public function sanitize(?string $css): string
    {
        if ($css === null || trim($css) === '') {
            return '';
        }

        if (mb_strlen($css) > self::MAX_CSS_LENGTH) {
            throw new InvalidArgumentException('Custom CSS exceeds the maximum allowable length of '.self::MAX_CSS_LENGTH.' characters.');
        }

        $sanitized = $css;

        // 1. Remove dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $sanitized = preg_replace($pattern, '/* [stripped] */', $sanitized);
        }

        // 2. Remove all HTML tags to prevent tag breakout
        $sanitized = strip_tags($sanitized);

        // 3. Remove raw '<' and '>' to completely prevent HTML tag formation
        $sanitized = str_replace(['<', '>'], '', $sanitized);

        // 4. Neutralize any residual closing style tags or null bytes
        $sanitized = str_ireplace('</style', '', $sanitized);
        $sanitized = str_replace("\0", '', $sanitized);

        return trim($sanitized);
    }

    /**
     * Check if the provided CSS contains injection attempts.
     */
    public function containsMaliciousContent(string $css): bool
    {
        if (mb_strlen($css) > self::MAX_CSS_LENGTH) {
            return true;
        }

        if (stripos($css, '</style') !== false || stripos($css, '<script') !== false || stripos($css, '<svg') !== false || stripos($css, '<img') !== false) {
            return true;
        }

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $css)) {
                return true;
            }
        }

        return false;
    }
}
