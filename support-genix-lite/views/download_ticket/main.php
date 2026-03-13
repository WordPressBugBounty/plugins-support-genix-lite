<?php

/** @var $detailsObj */
/** @var $ticketObj */
/** @var $userObj */
/** @var $replied */
/** @var $fields */

// Creator
$userName = trim($userObj->first_name . ' ' . $userObj->last_name);
$userName = (0 < strlen($userName) ? $userName : $userObj->display_name);
$userEmail = current_user_can('show-ticket-email') ? sprintf('[%1$s]', $userObj->email) : '';
$userInfo = trim($userName . ' ' . $userEmail);

// Category
$categoryId = $ticketObj->cat_id;
$cateogryObj = Mapbd_wps_ticket_category::FindBy('id', $categoryId);
$categoryTitle = ((is_object($cateogryObj) && isset($cateogryObj->title)) ? sanitize_text_field($cateogryObj->title) : '');

// Track ID
$ticket_track_id = apply_filters('apbd-wps/filter/display-track-id', $ticketObj->ticket_track_id);

// Assigned
$assignedOn = ApbdWps_GetUserTitleById($ticketObj->assigned_on);

// Status
$statusText = $ticketObj->getTextByKey('status');

// Custom fields
$fields = $detailsObj->custom_fields;
?>
<div class="sg_dt__header">
    <p class="sg_dt__title"><?php echo esc_html($ticketObj->title); ?></p>
    <div class="sg_dt__info">
        <div class="sg_dt__row sg_dt__justify-content-between">
            <div class="sg_dt__col-auto">
                <p class="sg_dt__info-item"><?php echo wp_kses_post(sprintf('<b>%1$s</b>: %2$s', $obj->__('Created at'), $ticketObj->opened_time)); ?></p>
            </div>
            <div class="sg_dt__col-auto">
                <?php
                if ($ticket_track_id) {
                ?>
                    <p class="sg_dt__info-item"><?php echo wp_kses_post(sprintf('%1$d %2$s | #%3$s', $ticketObj->reply_counter, $obj->__('Replied'), $ticket_track_id)); ?></p>
                <?php
                } else {
                ?>
                    <p class="sg_dt__info-item"><?php echo wp_kses_post(sprintf('%1$d %2$s', $ticketObj->reply_counter, $obj->__('Replied'))); ?></p>
                <?php
                }
                ?>
            </div>
        </div>
        <p class="sg_dt__info-item"><?php echo wp_kses_post(sprintf('<b>%1$s</b>: %2$s', $obj->__('Created by'), $userInfo)); ?></p>
        <?php
        if (0 < strlen($assignedOn)) {
        ?>
            <p class="sg_dt__info-item"><?php echo wp_kses_post(sprintf('<b>%1$s</b>: %2$s', $obj->__('Assigned on'), $assignedOn)); ?></p>
        <?php
        }
        if (0 < strlen($categoryTitle)) {
        ?>
            <p class="sg_dt__info-item"><?php echo wp_kses_post(sprintf('<b>%1$s</b>: %2$s', $obj->__('Category'), $categoryTitle)); ?></p>
        <?php
        }
        if (0 < strlen($statusText)) {
        ?>
            <p class="sg_dt__info-item"><?php echo wp_kses_post(sprintf('<b>%1$s</b>: %2$s', $obj->__('Status'), $statusText)); ?></p>
        <?php
        }
        ?>
    </div>
</div>
<div class="sg_dt__body">
    <div class="sg_dt__reply-list">
        <?php
        foreach ($replies as $replyObj) {
            $replyUserObj = $replyObj->reply_user;
            $replyUserName = trim($replyUserObj->first_name . ' ' . $replyUserObj->last_name);
            $replyUserName = 0 < strlen($replyUserName) ? $replyUserName : $replyUserObj->display_name;

            $replyClass = 'sg_dt__reply';
            $badgeText = $obj->__('Thread Starter');

            if ('A' === $replyObj->replied_by_type) {
                $replyClass .= ' sg_dt__reply-agent';
                $badgeText = $obj->__('Support Agent');
            }
        ?>
            <div class="<?php echo esc_attr($replyClass); ?>">
                <div class="sg_dt__reply-wrap">
                    <div class="sg_dt__reply-head">
                        <p class="sg_dt__reply-badge"><?php echo esc_html($badgeText); ?></p>
                        <p class="sg_dt__reply-user"><b><?php echo esc_html($replyUserName); ?></b></p>
                        <p class="sg_dt__reply-date"><?php echo esc_html($replyObj->reply_time); ?></p>
                    </div>
                    <div class="sg_dt__reply-body">
                        <div class="sg_dt__reply-text"><?php echo ApbdWps_KsesEmailHtml($replyObj->reply_text); ?></div>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</div>
<?php
if (! empty($fields)) {
?>
    <div class="sg_dt__footer">
        <div class="sg_dt__field-list">
            <?php
            $dGroupFound = false;
            $wGroupFound = false;
            $eGroupFound = false;
            $lGroupFound = false;

            foreach ($fields as $field) {
                $fieldId = $field->id;
                $fieldStatus = $field->status;
                $fieldCategories = $field->categories;
                $fieldLabel = $field->field_label;
                $fieldValue = $field->field_value;
                $fieldType = $field->field_type;
                $inputName = $field->input_name;

                if ('A' !== $fieldStatus || (! $categoryId && ! empty($fieldCategories) && ! in_array($categoryId, $fieldCategories))) {
                    continue;
                }

                switch ($fieldId) {
                    case 'wc_store_id':
                        $storeId = intval($fieldValue);

                        if ($storeId) {
                            $storeData = Mapbd_wps_woocommerce::FindBy('id', $storeId);

                            if (is_object($storeData) && ! empty($storeData)) {
                                $storeTitle = $storeData->store_title;
                                $storeUrl = $storeData->store_url;
                                $fieldValue = $storeUrl ? trim($storeTitle . ' ' . '[' . $storeUrl . ']') : trim($storeTitle);
                            }
                        }
                        break;
                }

                switch ($fieldType) {
                    case 'S':
                        $fieldValue = (1 === intval($fieldValue) ? $obj->__('On') : $obj->__('Off'));
                        break;
                }

                if (! strlen($fieldValue)) {
                    continue;
                }

                $groupTitle = '';

                if (! $dGroupFound && preg_match('/^D\d/', $inputName)) {
                    $groupTitle = $obj->__('Additional Data');
                    $dGroupFound = true;
                }

                if (! $wGroupFound && 'wc_store_id' === $inputName) {
                    $groupTitle = $obj->__('WooCommerce');
                    $wGroupFound = true;
                }

                if (! $eGroupFound && preg_match('/^L\d/', $inputName)) {
                    $groupTitle = $obj->__('Envato');
                    $eGroupFound = true;
                }

                if (! $lGroupFound && preg_match('/^L\d/', $inputName)) {
                    $groupTitle = $obj->__('Elite Licenser');
                    $lGroupFound = true;
                }

                if ($groupTitle) {
            ?>
                    <p class="sg_dt__field-group"><b><?php echo esc_html($groupTitle); ?></b></p>
                <?php
                }
                ?>
                <p class="sg_dt__field"><?php echo wp_kses_post(sprintf('<b>%1$s</b>: %2$s', $fieldLabel, $fieldValue)); ?></p>
            <?php
            }
            ?>
        </div>
    </div>
<?php
}
?>