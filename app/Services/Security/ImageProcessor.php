<?php

namespace App\Services\Security;

use App\Exceptions\InvalidFileException;

class ImageProcessor
{
    public function __construct(
        protected ?SvgSanitizer $svgSanitizer = null
    ) {
        $this->svgSanitizer = $svgSanitizer ?? new SvgSanitizer;
    }

    /**
     * Re-encode raster image to strip EXIF and metadata and eliminate polyglot payloads.
     * For SVG, sanitizes and verifies XML structure.
     *
     * @param  string  $sourcePath  Path to original uploaded file
     * @param  string  $mimeType  Detected MIME type
     * @param  string  $destinationPath  Destination file path
     * @return array{width: int, height: int, size: int, mime: string}
     *
     * @throws InvalidFileException
     */
    public function reencodeAndStripMetadata(string $sourcePath, string $mimeType, string $destinationPath): array
    {
        if (! file_exists($sourcePath)) {
            throw new InvalidFileException('Source image file not found.');
        }

        $contents = file_get_contents($sourcePath);
        if ($contents === false || strlen($contents) === 0) {
            throw new InvalidFileException('Image file is empty.');
        }

        // Handle SVG separately
        if ($mimeType === 'image/svg+xml' || str_ends_with($sourcePath, '.svg')) {
            $cleanSvg = $this->svgSanitizer->sanitize($contents);
            file_put_contents($destinationPath, $cleanSvg);

            return [
                'width' => 0,
                'height' => 0,
                'size' => filesize($destinationPath) ?: strlen($cleanSvg),
                'mime' => 'image/svg+xml',
            ];
        }

        // Ensure GD extension is available
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('PHP GD extension is required for server-side image processing.');
        }

        // Validate image content can be decoded
        $sourceImage = @imagecreatefromstring($contents);
        if ($sourceImage === false) {
            throw new InvalidFileException('Invalid or corrupted image content. Unable to decode image.');
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($sourceImage);
            throw new InvalidFileException('Invalid image dimensions detected.');
        }

        // Create new clean image canvas
        $cleanImage = imagecreatetruecolor($width, $height);
        if ($cleanImage === false) {
            imagedestroy($sourceImage);
            throw new InvalidFileException('Failed to allocate image surface for processing.');
        }

        $normalizedMime = match ($mimeType) {
            'image/jpeg', 'image/jpg', 'image/pjpeg' => 'image/jpeg',
            'image/png', 'image/x-png' => 'image/png',
            'image/webp' => 'image/webp',
            'image/avif' => 'image/avif',
            default => 'image/jpeg',
        };

        // Handle transparency
        if (in_array($normalizedMime, ['image/png', 'image/webp', 'image/avif'], true)) {
            imagealphablending($cleanImage, false);
            imagesavealpha($cleanImage, true);
            $transparent = imagecolorallocatealpha($cleanImage, 255, 255, 255, 127);
            imagefilledrectangle($cleanImage, 0, 0, $width, $height, $transparent);
        } else {
            // Fill background with white for JPEG
            $white = imagecolorallocate($cleanImage, 255, 255, 255);
            imagefilledrectangle($cleanImage, 0, 0, $width, $height, $white);
        }

        // Copy raw pixels from source to clean canvas
        imagecopy($cleanImage, $sourceImage, 0, 0, 0, 0, $width, $height);

        // Ensure destination directory exists
        $dir = dirname($destinationPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $saved = match ($normalizedMime) {
            'image/png' => imagepng($cleanImage, $destinationPath, 8),
            'image/webp' => imagewebp($cleanImage, $destinationPath, 85),
            'image/avif' => function_exists('imageavif')
                ? imageavif($cleanImage, $destinationPath, 85)
                : imagejpeg($cleanImage, $destinationPath, 85),
            default => imagejpeg($cleanImage, $destinationPath, 88),
        };

        imagedestroy($sourceImage);
        imagedestroy($cleanImage);

        if (! $saved || ! file_exists($destinationPath)) {
            throw new InvalidFileException('Failed to encode and save sanitized image.');
        }

        return [
            'width' => $width,
            'height' => $height,
            'size' => filesize($destinationPath) ?: 0,
            'mime' => $normalizedMime,
        ];
    }
}
