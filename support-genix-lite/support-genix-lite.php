<?php
/*
Plugin Name: Support Genix Lite
Plugin URI: http://supportgenix.com
Description: Helpdesk, AI Chatbot, Knowledge Base & Customer Support Ticketing System.
Version: 1.4.43
Author: Support Genix
Author URI: https://supportgenix.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: support-genix-lite
Domain Path: /languages/
*/

defined('ABSPATH') || exit;

global $wpdb;
$apbdWpSupportLiteLoad = false;
$apbdWpSupportLiteFile = __FILE__;
$apbdWpSupportLitePath = dirname($apbdWpSupportLiteFile);
$apbdWpSupportLiteVersion = '1.4.43';

if (!defined('SUPPORT_GENIX_LITE_FILE_PATH')) {
    define('SUPPORT_GENIX_LITE_FILE_PATH', $apbdWpSupportLitePath);
}

if (!defined('SUPPORT_GENIX_LITE_VERSION')) {
    define('SUPPORT_GENIX_LITE_VERSION', $apbdWpSupportLiteVersion);
}

include_once ABSPATH . 'wp-admin/includes/plugin.php';
include_once $apbdWpSupportLitePath . '/appcore/ApbdWps_DiagnosticData.php';
include_once $apbdWpSupportLitePath . '/appcore/ApbdWps_LoaderLite.php';
// include_once $apbdWpSupportLitePath . '/appcore/ApbdWps_OfferLite.php';
include_once $apbdWpSupportLitePath . '/appcore/ApbdWps_HTNewsAPI.php';
include_once $apbdWpSupportLitePath . '/appcore/ApbdWps_DeactiveFeedback.php';

$apbdWpSupportLiteLoad = ApbdWps_LoaderLite::isReadyToLoad($apbdWpSupportLiteFile, $apbdWpSupportLiteVersion);

if (true === $apbdWpSupportLiteLoad) {
    include_once $apbdWpSupportLitePath . '/appcore/ApbdWps_Promo.php';

    include_once $apbdWpSupportLitePath . '/core/helper.php';
    include_once $apbdWpSupportLitePath . '/appcore/plugin_helper.php';
    include_once $apbdWpSupportLitePath . '/appcore/docs_helper.php';
    include_once $apbdWpSupportLitePath . '/appcore/ApbdWps_SupportLite.php';
    include_once $apbdWpSupportLitePath . '/templates/functions.php';

    $apbdWpSupportLitePos = new ApbdWps_SupportLite($apbdWpSupportLiteFile, $apbdWpSupportLiteVersion);
    $apbdWpSupportLitePos->StartPlugin();

    // Deactivation hook.
    register_deactivation_hook($apbdWpSupportLiteFile, [$apbdWpSupportLitePos, 'OnDeactive']);
}
