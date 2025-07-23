<?php

namespace PicPilotStudio\Helpers;

class Logger {
    const LOG_FILE = \WP_CONTENT_DIR . '/uploads/pic-pilot-studio.log';
    const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    public static function log($message) {
        $settings = get_option('picpilot_studio_settings', []);
        if (empty($settings['log_enabled'])) return;

        $timestamp = current_time('mysql');
        $line = "[$timestamp] $message\n";

        // Rotate log if too big
        if (file_exists(self::LOG_FILE) && filesize(self::LOG_FILE) >= self::MAX_SIZE) {
            unlink(self::LOG_FILE); // start fresh
        }

        file_put_contents(self::LOG_FILE, $line, FILE_APPEND);
    }
}
