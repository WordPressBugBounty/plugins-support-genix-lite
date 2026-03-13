<?php

/**
 * Report email template.
 */
defined('ABSPATH') || exit;

// Site title.
$site_title = get_bloginfo('name');
$site_url = get_home_url();

// Date range.
$dateStartLocal = !empty($dateStartLocal) ? $dateStartLocal : '';
$dateEndLocal = !empty($dateEndLocal) ? $dateEndLocal : '';

// Category data.
$categoryData = !empty($categoryData) ? $categoryData : [];
$categoryTableData = isset($categoryData['table_data']) ? $categoryData['table_data'] : [];
$categoryTableBody = isset($categoryTableData['body']) ? $categoryTableData['body'] : [];

// Agent data.
$agentData = !empty($agentData) ? $agentData : [];
$agentTableData = isset($agentData['table_data']) ? $agentData['table_data'] : [];
$agentTableBody = isset($agentTableData['body']) ? $agentTableData['body'] : [];

// Total data.
$totalData = isset($categoryData['total_data']) ? $categoryData['total_data'] : [];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title></title>
</head>

<body data-start="start-here" itemscope itemtype="http://schema.org/EmailMessage" style="margin: 0; padding: 0;">
    <table role="presentation" class="email-wrapper" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;color:#3b4049;width:100%;background-color:#f5f5f5;">
        <tbody>
            <tr>
                <td class="email-wrapper-td" align="center" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;color:#3b4049;">
                    <table class="email-content" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;width:100%;">
                        <tbody>
                            <tr>
                                <td class="email-top" width="100%" cellpadding="5" cellspacing="0" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;color:#3b4049;padding-top:20px;padding-bottom:20px;padding-right:0;padding-left:0;">
                                    <table class="email-top-inner" align="center" width="600" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;width:600px;">
                                        <tbody>
                                            <tr>
                                                <td class="email-top-content" style="text-align:center;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;color:#3b4049;font-size:14px;line-height:24px;"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="email-body" width="100%" cellpadding="0" cellspacing="0" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;color:#3b4049;">
                                    <table class="email-body-inner" align="center" width="600" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;width:600px;">
                                        <tbody>
                                            <tr>
                                                <td class="email-body-content" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;color:#3b4049;font-size:14px;line-height:24px;padding-top:0;padding-bottom:0;padding-right:15px;padding-left:15px;">
                                                    <div class="email-body-main" style="border-width:1px;border-style:solid;border-color:#eeece4;background-color:#fff;border-radius:5px;padding-top:40px;padding-bottom:40px;padding-right:40px;padding-left:40px;overflow:hidden;">
                                                        <p class="email-body-title" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:0;color:#3b4049;font-size:18px;line-height:22px;font-weight:600;"><?php $this->_e('Support Performance Insights'); ?></p>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><br></p>
                                                        <p class="email-body-desc" style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><?php $this->_ee('Your support performance metrics for %s from %s to %s are summarized below.', '<strong>' . $site_title . '</strong>', '<strong>' . $dateStartLocal . '</strong>', '<strong>' . $dateEndLocal . '</strong>'); ?></p>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><br></p>
                                                        <table class="email-body-summary" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;width:100%;">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="padding-top:0;padding-bottom:0;padding-right:6px;padding-left:0;">
                                                                        <div class="email-summary-item" style="padding-top:8px;padding-bottom:8px;padding-right:8px;padding-left:8px;background-color:#e2edff;border-width:1px;border-style:solid;border-color:#e2edff;border-radius:5px;margin-top:4px;margin-bottom:4px;margin-right:0;margin-left:0;">
                                                                            <p class="email-summary-item-title" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:0;color:#3b4049;font-size:14px;line-height:18px;font-weight:500;text-transform:uppercase;"><?php $this->_e('Tickets'); ?></p>
                                                                            <p class="email-summary-item-count" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:0;color:#3b4049;font-size:24px;line-height:28px;font-weight:600;"><?php echo esc_html($totalData['tickets']) ?></p>
                                                                        </div>
                                                                    </td>
                                                                    <td style="padding-top:0;padding-bottom:0;padding-right:3px;padding-left:3px;">
                                                                        <div class="email-summary-item" style="padding-top:8px;padding-bottom:8px;padding-right:8px;padding-left:8px;background-color:#e0f7f6;border-width:1px;border-style:solid;border-color:#e0f7f6;border-radius:5px;margin-top:4px;margin-bottom:4px;margin-right:0;margin-left:0;">
                                                                            <p class="email-summary-item-title" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:0;color:#3b4049;font-size:14px;line-height:18px;font-weight:500;text-transform:uppercase;"><?php $this->_e('Responses'); ?></p>
                                                                            <p class="email-summary-item-count" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:0;color:#3b4049;font-size:24px;line-height:28px;font-weight:600;"><?php echo esc_html($totalData['responses']) ?></p>
                                                                        </div>
                                                                    </td>
                                                                    <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:6px;">
                                                                        <div class="email-summary-item" style="padding-top:8px;padding-bottom:8px;padding-right:8px;padding-left:8px;background-color:#ffe8e2;border-width:1px;border-style:solid;border-color:#ffe8e2;border-radius:5px;margin-top:4px;margin-bottom:4px;margin-right:0;margin-left:0;">
                                                                            <p class="email-summary-item-title" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:0;color:#3b4049;font-size:14px;line-height:18px;font-weight:500;text-transform:uppercase;"><?php $this->_e('Closed'); ?></p>
                                                                            <p class="email-summary-item-count" style="font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:0;color:#3b4049;font-size:24px;line-height:28px;font-weight:600;"><?php echo esc_html($totalData['closed']) ?></p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><br></p>
                                                        <p class="email-body-heading" style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:8px;color:#3b4049;font-weight:600;"><?php $this->_e('Category Performance Metrics'); ?></p>
                                                        <table class="email-body-data-table" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;width:100%;border-width:1px;border-style:solid;border-color:#e2e8f0;border-radius:5px;">
                                                            <thead>
                                                                <tr>
                                                                    <th style="text-align:left;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Category'); ?></th>
                                                                    <th style="text-align:center;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Tickets'); ?></th>
                                                                    <th style="text-align:center;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Responses'); ?></th>
                                                                    <th style="text-align:center;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Closed'); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                foreach ($categoryTableBody as $category) {
                                                                ?>
                                                                    <tr>
                                                                        <td style="text-align:left;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;">
                                                                            <?php echo esc_html($category['title']); ?>
                                                                        </td>
                                                                        <td style="text-align:center;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;">
                                                                            <?php echo esc_html($category['tickets']); ?>
                                                                        </td>
                                                                        <td style="text-align:center;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;">
                                                                            <?php echo esc_html($category['responses']); ?>
                                                                        </td>
                                                                        <td style="text-align:center;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;">
                                                                            <?php echo esc_html($category['closed']); ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><br></p>
                                                        <p class="email-body-heading" style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;margin-top:0;margin-bottom:8px;color:#3b4049;font-weight:600;"><?php $this->_e('Agent Performance Metrics'); ?></p>
                                                        <table class="email-body-data-table" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;width:100%;border-width:1px;border-style:solid;border-color:#e2e8f0;border-radius:5px;">
                                                            <thead>
                                                                <tr>
                                                                    <th style="text-align:left;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Agent'); ?></th>
                                                                    <th style="text-align:center;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Tickets'); ?></th>
                                                                    <th style="text-align:center;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Responses'); ?></th>
                                                                    <th style="text-align:center;background-color:#edf2f7;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;"><?php $this->_e('Closed'); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                foreach ($agentTableBody as $agent) {
                                                                ?>
                                                                    <tr>
                                                                        <td style="text-align:left;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;font-weight:500;">
                                                                            <?php echo esc_html($agent['title']); ?>
                                                                        </td>
                                                                        <td style="text-align:center;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;">
                                                                            <?php echo esc_html($agent['tickets']); ?>
                                                                        </td>
                                                                        <td style="text-align:center;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;">
                                                                            <?php echo esc_html($agent['responses']); ?>
                                                                        </td>
                                                                        <td style="text-align:center;border-top-width:1px;border-top-style:solid;border-top-color:#e2e8f0;padding-top:5px;padding-bottom:5px;padding-right:8px;padding-left:8px;">
                                                                            <?php echo esc_html($agent['closed']); ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><br></p>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><?php $this->_e('* Closed ticket count reflects the total number of times tickets have been marked as closed.'); ?></p>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:14px;color:#3b4049;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#e2e8f0;"><br></p>
                                                        <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;"><?php printf($this->__('Upgrade to %s for advanced features, powerful integrations, and enhanced productivity tools.'), '<a href="https://supportgenix.com/pricing/" style="font-family:Helvetica Neue, Helvetica, Arial, sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#00e;text-decoration:none;">Support Genix Pro</a>'); ?></p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="footer" align="center" style="text-align:center;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;color:#3b4049;font-size:14px;line-height:24px;background-color:#f5f5f5;border-top-width:0;padding-top:15px;padding-bottom:35px;padding-right:50px;padding-left:50px;">
                                    <p style="font-size:14px;line-height:24px;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#3b4049;">
                                        <a href="<?php echo esc_url($site_url); ?>" style="font-family:Helvetica Neue, Helvetica, Arial, sans-serif;font-weight:400;margin-top:0;margin-bottom:0;color:#00e;text-decoration:underline;"><?php echo esc_html($site_title); ?></a>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>