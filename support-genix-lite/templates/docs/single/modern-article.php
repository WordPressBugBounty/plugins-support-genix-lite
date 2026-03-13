<?php

/**
 * Modern Single Article Template
 * Displays individual documentation articles with modern layout
 */

defined('ABSPATH') || exit;

// Helper function for reading time
if (!function_exists('sgkb_reading_time')) {
    function sgkb_reading_time($content)
    {
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200); // Average reading speed
        return sprintf(__('%d min read', 'support-genix'), $reading_time);
    }
}

// Get current post data
global $post;
if (!$post) return;

// Get settings
$hide_powered_by = Apbd_wps_settings::GetModuleOption('is_hide_cp_text', 'N');

// Get module options
$show_breadcrumb = Apbd_wps_knowledge_base::GetModuleOption('single_doc_breadcrumb', 'Y');
$show_title = Apbd_wps_knowledge_base::GetModuleOption('single_doc_title', 'Y');
$show_meta = Apbd_wps_knowledge_base::GetModuleOption('single_doc_tags', 'Y');
$show_modified = Apbd_wps_knowledge_base::GetModuleOption('single_doc_modified_date', 'Y');
$show_reaction = Apbd_wps_knowledge_base::GetModuleOption('single_doc_reaction', 'Y');
$show_toc = Apbd_wps_knowledge_base::GetModuleOption('single_doc_toc', 'Y');
$show_author = Apbd_wps_knowledge_base::GetModuleOption('single_doc_author', 'Y');
$show_thumbnail = Apbd_wps_knowledge_base::GetModuleOption('single_doc_thumbnail', 'Y');
$show_lightbox = Apbd_wps_knowledge_base::GetModuleOption('single_doc_image_lightbox', 'Y');

// Get categories
$categories = get_the_terms($post->ID, 'sgkb-docs-category');
$primary_category = ($categories && !is_wp_error($categories)) ? $categories[0] : null;

// Get tags
$tags = get_the_terms($post->ID, 'sgkb-docs-tag');

// Get author data - use post object directly for better theme compatibility
$author_id = $post->post_author;
$author_name = get_the_author_meta('display_name', $author_id);
// Fallback to other name fields if display_name is empty
if (empty($author_name)) {
    $author_name = get_the_author_meta('nickname', $author_id);
}
if (empty($author_name)) {
    $author_name = get_the_author_meta('user_login', $author_id);
}
$author_avatar = get_avatar($author_id, 48);

// Generate table of contents
$content = get_the_content();
$toc_items = [];
if ($show_toc === 'Y') {
    // Extract headings for TOC
    preg_match_all('/<h([2-4])[^>]*>(.*?)<\/h\1>/i', $content, $matches);
    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $heading) {
            $level = $matches[1][$index];
            $text = strip_tags($matches[2][$index]);
            $id = 'section-' . ($index + 1);

            // Add ID to heading in content
            $content = str_replace($heading, preg_replace('/<h' . $level . '/', '<h' . $level . ' id="' . $id . '"', $heading, 1), $content);

            $toc_items[] = [
                'level' => $level,
                'text' => $text,
                'id' => $id
            ];
        }
    }
}

// Get related articles
$related_args = [
    'post_type' => 'sgkb-docs',
    'posts_per_page' => 5,
    'post__not_in' => [$post->ID],
    'orderby' => 'rand',
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => 'only_for_chatbot',
            'compare' => 'NOT EXISTS'
        ],
        [
            'key' => 'only_for_chatbot',
            'value' => '1',
            'compare' => '!='
        ]
    ]
];

if ($primary_category) {
    $related_args['tax_query'] = [
        [
            'taxonomy' => 'sgkb-docs-category',
            'terms' => $primary_category->term_id
        ]
    ];
}

$related_articles = new WP_Query($related_args);
?>

