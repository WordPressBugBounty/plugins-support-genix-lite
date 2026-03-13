<?php

/**
 * Modern Category Card Component
 *
 * @package Support_Genix
 * @version 1.0.0
 *
 * Variables available:
 * - $category: WP_Term object
 * - $docs_count: Number of docs in category
 * - $top_docs: Array of top docs (WP_Post objects)
 * - $category_icon: Icon HTML or class
 * - $category_color: Hex color for accent
 * - $show_description: Boolean to show/hide description
 * - $show_icon: Boolean to show/hide icon
 * - $is_featured: Boolean for featured styling
 * - $animation_delay: Animation delay in ms
 */

defined('ABSPATH') || exit;

// Set defaults if not provided
$category = isset($category) ? $category : null;
$docs_count = isset($docs_count) ? $docs_count : 0;
$top_docs = isset($top_docs) ? $top_docs : array();
$category_icon = isset($category_icon) ? $category_icon : '';
$category_color = isset($category_color) ? $category_color : '#7229dd';
$show_description = isset($show_description) ? $show_description : true;
$show_icon = isset($show_icon) ? $show_icon : true;
$is_featured = isset($is_featured) ? $is_featured : false;
$animation_delay = isset($animation_delay) ? $animation_delay : 0;

// Exit if no category
if (!$category) {
    return;
}

// Get category link
$category_link = get_term_link($category);

// Adjust color brightness for gradient
function sgkb_adjust_brightness($hex, $percent)
{
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
        . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
        . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

$gradient_color_dark = sgkb_adjust_brightness($category_color, -20);

// Card classes
$card_classes = array('sgkb-category-card-modern');
if ($is_featured) {
    $card_classes[] = 'sgkb-category-card-featured';
}
$card_class = implode(' ', $card_classes);

// Get last modified date
$last_modified = '';
if (!empty($top_docs)) {
    $recent_doc = get_posts(array(
        'post_type' => 'sgkb-docs',
        'posts_per_page' => 1,
        'orderby' => 'modified',
        'order' => 'DESC',
        'suppress_filters' => false, // Allow WPML/Polylang to filter by language
        'tax_query' => array(
            array(
                'taxonomy' => 'sgkb-docs-category',
                'field' => 'term_id',
                'terms' => $category->term_id,
            )
        )
    ));
    if (!empty($recent_doc)) {
        $last_modified = $recent_doc[0]->post_modified;
    }
}
?>

<div class="<?php echo esc_attr($card_class); ?>"
    data-category="<?php echo esc_attr($category->term_id); ?>"
    data-category-slug="<?php echo esc_attr($category->slug); ?>"
    data-count="<?php echo esc_attr($docs_count); ?>"
    data-updated="<?php echo esc_attr($last_modified); ?>"
    data-animate="sgkb-animate-fadeInUp"
    <?php if ($animation_delay > 0) : ?>
    style="animation-delay: <?php echo esc_attr($animation_delay); ?>ms;"
    <?php endif; ?>>

    <!-- Category Header -->
    <div class="sgkb-category-header">
        <?php if ($show_icon) : ?>
            <div class="sgkb-category-icon"
                style="background: linear-gradient(135deg, <?php echo esc_attr($category_color); ?>, <?php echo esc_attr($gradient_color_dark); ?>);">
                <?php if (!empty($category_icon)) : ?>
                    <?php
                    // Handle different icon types
                    if (strpos($category_icon, 'dashicons-') === 0) {
                        echo '<span class="dashicons ' . esc_attr($category_icon) . '"></span>';
                    } elseif (filter_var($category_icon, FILTER_VALIDATE_URL)) {
                        echo '<img src="' . esc_url($category_icon) . '" alt="' . esc_attr($category->name) . '" width="28" height="28">';
                    } elseif (strpos($category_icon, '<svg') !== false) {
                        echo wp_kses($category_icon, array(
                            'svg' => array('width' => array(), 'height' => array(), 'viewBox' => array(), 'fill' => array(), 'xmlns' => array()),
                            'path' => array('d' => array(), 'stroke' => array(), 'stroke-width' => array(), 'stroke-linecap' => array(), 'stroke-linejoin' => array(), 'fill' => array()),
                            'circle' => array('cx' => array(), 'cy' => array(), 'r' => array(), 'stroke' => array(), 'stroke-width' => array(), 'fill' => array()),
                            'rect' => array('x' => array(), 'y' => array(), 'width' => array(), 'height' => array(), 'rx' => array(), 'fill' => array()),
                            'g' => array('fill' => array(), 'stroke' => array()),
                            'polygon' => array('points' => array(), 'fill' => array()),
                            'line' => array('x1' => array(), 'y1' => array(), 'x2' => array(), 'y2' => array(), 'stroke' => array(), 'stroke-width' => array()),
                        ));
                    } else {
                        // Fallback icon HTML
                        echo $category_icon;
                    }
                    ?>
                <?php else : ?>
                    <!-- Default icon -->
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="sgkb-category-info">
            <h3 class="sgkb-category-title">
                <a href="<?php echo esc_url($category_link); ?>" class="sgkb-category-title-link">
                    <?php echo esc_html($category->name); ?>
                </a>
            </h3>
            <span class="sgkb-category-count">
                <?php
                echo sprintf(
                    _n('%s article', '%s articles', $docs_count, 'support-genix'),
                    number_format_i18n($docs_count)
                );
                ?>
            </span>
        </div>
    </div>

    <!-- Category Description -->
    <?php if ($show_description && !empty($category->description)) : ?>
        <p class="sgkb-category-description">
            <?php echo esc_html(wp_trim_words($category->description, 20, '...')); ?>
        </p>
    <?php endif; ?>

    <!-- Top Documents List -->
    <?php if (!empty($top_docs)) : ?>
        <ul class="sgkb-category-docs-list">
            <?php foreach ($top_docs as $doc) : ?>
                <li>
                    <a href="<?php echo get_permalink($doc->ID); ?>"
                        title="<?php echo esc_attr($doc->post_title); ?>">
                        <?php echo esc_html($doc->post_title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <div class="sgkb-category-empty">
            <p class="sgkb-text-secondary sgkb-text-sm">
                <?php esc_html_e('No articles available yet.', 'support-genix'); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- View All Link -->
    <a href="<?php echo esc_url($category_link); ?>"
        class="sgkb-category-link"
        aria-label="<?php echo esc_attr(sprintf(__('View all articles in %s', 'support-genix'), $category->name)); ?>">
        <?php esc_html_e('View all articles', 'support-genix'); ?>
    </a>

    <?php if ($is_featured) : ?>
        <!-- Featured Badge -->
        <span class="sgkb-featured-badge sgkb-absolute" style="top: 16px; right: 16px;">
            <?php esc_html_e('Featured', 'support-genix'); ?>
        </span>
    <?php endif; ?>
</div>