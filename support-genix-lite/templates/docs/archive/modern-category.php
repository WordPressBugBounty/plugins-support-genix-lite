<?php

/**
 * Modern Category Archive Template
 * Displays articles for a specific category with modern layout
 */

defined('ABSPATH') || exit;

// Get current category
$current_category = get_queried_object();
if (!$current_category || !is_a($current_category, 'WP_Term')) {
    return;
}

// Get category metadata
$category_color = get_term_meta($current_category->term_id, '_sg_color', true) ?: '#7229dd';
$category_icon = get_term_meta($current_category->term_id, '_sg_icon', true);
$category_icon_image = get_term_meta($current_category->term_id, '_sg_icon_image', true);

// Get module options
$show_search = Apbd_wps_knowledge_base::GetModuleOption('archive_search', 'Y');
$show_breadcrumb = Apbd_wps_knowledge_base::GetModuleOption('archive_breadcrumb', 'Y');
$docs_per_page = Apbd_wps_knowledge_base::GetModuleOption('archive_docs_per_page', 10);

// Get parent category if exists
$parent_category = null;
if ($current_category->parent) {
    $parent_category = get_term($current_category->parent, 'sgkb-docs-category');
}

// Get subcategories and filter out those with only chatbot posts
$all_subcategories = get_terms(array(
    'taxonomy' => 'sgkb-docs-category',
    'parent' => $current_category->term_id,
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC'
));

