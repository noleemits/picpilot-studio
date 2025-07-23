<?php

namespace PicPilotStudio\Services;

class ImageTrimmer {

    public static function trim(string $originalPath, ?int $attachmentId = null): array {
        if (!file_exists($originalPath)) {
            return ['success' => false, 'message' => __('File not found.', 'pic-pilot-studio')];
        }

        $result = self::trim_one($originalPath);

        // Trim all thumbnail sizes if an attachment ID is provided
        if ($attachmentId && function_exists('wp_get_attachment_metadata')) {
            $uploadDir = wp_upload_dir();
            $basePath = trailingslashit($uploadDir['basedir']);
            $meta = wp_get_attachment_metadata($attachmentId);

            if (!$meta || empty($meta['file'])) {
                return ['success' => false, 'message' => __('Could not load attachment metadata.', 'pic-pilot-studio')];
            }

            // ✅ Trim original image only once
            $originalTrimResult = self::trim_one($originalPath);

            // ✅ Trim registered thumbnails
            if (!empty($meta['sizes'])) {
                $baseFolder = dirname($meta['file']);
                foreach ($meta['sizes'] as $size) {
                    $thumbPath = $basePath . $baseFolder . '/' . $size['file'];
                    if (file_exists($thumbPath)) {
                        self::trim_one($thumbPath);
                    }
                }
            }

            return $originalTrimResult;
        }

        return $result;
    }

    private static function trim_one(string $path): array {
        if (extension_loaded('imagick')) {
            try {
                $image = new \Imagick($path);
                $image->setOption('trim:fuzz', '20%');

                error_log("🧪 Trimming: $path");
                error_log("🔍 Original size: " . $image->getImageWidth() . "x" . $image->getImageHeight());

                $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $image->trimImage(0);
                $image->setImagePage(0, 0, 0, 0);
                $image->writeImage($path);

                $image->readImage($path); // reload to confirm
                error_log("✅ Final size: " . $image->getImageWidth() . "x" . $image->getImageHeight());

                $image->destroy();

                return ['success' => true, 'message' => __('Trimmed successfully.', 'pic-pilot-studio')];
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => __('Imagick trimming failed.', 'pic-pilot-studio')];
            }
        }

        return ['success' => false, 'message' => __('No supported image engine found.', 'pic-pilot-studio')];
    }


    private static function trim_with_imagick(string $path): array {
        try {
            $image = new \Imagick($path);
            $image->setOption('trim:fuzz', '20%');
            error_log("🔍 Original size: " . $image->getImageWidth() . "x" . $image->getImageHeight());

            $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            $image->trimImage(0);
            $image->setImagePage(0, 0, 0, 0);
            $image->writeImage($path);

            $image->readImage($path); // reload to confirm final
            error_log("✅ Final size: " . $image->getImageWidth() . "x" . $image->getImageHeight());

            $image->destroy();

            return ['success' => true, 'message' => __('Image trimmed successfully.', 'pic-pilot-studio')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => __('Imagick trimming failed.', 'pic-pilot-studio')];
        }
    }

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

    private static function trim_with_cli(string $path): array {
        $escaped = escapeshellarg($path);
        $cmd = "convert $escaped -trim +repage $escaped";
        shell_exec($cmd);

        return file_exists($path)
            ? ['success' => true, 'message' => __('Image trimmed via CLI.', 'pic-pilot-studio')]
            : ['success' => false, 'message' => __('CLI trimming failed.', 'pic-pilot-studio')];
    }
    //Delete ghost images
    private static function remove_old_thumbnails(int $attachmentId, array $oldMeta, array $newMeta) {
        $uploadDir = wp_upload_dir();
        $baseFolder = dirname($oldMeta['file']);
        $basePath = trailingslashit($uploadDir['basedir']) . $baseFolder . '/';

        $oldFiles = array_map(fn($size) => $basePath . $size['file'], $oldMeta['sizes'] ?? []);
        $newFiles = array_map(fn($size) => $basePath . $size['file'], $newMeta['sizes'] ?? []);

        foreach ($oldFiles as $file) {
            if (!in_array($file, $newFiles) && file_exists($file)) {
                unlink($file); // cleanup orphaned thumbnail
            }
        }
    }
}
