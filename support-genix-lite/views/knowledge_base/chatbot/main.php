<?php

/**
 * Main.
 */

defined('ABSPATH') || exit;

/**
 * @var Apbd_wps_knowledge_base
 */

// Build iframe URL without nonce (using secure cookie-based authentication)
// Adjust to current host so iframe origin matches parent (fixes www/non-www X-Frame-Options block)
$home_url = ApbdWps_AdjustUrlToCurrentHost(get_home_url());
$iframeBase = trailingslashit((false !== strpos($home_url, '?')) ? substr($home_url, 0, (strpos($home_url, '?'))) : $home_url);
$page_url = home_url(add_query_arg(array(), wp_unslash($_SERVER['REQUEST_URI'])));
$page_url = strtok($page_url, '?');
$iframeUrl = add_query_arg(array('chatbot_iframe' => 1, 'page_url' => $page_url), $iframeBase);
?>
<style type="text/css">
    #support-genix-chatbot-iframe-container,
    #support-genix-chatbot-iframe-container::before,
    #support-genix-chatbot-iframe-container::after,
    #support-genix-chatbot-iframe-container *,
    #support-genix-chatbot-iframe-container *::before,
    #support-genix-chatbot-iframe-container *::after {
        box-sizing: border-box !important;
        line-height: 0 !important;
    }

    #support-genix-chatbot-iframe-container {
        display: initial !important;
        position: fixed !important;
        z-index: 99999 !important;
        bottom: 0 !important;
        right: 0 !important;
        max-width: 100vw !important;
        max-height: 100vh !important;
    }

    #support-genix-chatbot-iframe {
        display: initial !important;
        width: 100% !important;
        height: 100% !important;
        border: none !important;
        position: absolute !important;
        bottom: 0 !important;
        right: 0 !important;
        background: transparent !important;
    }
</style>
<div id="support-genix-chatbot-iframe-container" role="region" aria-label="<?php echo esc_attr(__('Chatbot Widget', 'support-genix')); ?>" style="width: 96px; height: 96px;">
    <iframe id="support-genix-chatbot-iframe" src="<?php echo esc_url($iframeUrl); ?>" title="<?php echo esc_attr(__('Chatbot Widget', 'support-genix')); ?>" allowfullscreen></iframe>
</div>