<?php

defined('ABSPATH') || exit;

/**
 * Template: Single project tile.
 *
 * @var WP_Post $project Available from the tile-grid foreach loop.
 */

$description  = $project->post_content;
$github_url   = get_post_meta($project->ID, '_community_master_github_url', true);
$installer    = get_post_meta($project->ID, '_community_master_installer', true);
$proxmox      = get_post_meta($project->ID, '_community_master_proxmox', true);
$can_edit     = current_user_can('edit_community_project', $project->ID);
$has_logo     = has_post_thumbnail($project->ID);

// Get linked blogpost IDs (with migration from old single field)
$blogpost_ids = get_post_meta($project->ID, '_community_master_blogpost_ids', true);
if (!is_array($blogpost_ids) || empty($blogpost_ids)) {
    $old_id = get_post_meta($project->ID, '_community_master_blogpost_id', true);
    $blogpost_ids = $old_id ? [(int) $old_id] : [];
}

// Load blogpost objects (newest first)
$blogposts = [];
if (!empty($blogpost_ids)) {
    $blogposts = get_posts([
        'post_type'      => 'post',
        'post__in'       => $blogpost_ids,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    ]);
}
$bp_count = count($blogposts);
?>
<div class="cm-tile<?php echo $has_logo ? ' cm-tile--has-logo' : ''; ?>" data-cm-title="<?php echo esc_attr(strtolower(get_the_title($project->ID))); ?>" data-cm-desc="<?php echo esc_attr(strtolower(wp_strip_all_tags($description))); ?>" data-cm-date="<?php echo esc_attr($project->post_date); ?>">
    <?php if ($has_logo) : ?>
        <div class="cm-tile__logo">
            <?php echo get_the_post_thumbnail($project->ID, 'community-master-icon'); ?>
        </div>
    <?php endif; ?>

    <div class="cm-tile__body">
        <div class="cm-tile__header">
            <h3 class="cm-tile__title">
                <?php echo esc_html(get_the_title($project->ID)); ?>
                <?php if ($proxmox) : ?>
                    <span class="cm-badge cm-badge--proxmox" title="<?php esc_attr_e('Setzt Proxmox VE voraus', 'community-master'); ?>">Proxmox</span>
                <?php endif; ?>
            </h3>
            <div class="cm-tile__header-links">
                <?php if ($github_url) : ?>
                    <a class="cm-tile__btn cm-tile__btn--github" href="<?php echo esc_url($github_url); ?>" target="_blank" rel="noopener noreferrer">GitHub ↗</a>
                <?php endif; ?>
                <?php if ($bp_count > 0) : ?>
                    <a class="cm-tile__btn cm-tile__btn--blogpost" href="<?php echo esc_url(get_permalink($blogposts[0])); ?>">
                        <?php echo $bp_count === 1
                            ? esc_html__('Blogartikel →', 'community-master')
                            : sprintf(esc_html__('%d Blogartikel →', 'community-master'), $bp_count); ?>
                    </a>
                <?php endif; ?>
                <?php if ($can_edit) : ?>
                    <a class="cm-tile__edit" href="<?php echo esc_url(get_edit_post_link($project->ID)); ?>" title="<?php esc_attr_e('Bearbeiten', 'community-master'); ?>">&#9998;</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($description) : ?>
            <div class="cm-tile__description"><?php echo wp_kses_post(do_blocks(wpautop($description))); ?></div>
        <?php endif; ?>

        <?php if ($installer) : ?>
            <div class="cm-tile__installer">
                <p class="cm-tile__installer-label"><?php echo esc_html__('Mit einem Befehl installieren — einfach kopieren und auf dem Zielserver ausführen:', 'community-master'); ?></p>
                <pre><code><?php echo esc_html($installer); ?></code></pre>
                <button class="cm-copy-btn" type="button" data-copy="<?php echo esc_attr($installer); ?>">
                    <?php echo esc_html__('Copy', 'community-master'); ?>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($bp_count > 0) : ?>
            <div class="cm-tile__blogposts">
                <p class="cm-tile__blogposts-label">
                    <?php echo $bp_count === 1
                        ? esc_html__('Mehr Infos im Blogpost:', 'community-master')
                        : esc_html__('Mehr Infos in den Blogposts:', 'community-master'); ?>
                </p>
                <?php foreach ($blogposts as $bp) :
                    $bp_parts = get_extended($bp->post_content);
                    $bp_excerpt = $bp_parts['main'] ? $bp_parts['main'] : wp_trim_words($bp->post_content, 40);
                    $bp_thumb = get_the_post_thumbnail_url($bp, 'medium');
                    ?>
                    <details class="cm-tile__blogpost-preview">
                        <summary class="cm-tile__blogpost-summary">
                            <span class="cm-tile__blogpost-icon">📄</span>
                            <span class="cm-tile__blogpost-title"><?php echo esc_html($bp->post_title); ?></span>
                            <small class="cm-tile__blogpost-date"><?php echo esc_html(get_the_date('d.m.Y', $bp)); ?></small>
                            <span class="cm-tile__blogpost-toggle"></span>
                        </summary>
                        <div class="cm-tile__blogpost-content">
                            <?php if ($bp_thumb) : ?>
                                <a href="<?php echo esc_url(get_permalink($bp)); ?>" class="cm-tile__blogpost-thumb">
                                    <img src="<?php echo esc_url($bp_thumb); ?>" alt="<?php echo esc_attr($bp->post_title); ?>" loading="lazy" />
                                </a>
                            <?php endif; ?>
                            <div class="cm-tile__blogpost-text">
                                <?php echo wp_kses_post(wpautop($bp_excerpt)); ?>
                                <a href="<?php echo esc_url(get_permalink($bp)); ?>" class="cm-tile__blogpost-readmore"><?php echo esc_html__('Weiterlesen →', 'community-master'); ?></a>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
