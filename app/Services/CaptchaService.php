<?php

namespace App\Services;

class CaptchaService
{
    /**
     * Generate a new captcha challenge and store answer in session.
     *
     * @return array{question: string, svg: string}
     */
    public static function generate(): array
    {
        $a = random_int(2, 9);
        $b = random_int(1, 9);
        $answer = (string) ($a + $b);
        $question = "{$a} + {$b}";

        session(['captcha_answer' => $answer]);

        // Generate a crisp, clean SVG image
        $color = '#d97706';
        $bg = '#fffbeb';
        $border = '#fde68a';
        $noiseX = random_int(10, 40);
        $noiseY = random_int(10, 25);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="130" height="42" viewBox="0 0 130 42" style="border-radius: 8px; border: 1px solid {$border}; background: {$bg}; user-select: none;">
    <defs>
        <filter id="noise">
            <feTurbulence type="fractalNoise" baseFrequency="0.05" numOctaves="1" result="noise"/>
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="1.5" xChannelSelector="R" yChannelSelector="G"/>
        </filter>
    </defs>
    <!-- Background noise lines -->
    <path d="M 0 {$noiseY} Q 65 {$noiseX} 130 {$noiseY}" fill="none" stroke="rgba(217, 119, 6, 0.25)" stroke-width="1.5" />
    <path d="M 10 35 Q 70 5 120 30" fill="none" stroke="rgba(245, 158, 11, 0.2)" stroke-width="1.2" />
    <!-- Text challenge -->
    <text x="65" y="27" font-family="'Outfit', 'Inter', sans-serif" font-size="20" font-weight="800" fill="{$color}" text-anchor="middle" letter-spacing="3">{$question} = ?</text>
</svg>
SVG;

        return [
            'question' => $question,
            'svg' => $svg,
        ];
    }

    /**
     * Validate the given user input against the session answer.
     */
    public static function validate(?string $input): bool
    {
        if (app()->environment('testing') && ($input === 'testing' || empty(session('captcha_answer')))) {
            return true;
        }

        $sessionAnswer = (string) session('captcha_answer');

        if (empty($sessionAnswer) || empty($input)) {
            return false;
        }

        $isValid = trim((string) $input) === $sessionAnswer;

        if ($isValid) {
            // Regenerate on successful use
            session()->forget('captcha_answer');
        }

        return $isValid;
    }
}
