<?php

/**
 * Help me write.
 */

defined('ABSPATH') || exit;

require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_ai_proxy_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_help_me_write_generate_trait.php';

class Apbd_wps_help_me_write extends ApbdWpsBaseModuleLite
{
    use Apbd_wps_ai_proxy_trait;
    use Apbd_wps_help_me_write_generate_trait;

    public function initialize()
    {
        parent::initialize();

        $this->initialize__generate();
    }

    public function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $status = sanitize_text_field($this->GetOption('status', 'I'));
        $ai_tools = $this->GetOption('ai_tools', '');

        $status = ('A' === $status) ? true : false;
        $ai_tools = maybe_unserialize($ai_tools);

        // Default to ai_proxy if no tools configured
        if (empty($ai_tools) || !is_array($ai_tools)) {
            $ai_tools = ['ai_proxy'];
        }

        $data = [
            'status' => $status,
            'ai_tools' => $ai_tools,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallback()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;
        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

            if ('A' === $status) {
                $ai_tools = sanitize_text_field(ApbdWps_PostValue('ai_tools', ''));

                // AI tools.
                $ai_tools = explode(',', $ai_tools);
                $ai_tools = array_filter($ai_tools, function ($value) {
                    return ('ai_proxy' === $value || 'openai' === $value || 'claude' === $value);
                });

                if (empty($ai_tools)) {
                    $hasError = true;
                }

                $this->AddIntoOption('status', 'A');
                $this->AddIntoOption('ai_tools', maybe_serialize($ai_tools));
            } else {
                $this->AddIntoOption('status', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }
}
