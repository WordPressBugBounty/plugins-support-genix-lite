<?php

/**
 * Modern Grid Layout for Documentation Categories
 *
 * @package Support_Genix
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

// Helper function to adjust color brightness
if (!function_exists('sgkb_adjust_brightness')) {
    function sgkb_adjust_brightness($hex, $percent)
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return '#' . str_pad(dechex((int)$r), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex((int)$g), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex((int)$b), 2, '0', STR_PAD_LEFT);
    }
}

// Helper function to get icon HTML
if (!function_exists('sgkb_get_icon_html')) {
    function sgkb_get_icon_html($icon)
    {
        // If it's a dashicon class
        if (strpos($icon, 'dashicons-') === 0) {
            return '<span class="dashicons ' . esc_attr($icon) . '"></span>';
        }
        // If it's an image URL
        elseif (filter_var($icon, FILTER_VALIDATE_URL)) {
            return '<img src="' . esc_url($icon) . '" alt="" width="28" height="28">';
        }
        // If it's SVG code
        elseif (strpos($icon, '<svg') !== false) {
            return wp_kses($icon, array(
                'svg' => array('width' => array(), 'height' => array(), 'viewBox' => array(), 'fill' => array()),
                'path' => array('d' => array(), 'stroke' => array(), 'stroke-width' => array(), 'stroke-linecap' => array(), 'stroke-linejoin' => array(), 'fill' => array()),
                'circle' => array('cx' => array(), 'cy' => array(), 'r' => array(), 'stroke' => array(), 'stroke-width' => array(), 'fill' => array()),
                'rect' => array('x' => array(), 'y' => array(), 'width' => array(), 'height' => array(), 'rx' => array(), 'fill' => array()),
            ));
        }
        // Default fallback
        return '';
    }
}

// Get configuration options with error checking
if (!class_exists('Apbd_wps_knowledge_base')) {
    // If class doesn't exist, use defaults
    $show_hero = 'Y';
    $show_stats = 'Y';
    $show_featured = 'Y';
    // Grid columns is now fixed to 2 columns on desktop, 1 on mobile
    // $grid_columns = '3';
    $show_icons = 'Y';
    $show_description = 'Y';
    $docs_per_category = 5;
} else {
    $show_hero = Apbd_wps_knowledge_base::GetModuleOption('modern_show_hero', 'Y');
    $show_stats = Apbd_wps_knowledge_base::GetModuleOption('modern_show_stats', 'Y');
    $show_featured = Apbd_wps_knowledge_base::GetModuleOption('modern_show_featured', 'Y');
    // Grid columns is now fixed to 2 columns on desktop, 1 on mobile
    // $grid_columns = Apbd_wps_knowledge_base::GetModuleOption('modern_grid_columns', '3');
    $show_icons = Apbd_wps_knowledge_base::GetModuleOption('modern_show_icons', 'Y');
    $show_description = Apbd_wps_knowledge_base::GetModuleOption('modern_show_description', 'Y');
    $docs_per_category = Apbd_wps_knowledge_base::GetModuleOption('modern_docs_per_category', 5);
}

// Get categories
$cat_args = array(
    'taxonomy' => 'sgkb-docs-category',
    'hide_empty' => true,
    'hierarchical' => false,
    'meta_key' => '_sg_order',
    'orderby' => 'meta_value_num',
    'order' => 'ASC',
);

$all_categories = get_terms($cat_args);

// Check if categories is a WP_Error or empty
if (is_wp_error($all_categories)) {
    $all_categories = array();
}

// Filter out categories that only have chatbot-only posts
$categories = array();
if (!empty($all_categories)) {
    foreach ($all_categories as $category) {
        // Check if this category has any non-chatbot posts
        $non_chatbot_posts = new WP_Query(array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'sgkb-docs-category',
                    'field' => 'term_id',
                    'terms' => $category->term_id,
                    'include_children' => false,
                )
            ),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'only_for_chatbot',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'only_for_chatbot',
                    'value' => '1',
                    'compare' => '!='
                )
            )
        ));

        // Only include category if it has at least one non-chatbot post
        if ($non_chatbot_posts->found_posts > 0) {
            $categories[] = $category;
        }
        wp_reset_postdata();
    }
}

// Get stats data - always exclude chatbot-only posts
$docs_query = new WP_Query(array(
    'post_type' => 'sgkb-docs',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'only_for_chatbot',
            'compare' => 'NOT EXISTS'
        ),
        array(
            'key' => 'only_for_chatbot',
            'value' => '1',
            'compare' => '!='
        )
    )
));
$total_docs = $docs_query->found_posts;
wp_reset_postdata();

$total_categories = is_array($categories) ? count($categories) : 0;

// Get recent docs - always excluding chatbot-only posts
$recent_args = array(
    'post_type' => 'sgkb-docs',
    'posts_per_page' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
    'suppress_filters' => false, // Allow WPML/Polylang to filter by language
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'only_for_chatbot',
            'compare' => 'NOT EXISTS'
        ),
        array(
            'key' => 'only_for_chatbot',
            'value' => '1',
            'compare' => '!='
        )
    )
);

$recent_docs = get_posts($recent_args);
$last_updated = !empty($recent_docs) ? human_time_diff(strtotime($recent_docs[0]->post_modified), current_time('timestamp')) . ' ago' : 'N/A';

// Fixed grid class: 1 column on mobile, 2 columns on tablets and desktop
$grid_class = 'sgkb-grid sgkb-grid-cols-1 sgkb-md:grid-cols-2';
?>

<div class="sgkb-modern-docs-wrapper">

    <?php if ('Y' === $show_hero) : ?>
        <!-- Hero Section with Search (Full Width) -->
        <section class="sgkb-hero-modern sgkb-hero-full-width" data-animate="sgkb-animate-fadeIn">
            <div class="sgkb-container">
                <div class="sgkb-hero-container">
                    <div class="sgkb-hero-content">
                        <h1 class="sgkb-hero-title">
                            <?php
                            $hero_title = __('How can we help?', 'support-genix');
                            if (class_exists('Apbd_wps_knowledge_base')) {
                                $hero_title = Apbd_wps_knowledge_base::GetModuleOption('hero_title', $hero_title);
                            }
                            echo esc_html($hero_title);
                            ?>
                        </h1>
                        <p class="sgkb-hero-subtitle">
                            <?php
                            $hero_subtitle = __('Search our knowledge base or browse categories below', 'support-genix');
                            if (class_exists('Apbd_wps_knowledge_base')) {
                                $hero_subtitle = Apbd_wps_knowledge_base::GetModuleOption('hero_subtitle', $hero_subtitle);
                            }
                            echo esc_html($hero_subtitle);
                            ?>
                        </p>

                        <!-- Modern Search Box -->
                        <div class="sgkb-search-modern">
                            <div class="sgkb-search-wrapper">
                                <input
                                    type="search"
                                    class="sgkb-search-input-modern"
                                    placeholder="<?php echo esc_attr__('Search for articles...', 'support-genix'); ?>"
                                    aria-label="<?php echo esc_attr__('Search documentation', 'support-genix'); ?>">
                                <button class="sgkb-search-icon-wrapper" aria-label="<?php echo esc_attr__('Search', 'support-genix'); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                            <div class="sgkb-search-results-modern"></div>
                        </div>

                        <?php if ('Y' === $show_stats) : ?>
                            <!-- Quick Stats Bar -->
                            <div class="sgkb-stats-bar">
                                <div class="sgkb-stat-card">
                                    <div class="sgkb-stat-number"><?php echo esc_html($total_docs); ?></div>
                                    <div class="sgkb-stat-label"><?php esc_html_e('Articles', 'support-genix'); ?></div>
                                </div>
                                <div class="sgkb-stat-card">
                                    <div class="sgkb-stat-number"><?php echo esc_html($total_categories); ?></div>
                                    <div class="sgkb-stat-label"><?php esc_html_e('Categories', 'support-genix'); ?></div>
                                </div>
                                <div class="sgkb-stat-card">
                                    <div class="sgkb-stat-number"><?php echo esc_html($last_updated); ?></div>
                                    <div class="sgkb-stat-label"><?php esc_html_e('Last Updated', 'support-genix'); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Main Content Container -->
    <div class="sgkb-container">

        <!-- Content with Sidebar Layout -->
        <div class="sgkb-content-with-sidebar">

            <!-- Sidebar -->
            <aside class="sgkb-docs-sidebar">
                <!-- Quick Navigation -->
                <div class="sgkb-sidebar-section">
                    <h3 class="sgkb-sidebar-title">
                        <svg class="sgkb-sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M3 12h18m-9-9v18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <?php esc_html_e('Quick Navigation', 'support-genix'); ?>
                    </h3>
                    <ul class="sgkb-sidebar-nav">
                        <?php
                        // Get all categories for navigation
                        $nav_categories = get_terms(array(
                            'taxonomy' => 'sgkb-docs-category',
                            'hide_empty' => true,
                            'hierarchical' => true,
                            'meta_key' => '_sg_order',
                            'orderby' => 'meta_value_num',
                            'order' => 'ASC',
                        ));

                        if (!is_wp_error($nav_categories) && !empty($nav_categories)) :
                            foreach ($nav_categories as $nav_cat) :
                                $nav_link = get_term_link($nav_cat);
                                if (is_wp_error($nav_link)) continue;
                                $nav_color = get_term_meta($nav_cat->term_id, '_sg_color', true) ?: '#7229dd';
                                $nav_icon = get_term_meta($nav_cat->term_id, '_sg_icon', true);

                                // Always calculate correct count excluding chatbot-only posts
                                $count_args = array(
                                    'post_type' => 'sgkb-docs',
                                    'post_status' => 'publish',
                                    'posts_per_page' => -1,
                                    'fields' => 'ids',
                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => 'sgkb-docs-category',
                                            'field' => 'term_id',
                                            'terms' => $nav_cat->term_id,
                                            'include_children' => false,
                                        )
                                    ),
                                    'meta_query' => array(
                                        'relation' => 'OR',
                                        array(
                                            'key' => 'only_for_chatbot',
                                            'compare' => 'NOT EXISTS'
                                        ),
                                        array(
                                            'key' => 'only_for_chatbot',
                                            'value' => '1',
                                            'compare' => '!='
                                        )
                                    )
                                );
                                $count_query = new WP_Query($count_args);
                                $nav_count = $count_query->found_posts;
                                wp_reset_postdata();

                                // Skip categories with no non-chatbot posts
                                if ($nav_count == 0) {
                                    continue;
                                }
                        ?>
                                <li class="sgkb-nav-item">
                                    <a href="<?php echo esc_url($nav_link); ?>" class="sgkb-nav-link">
                                        <span class="sgkb-nav-indicator" style="background-color: <?php echo esc_attr($nav_color); ?>;"></span>
                                        <span class="sgkb-nav-text"><?php echo esc_html($nav_cat->name); ?></span>
                                        <span class="sgkb-nav-count"><?php echo esc_html($nav_count); ?></span>
                                    </a>
                                    <?php
                                    // Get child categories if any
                                    $child_categories = get_terms(array(
                                        'taxonomy' => 'sgkb-docs-category',
                                        'hide_empty' => true,
                                        'parent' => $nav_cat->term_id,
                                        'meta_key' => '_sg_order',
                                        'orderby' => 'meta_value_num',
                                        'order' => 'ASC',
                                    ));

                                    if (!is_wp_error($child_categories) && !empty($child_categories)) :
                                        $has_valid_children = false;
                                    ?>
                                        <ul class="sgkb-nav-children">
                                            <?php foreach ($child_categories as $child_cat) :
                                                $child_link = get_term_link($child_cat);
                                                if (is_wp_error($child_link)) continue;

                                                // Check if child category has non-chatbot posts
                                                $child_count_query = new WP_Query(array(
                                                    'post_type' => 'sgkb-docs',
                                                    'post_status' => 'publish',
                                                    'posts_per_page' => 1,
                                                    'fields' => 'ids',
                                                    'tax_query' => array(
                                                        array(
                                                            'taxonomy' => 'sgkb-docs-category',
                                                            'field' => 'term_id',
                                                            'terms' => $child_cat->term_id,
                                                            'include_children' => false,
                                                        )
                                                    ),
                                                    'meta_query' => array(
                                                        'relation' => 'OR',
                                                        array(
                                                            'key' => 'only_for_chatbot',
                                                            'compare' => 'NOT EXISTS'
                                                        ),
                                                        array(
                                                            'key' => 'only_for_chatbot',
                                                            'value' => '1',
                                                            'compare' => '!='
                                                        )
                                                    )
                                                ));

                                                if ($child_count_query->found_posts == 0) {
                                                    wp_reset_postdata();
                                                    continue;
                                                }
                                                $has_valid_children = true;
                                                wp_reset_postdata();
                                            ?>
                                                <li class="sgkb-nav-child-item">
                                                    <a href="<?php echo esc_url($child_link); ?>" class="sgkb-nav-child-link">
                                                        <span class="sgkb-nav-text"><?php echo esc_html($child_cat->name); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </ul>
                </div>

                <!-- Popular Articles -->
                <div class="sgkb-sidebar-section">
                    <h3 class="sgkb-sidebar-title">
                        <svg class="sgkb-sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <?php esc_html_e('Popular Articles', 'support-genix'); ?>
                    </h3>
                    <ul class="sgkb-popular-list">
                        <?php
                        // Get popular articles (most viewed or you can customize the query)
                        $popular_args = array(
                            'post_type' => 'sgkb-docs',
                            'posts_per_page' => 8,
                            'orderby' => 'comment_count', // Or use a custom meta field for views
                            'order' => 'DESC',
                            'suppress_filters' => false, // Allow WPML/Polylang to filter by language
                            'meta_query' => array(
                                array(
                                    'key' => 'only_for_chatbot',
                                    'compare' => 'NOT EXISTS'
                                )
                            )
                        );

                        $popular_docs = get_posts($popular_args);

                        if (!empty($popular_docs)) :
                            foreach ($popular_docs as $popular_doc) :
                                $doc_categories = get_the_terms($popular_doc->ID, 'sgkb-docs-category');
                                $category_name = (!empty($doc_categories) && !is_wp_error($doc_categories)) ? $doc_categories[0]->name : '';
                        ?>
                                <li class="sgkb-popular-item">
                                    <a href="<?php echo get_permalink($popular_doc->ID); ?>" class="sgkb-popular-link">
                                        <svg class="sgkb-popular-icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 12h6m-3-3v6m-7 4h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        </svg>
                                        <div class="sgkb-popular-content">
                                            <span class="sgkb-popular-title"><?php echo esc_html($popular_doc->post_title); ?></span>
                                            <?php if ($category_name) : ?>
                                                <span class="sgkb-popular-meta"><?php echo esc_html($category_name); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </li>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </ul>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="sgkb-docs-main-content">

                <?php if ('Y' === $show_featured && !empty($categories) && is_array($categories) && count($categories) > 0) :
                    $featured_category = $categories[0]; // Get first category as featured
                    $featured_id = $featured_category->term_id;
                    $featured_color = get_term_meta($featured_id, '_sg_color', true) ?: '#7229dd';
                    $featured_icon = get_term_meta($featured_id, '_sg_icon', true);
                    $featured_icon_image = get_term_meta($featured_id, '_sg_icon_image', true);


                    // Get featured docs
                    $featured_docs = get_posts(array(
                        'post_type' => 'sgkb-docs',
                        'posts_per_page' => 3,
                        'suppress_filters' => false, // Allow WPML/Polylang to filter by language
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'sgkb-docs-category',
                                'field' => 'term_id',
                                'terms' => $featured_id,
                            )
                        ),
                        'meta_query' => array(
                            array(
                                'key' => 'only_for_chatbot',
                                'compare' => 'NOT EXISTS'
                            )
                        )
                    ));

                    if (!empty($featured_docs)) :
                        // Determine grid class based on number of featured posts
                        $featured_count = count($featured_docs);
                        $featured_grid_class = 'sgkb-featured-grid';
                        if ($featured_count === 1) {
                            $featured_grid_class .= ' sgkb-featured-grid-1';
                        } elseif ($featured_count === 2) {
                            $featured_grid_class .= ' sgkb-featured-grid-2';
                        }
                ?>
                        <!-- Featured Section -->
                        <section class="sgkb-featured-section" data-animate="sgkb-animate-fadeInUp">
                            <div class="sgkb-section-header">
                                <h2 class="sgkb-section-title"><?php esc_html_e('Featured Topics', 'support-genix'); ?></h2>
                                <a href="<?php echo get_term_link($featured_category); ?>" class="sgkb-section-link">
                                    <?php esc_html_e('View all', 'support-genix'); ?> →
                                </a>
                            </div>

                            <div class="<?php echo esc_attr($featured_grid_class); ?>">
                                <?php
                                foreach ($featured_docs as $doc) : ?>
                                    <article class="sgkb-featured-card">
                                        <span class="sgkb-featured-badge"><?php esc_html_e('Featured', 'support-genix'); ?></span>
                                        <h3 class="sgkb-article-title">
                                            <a href="<?php echo get_permalink($doc->ID); ?>">
                                                <?php echo esc_html($doc->post_title); ?>
                                            </a>
                                        </h3>
                                        <p class="sgkb-article-excerpt">
                                            <?php echo wp_trim_words($doc->post_excerpt ?: $doc->post_content, 20); ?>
                                        </p>
                                        <a href="<?php echo get_permalink($doc->ID); ?>" class="sgkb-category-link">
                                            <?php esc_html_e('Read more', 'support-genix'); ?>
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                <?php
                    endif;
                endif;
                ?>

                <!-- Filter controls removed as they are not needed when no categories exist -->

                <!-- Categories Grid -->
                <section class="sgkb-categories-section">
                    <h2 class="sgkb-section-title sgkb-category-browse-title"><?php esc_html_e('Browse by Category', 'support-genix'); ?></h2>

                    <?php if (!empty($categories)) :
                        // Determine grid class based on number of categories
                        $category_count = count($categories);
                        $category_grid_class = $grid_class;
                        if ($category_count === 1) {
                            $category_grid_class .= ' sgkb-categories-grid-1';
                        }
                    ?>
                        <div class="<?php echo esc_attr($category_grid_class); ?>">
                            <?php
                            foreach ($categories as $index => $category) :
                                $term_id = $category->term_id;
                                $term_link = get_term_link($category);
                                if (is_wp_error($term_link)) {
                                    continue; // Skip this category if we can't get the link
                                }
                                $term_color = get_term_meta($term_id, '_sg_color', true) ?: '#7229dd';
                                $term_icon = get_term_meta($term_id, '_sg_icon', true);
                                $term_icon_image = get_term_meta($term_id, '_sg_icon_image', true);
                                $docs_order = get_term_meta($term_id, '_sg_docs_order', true);
                                $docs_order = $docs_order ? array_filter(array_unique(array_map('absint', explode(',', $docs_order)))) : [];

                                // Get docs for this category
                                $docs_args = array(
                                    'post_type' => 'sgkb-docs',
                                    'posts_per_page' => $docs_per_category,
                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => 'sgkb-docs-category',
                                            'field' => 'term_id',
                                            'terms' => $term_id,
                                            'operator' => 'IN',
                                            'include_children' => false,
                                        )
                                    ),
                                    'meta_query' => array(
                                        array(
                                            'key' => 'only_for_chatbot',
                                            'compare' => 'NOT EXISTS'
                                        )
                                    )
                                );

                                if (!empty($docs_order)) {
                                    $docs_args['post__in'] = $docs_order;
                                    $docs_args['orderby'] = 'post__in';
                                }

                                $docs_query = new WP_Query($docs_args);
                                $docs_count = $docs_query->found_posts;
                                $last_modified = '';

                                if ($docs_query->have_posts()) {
                                    $recent_doc = get_posts(array(
                                        'post_type' => 'sgkb-docs',
                                        'posts_per_page' => 1,
                                        'orderby' => 'modified',
                                        'order' => 'DESC',
                                        'suppress_filters' => false, // Allow WPML/Polylang to filter by language
                                        'tax_query' => $docs_args['tax_query'],
                                        'meta_query' => $docs_args['meta_query']
                                    ));
                                    if (!empty($recent_doc)) {
                                        $last_modified = $recent_doc[0]->post_modified;
                                    }
                                }

                                // Determine if this should be a featured card
                                $highlight_first = 'N';
                                if (class_exists('Apbd_wps_knowledge_base')) {
                                    $highlight_first = Apbd_wps_knowledge_base::GetModuleOption('highlight_first_category', 'N');
                                }
                                $is_featured = ($index === 0 && 'Y' === $highlight_first);
                                $card_class = $is_featured ? 'sgkb-category-card-modern sgkb-category-card-featured' : 'sgkb-category-card-modern';
                            ?>

                                <div class="<?php echo esc_attr($card_class); ?>"
                                    data-category="<?php echo esc_attr($term_id); ?>"
                                    data-count="<?php echo esc_attr($docs_count); ?>"
                                    data-animate="sgkb-animate-fadeInUp">

                                    <div class="sgkb-category-header">
                                        <?php if ('Y' === $show_icons) : ?>
                                            <div class="sgkb-category-icon" style="<?php echo $term_icon_image ? '' : 'background: linear-gradient(135deg, ' . esc_attr($term_color) . ', ' . esc_attr(sgkb_adjust_brightness($term_color, -20)) . ');'; ?>">
                                                <?php if ($term_icon_image) : ?>
                                                    <img src="<?php echo esc_url($term_icon_image); ?>" alt="<?php echo esc_attr($category->name); ?>" class="sgkb-category-icon-image">
                                                <?php elseif ($term_icon) : ?>
                                                    <?php echo sgkb_get_icon_html($term_icon); ?>
                                                <?php else : ?>
                                                    <!-- Default icon -->
                                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                                                        <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="sgkb-category-info">
                                            <h3 class="sgkb-category-title">
                                                <a href="<?php echo esc_url($term_link); ?>">
                                                    <?php echo esc_html($category->name); ?>
                                                </a>
                                            </h3>
                                            <div class="sgkb-category-meta">
                                                <span class="sgkb-meta-item">
                                                    <?php echo esc_html($docs_count); ?> <?php echo _n('article', 'articles', $docs_count, 'support-genix'); ?>
                                                </span>
                                                <?php /* Removed time ago display
                                        <?php if ($last_modified) : ?>
                                        <span class="sgkb-meta-separator">•</span>
                                        <span class="sgkb-meta-item sgkb-text-muted">
                                            <?php echo human_time_diff(strtotime($last_modified), current_time('timestamp')) . ' ' . __('ago', 'support-genix'); ?>
                                        </span>
                                        <?php endif; ?>
                                        */ ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ('Y' === $show_description && !empty($category->description)) : ?>
                                        <p class="sgkb-category-description">
                                            <?php echo esc_html($category->description); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($docs_query->have_posts()) : ?>
                                        <ul class="sgkb-category-docs-list">
                                            <?php while ($docs_query->have_posts()) : $docs_query->the_post(); ?>
                                                <li>
                                                    <a href="<?php the_permalink(); ?>">
                                                        <?php the_title(); ?>
                                                    </a>
                                                </li>
                                            <?php endwhile;
                                            wp_reset_postdata(); ?>
                                        </ul>
                                    <?php endif; ?>

                                    <a href="<?php echo esc_url($term_link); ?>" class="sgkb-category-link">
                                        <?php esc_html_e('View all articles', 'support-genix'); ?>
                                    </a>
                                </div>

                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="sgkb-alert sgkb-alert-info">
                            <svg class="sgkb-alert-icon" viewBox="0 0 24 24" fill="none">
                                <path d="M13 16H12V12H11M12 8H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="sgkb-alert-content">
                                <?php esc_html_e('No documentation categories found.', 'support-genix'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Recently Updated Section -->
                <?php
                $show_recent_docs = 'Y';
                if (class_exists('Apbd_wps_knowledge_base')) {
                    $show_recent_docs = Apbd_wps_knowledge_base::GetModuleOption('show_recent_docs', 'Y');
                }
                if ($show_recent_docs === 'Y') :
                    $recent_docs = get_posts(array(
                        'post_type' => 'sgkb-docs',
                        'posts_per_page' => 5,
                        'orderby' => 'modified',
                        'order' => 'DESC',
                        'suppress_filters' => false, // Allow WPML/Polylang to filter by language
                        'meta_query' => array(
                            array(
                                'key' => 'only_for_chatbot',
                                'compare' => 'NOT EXISTS'
                            )
                        )
                    ));

                    if (!empty($recent_docs)) :
                ?>
                        <section class="sgkb-mt-16">
                            <div class="sgkb-section-header">
                                <h2 class="sgkb-section-title"><?php esc_html_e('Recently Updated', 'support-genix'); ?></h2>
                            </div>

                            <div class="sgkb-grid sgkb-grid-cols-1">
                                <?php foreach ($recent_docs as $doc) :
                                    $categories = get_the_terms($doc->ID, 'sgkb-docs-category');
                                ?>
                                    <article class="sgkb-article-card" data-animate="sgkb-animate-fadeInUp">
                                        <h3 class="sgkb-article-title">
                                            <a href="<?php echo get_permalink($doc->ID); ?>">
                                                <?php echo esc_html($doc->post_title); ?>
                                            </a>
                                        </h3>
                                        <div class="sgkb-article-meta">
                                            <span class="sgkb-article-meta-item">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <?php echo human_time_diff(strtotime($doc->post_modified), current_time('timestamp')) . ' ' . __('ago', 'support-genix'); ?>
                                            </span>
                                            <?php if (!empty($categories)) : ?>
                                                <span class="sgkb-article-meta-item">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                        <path d="M7 7H17M7 12H17M7 17H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <?php echo esc_html($categories[0]->name); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="sgkb-article-excerpt">
                                            <?php echo wp_trim_words($doc->post_excerpt ?: $doc->post_content, 30); ?>
                                        </p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                <?php endif;
                endif; ?>

            </div> <!-- End sgkb-docs-main-content -->

        </div> <!-- End sgkb-content-with-sidebar -->

    </div> <!-- End sgkb-container -->

</div> <!-- End sgkb-modern-docs-wrapper -->