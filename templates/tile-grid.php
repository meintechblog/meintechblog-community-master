<?php

defined('ABSPATH') || exit;

/**
 * Template: Tile grid wrapper.
 *
 * @var WP_Post[] $projects Available from the shortcode render method.
 */
?>
<div class="cm-grid">
    <?php foreach ($projects as $project) : ?>
        <?php include COMMUNITY_MASTER_DIR . 'templates/single-tile.php'; ?>
    <?php endforeach; ?>
</div>
