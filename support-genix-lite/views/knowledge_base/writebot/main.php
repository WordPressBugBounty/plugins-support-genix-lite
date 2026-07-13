<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial linted in isolation; its template-scoped variables are not global and cannot be individually prefixed.

/**
 * Main.
 */

defined('ABSPATH') || exit;

/**
 * @var Apbd_wps_knowledge_base
 */

$ai_tools = maybe_unserialize(Apbd_wps_knowledge_base::GetModuleOption('write_with_ai_tools', ''));

if (empty($ai_tools) || !is_array($ai_tools)) {
    return;
}

$available_tools = Apbd_wps_settings::GetAvailableAITools();
$ai_proxy_status = 'I';
$openai_status = 'I';
$claude_status = 'I';

foreach ($ai_tools as $tool) {
    if ('ai_proxy' === $tool && $available_tools['ai_proxy']) {
        $ai_proxy_status = 'A';
    }

    if ('openai' === $tool && $available_tools['openai']) {
        $openai_status = 'A';
    }

    if ('claude' === $tool && $available_tools['claude']) {
        $claude_status = 'A';
    }
}

// Count how many tools are active
$active_tools = [];
if ('A' === $ai_proxy_status) {
    $active_tools['ai_proxy'] = __('Support Genix AI', 'support-genix-lite');
}
if ('A' === $openai_status) {
    $active_tools['openai'] = __('OpenAI', 'support-genix-lite');
}
if ('A' === $claude_status) {
    $active_tools['claude'] = __('Claude', 'support-genix-lite');
}

// If no tools are active, don't render the modal
if (empty($active_tools)) {
    return;
}
?>
<div class="sgkb-writebot-modal-wrapper sgkb-writebot-hidden">
    <div class="sgkb-writebot-modal">
        <div class="sgkb-writebot-modal-header">
            <h2><?php echo esc_html__('Generate Content with AI', 'support-genix-lite'); ?></h2>
            <button class="sgkb-writebot-close-button">✕</button>
        </div>
        <div class="sgkb-writebot-modal-content">
            <div class="sgkb-writebot-form-container">
                <div class="sgkb-writebot-error sgkb-writebot-hidden"></div>
                <form id="sgkb-writebot-form">
                    <?php
                    if (count($active_tools) > 1) {
                        // Multiple tools available - show dropdown
                    ?>
                        <div class="sgkb-writebot-form-group">
                            <label for="sgkb-writebot-tool"><?php echo esc_html__('Tool:', 'support-genix-lite'); ?></label>
                            <select id="sgkb-writebot-tool">
                                <?php foreach ($active_tools as $tool_key => $tool_label) : ?>
                                    <option value="<?php echo esc_attr($tool_key); ?>"><?php echo esc_html($tool_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php
                    } else {
                        // Single tool - use hidden input
                        $single_tool = array_key_first($active_tools);
                    ?>
                        <input type="hidden" id="sgkb-writebot-tool" value="<?php echo esc_attr($single_tool); ?>">
                    <?php
                    }
                    ?>
                    <div class="sgkb-writebot-form-group">
                        <label for="sgkb-writebot-title"><?php echo esc_html__('Title:', 'support-genix-lite'); ?></label>
                        <input type="text" id="sgkb-writebot-title" placeholder="<?php echo esc_attr__('Enter a descriptive title', 'support-genix-lite'); ?>" required>
                    </div>
                    <div class="sgkb-writebot-form-group">
                        <label for="sgkb-writebot-keywords"><?php echo esc_html__('Keywords:', 'support-genix-lite'); ?></label>
                        <input type="text" id="sgkb-writebot-keywords" placeholder="<?php echo esc_attr__('Enter keywords separated by commas', 'support-genix-lite'); ?>" required>
                    </div>
                    <div class="sgkb-writebot-form-group">
                        <label for="sgkb-writebot-prompt"><?php echo esc_html__('Prompt:', 'support-genix-lite'); ?></label>
                        <textarea id="sgkb-writebot-prompt" rows="5" required></textarea>
                        <p class="sgkb-writebot-help-text"><?php echo esc_html__('The prompt will be automatically generated based on your title and keywords.', 'support-genix-lite'); ?></p>
                    </div>
                    <div class="sgkb-writebot-form-group sgkb-writebot-checkbox-wrapper">
                        <div class="sgkb-writebot-checkbox-label">
                            <label for="sgkb-writebot-overwrite"><?php echo esc_html__('Overwrite existing content:', 'support-genix-lite'); ?></label>
                            <p class="sgkb-writebot-help-text"><?php echo esc_html__('Replace existing content with AI generated content', 'support-genix-lite'); ?></p>
                        </div>
                        <div class="sgkb-writebot-toggle">
                            <input type="checkbox" id="sgkb-writebot-overwrite">
                            <label for="sgkb-writebot-overwrite" class="sgkb-writebot-toggle-label"></label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="sgkb-writebot-modal-footer">
            <button class="sgkb-writebot-generate-button">
                <?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal SVG icon markup. */ echo ApbdWps_GetSvgIcon('wand-magic-sparkles'); ?>
                <span><?php echo esc_html__('Generate Content', 'support-genix-lite'); ?></span>
            </button>
        </div>
    </div>
    <div class="sgkb-writebot-modal-overlay"></div>
</div>
