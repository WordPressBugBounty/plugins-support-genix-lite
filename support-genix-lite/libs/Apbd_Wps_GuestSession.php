<?php

/**
 * Scoped guest session cookie (portal-only, ticket-bound).
 */

defined('ABSPATH') || exit;

if (!class_exists("Apbd_Wps_GuestSession")) {
    class Apbd_Wps_GuestSession
    {
        const COOKIE = 'sg_guest_ticket_session';

        protected static function CookieOptions($expires)
        {
            return [
                'expires'  => $expires,
                'path'     => (defined('COOKIEPATH') && COOKIEPATH) ? COOKIEPATH : '/',
                'domain'   => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax', // blocks the cookie on cross-site POST -> CSRF mitigation
            ];
        }

        /**
         * Issue the session cookie for one ticket/user pair.
         */
        public static function Issue($ticket_id, $ticket_user, $ttl, $encryptionKey)
        {
            $exp = time() + absint($ttl);

            $claim = new stdClass();
            $claim->ticket_id   = (int) $ticket_id;
            $claim->ticket_user = (int) $ticket_user;
            $claim->purpose     = 'guest_session';
            $claim->iat         = time();
            $claim->exp         = $exp;

            $encObj = Apbd_Wps_EncryptionLib::getInstance($encryptionKey);
            $value  = $encObj->encryptObj($claim);
            if (empty($value)) {
                return false;
            }

            setcookie(self::COOKIE, $value, self::CookieOptions($exp));
            $_COOKIE[self::COOKIE] = $value;
            return true;
        }

        /**
         * @return object|null Claim, or null when absent/invalid/expired.
         */
        public static function Validate($raw, $encryptionKey)
        {
            if (empty($raw)) {
                return null;
            }

            $encObj = Apbd_Wps_EncryptionLib::getInstance($encryptionKey);
            $claim  = $encObj->decryptObj($raw);

            if (
                empty($claim) ||
                empty($claim->ticket_user) ||
                empty($claim->ticket_id) ||
                empty($claim->purpose) ||
                'guest_session' !== $claim->purpose ||
                empty($claim->exp) ||
                (int) $claim->exp < time()
            ) {
                return null;
            }

            return $claim;
        }

        /**
         * Expire the cookie and drop it from the current request.
         */
        public static function Clear()
        {
            setcookie(self::COOKIE, '', self::CookieOptions(time() - 3600));
            unset($_COOKIE[self::COOKIE]);
        }

        /**
         * Sanitized raw cookie value, '' when absent.
         */
        public static function ReadRaw()
        {
            if (empty($_COOKIE[self::COOKIE])) {
                return '';
            }

            return sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE]));
        }
    }
}
