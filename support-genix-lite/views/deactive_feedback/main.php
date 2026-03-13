<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

$ajaxurl = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('sgenix_deactivation_nonce');
?>

<div id="sgenix-deactivation-dialog" class="sgenix-deactivate-overlay" style="display: none;">
    <div class="sgenix-deactivate-modal">
        <!-- Header -->
        <div class="sgenix-deactivate-header">
            <button type="button" class="sgenix-close-btn sgenix-close-dialog" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div class="sgenix-header-content">
                <div class="sgenix-header-icon">
                    <img src="<?php echo esc_url(plugins_url('assets/img/logo-white.png', SUPPORT_GENIX_LITE_FILE_PATH . '/support-genix-lite.php')); ?>" alt="<?php esc_attr_e('Support Genix Logo', 'support-genix-lite'); ?>">
                </div>
                <div class="sgenix-header-text">
                    <h3><?php esc_html_e("We're Sorry to See You Go!", 'support-genix-lite') ?></h3>
                    <p><?php esc_html_e('Your feedback helps us improve Support Genix for everyone.', 'support-genix-lite') ?></p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="sgenix-deactivate-body">
            <p class="sgenix-body-title"><?php esc_html_e('Please share why you\'re deactivating Support Genix:', 'support-genix-lite') ?></p>

            <form id="sgenix-deactivation-feedback-form">
                <div class="sgenix-reasons-list">
                    <!-- Reason 1: Temporary -->
                    <div class="sgenix-reason-item">
                        <input type="radio" name="reason" id="reason_temporary" data-id="" value="<?php esc_attr_e("It's a temporary deactivation", 'support-genix-lite') ?>">
                        <label for="reason_temporary" class="sgenix-reason-label">
                            <span class="sgenix-reason-radio"></span>
                            <span class="sgenix-reason-icon">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                            </span>
                            <span class="sgenix-reason-text">
                                <span><?php esc_html_e("It's a temporary deactivation", 'support-genix-lite') ?></span>
                            </span>
                        </label>
                    </div>

                    <!-- Reason 2: No longer need -->
                    <div class="sgenix-reason-item">
                        <input type="radio" name="reason" id="reason_no_need" data-id="" value="<?php esc_attr_e('I no longer need the plugin', 'support-genix-lite') ?>">
                        <label for="reason_no_need" class="sgenix-reason-label">
                            <span class="sgenix-reason-radio"></span>
                            <span class="sgenix-reason-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M18 6L6 18M6 6l12 12" />
                                </svg>
                            </span>
                            <span class="sgenix-reason-text">
                                <span><?php esc_html_e('I no longer need the plugin', 'support-genix-lite') ?></span>
                            </span>
                        </label>
                    </div>

                    <!-- Reason 3: Found better -->
                    <div class="sgenix-reason-item">
                        <input type="radio" name="reason" id="reason_better" data-id="found_better" value="<?php esc_attr_e('I found a better plugin', 'support-genix-lite') ?>">
                        <label for="reason_better" class="sgenix-reason-label">
                            <span class="sgenix-reason-radio"></span>
                            <span class="sgenix-reason-icon">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="M21 21l-4.35-4.35" />
                                </svg>
                            </span>
                            <span class="sgenix-reason-text">
                                <span><?php esc_html_e('I found a better plugin', 'support-genix-lite') ?></span>
                            </span>
                        </label>
                    </div>
                    <div id="sgenix-found_better-reason-text" class="sgenix-additional-input sgenix-deactivation-reason-input">
                        <textarea name="found_better_reason" placeholder="<?php esc_attr_e('Which plugin are you switching to? We\'d love to know...', 'support-genix-lite') ?>"></textarea>
                    </div>

                    <!-- Reason 4: Not working -->
                    <div class="sgenix-reason-item">
                        <input type="radio" name="reason" id="reason_not_working" data-id="stopped_working" value="<?php esc_attr_e('The plugin suddenly stopped working', 'support-genix-lite') ?>">
                        <label for="reason_not_working" class="sgenix-reason-label">
                            <span class="sgenix-reason-radio"></span>
                            <span class="sgenix-reason-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                    <line x1="12" y1="9" x2="12" y2="13" />
                                    <line x1="12" y1="17" x2="12.01" y2="17" />
                                </svg>
                            </span>
                            <span class="sgenix-reason-text">
                                <span><?php esc_html_e('The plugin suddenly stopped working', 'support-genix-lite') ?></span>
                            </span>
                        </label>
                    </div>
                    <div id="sgenix-stopped_working-reason-text" class="sgenix-additional-input sgenix-deactivation-reason-input">
                        <textarea name="stopped_working_reason" placeholder="<?php esc_attr_e('Please describe the issue you\'re experiencing...', 'support-genix-lite') ?>"></textarea>
                    </div>

                    <!-- Reason 5: Bug -->
                    <div class="sgenix-reason-item">
                        <input type="radio" name="reason" id="reason_bug" data-id="found_bug" value="<?php esc_attr_e('I encountered an error or bug', 'support-genix-lite') ?>">
                        <label for="reason_bug" class="sgenix-reason-label">
                            <span class="sgenix-reason-radio"></span>
                            <span class="sgenix-reason-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
                                </svg>
                            </span>
                            <span class="sgenix-reason-text">
                                <span><?php esc_html_e('I encountered an error or bug', 'support-genix-lite') ?></span>
                            </span>
                        </label>
                    </div>
                    <div id="sgenix-found_bug-reason-text" class="sgenix-additional-input sgenix-deactivation-reason-input">
                        <textarea name="found_bug_reason" placeholder="<?php esc_attr_e('Please describe the error/bug. This will help us fix it...', 'support-genix-lite') ?>"></textarea>
                    </div>

                    <!-- Reason 6: Other -->
                    <div class="sgenix-reason-item">
                        <input type="radio" name="reason" id="reason_other" data-id="other" value="<?php esc_attr_e('Other', 'support-genix-lite') ?>">
                        <label for="reason_other" class="sgenix-reason-label">
                            <span class="sgenix-reason-radio"></span>
                            <span class="sgenix-reason-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
                                </svg>
                            </span>
                            <span class="sgenix-reason-text">
                                <span><?php esc_html_e('Other', 'support-genix-lite') ?></span>
                            </span>
                        </label>
                    </div>
                    <div id="sgenix-other-reason-text" class="sgenix-additional-input sgenix-deactivation-reason-input">
                        <textarea name="other_reason" placeholder="<?php esc_attr_e('Please share the reason...', 'support-genix-lite') ?>"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="sgenix-deactivate-footer">
                    <a href="#" class="sgenix-btn sgenix-btn-skip sgenix-skip-feedback"><?php esc_html_e('Skip & Deactivate', 'support-genix-lite') ?></a>
                    <button type="submit" class="sgenix-btn sgenix-btn-submit">
                        <span><?php esc_html_e('Submit & Deactivate', 'support-genix-lite') ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    ;
    jQuery(document).ready(function($) {
        let pluginToDeactivate = '';

        function closeDialog() {
            $('#sgenix-deactivation-dialog').animate({
                opacity: 0
            }, 'slow', function() {
                $(this).css('display', 'none');
                $('body').css('overflow', '');
            });
            pluginToDeactivate = '';
        }

        // Open dialog when deactivate is clicked
        $('[data-slug="<?php echo esc_attr($this->PROJECT_SLUG); ?>"] .deactivate a').on('click', function(e) {
            e.preventDefault();
            pluginToDeactivate = $(this).attr('href');
            $('body').css('overflow', 'hidden');
            $('#sgenix-deactivation-dialog').css({
                'display': 'flex',
                'opacity': '1'
            });
        });

        // Close dialog on X button click
        $('.sgenix-close-dialog').on('click', closeDialog);

        // Close dialog on overlay click
        $('#sgenix-deactivation-dialog').on('click', function(e) {
            if (e.target === this) {
                closeDialog();
            }
        });

        // Prevent close when clicking modal content
        $('.sgenix-deactivate-modal').on('click', function(e) {
            e.stopPropagation();
        });

        // Handle radio button change - show/hide textarea
        $('input[name="reason"]').on('change', function() {
            $('.sgenix-deactivation-reason-input').removeClass('active').hide();

            const id = $(this).data('id');
            if (['other', 'found_better', 'stopped_working', 'found_bug'].includes(id)) {
                $(`#sgenix-${id}-reason-text`).addClass('active').show();
                $(`#sgenix-${id}-reason-text textarea`).focus();
            }
        });

        // Handle form submission
        $('#sgenix-deactivation-feedback-form').on('submit', function(e) {
            e.preventDefault();

            const $submitButton = $(this).find('button[type="submit"]');
            const $buttonText = $submitButton.find('span');
            const originalText = $buttonText.text();

            $buttonText.text('<?php esc_html_e('Submitting...', 'support-genix-lite') ?>');
            $submitButton.prop('disabled', true);

            const reason = $('input[name="reason"]:checked').val() || 'No reason selected';
            const message = $('.sgenix-deactivation-reason-input.active textarea').val() || '';

            const data = {
                action: 'sgenix_deactivation_feedback',
                reason: reason,
                message: message,
                nonce: '<?php echo esc_js($nonce); ?>'
            };

            $.post('<?php echo esc_url_raw($ajaxurl); ?>', data)
                .done(function(response) {
                    if (response.success) {
                        window.location.href = pluginToDeactivate;
                    } else {
                        console.error('Feedback submission failed:', response.data);
                        $buttonText.text(originalText);
                        $submitButton.prop('disabled', false);
                    }
                })
                .fail(function(xhr) {
                    console.error('Feedback submission failed:', xhr.responseText);
                    $buttonText.text(originalText);
                    $submitButton.prop('disabled', false);
                });
        });

        // Skip feedback and deactivate
        $('.sgenix-skip-feedback').on('click', function(e) {
            e.preventDefault();
            window.location.href = pluginToDeactivate;
        });
    });