<!-- Modern Article Layout -->
<div class="sgkb-article-modern">

    <!-- Article Header -->
    <header class="sgkb-article-header-modern">
        <div class="sgkb-container">

            <?php if ($show_breadcrumb === 'Y') : ?>
                <!-- Breadcrumbs -->
                <?php sgkb_render_breadcrumbs(); ?>
            <?php endif; ?>

            <?php if ($show_title === 'Y') : ?>
                <!-- Article Title -->
                <h1 class="sgkb-article-title-modern"><?php the_title(); ?></h1>
            <?php endif; ?>

            <?php if ($show_meta === 'Y' || $show_author === 'Y') : ?>
                <!-- Article Meta -->
                <div class="sgkb-article-meta-modern">
                    <?php if ($show_author === 'Y') : ?>
                        <div class="sgkb-article-meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span><?php echo esc_html($author_name); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="sgkb-article-meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-linecap="round" stroke-linejoin="round" />
                            <line x1="16" y1="2" x2="16" y2="6" stroke-linecap="round" stroke-linejoin="round" />
                            <line x1="8" y1="2" x2="8" y2="6" stroke-linecap="round" stroke-linejoin="round" />
                            <line x1="3" y1="10" x2="21" y2="10" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span><?php echo get_the_date(); ?></span>
                    </div>

                    <div class="sgkb-article-meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round" />
                            <polyline points="12 6 12 12 16 14" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span><?php echo sgkb_reading_time(get_the_content()); ?></span>
                    </div>

                    <?php if ($tags && !is_wp_error($tags) && $show_meta === 'Y') : ?>
                        <div class="sgkb-article-meta-item sgkb-article-tags-inline">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="sgkb-article-tag">
                                    #<?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Article Content Area -->
    <div class="sgkb-article-body-modern">
        <div class="sgkb-container">
            <?php
            // Get all categories for the sidebar
            $sidebar_categories = array();

            // Get all categories ordered by _sg_order
            $all_categories = get_terms(array(
                'taxonomy' => 'sgkb-docs-category',
                'hide_empty' => true,
                'meta_key' => '_sg_order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC'
            ));
            if (is_wp_error($all_categories)) {
                $all_categories = array();
            }

            // Filter categories and get their articles
            if (!empty($all_categories)) {
                foreach ($all_categories as $cat) {
                    // Build tax query
                    $tax_query = array(
                        array(
                            'taxonomy' => 'sgkb-docs-category',
                            'field' => 'term_id',
                            'terms' => $cat->term_id
                        )
                    );

                    // Get articles for this category
                    $cat_articles = new WP_Query(array(
                        'post_type' => 'sgkb-docs',
                        'posts_per_page' => 200,
                        'post_status' => 'publish',
                        'tax_query' => $tax_query,
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
                        ),
                        'orderby' => 'menu_order',
                        'order' => 'ASC'
                    ));

                    // Only include categories that have articles
                    if ($cat_articles->have_posts()) {
                        $articles = array();
                        while ($cat_articles->have_posts()) {
                            $cat_articles->the_post();
                            $articles[] = array(
                                'id' => get_the_ID(),
                                'title' => get_the_title(),
                                'permalink' => get_permalink()
                            );
                        }
                        wp_reset_postdata();

                        $sidebar_categories[] = array(
                            'term' => $cat,
                            'articles' => $articles,
                            'is_current' => ($primary_category && $primary_category->term_id === $cat->term_id)
                        );
                    }
                }
            }

            // Calculate total articles across all categories
            $total_sidebar_articles = 0;
            foreach ($sidebar_categories as $cat_data) {
                $total_sidebar_articles += count($cat_data['articles']);
            }

            // Show sidebar only if there are other articles to navigate to (more than just current)
            $show_left_sidebar = !empty($sidebar_categories) && $total_sidebar_articles > 1;

            // Check if we should show the right sidebar (TOC)
            $show_right_sidebar = ($show_toc === 'Y' && !empty($toc_items));

            // Determine layout class
            $layout_class = 'sgkb-article-layout-three-col';
            if (!$show_left_sidebar && !$show_right_sidebar) {
                $layout_class .= ' sgkb-no-sidebars';
            } elseif (!$show_left_sidebar) {
                $layout_class .= ' sgkb-no-left-sidebar';
            } elseif (!$show_right_sidebar) {
                $layout_class .= ' sgkb-no-right-sidebar';
            }
            ?>
            <div class="<?php echo esc_attr($layout_class); ?>">

                <?php if ($show_left_sidebar) : ?>
                    <!-- Left Sidebar: All Categories with Articles -->
                    <aside class="sgkb-article-left-sidebar">
                        <div class="sgkb-sidebar-categories">
                            <?php foreach ($sidebar_categories as $cat_data) :
                                $is_expanded = $cat_data['is_current'];
                            ?>
                                <div class="sgkb-sidebar-category <?php echo $is_expanded ? 'sgkb-expanded' : ''; ?>">
                                    <button type="button" class="sgkb-sidebar-category-header" aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>">
                                        <svg class="sgkb-sidebar-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="sgkb-sidebar-category-name"><?php echo esc_html($cat_data['term']->name); ?></span>
                                        <span class="sgkb-sidebar-category-count"><?php echo count($cat_data['articles']); ?></span>
                                    </button>
                                    <nav class="sgkb-sidebar-category-content" <?php echo !$is_expanded ? 'style="display: none;"' : ''; ?>>
                                        <?php foreach ($cat_data['articles'] as $article) :
                                            $is_active = ($article['id'] === $post->ID);
                                        ?>
                                            <a href="<?php echo esc_url($article['permalink']); ?>" class="sgkb-category-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                                                <?php echo esc_html($article['title']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </nav>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                <?php endif; ?>

                <!-- Main Content -->
                <main class="sgkb-article-content-modern">
                    <?php if ($show_thumbnail === 'Y' && has_post_thumbnail()) : ?>
                        <!-- Featured Image -->
                        <div class="sgkb-article-featured-image">
                            <?php the_post_thumbnail('large', ['class' => 'sgkb-featured-img']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="sgkb-content-wrapper"<?php echo $show_lightbox === 'Y' ? ' data-lightbox="true"' : ''; ?>>
                        <?php echo apply_filters('the_content', $content); ?>
                    </div>

                    <?php if ($show_modified === 'Y') : ?>
                        <!-- Last Modified -->
                        <div class="sgkb-article-modified">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span><?php printf(__('Last updated on %s', 'support-genix'), get_the_modified_date()); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_reaction === 'Y') : ?>
                        <!-- Article Feedback -->
                        <div class="sgkb-article-feedback">
                            <h3><?php _e('Was this article helpful?', 'support-genix'); ?></h3>
                            <div class="sgkb-feedback-buttons">
                                <button class="sgkb-feedback-btn sgkb-feedback-yes" data-article="<?php echo get_the_ID(); ?>" data-type="helpful">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M14 9V5C14 4.20435 13.6839 3.44129 13.1213 2.87868C12.5587 2.31607 11.7956 2 11 2L7 9V22H18.28C18.7623 22.0055 19.2304 21.8364 19.5979 21.524C19.9654 21.2116 20.2077 20.7769 20.28 20.3L21.66 11.3C21.7035 11.0134 21.6842 10.7207 21.6033 10.4423C21.5225 10.1638 21.3821 9.90629 21.1919 9.68751C21.0016 9.46873 20.7661 9.29393 20.5016 9.17522C20.2371 9.0565 19.9499 8.99672 19.66 9H14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M7 9H4C3.46957 9 2.96086 9.21071 2.58579 9.58579C2.21071 9.96086 2 10.4696 2 11V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span><?php _e('Yes', 'support-genix'); ?></span>
                                </button>

                                <button class="sgkb-feedback-btn sgkb-feedback-no" data-article="<?php echo get_the_ID(); ?>" data-type="not-helpful">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M10 15V19C10 19.7956 10.3161 20.5587 10.8787 21.1213C11.4413 21.6839 12.2044 22 13 22L17 15V2H5.72C5.23767 1.99454 4.76962 2.16359 4.40209 2.47599C4.03457 2.78839 3.79232 3.22309 3.72 3.7L2.34 12.7C2.29649 12.9866 2.31583 13.2793 2.39666 13.5577C2.47749 13.8362 2.61794 14.0937 2.80814 14.3125C2.99834 14.5313 3.23392 14.7061 3.49843 14.8248C3.76294 14.9435 4.05009 15.0033 4.34 15H10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M17 2H20C20.5304 2 21.0391 2.21071 21.4142 2.58579C21.7893 2.96086 22 3.46957 22 4V13C22 13.5304 21.7893 14.0391 21.4142 14.4142C21.0391 14.7893 20.5304 15 20 15H17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span><?php _e('No', 'support-genix'); ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Get previous and next articles from the same category
                    if ($primary_category) {
                        $category_articles = new WP_Query([
                            'post_type' => 'sgkb-docs',
                            'posts_per_page' => -1,
                            'tax_query' => [
                                [
                                    'taxonomy' => 'sgkb-docs-category',
                                    'terms' => $primary_category->term_id
                                ]
                            ],
                            'meta_query' => [
                                'relation' => 'OR',
                                [
                                    'key' => 'only_for_chatbot',
                                    'compare' => 'NOT EXISTS'
                                ],
                                [
                                    'key' => 'only_for_chatbot',
                                    'value' => '1',
                                    'compare' => '!='
                                ]
                            ],
                            'orderby' => 'menu_order',
                            'order' => 'ASC',
                            'fields' => 'ids'
                        ]);

                        $article_ids = $category_articles->posts;
                        $current_index = array_search($post->ID, $article_ids);

                        $prev_post = null;
                        $next_post = null;

                        if ($current_index !== false) {
                            if ($current_index > 0) {
                                $prev_post = get_post($article_ids[$current_index - 1]);
                            }
                            if ($current_index < count($article_ids) - 1) {
                                $next_post = get_post($article_ids[$current_index + 1]);
                            }
                        }
                    ?>

                        <?php if ($prev_post || $next_post) : ?>
                            <!-- Article Navigation -->
                            <div class="sgkb-article-navigation">
                                <?php if ($prev_post) : ?>
                                    <a href="<?php echo get_permalink($prev_post); ?>" class="sgkb-nav-card sgkb-nav-prev">
                                        <div class="sgkb-nav-direction">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M19 12H5M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <span><?php _e('PREVIOUS', 'support-genix'); ?></span>
                                        </div>
                                        <h4 class="sgkb-nav-title"><?php echo esc_html($prev_post->post_title); ?></h4>
                                    </a>
                                <?php endif; ?>

                                <?php if ($next_post) : ?>
                                    <a href="<?php echo get_permalink($next_post); ?>" class="sgkb-nav-card sgkb-nav-next">
                                        <div class="sgkb-nav-direction">
                                            <span><?php _e('NEXT', 'support-genix'); ?></span>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <h4 class="sgkb-nav-title"><?php echo esc_html($next_post->post_title); ?></h4>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php } ?>

                    <?php if ($hide_powered_by !== 'Y') : ?>
                        <!-- Powerd By -->
                        <div class="sgkb-article-powered-by">
                            <span><?php printf(__('Powered by %s', 'support-genix'), '<a href="https://supportgenix.com" target="_blank">Support Genix</a>'); ?></span>
                        </div>
                    <?php endif; ?>
                </main>

                <!-- Right Sidebar: Table of Contents -->
                <?php if ($show_right_sidebar) : ?>
                    <aside class="sgkb-article-right-sidebar">
                        <div class="sgkb-toc-wrapper">
                            <h3 class="sgkb-toc-title"><?php _e('ON THIS PAGE', 'support-genix'); ?></h3>
                            <nav class="sgkb-toc-nav">
                                <?php foreach ($toc_items as $item) : ?>
                                    <a href="#<?php echo esc_attr($item['id']); ?>"
                                        class="sgkb-toc-link sgkb-toc-level-<?php echo esc_attr($item['level']); ?>">
                                        <?php echo esc_html($item['text']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                    </aside>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Articles -->
    <?php if ($related_articles->have_posts()) : ?>
        <section class="sgkb-related-articles-modern">
            <div class="sgkb-container">
                <h2 class="sgkb-related-title"><?php _e('Related Articles', 'support-genix'); ?></h2>
                <div class="sgkb-related-grid">
                    <?php while ($related_articles->have_posts()) : $related_articles->the_post(); ?>
                        <article class="sgkb-related-card">
                            <a href="<?php the_permalink(); ?>" class="sgkb-related-link">
                                <h3 class="sgkb-related-card-title"><?php the_title(); ?></h3>
                                <p class="sgkb-related-excerpt"><?php
                                    $excerpt = get_the_excerpt();
                                    if (empty(trim(wp_strip_all_tags($excerpt)))) {
                                        $excerpt = wp_strip_all_tags(get_the_content());
                                    }
                                    echo wp_trim_words($excerpt, 15);
                                ?></p>
                                <span class="sgkb-related-readmore">
                                    <?php _e('Read more', 'support-genix'); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </a>
                        </article>
                    <?php endwhile;
                    wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

</div>

<?php if ($show_lightbox === 'Y') : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof GLightbox !== 'undefined') {
        const lightbox = GLightbox({
            selector: '.sgkb-article-content img:not(.no-lightbox)',
            openEffect: 'fade',
            closeEffect: 'fade',
            cssEfects: {
                fade: { in: 'fadeIn', out: 'fadeOut' }
            }
        });
    }
});
</script>
<?php endif; ?>