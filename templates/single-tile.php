<?php

defined('ABSPATH') || exit;

/**
 * Template: Single project tile.
 *
 * @var WP_Post $project Available from the tile-grid foreach loop.
 */

$description = get_post_meta($project->ID, '_community_master_description', true);
$github_url  = get_post_meta($project->ID, '_community_master_github_url', true);
$installer   = get_post_meta($project->ID, '_community_master_installer', true);
?>
<div class="cm-tile">
    <?php if (has_post_thumbnail($project->ID)) : ?>
        <div class="cm-tile__logo">
            <?php echo get_the_post_thumbnail($project->ID, 'medium'); ?>
        </div>
    <?php endif; ?>

    <h3 class="cm-tile__title"><?php echo esc_html(get_the_title($project->ID)); ?></h3>

    <?php if ($description) : ?>
        <p class="cm-tile__description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>

    <?php if ($installer) : ?>
        <div class="cm-tile__installer">
            <pre><code><?php echo esc_html($installer); ?></code></pre>
            <button class="cm-copy-btn" type="button" data-copy="<?php echo esc_attr($installer); ?>">
                <?php echo esc_html__('Copy', 'community-master'); ?>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($github_url) : ?>
        <a class="cm-tile__github" href="<?php echo esc_url($github_url); ?>" target="_blank" rel="noopener noreferrer">
            <?php echo esc_html__('View on GitHub', 'community-master'); ?>
        </a>
    <?php endif; ?>
</div>