</script>

<style>
    /* Overlay */
    #sgenix-deactivation-dialog.sgenix-deactivate-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: flex-start;
        justify-content: center;
        z-index: 999999;
        overflow-y: auto;
        animation: sgenixFadeIn 0.3s ease;
    }

    @keyframes sgenixFadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes sgenixFadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
        }
    }

    @keyframes sgenixSlideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes sgenixSlideDown {
        from {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        to {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
    }

    /* Closing animation classes */
    #sgenix-deactivation-dialog.sgenix-deactivate-overlay.sgenix-closing {
        animation: sgenixFadeOut 0.25s ease forwards;
    }

    #sgenix-deactivation-dialog.sgenix-closing .sgenix-deactivate-modal {
        animation: sgenixSlideDown 0.25s ease forwards;
    }

    /* Modal Container */
    .sgenix-deactivate-modal {
        background: #ffffff;
        border-radius: 8px;
        width: 480px;
        max-width: 95%;
        margin: auto 0;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        animation: sgenixSlideUp 0.4s ease;
    }

    /* Header */
    .sgenix-deactivate-header {
        background: linear-gradient(135deg, #0aaa53 0%, #0bbc5c 50%, #1dcc6b 100%);
        padding: 20px 28px;
        position: relative;
        overflow: hidden;
    }

    .sgenix-deactivate-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .sgenix-deactivate-header::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: 10%;
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
    }

    .sgenix-header-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .sgenix-header-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sgenix-header-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .sgenix-header-text h3 {
        color: #ffffff;
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 2px 0;
    }

    .sgenix-header-text p {
        color: rgba(255, 255, 255, 0.85);
        font-size: 12px;
        font-weight: 400;
        margin: 0;
    }

    /* Close Button */
    .sgenix-close-btn {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 28px;
        height: 28px;
        background: rgba(255, 255, 255, 0.15);
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 2;
        padding: 0;
    }

    .sgenix-close-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: rotate(90deg);
    }

    .sgenix-close-btn svg {
        width: 16px;
        height: 16px;
        stroke: #ffffff;
        stroke-width: 2;
    }

    /* Body Content */
    .sgenix-body-title {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
        margin: 20px 0 0 0;
        padding: 0 25px;
    }

    /* Reason Options */
    .sgenix-reasons-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 20px 25px;
    }

    .sgenix-reason-item {
        position: relative;
    }

    .sgenix-reason-item input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .sgenix-reason-label {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        background: #ffffff;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .sgenix-reason-label:hover {
        border-color: #0bbc5c;
        background: #f0faf4;
    }

    .sgenix-reason-item input[type="radio"]:checked+.sgenix-reason-label {
        border-color: #0bbc5c;
        background: #f0faf4;
        box-shadow: 0 2px 8px rgba(11, 188, 92, 0.15);
    }

    .sgenix-reason-radio {
        width: 14px;
        height: 14px;
        min-width: 14px;
        border: 2px solid #d1d5db;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.25s ease;
    }

    .sgenix-reason-item input[type="radio"]:checked+.sgenix-reason-label .sgenix-reason-radio {
        border-color: #0bbc5c;
        background: #0bbc5c;
    }

    .sgenix-reason-radio::after {
        content: '';
        width: 6px;
        height: 6px;
        background: #ffffff;
        border-radius: 50%;
        transform: scale(0);
        transition: transform 0.2s ease;
    }

    .sgenix-reason-item input[type="radio"]:checked+.sgenix-reason-label .sgenix-reason-radio::after {
        transform: scale(1);
    }

    .sgenix-reason-icon {
        width: 30px;
        height: 30px;
        min-width: 30px;
        background: #e6f7ed;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.25s ease;
    }

    .sgenix-reason-item input[type="radio"]:checked+.sgenix-reason-label .sgenix-reason-icon {
        background: #0bbc5c;
    }

    .sgenix-reason-icon svg {
        width: 15px;
        height: 15px;
        stroke: #0bbc5c;
        stroke-width: 2;
        fill: none;
        transition: all 0.25s ease;
    }

    .sgenix-reason-item input[type="radio"]:checked+.sgenix-reason-label .sgenix-reason-icon svg {
        stroke: #ffffff;
    }

    .sgenix-reason-text {
        flex: 1;
    }

    .sgenix-reason-text span {
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        display: block;
        line-height: 1.3;
    }

    /* Additional Input Field */
    .sgenix-additional-input.sgenix-deactivation-reason-input {
        margin-top: 6px;
        margin-bottom: 6px;
        display: none;
        animation: sgenixSlideDown 0.3s ease;
    }

    .sgenix-additional-input.sgenix-deactivation-reason-input.active {
        display: block;
    }

    @keyframes sgenixSlideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .sgenix-additional-input textarea {
        width: 100%;
        min-height: 70px;
        padding: 12px 14px;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        font-size: 13px;
        font-family: inherit;
        resize: vertical;
        transition: all 0.25s ease;
        background: #ffffff;
        box-sizing: border-box;
    }

    .sgenix-additional-input textarea:focus {
        outline: none;
        border-color: #0bbc5c;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(11, 188, 92, 0.1);
    }

    .sgenix-additional-input textarea::placeholder {
        color: #94a3b8;
    }

    /* Footer */
    .sgenix-deactivate-footer {
        padding: 16px 28px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-top: 1px solid #e5e5e5;
        margin-top: 10px;
    }

    .sgenix-btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .sgenix-btn-skip {
        background: transparent;
        color: #6B7280;
        padding: 10px 0;
    }

    .sgenix-btn-skip:hover {
        color: #0bbc5c;
    }

    .sgenix-btn-submit {
        background: #0bbc5c;
        border: 1px solid #0bbc5c;
        color: #ffffff;
    }

    .sgenix-btn-submit:hover {
        background: #0aaa53;
        border-color: #0aaa53;
        color: #ffffff;
    }

    .sgenix-btn-submit:active {
        background: #09994b;
    }

    .sgenix-btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .sgenix-btn-submit svg {
        width: 14px;
        height: 14px;
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .sgenix-deactivate-modal {
            margin: 16px;
        }

        .sgenix-deactivate-header {
            padding: 16px 20px;
        }

        .sgenix-deactivate-body {
            padding: 16px 20px;
        }

        .sgenix-deactivate-footer {
            padding: 14px 20px 18px;
            flex-direction: column;
        }

        .sgenix-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>