<?php

namespace App\Support;

use GdImage;
use Throwable;

/**
 * Makes a picture light for the web with PHP's GD: turned upright (the phone's EXIF orientation), at most MAX pixels
 * on its longest side, saved as JPEG (or PNG where the server's GD has no JPEG). What the server cannot read or
 * write is left to the caller (resize() returns null), which keeps the original.
 */
class ImageResizer
{
    public const MAX = 1200;
    public const QUALITY = 82;

    /** What this server's GD reads and writes (for the health check, see RefreshDeployCaches). */
    public static function support(): array
    {
        $gd = function_exists('gd_info') ? gd_info() : [];

        return array_keys(array_filter([
            'jpeg' => ! empty($gd['JPEG Support']),
            'png' => ! empty($gd['PNG Support']),
            'webp' => ! empty($gd['WebP Support']),
            'exif' => function_exists('exif_read_data'),
        ]));
    }

    /**
     * Saves the picture at $source, made light, as $target without extension; returns the file's name with its
     * extension (".jpg" or ".png"), or null when this server cannot do it.
     */
    public static function resize(string $source, string $target, int $max = self::MAX): ?string
    {
        try {
            $image = self::load($source);
            if (! $image) {
                return null;
            }
            $image = self::upright($image, $source);
            [$width, $height] = [imagesx($image), imagesy($image)];
            $scale = min(1, $max / max($width, $height));
            $canvas = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
            // A transparent picture gets a white background (JPEG has no transparency).
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, imagesx($canvas), imagesy($canvas), $width, $height);

            if (in_array('jpeg', self::support(), true)) {
                return imagejpeg($canvas, $target . '.jpg', self::QUALITY) ? basename($target) . '.jpg' : null;
            }

            return imagepng($canvas, $target . '.png', 8) ? basename($target) . '.png' : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function load(string $source): ?GdImage
    {
        $type = @getimagesize($source)[2] ?? null;
        $support = self::support();
        $image = match (true) {
            $type === IMAGETYPE_JPEG && in_array('jpeg', $support, true) => @imagecreatefromjpeg($source),
            $type === IMAGETYPE_PNG && in_array('png', $support, true) => @imagecreatefrompng($source),
            $type === IMAGETYPE_WEBP && in_array('webp', $support, true) => @imagecreatefromwebp($source),
            $type === IMAGETYPE_GIF && function_exists('imagecreatefromgif') => @imagecreatefromgif($source),
            default => false,
        };

        return $image ?: null;
    }

    /** A phone's picture, turned as it was taken (its EXIF orientation). */
    private static function upright(GdImage $image, string $source): GdImage
    {
        $orientation = function_exists('exif_read_data') ? (int) (@exif_read_data($source)['Orientation'] ?? 1) : 1;
        $turned = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($turned, $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        }

        return $turned ?: $image;
    }
}
