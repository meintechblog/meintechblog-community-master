<?php

defined('ABSPATH') || exit;

/**
 * Template: Tile grid wrapper with search and sort.
 *
 * @var WP_Post[] $projects Available from the shortcode render method.
 */
?>
<div class="cm-wrapper">
    <div class="cm-toolbar">
        <input type="text" class="cm-search__input" placeholder="<?php echo esc_attr__('Projekt suchen…', 'community-master'); ?>" aria-label="<?php echo esc_attr__('Projekte filtern', 'community-master'); ?>">
        <select class="cm-sort__select" aria-label="<?php echo esc_attr__('Sortierung', 'community-master'); ?>">
            <option value="newest"><?php echo esc_html__('Neueste zuerst', 'community-master'); ?></option>
            <option value="name"><?php echo esc_html__('Name (A–Z)', 'community-master'); ?></option>
        </select>
    </div>

    <div class="cm-grid">
        <?php foreach ($projects as $project) : ?>
            <?php include COMMUNITY_MASTER_DIR . 'templates/single-tile.php'; ?>
        <?php endforeach; ?>
    </div>

    <p class="cm-no-results" style="display:none;"><?php echo esc_html__('Keine Projekte gefunden.', 'community-master'); ?></p>
</div>
