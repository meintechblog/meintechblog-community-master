<?php

defined('ABSPATH') || exit;

/**
 * Template: Tile grid wrapper with intro and search.
 *
 * @var WP_Post[] $projects Available from the shortcode render method.
 */
?>
<div class="cm-wrapper">
    <div class="cm-intro">
        <p><?php echo esc_html(get_option('community_master_intro', 'Hier findest du unsere Community-Projekte — Open-Source-Tools zum Selbsthosten. Jedes Projekt hat ein eigenes GitHub-Repository mit Dokumentation und One-Line-Installer.')); ?></p>
    </div>

    <div class="cm-search">
        <input type="text" class="cm-search__input" placeholder="<?php echo esc_attr__('Projekt suchen…', 'community-master'); ?>" aria-label="<?php echo esc_attr__('Projekte filtern', 'community-master'); ?>">
    </div>

    <div class="cm-grid">
        <?php foreach ($projects as $project) : ?>
            <?php include COMMUNITY_MASTER_DIR . 'templates/single-tile.php'; ?>
        <?php endforeach; ?>
    </div>

    <p class="cm-no-results" style="display:none;"><?php echo esc_html__('Keine Projekte gefunden.', 'community-master'); ?></p>
</div>
