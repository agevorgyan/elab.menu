<?php

namespace App\Services\Security;

use App\Exceptions\InvalidFileException;
use DOMDocument;
use DOMElement;

class SvgSanitizer
{
    /**
     * Disallowed tags in SVG.
     */
    protected const DISALLOWED_TAGS = [
        'script',
        'object',
        'iframe',
        'embed',
        'foreignobject',
        'applet',
        'meta',
        'link',
        'base',
        'form',
        'input',
        'button',
    ];

    /**
     * Allowed SVG elements.
     */
    protected const ALLOWED_TAGS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline',
        'polygon', 'text', 'tspan', 'defs', 'lineargradient', 'radialgradient',
        'stop', 'mask', 'clippath', 'pattern', 'desc', 'title', 'use', 'style',
    ];

    /**
     * Dangerous protocols in URLs.
     */
    protected const DANGEROUS_PROTOCOLS = [
        'javascript:',
        'data:',
        'vbscript:',
        'file:',
        'php:',
    ];

    /**
     * Validate and sanitize SVG content.
     *
     * @throws InvalidFileException
     */
    public function sanitize(string $svgContent): string
    {
        $trimmed = trim($svgContent);

        if (empty($trimmed)) {
            throw new InvalidFileException('SVG file is empty.');
        }

        // 1. Check for XML External Entity (XXE) attacks
        if (preg_match('/<!ENTITY/i', $trimmed) || preg_match('/<!DOCTYPE[^>]*\[/i', $trimmed) || preg_match('/SYSTEM\s+["\']/i', $trimmed) || preg_match('/PUBLIC\s+["\']/i', $trimmed)) {
            throw new InvalidFileException('Malicious SVG detected: XML External Entity (XXE) or custom DOCTYPE declarations are strictly prohibited.');
        }

        // 2. Pre-check for embedded scripts, event handlers or data/javascript URIs
        if (preg_match('/<script[\s>]/i', $trimmed) || preg_match('/<\/script>/i', $trimmed)) {
            throw new InvalidFileException('Malicious SVG detected: script tags are strictly prohibited.');
        }

        if (preg_match('/\bon[a-z]+\s*=/i', $trimmed)) {
            throw new InvalidFileException('Malicious SVG detected: inline JavaScript event handlers are strictly prohibited.');
        }

        foreach (self::DANGEROUS_PROTOCOLS as $protocol) {
            if (stripos($trimmed, $protocol) !== false) {
                throw new InvalidFileException("Malicious SVG detected: dangerous protocol [{$protocol}] is prohibited.");
            }
        }

        // 3. Parse XML using DOMDocument securely
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $previousEntityLoader = libxml_disable_entity_loader(true);
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $flags = LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR;
            if (defined('LIBXML_NOENT')) {
                $flags |= LIBXML_NOENT;
            }

            $loaded = $dom->loadXML($trimmed, $flags);
            if (! $loaded || ! $dom->documentElement) {
                throw new InvalidFileException('Invalid SVG: Malformed or unparseable XML structure.');
            }

            // Root element must be <svg>
            if (strtolower($dom->documentElement->tagName) !== 'svg') {
                throw new InvalidFileException('Invalid SVG: Root element must be <svg>.');
            }

            $this->cleanNode($dom->documentElement);

            $cleanedXml = $dom->saveXML($dom->documentElement);
            if ($cleanedXml === false || empty(trim($cleanedXml))) {
                throw new InvalidFileException('SVG sanitization produced empty or invalid XML output.');
            }

            return $cleanedXml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
            if (function_exists('libxml_disable_entity_loader')) {
                @libxml_disable_entity_loader($previousEntityLoader);
            }
        }
    }

    /**
     * Recursively inspect and clean DOM elements.
     *
     * @throws InvalidFileException
     */
    protected function cleanNode(DOMElement $element): void
    {
        $tagName = strtolower($element->tagName);

        if (in_array($tagName, self::DISALLOWED_TAGS, true)) {
            throw new InvalidFileException("Malicious SVG detected: prohibited element <{$tagName}>.");
        }

        // Inspect and remove disallowed attributes
        $attrsToRemove = [];
        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->name);
            $attrValue = trim($attr->value);

            // Reject event handlers
            if (str_starts_with($attrName, 'on')) {
                throw new InvalidFileException("Malicious SVG detected: event handler [{$attrName}] is prohibited.");
            }

            // Inspect href and xlink:href
            if ($attrName === 'href' || $attrName === 'xlink:href' || str_ends_with($attrName, ':href')) {
                foreach (self::DANGEROUS_PROTOCOLS as $protocol) {
                    if (str_starts_with(strtolower($attrValue), $protocol)) {
                        throw new InvalidFileException("Malicious SVG detected: prohibited protocol in [{$attrName}].");
                    }
                }

                // Disallow external URLs in href to prevent SSRF / tracking
                if (preg_match('/^https?:\/\//i', $attrValue)) {
                    throw new InvalidFileException("Malicious SVG detected: external reference [{$attrValue}] is prohibited.");
                }
            }

            // Check style attributes for javascript/expression
            if ($attrName === 'style') {
                if (preg_match('/expression\s*\(/i', $attrValue) || preg_match('/javascript\s*:/i', $attrValue) || preg_match('/url\s*\(\s*["\']?(?:javascript|data):/i', $attrValue)) {
                    throw new InvalidFileException('Malicious SVG detected: dangerous style attribute content.');
                }
            }
        }

        foreach ($attrsToRemove as $attrName) {
            $element->removeAttribute($attrName);
        }

        // Recursively clean children
        $children = iterator_to_array($element->childNodes);
        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $this->cleanNode($child);
            }
        }
    }
}
