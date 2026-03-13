<?php

/**
 * Metabox Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_metabox_trait
{
    public function initialize__metabox()
    {
        add_action('add_meta_boxes', array($this, 'add_chatbot_metabox'));
        add_action('save_post_sgkb-docs', array($this, 'save_chatbot_metabox'));
    }

    public function add_chatbot_metabox()
    {
        add_meta_box(
            'sgkb_chatbot_options',
            $this->__('Chatbot'),
            array($this, 'render_chatbot_metabox'),
            'sgkb-docs',
            'side',
            'default'
        );
    }

    public function render_chatbot_metabox($post)
    {
        $only_for_chatbot = get_post_meta($post->ID, 'only_for_chatbot', true);
?>
        <label for="only_for_chatbot">
            <input
                type="checkbox"
                id="only_for_chatbot"
                name="only_for_chatbot"
                value="1"
                <?php checked($only_for_chatbot, '1'); ?> />
            <?php $this->_e('Only for Chatbot'); ?>
        </label>
        <p class="description" style="margin-top: 12px;">
            <?php $this->_e('If checked, this doc will not be visible on the website, but it will provide data only to the chatbot.'); ?>
        </p>
<?php
        wp_nonce_field('chatbot_metabox_nonce', '_chatbot_metabox_nonce');
    }

    public function save_chatbot_metabox($post_id)
    {
        if (
            !isset($_POST['_chatbot_metabox_nonce']) ||
            !wp_verify_nonce($_POST['_chatbot_metabox_nonce'], 'chatbot_metabox_nonce') ||
            !current_user_can('edit_post', $post_id) ||
            ('sgkb-docs' !== get_post_type($post_id)) ||
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        ) {
            return;
        }

        if (
            isset($_POST['only_for_chatbot']) &&
            ('1' == $_POST['only_for_chatbot'])
        ) {
            update_post_meta($post_id, 'only_for_chatbot', '1');
        } else {
            delete_post_meta($post_id, 'only_for_chatbot');
        }
    }

    public function is_only_for_chatbot($post_id)
    {
        $value = get_post_meta($post_id, 'only_for_chatbot', true);
        $value = ('1' === $value);

        return $value;
    }
}
