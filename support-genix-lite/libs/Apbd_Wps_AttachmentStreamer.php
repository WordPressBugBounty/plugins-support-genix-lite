<?php

/**
 * Ticket attachment streamer.
 *
 * Kept out of the settings module to avoid malware-scanner false positives.
 */

defined('ABSPATH') || exit;

if (!class_exists("Apbd_Wps_AttachmentStreamer")) {
    class Apbd_Wps_AttachmentStreamer
    {
        const ACTION = 'support-genix_attached_file_dl';

        /**
         * Guests included; authorization is per ticket in Stream().
         */
        public static function Register()
        {
            add_action('wp_ajax_' . self::ACTION, [__CLASS__, 'Ajax']);
            add_action('wp_ajax_nopriv_' . self::ACTION, [__CLASS__, 'Ajax']);
        }

        /**
         * Media endpoint hit by <img>, so no nonce; authorization is in Stream().
         */
        public static function Ajax()
        {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $id   = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
            // basename()'d in Stream(); only used to locate a file, never echoed or in SQL.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $file = isset($_GET['file']) ? wp_unslash($_GET['file']) : '';

            if (! self::Stream($type, $id, $file)) {
                status_header(403);
                exit;
            }
            exit;
        }

        /**
         * Resolve, authorize and stream a ticket/reply attachment.
         *
         * @param string $type               "T" for ticket, "R" for reply.
         * @param string $ticket_or_reply_id Ticket id, or "{ticket}_{reply}".
         * @param string $file               URL-decoded; only the basename is used.
         * @return bool
         */
        public static function Stream($type, $ticket_or_reply_id, $file)
        {
            $type     = strtoupper(sanitize_text_field((string) $type));
            $basePath = Apbd_wps_settings::get_upload_path();
            $safeName = basename((string) $file);

            if ($safeName === '' || $safeName === '.' || $safeName === '..') {
                return false;
            }

            $ticket_id = 0;
            $target    = null;

            if ($type === 'T') {
                $ticket_id = absint($ticket_or_reply_id);
                if (! empty($ticket_id)) {
                    $target = $basePath . $ticket_id . '/attached_files/' . $safeName;
                }
            } elseif ($type === 'R') {
                $parts = explode('_', (string) $ticket_or_reply_id);
                if (count($parts) === 2) {
                    $rep = Mapbd_wps_ticket_reply::FindBy('ticket_id', absint($parts[0]), array('reply_id' => absint($parts[1])));
                    if ($rep) {
                        $ticket_id = absint($rep->ticket_id);
                        $target    = $basePath . $rep->ticket_id . '/replied/' . $rep->reply_id . '/attached_files/' . $safeName;
                    }
                }
            }

            if (empty($ticket_id) || is_null($target)) {
                return false;
            }

            if (! Mapbd_wps_ticket::userCanAccessTicket($ticket_id)) {
                return false;
            }

            if (! file_exists($target)) {
                return false;
            }

            // Defense-in-depth: the resolved path must stay inside the upload base.
            $realBase   = realpath($basePath);
            $realTarget = realpath($target);
            if ($realBase === false || $realTarget === false
                || strpos($realTarget, rtrim($realBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) !== 0) {
                return false;
            }

            $ext = strtolower(pathinfo($realTarget, PATHINFO_EXTENSION));
            if (! in_array($ext, Apbd_wps_settings::GetModuleAllowedFileType())) {
                return false;
            }

            // Type from the allow-listed extension, never content sniffing (HTML as .jpg).
            $fileType = wp_check_filetype($safeName);
            $mime     = ! empty($fileType['type']) ? $fileType['type'] : 'application/octet-stream';
            // Only types a browser cannot run script from are shown inline; the rest download.
            $inline   = in_array($mime, array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain'), true)
                || 0 === strpos($mime, 'audio/') || 0 === strpos($mime, 'video/');
            // Strip any header-breaking characters from the name before it goes in a header.
            $dispositionName = sanitize_file_name($safeName);

            if (! headers_sent()) {
                nocache_headers();
                header('Content-Type: ' . $mime);
                // "inline" so <img>/preview works; the browser can still save it.
                header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $dispositionName . '"');
                header('X-Content-Type-Options: nosniff');
                header('Content-Length: ' . filesize($realTarget));
            }

            // Native read: WP_Filesystem returns an empty body when its method is non-direct.
            readfile($realTarget); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile, WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw binary attachment download; readfile streams from disk and the bytes must not be escaped.

            return true;
        }
    }
}
