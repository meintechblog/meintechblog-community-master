<?php

defined('ABSPATH') || exit;

/**
 * Template: Tile grid wrapper with search.
 *
 * Intro text is managed via the page content in the WordPress editor
 * (write text above the [community-master] shortcode).
 *
 * @var WP_Post[] $projects Available from the shortcode render method.
 */
?>
<div class="cm-wrapper">
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
