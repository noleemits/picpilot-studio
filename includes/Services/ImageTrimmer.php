<?php

namespace PicPilot\Studio\Services;

class ImageTrimmer {

    /**
     * Trim empty space from an image and its thumbnails (if applicable).
     *
     * @param string $originalPath Full path to the original image file.
     * @param int|null $attachmentId WordPress attachment ID (optional, used for thumbnail trimming).
     * @return array Result status and message.
     */
    public static function trim(string $originalPath, ?int $attachmentId = null): array {
        if (!file_exists($originalPath)) {
            return ['success' => false, 'message' => __('File not found.', 'pic-pilot-studio')];
        }

        // Attempt to trim the original image
        $result = self::trim_one($originalPath);

        // If attachment ID is provided, also trim all thumbnails
        if ($attachmentId && function_exists('wp_get_attachment_metadata')) {
            $meta = wp_get_attachment_metadata($attachmentId);
            $uploadDir = wp_upload_dir();
            $basePath = trailingslashit($uploadDir['basedir']);

            if (!empty($meta['file']) && !empty($meta['sizes'])) {
                $baseFolder = dirname($meta['file']);
                foreach ($meta['sizes'] as $size) {
                    $thumbPath = $basePath . $baseFolder . '/' . $size['file'];
                    if (file_exists($thumbPath)) {
                        self::trim_one($thumbPath); // No need to store result per thumbnail
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Trim a single image file using Imagick, GD, or CLI fallback.
     *
     * @param string $path Full file path to image.
     * @return array Result array with success boolean and message.
     */
    private static function trim_one(string $path): array {
        if (extension_loaded('imagick')) {
            return self::trim_with_imagick($path);
        }

        if (function_exists('imagecreatefrompng')) {
            return self::trim_with_gd($path);
        }

        if (shell_exec('which convert')) {
            return self::trim_with_cli($path);
        }

        return ['success' => false, 'message' => __('No supported image libraries found on server.', 'pic-pilot-studio')];
    }

    /**
     * Use Imagick to trim transparent/white space.
     */
    private static function trim_with_imagick(string $path): array {
        try {
            $image = new \Imagick($path);
            $image->trimImage(0);
            $image->setImagePage(0, 0, 0, 0);
            $image->writeImage($path);
            $image->destroy();

            return ['success' => true, 'message' => __('Image trimmed successfully.', 'pic-pilot-studio')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => __('Imagick trimming failed.', 'pic-pilot-studio')];
        }
    }

    /**
     * Use GD to trim white space (alpha or opaque).
     */
    private static function trim_with_gd(string $path): array {
        $info = getimagesize($path);
        $mime = $info['mime'] ?? '';

        switch ($mime) {
            case 'image/png':
                $src = imagecreatefrompng($path);
                break;
            case 'image/jpeg':
                $src = imagecreatefromjpeg($path);
                break;
            default:
                return ['success' => false, 'message' => __('Unsupported format for GD.', 'pic-pilot-studio')];
        }

        if (!$src) {
            return ['success' => false, 'message' => __('Failed to load image with GD.', 'pic-pilot-studio')];
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $top = $height;
        $left = $width;
        $right = 0;
        $bottom = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha < 127) {
                    if ($x < $left) $left = $x;
                    if ($x > $right) $right = $x;
                    if ($y < $top) $top = $y;
                    if ($y > $bottom) $bottom = $y;
                }
            }
        }

        $trimW = $right - $left + 1;
        $trimH = $bottom - $top + 1;

        if ($trimW <= 0 || $trimH <= 0) {
            imagedestroy($src);
            return ['success' => false, 'message' => __('No visible content found.', 'pic-pilot-studio')];
        }

        $trimmed = imagecreatetruecolor($trimW, $trimH);
        imagealphablending($trimmed, false);
        imagesavealpha($trimmed, true);
        $transparent = imagecolorallocatealpha($trimmed, 0, 0, 0, 127);
        imagefill($trimmed, 0, 0, $transparent);
        imagecopy($trimmed, $src, 0, 0, $left, $top, $trimW, $trimH);

        $saved = ($mime === 'image/png') ? imagepng($trimmed, $path) : imagejpeg($trimmed, $path, 90);

        imagedestroy($src);
        imagedestroy($trimmed);

        return $saved
            ? ['success' => true, 'message' => __('Image trimmed with GD.', 'pic-pilot-studio')]
            : ['success' => false, 'message' => __('Failed to save GD trimmed image.', 'pic-pilot-studio')];
    }

    /**
     * Use ImageMagick CLI tool if available.
     */
    private static function trim_with_cli(string $path): array {
        $escaped = escapeshellarg($path);
        $cmd = "convert $escaped -trim +repage $escaped";
        shell_exec($cmd);

        return file_exists($path)
            ? ['success' => true, 'message' => __('Image trimmed via CLI.', 'pic-pilot-studio')]
            : ['success' => false, 'message' => __('CLI trimming failed.', 'pic-pilot-studio')];
    }
}
