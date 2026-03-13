<?php

/**
 * Modern Tag Archive Template
 * Displays articles for a specific tag with modern layout
 * Cloned from modern-category.php with minimal changes for tags
 */

defined('ABSPATH') || exit;

// Get current tag (changed from category)
$current_tag = get_queried_object();
if (!$current_tag || !is_a($current_tag, 'WP_Term')) {
    return;
}

// Get tag metadata (tags might not have these, but keep for consistency)
$tag_color = get_term_meta($current_tag->term_id, '_sg_color', true) ?: '#7229dd';
$tag_icon = get_term_meta($current_tag->term_id, '_sg_icon', true);
$tag_icon_image = get_term_meta($current_tag->term_id, '_sg_icon_image', true);

// Get module options
$show_search = Apbd_wps_knowledge_base::GetModuleOption('archive_search', 'Y');
$show_breadcrumb = Apbd_wps_knowledge_base::GetModuleOption('archive_breadcrumb', 'Y');
$docs_per_page = Apbd_wps_knowledge_base::GetModuleOption('archive_docs_per_page', 10);

// Tags don't have parent/child relationships
$parent_tag = null;
$subtags = array(); // Tags don't have subtags

// Get related tags (other tags that share posts with this tag)
$tag_posts = new WP_Query(array(
    'post_type' => 'sgkb-docs',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'tax_query' => array(
        array(
            'taxonomy' => 'sgkb-docs-tag',
            'terms' => $current_tag->term_id
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

$related_tags = array();
if ($tag_posts->have_posts()) {
    $post_ids = $tag_posts->posts;

    // Get all tags for these posts, excluding current tag
    $all_related_tags = wp_get_object_terms($post_ids, 'sgkb-docs-tag', array(
        'exclude' => array($current_tag->term_id)
    ));

    // Filter and limit related tags
    foreach ($all_related_tags as $related_tag) {
        if ($related_tag->term_id === $current_tag->term_id) continue;

        // Check if tag has non-chatbot posts
        $non_chatbot_posts = new WP_Query(array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'sgkb-docs-tag',
                    'terms' => $related_tag->term_id
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

        if ($non_chatbot_posts->have_posts()) {
            $related_tags[] = $related_tag;
            if (count($related_tags) >= 5) break; // Limit to 5 related tags
        }
        wp_reset_postdata();
    }
}
wp_reset_postdata();

// Helper function for color adjustment (keep from category template)
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

        <!-- Category Header (keeping same class for consistency) -->
        <header class="sgkb-category-header-modern">
            <div class="sgkb-container">

                <?php if ('Y' === $show_breadcrumb) : ?>
                    <!-- Breadcrumb -->
                    <?php sgkb_render_breadcrumbs(); ?>
                <?php endif; ?>

                <!-- Category Info (keeping same structure) -->
                <div class="sgkb-category-hero">
                    <div class="sgkb-category-hero-content">
                        <div class="sgkb-category-hero-text">
                            <h1 class="sgkb-category-title"><?php echo esc_html($current_tag->name); ?></h1>
                            <?php if (!empty($current_tag->description)) : ?>
                                <p class="sgkb-category-description"><?php echo esc_html($current_tag->description); ?></p>
                            <?php endif; ?>
                            <div class="sgkb-category-meta">
                                <span class="sgkb-meta-item">
                                    <?php
                                    // Get actual count excluding chatbot-only posts
                                    $non_chatbot_query = new WP_Query(array(
                                        'post_type' => 'sgkb-docs',
                                        'post_status' => 'publish',
                                        'posts_per_page' => -1,
                                        'fields' => 'ids',
                                        'tax_query' => array(
                                            array(
                                                'taxonomy' => 'sgkb-docs-tag', // Changed to tag
                                                'field' => 'term_id',
                                                'terms' => $current_tag->term_id,
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
                                    $post_count = $non_chatbot_query->found_posts;
                                    wp_reset_postdata();

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
                                    data-category="<?php echo esc_attr($current_tag->slug); ?>">
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
                // Check if sidebar has any content to display (only related tags for tag archives)
                $has_sidebar_content = !empty($related_tags);
                ?>
                <div class="sgkb-category-layout<?php echo !$has_sidebar_content ? ' sgkb-no-sidebar' : ''; ?>">

                    <?php if ($has_sidebar_content) : ?>
                        <!-- Sidebar -->
                        <aside class="sgkb-category-sidebar">

                            <!-- Related Tags (changed from Related Categories) -->
                            <?php if (!empty($related_tags)) : ?>
                                <div class="sgkb-sidebar-section">
                                    <h3 class="sgkb-sidebar-title"><?php esc_html_e('Related Tags', 'support-genix'); ?></h3>
                                    <ul class="sgkb-related-list">
                                        <?php foreach ($related_tags as $related) :
                                            // Get the actual count of non-chatbot posts for this tag
                                            $count_args = array(
                                                'post_type' => 'sgkb-docs',
                                                'post_status' => 'publish',
                                                'posts_per_page' => -1,
                                                'fields' => 'ids',
                                                'tax_query' => array(
                                                    array(
                                                        'taxonomy' => 'sgkb-docs-tag', // Changed to tag
                                                        'field' => 'term_id',
                                                        'terms' => $related->term_id,
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

                                            // Get tag color (might not exist, use default)
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
                                    <?php esc_html_e('There are no articles with this tag yet.', 'support-genix'); ?>
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