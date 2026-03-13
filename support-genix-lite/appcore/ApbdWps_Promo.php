<?php

/**
 * Promotional Banner Notice.
 */

// If this file is accessed directly, exit.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling promotional banner notices.
 */
if (! class_exists('ApbdWps_Promo')) {
    class ApbdWps_Promo
    {
        /**
         * Instance.
         */
        private static $_instance = null;

        /**
         * Banner image URL.
         */
        private $banner_image;

        /**
         * Banner link URL.
         */
        private $banner_link;

        /**
         * Notice key for dismissal.
         */
        private $notice_key;

        /**
         * Get instance.
         */
        public static function get_instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor.
         */
        private function __construct()
        {
            $this->banner_image = plugins_url('assets/img/christmas-offer-banner-2025.png', dirname(__FILE__));
            $this->banner_link = 'https://supportgenix.com/pricing/?utm_source=dashboard&utm_medium=admin-notice-bar';
            $this->notice_key = 'support_genix_promo_banner_christmas_2025';

            add_action('admin_notices', function () {
                $screen = get_current_screen();

                // Don't show on Support Genix admin page.
                if ($screen && $screen->id === 'toplevel_page_support-genix') {
                    remove_all_actions('admin_notices');
                    remove_all_actions('all_admin_notices');
                }
            }, ~PHP_INT_MAX);

            // add_action('admin_notices', array($this, 'show_banner'));
            add_action('admin_notices', [$this, 'show_banner'], ~PHP_INT_MAX);
            add_action('wp_ajax_dismiss_support_genix_promo', array($this, 'dismiss_banner'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        }

        /**
         * Set banner properties.
         */
        public function set_banner($image_url, $link_url)
        {
            $this->banner_image = esc_url($image_url);
            $this->banner_link = esc_url($link_url);
        }

        /**
         * Enqueue required scripts.
         */
        public function enqueue_scripts()
        {
            wp_enqueue_script('jquery');
            add_action('admin_footer', array($this, 'add_dismiss_script'));
            add_action('admin_head', array($this, 'add_banner_styles'));
        }

        /**
         * Add banner styles.
         */
        public function add_banner_styles()
        {
?>
            <style>
                .support-genix-promo-banner {
                    position: relative;
                    border-radius: 4px;
                    margin: 10px 23px 10px;
                    background: #f0f6fc !important;
                    box-shadow: 0 0 5px rgb(0 0 0 / 0.1);
                    overflow: hidden;
                    padding: 10px !important;
                    border-left-color: #4a138a;
                }

                .support-genix-promo-banner img {
                    width: 100%;
                    height: auto;
                    display: block;
                    border-radius: 3px;
                }

                .support-genix-promo-banner .notice-dismiss:before {
                    color: #ddd;
                }

                .toplevel_page_support-genix .support-genix-promo-banner {
                    border-radius: 0px;
                    margin: 0;
                    background: #f0f6fc !important;
                    box-shadow: none;
                    border: none;
                }
            </style>
        <?php
        }

        /**
         * Show banner notice.
         */
        public function show_banner()
        {

            $screen = get_current_screen();

            // Don't show on Support Genix admin page
            // if ($screen && $screen->id === 'toplevel_page_support-genix') {
            //     return;
            // }
            // Check if banner should be shown
            if (empty($this->banner_image) || get_option($this->notice_key . '_dismissed')) {
                return;
            }

            $current_time = current_time('timestamp');
            $christmas_start = strtotime('2025-12-22');
            $christmas_end = strtotime('2026-01-12');

            if (
                ($current_time < $christmas_start) ||
                ($current_time > $christmas_end)
            ) {
                return;
            }

            $banner_html = sprintf(
                '<div class="notice notice-info is-dismissible support-genix-promo-banner" data-notice="%s">
                    <a href="%s" target="_blank">
                        <img src="%s" alt="Promotional Banner">
                    </a>',
                esc_attr($this->notice_key),
                esc_url($this->banner_link),
                esc_url($this->banner_image)
            );

            // Add Black Friday message if within date range
            //if ($current_time >= $christmas_start && $current_time <= $christmas_end) {
            // $banner_html .= sprintf(
            //     '<p> 🎉
            //     %s <a href="%s" target="_blank">%s</a></p>',
            //     esc_html__('Support Genix Pro Lifetime Deal is Now LIVE for Only $59 - Limited Time Offer! ', 'support-genix-lite'),
            //     esc_url('https://supportgenix.com/pricing/?utm_source=onedollar&utm_medium=admin-notice-bar'),
            //     esc_html__(' Claim It Now!', 'support-genix-lite')
            // );
            //}

            $banner_html .= '</div>';

            echo wp_kses_post($banner_html);
        }

        /**
         * Add dismiss script.
         */
        public function add_dismiss_script()
        {
        ?>
            <script>
                jQuery(document).ready(function($) {
                    $(document).on('click', '.support-genix-promo-banner .notice-dismiss', function(e) {
                        e.preventDefault();
                        var banner = $(this).closest('.support-genix-promo-banner');
                        var notice = banner.data('notice');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'dismiss_support_genix_promo',
                                notice: notice,
                                nonce: '<?php echo esc_attr(wp_create_nonce('dismiss-promo-banner')); ?>'
                            },
                            success: function() {
                                banner.fadeOut();
                            }
                        });
                    });
                });
            </script>
<?php
        }

        /**
         * Dismiss banner ajax handler.
         */
        public function dismiss_banner()
        {
            if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'dismiss-promo-banner')) {
                wp_die(esc_html__('Invalid nonce', 'support-genix-lite'));
            }

            if (! current_user_can('manage_options')) {
                wp_die(esc_html__('Unauthorized', 'support-genix-lite'));
            }

            $notice = isset($_POST['notice']) ? sanitize_text_field($_POST['notice']) : '';
            if ($notice === $this->notice_key) {
                update_option($this->notice_key . '_dismissed', true);
            }

            wp_die();
        }
    }

    // Initialize the banner
    ApbdWps_Promo::get_instance();
}