// Filter out subcategories that only have chatbot-only posts
$subcategories = array();
if (!empty($all_subcategories)) {
    foreach ($all_subcategories as $subcategory) {
        $non_chatbot_posts = new WP_Query(array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'sgkb-docs-category',
                    'field' => 'term_id',
                    'terms' => $subcategory->term_id,
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

        if ($non_chatbot_posts->found_posts > 0) {
            // Update the count to reflect only non-chatbot posts
            $subcategory->count = $non_chatbot_posts->found_posts;
            $subcategories[] = $subcategory;
        }
        wp_reset_postdata();
    }
}

// Get related categories and filter out those with only chatbot posts
$all_related_categories = get_terms(array(
    'taxonomy' => 'sgkb-docs-category',
    'parent' => $current_category->parent,
    'exclude' => $current_category->term_id,
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC'
));

// Filter out categories that only have chatbot-only posts
$related_categories = array();
if (!empty($all_related_categories)) {
    foreach ($all_related_categories as $related) {
        $non_chatbot_posts = new WP_Query(array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'sgkb-docs-category',
                    'field' => 'term_id',
                    'terms' => $related->term_id,
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

        if ($non_chatbot_posts->found_posts > 0) {
            $related_categories[] = $related;
            // Limit to 5 categories
            if (count($related_categories) >= 5) {
                break;
            }
        }
        wp_reset_postdata();
    }
}

// Helper function for color adjustment
if (!function_exists('sgkb_adjust_brightness')) {
    function sgkb_adjust_brightness($hex, $percent)
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return '#' . str_pad(dechex((int)$r), 2, '0', STR_PAD_LEFT) .
            str_pad(dechex((int)$g), 2, '0', STR_PAD_LEFT) .
            str_pad(dechex((int)$b), 2, '0', STR_PAD_LEFT);
    }
}
?>

<div class="sgkb-modern-wrapper">
    <div class="sgkb-modern-container">

        <!-- Category Header -->
        <header class="sgkb-category-header-modern">
            <div class="sgkb-container">

                <?php if ('Y' === $show_breadcrumb) : ?>
                    <!-- Breadcrumb -->
                    <?php sgkb_render_breadcrumbs(array('separator' => '/')); ?>
                <?php endif; ?>

                <!-- Category Info -->
                <div class="sgkb-category-hero">
                    <div class="sgkb-category-hero-content">
                        <div class="sgkb-category-hero-text">
                            <h1 class="sgkb-category-title"><?php echo esc_html($current_category->name); ?></h1>
                            <?php if (!empty($current_category->description)) : ?>
                                <p class="sgkb-category-description"><?php echo esc_html($current_category->description); ?></p>
                            <?php endif; ?>
                            <div class="sgkb-category-meta">
                                <span class="sgkb-meta-item">
                                    <?php
                                    // Use main query's found_posts which already has all filters applied
                                    // (chatbot-only exclusion, space filtering, etc.) via docs_pre_get_posts()
                                    global $wp_query;
                                    $post_count = $wp_query->found_posts;

                                    echo sprintf(
                                        esc_html(_n('%d article', '%d articles', $post_count, 'support-genix')),
                                        $post_count
                                    );
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ('Y' === $show_search) : ?>
                    <!-- Modern AJAX Search Bar -->
                    <div class="sgkb-search-modern sgkb-category-search-modern">
                        <div class="sgkb-search-wrapper">
                            <div class="sgkb-search-input-wrapper">
                                <span class="sgkb-search-icon-wrapper">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <input type="search"
                                    class="sgkb-search-input-modern"
                                    placeholder="<?php esc_attr_e('Search for articles...', 'support-genix'); ?>"
                                    data-category="<?php echo esc_attr($current_category->slug); ?>">
                                <button type="button" class="sgkb-search-clear" style="display: none;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                            <div class="sgkb-search-results-modern"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="sgkb-category-main">
            <div class="sgkb-container">
                <?php
                // Check if sidebar has any content to display
                $has_sidebar_content = !empty($subcategories) || !empty($related_categories);
                ?>
                <div class="sgkb-category-layout<?php echo !$has_sidebar_content ? ' sgkb-no-sidebar' : ''; ?>">

                    <?php if ($has_sidebar_content) : ?>
                        <!-- Sidebar -->
                        <aside class="sgkb-category-sidebar">

                            <?php if (!empty($subcategories)) : ?>
                                <!-- Subcategories -->
                                <div class="sgkb-sidebar-section">
                                    <h3 class="sgkb-sidebar-title"><?php esc_html_e('Subcategories', 'support-genix'); ?></h3>
                                    <ul class="sgkb-subcategory-list">
                                        <?php foreach ($subcategories as $subcategory) :
                                            $sub_color = get_term_meta($subcategory->term_id, '_sg_color', true) ?: '#7229dd';

                                            // Get actual count of non-chatbot posts for this subcategory
                                            $sub_non_chatbot_query = new WP_Query(array(
                                                'post_type' => 'sgkb-docs',
                                                'post_status' => 'publish',
                                                'posts_per_page' => -1,
                                                'fields' => 'ids',
                                                'tax_query' => array(
                                                    array(
                                                        'taxonomy' => 'sgkb-docs-category',
                                                        'field' => 'term_id',
                                                        'terms' => $subcategory->term_id,
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
                                            $sub_count = $sub_non_chatbot_query->found_posts;
                                            wp_reset_postdata();
                                        ?>
                                            <li class="sgkb-subcategory-item">
                                                <a href="<?php echo get_term_link($subcategory); ?>" class="sgkb-subcategory-link">
                                                    <span class="sgkb-subcategory-indicator" style="background-color: <?php echo esc_attr($sub_color); ?>"></span>
                                                    <span class="sgkb-subcategory-name"><?php echo esc_html($subcategory->name); ?></span>
                                                    <span class="sgkb-subcategory-count"><?php echo esc_html($sub_count); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Related Categories -->
                            <?php if (!empty($related_categories)) :
                            ?>
                                <div class="sgkb-sidebar-section">
                                    <h3 class="sgkb-sidebar-title"><?php esc_html_e('Related Categories', 'support-genix'); ?></h3>
                                    <ul class="sgkb-related-list">
                                        <?php foreach ($related_categories as $related) :
                                            // Get the actual count of non-chatbot posts for this category
                                            $count_args = array(
                                                'post_type' => 'sgkb-docs',
                                                'post_status' => 'publish',
                                                'posts_per_page' => -1,
                                                'fields' => 'ids',
                                                'tax_query' => array(
                                                    array(
                                                        'taxonomy' => 'sgkb-docs-category',
                                                        'field' => 'term_id',
                                                        'terms' => $related->term_id,
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
                                            $related_count = $count_query->found_posts;
                                            wp_reset_postdata();

                                            // Get category color
                                            $related_color = get_term_meta($related->term_id, '_sg_color', true) ?: '#7229dd';
                                        ?>
                                            <li class="sgkb-related-item">
                                                <a href="<?php echo get_term_link($related); ?>" class="sgkb-related-link">
                                                    <span class="sgkb-nav-indicator" style="background-color: <?php echo esc_attr($related_color); ?>;"></span>
                                                    <span class="sgkb-nav-text"><?php echo esc_html($related->name); ?></span>
                                                    <span class="sgkb-nav-count"><?php echo esc_html($related_count); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                        </aside>
                    <?php endif; // End if ($has_sidebar_content)
                    ?>

                    <!-- Articles List -->
                    <div class="sgkb-category-content">
                        <?php if (have_posts()) : ?>

                            <div class="sgkb-articles-header">
                                <h2 class="sgkb-articles-title">
                                    <?php esc_html_e('Articles', 'support-genix'); ?>
                                </h2>
                                <div class="sgkb-articles-count">
                                    <?php
                                    global $wp_query;
                                    $paged = max(1, get_query_var('paged'));
                                    $posts_per_page = $wp_query->query_vars['posts_per_page'];
                                    $start = (($paged - 1) * $posts_per_page) + 1;
                                    $end = min($paged * $posts_per_page, $wp_query->found_posts);

                                    echo sprintf(
                                        esc_html__('Showing %1$d-%2$d of %3$d articles', 'support-genix'),
                                        $start,
                                        $end,
                                        $wp_query->found_posts
                                    );
                                    ?>
                                </div>
                            </div>

                            <div class="sgkb-articles-list">
                                <?php while (have_posts()) : the_post(); ?>
                                    <article class="sgkb-article-card">
                                        <div class="sgkb-article-card-content">
                                            <header class="sgkb-article-header">
                                                <h3 class="sgkb-article-title">
                                                    <a href="<?php the_permalink(); ?>" class="sgkb-article-link">
                                                        <?php the_title(); ?>
                                                    </a>
                                                </h3>
                                                <div class="sgkb-article-meta">
                                                    <time class="sgkb-article-date" datetime="<?php echo get_the_modified_date('c'); ?>">
                                                        <?php echo human_time_diff(get_the_modified_time('U'), current_time('timestamp')) . ' ' . __('ago', 'support-genix'); ?>
                                                    </time>
                                                    <?php
                                                    $views = get_post_meta(get_the_ID(), 'sgkb_views', true);
                                                    if ($views) :
                                                    ?>
                                                        <span class="sgkb-meta-separator">•</span>
                                                        <span class="sgkb-article-views">
                                                            <?php echo sprintf(esc_html__('%s views', 'support-genix'), number_format_i18n($views)); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </header>

                                            <?php if (has_excerpt()) : ?>
                                                <div class="sgkb-article-excerpt">
                                                    <?php the_excerpt(); ?>
                                                </div>
                                            <?php else : ?>
                                                <div class="sgkb-article-excerpt">
                                                    <?php echo wp_trim_words(get_the_content(), 30); ?>
                                                </div>
                                            <?php endif; ?>

                                            <footer class="sgkb-article-footer">
                                                <a href="<?php the_permalink(); ?>" class="sgkb-article-read-more">
                                                    <?php esc_html_e('Read article', 'support-genix'); ?>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                        <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </a>
                                            </footer>
                                        </div>
                                    </article>
                                <?php endwhile; ?>
                            </div>

                            <!-- Pagination -->
                            <?php
                            $pagination = paginate_links(array(
                                'type' => 'array',
                                'prev_text' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                                'next_text' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                            ));

                            if ($pagination) :
                            ?>
                                <nav class="sgkb-pagination" aria-label="<?php esc_attr_e('Articles navigation', 'support-genix'); ?>">
                                    <ul class="sgkb-pagination-list">
                                        <?php foreach ($pagination as $page) : ?>
                                            <li class="sgkb-pagination-item">
                                                <?php echo $page; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        <?php else : ?>

                            <div class="sgkb-no-articles">
                                <svg class="sgkb-no-articles-icon" width="64" height="64" viewBox="0 0 24 24" fill="none">
                                    <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <h2 class="sgkb-no-articles-title">
                                    <?php esc_html_e('No articles found', 'support-genix'); ?>
                                </h2>
                                <p class="sgkb-no-articles-text">
                                    <?php esc_html_e('There are no articles in this category yet.', 'support-genix'); ?>
                                </p>
                                <a href="<?php echo get_post_type_archive_link('sgkb-docs'); ?>" class="sgkb-btn sgkb-btn-primary">
                                    <?php esc_html_e('Browse all articles', 'support-genix'); ?>
                                </a>
                            </div>

                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </main>

    </div>
</div>