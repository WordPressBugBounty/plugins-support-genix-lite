<?php

/**
 * Archive pages - Modern Layout Only
 */

defined('ABSPATH') || exit;

if (!sgkb_is_fse_theme()) {
    get_header();
}

// Modern layout for all archive pages
if (!is_search()) {
    // Get current page context for intelligent routing
    $context = sgkb_get_current_context();

    // Route to appropriate template based on context type
    switch ($context['type']) {
        case 'category':
            // Category archive
            sgkb_get_template_part('archive/modern-category');
            break;

        case 'tag':
            // Tag archive
            sgkb_get_template_part('archive/modern-tag');
            break;

        case 'main_archive':
        default:
            // Main archive page
            sgkb_get_template_part('archive/modern-grid');
            break;
    }
} else {
    // Search results page - use modern layout
?>
    <div class="sgkb-container" style="padding: 40px 20px;">
        <h1 style="font-size: 32px; margin-bottom: 24px;">
            <?php printf(__('Search Results for: %s', 'support-genix'), '<span>' . get_search_query() . '</span>'); ?>
        </h1>

        <?php if (have_posts()) : ?>
            <div class="sgkb-search-results" style="display: grid; gap: 20px;">
                <?php while (have_posts()) : the_post(); ?>
                    <article style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h2 style="margin: 0 0 12px 0; font-size: 20px;">
                            <a href="<?php the_permalink(); ?>" style="color: #111827; text-decoration: none;">
                                <?php the_title(); ?>
                            </a>
                        </h2>
                        <div style="color: #6b7280;">
                            <?php echo wp_trim_words(get_the_excerpt(), 30); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php _e('No results found. Please try different keywords.', 'support-genix'); ?></p>
        <?php endif; ?>
    </div>
<?php
}

if (!sgkb_is_fse_theme()) {
    get_footer();
}
?>