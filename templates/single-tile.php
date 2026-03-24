<?php

defined('ABSPATH') || exit;

/**
 * Template: Single project tile.
 *
 * @var WP_Post $project Available from the tile-grid foreach loop.
 */

$description = $project->post_content;
$github_url  = get_post_meta($project->ID, '_community_master_github_url', true);
$installer   = get_post_meta($project->ID, '_community_master_installer', true);
$can_edit    = current_user_can('edit_community_project', $project->ID);
$has_logo    = has_post_thumbnail($project->ID);
?>
<div class="cm-tile<?php echo $has_logo ? ' cm-tile--has-logo' : ''; ?>" data-cm-title="<?php echo esc_attr(strtolower(get_the_title($project->ID))); ?>" data-cm-desc="<?php echo esc_attr(strtolower(wp_strip_all_tags($description))); ?>">
    <?php if ($has_logo) : ?>
        <div class="cm-tile__logo">
            <?php echo get_the_post_thumbnail($project->ID, 'thumbnail'); ?>
        </div>
    <?php endif; ?>

    <div class="cm-tile__body">
        <div class="cm-tile__header">
            <h3 class="cm-tile__title"><?php echo esc_html(get_the_title($project->ID)); ?></h3>
            <?php if ($can_edit) : ?>
                <a class="cm-tile__edit" href="<?php echo esc_url(get_edit_post_link($project->ID)); ?>" title="<?php esc_attr_e('Bearbeiten', 'community-master'); ?>">&#9998;</a>
            <?php endif; ?>
        </div>

        <?php if ($description) : ?>
            <div class="cm-tile__description"><?php echo wp_kses_post(do_blocks(wpautop($description))); ?></div>
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
</div>
