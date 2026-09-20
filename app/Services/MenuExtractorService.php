<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class MenuExtractorService
{
    /**
     * Extract menu text or structured multimodal payload from an uploaded file.
     *
     * @return array{type: string, text?: string, mime?: string, base64?: string, items?: array}
     */
    public function extractFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $realPath = $file->getRealPath();

        return match ($extension) {
            'csv' => [
                'type' => 'text',
                'text' => $this->extractFromCsv($realPath),
            ],
            'xlsx' => [
                'type' => 'text',
                'text' => $this->extractFromXlsx($realPath),
            ],
            'xls' => [
                'type' => 'text',
                'text' => $this->extractFromLegacyXls($realPath),
            ],
            'docx' => [
                'type' => 'text',
                'text' => $this->extractFromDocx($realPath),
            ],
            'doc' => [
                'type' => 'text',
                'text' => $this->extractFromLegacyDoc($realPath),
            ],
            'png', 'jpg', 'jpeg', 'webp' => [
                'type' => 'image',
                'mime' => $mime ?: "image/{$extension}",
                'base64' => base64_encode(file_get_contents($realPath)),
            ],
            'pdf' => [
                'type' => 'pdf',
                'mime' => 'application/pdf',
                'base64' => base64_encode(file_get_contents($realPath)),
                'text' => $this->extractTextFromPdfStream($realPath),
            ],
            'json' => [
                'type' => 'text',
                'text' => $this->extractFromJsonFile($realPath),
            ],
            default => [
                'type' => 'text',
                'text' => file_get_contents($realPath) ?: '',
            ],
        };
    }

    /**
     * Extract menu text from a CSV file.
     */
    public function extractFromCsv(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        // Auto-detect delimiter from first line
        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $lines = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 4096, $bestDelimiter)) !== false) {
                // Filter out empty columns
                $row = array_map('trim', $row);
                $row = array_filter($row, fn ($val) => $val !== '');
                if (! empty($row)) {
                    $lines[] = implode(' | ', $row);
                }
            }
            fclose($handle);
        }

        return implode("\n", $lines);
    }

    /**
     * Extract menu text from modern Excel (.xlsx) using native ZipArchive + SimpleXML.
     */
    public function extractFromXlsx(string $filePath): string
    {
        if (! file_exists($filePath) || ! class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return '';
        }

        // 1. Extract shared strings table
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml !== false) {
            try {
                $xml = simplexml_load_string($stringsXml);
                if ($xml) {
                    foreach ($xml->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string) $si->t;
                        } elseif (isset($si->r)) {
                            $runText = '';
                            foreach ($si->r as $r) {
                                $runText .= (string) $r->t;
                            }
                            $sharedStrings[] = $runText;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('XLSX sharedStrings parse warning: '.$e->getMessage());
            }
        }

        // 2. Extract sheet rows
        $rows = [];
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml !== false) {
            try {
                $xml = simplexml_load_string($sheetXml);
                if ($xml && isset($xml->sheetData->row)) {
                    foreach ($xml->sheetData->row as $row) {
                        $cells = [];
                        foreach ($row->c as $cell) {
                            $type = (string) $cell['t'];
                            $val = (string) $cell->v;

                            if ($type === 's' && isset($sharedStrings[(int) $val])) {
                                $cellVal = $sharedStrings[(int) $val];
                            } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                                $cellVal = (string) $cell->is->t;
                            } else {
                                $cellVal = $val;
                            }

                            $cellVal = trim($cellVal);
                            if ($cellVal !== '') {
                                $cells[] = $cellVal;
                            }
                        }

                        if (! empty($cells)) {
                            $rows[] = implode(' | ', $cells);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('XLSX sheet1 parse warning: '.$e->getMessage());
            }
        }

        $zip->close();

        return implode("\n", $rows);
    }

    /**
     * Extract menu text from legacy Excel (.xls).
     */
    public function extractFromLegacyXls(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return '';
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            return '';
        }

        // Extract printable UTF-8 strings
        preg_match_all('/[\x20-\x7E\x{0400}-\x{04FF}\x{0530}-\x{058F}]{3,}/u', $raw, $matches);

        return implode("\n", array_unique($matches[0] ?? []));
    }

    /**
     * Extract menu text from modern Word (.docx) using native ZipArchive + SimpleXML.
     */
    public function extractFromDocx(string $filePath): string
    {
        if (! file_exists($filePath) || ! class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($documentXml === false) {
            return '';
        }

        // Convert paragraph and table tags to formatted text delimiters
        $formatted = str_replace(['</w:p>', '</w:tr>'], "\n", $documentXml);
        $formatted = str_replace('</w:tc>', ' | ', $formatted);
        $text = strip_tags($formatted);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $lines = explode("\n", $text);
        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $cleanLines[] = $line;
            }
        }

        return implode("\n", $cleanLines);
    }

    /**
     * Extract menu text from legacy Word (.doc).
     */
    public function extractFromLegacyDoc(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return '';
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            return '';
        }

        // Extract printable text sequences
        preg_match_all('/[\x20-\x7E\x{0400}-\x{04FF}\x{0530}-\x{058F}]{3,}/u', $raw, $matches);

        return implode("\n", $matches[0] ?? []);
    }

    /**
     * Extract JSON menu structure into readable text.
     */
    public function extractFromJsonFile(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);
        if (! $content) {
            return '';
        }

        $json = json_decode($content, true);
        if (! is_array($json)) {
            return $content;
        }

        $lines = [];
        $this->flattenJsonToText($json, $lines);

        return implode("\n", $lines);
    }

    private function flattenJsonToText(array $array, array &$lines, string $prefix = ''): void
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $lines[] = $prefix.$k.':';
                $this->flattenJsonToText($v, $lines, $prefix.'  ');
            } else {
                $lines[] = $prefix."{$k}: {$v}";
            }
        }
    }

    /**
     * Best-effort local PDF stream extraction for offline mode.
     */
    public function extractTextFromPdfStream(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        $text = '';
        // Look for PDF text show operations: (Some text) Tj or [(Some) -20 (text)] TJ
        if (preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches)) {
            $text .= implode("\n", $matches[1]);
        }

        // Fallback printable text
        if (strlen(trim($text)) < 50) {
            preg_match_all('/[\x20-\x7E\x{0400}-\x{04FF}\x{0530}-\x{058F}]{4,}/u', $content, $matches);
            $text = implode("\n", array_slice($matches[0] ?? [], 0, 200));
        }

        return trim($text);
    }

    /**
     * Extract menu content from a website URL.
     *
     * @throws \Exception
     */
    public function extractFromUrl(string $url): string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Անվավեր URL հասցե: Խնդրում ենք տրամադրել լիարժեք հասցե (օր. https://example.com/menu)');
        }

        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'hy,en-US,en;q=0.9,ru;q=0.8',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Հնարավոր չեղավ բացել կայքը (Կոդ: {$response->status()})։ Ստուգեք հասցեն կամ հասանելիությունը։");
        }

        $html = $response->body();

        // 1. Check for Schema.org JSON-LD (Restaurant or Menu)
        $schemaText = $this->extractSchemaJsonLd($html);
        if (! empty($schemaText)) {
            return "STRUCTURED SCHEMA.ORG MENU DATA FOUND:\n".$schemaText;
        }

        // 2. Sanitize and extract semantic HTML menu content
        return $this->convertHtmlToMenuText($html);
    }

    /**
     * Extract Schema.org JSON-LD microdata from HTML.
     */
    private function extractSchemaJsonLd(string $html): string
    {
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            $extractedItems = [];
            foreach ($matches[1] as $jsonString) {
                $data = json_decode(trim($jsonString), true);
                if (! is_array($data)) {
                    continue;
                }

                // Check for Menu, MenuItem, Restaurant or array of entities
                $dataList = isset($data['@graph']) ? $data['@graph'] : [$data];
                foreach ($dataList as $entry) {
                    $type = $entry['@type'] ?? '';
                    if (in_array($type, ['Menu', 'MenuItem', 'MenuSection', 'Restaurant', 'FoodEstablishment'], true)) {
                        $extractedItems[] = json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    }
                }
            }

            if (! empty($extractedItems)) {
                return implode("\n\n", $extractedItems);
            }
        }

        return '';
    }

    /**
     * Convert raw HTML into clean, semantic text for menu extraction.
     */
    private function convertHtmlToMenuText(string $html): string
    {
        // Strip out scripts, styles, iframes, svgs, header/footer/nav elements
        $cleanHtml = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $cleanHtml = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $cleanHtml);
        $cleanHtml = preg_replace('/<noscript\b[^>]*>(.*?)<\/noscript>/is', '', $cleanHtml);
        $cleanHtml = preg_replace('/<svg\b[^>]*>(.*?)<\/svg>/is', '', $cleanHtml);
        $cleanHtml = preg_replace('/<nav\b[^>]*>(.*?)<\/nav>/is', '', $cleanHtml);
        $cleanHtml = preg_replace('/<footer\b[^>]*>(.*?)<\/footer>/is', '', $cleanHtml);

        // Convert structural elements to readable text
        $cleanHtml = preg_replace('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', "\n\n# $1\n", $cleanHtml);
        $cleanHtml = preg_replace('/<h[4-6][^>]*>(.*?)<\/h[4-6]>/is', "\n## $1\n", $cleanHtml);
        $cleanHtml = preg_replace('/<(?:p|div|tr|li)[^>]*>/is', "\n", $cleanHtml);
        $cleanHtml = preg_replace('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', ' $1 | ', $cleanHtml);

        // Decode HTML entities and strip tags
        $text = strip_tags($cleanHtml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Clean up whitespace
        $lines = explode("\n", $text);
        $filtered = [];
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            // Keep meaningful lines, omit very short UI noise
            if (mb_strlen($line) >= 2) {
                $filtered[] = $line;
            }
        }

        // Limit size to avoid overflow (max 1000 lines)
        $filtered = array_slice($filtered, 0, 1000);

        return implode("\n", $filtered);
    }
}
