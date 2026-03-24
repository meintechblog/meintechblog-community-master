<?php

defined('ABSPATH') || exit;

/**
 * Registers the [community-master] shortcode and handles frontend rendering.
 */
class CM_Shortcode {

    public function __construct() {
        add_shortcode('community-master', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * Register frontend assets (enqueued conditionally in render()).
     */
    public function register_assets(): void {
        wp_register_style(
            'community-master-frontend',
            plugins_url('assets/css/frontend.css', COMMUNITY_MASTER_FILE),
            [],
            COMMUNITY_MASTER_VERSION
        );

        wp_register_script(
            'community-master-copy',
            plugins_url('assets/js/copy-installer.js', COMMUNITY_MASTER_FILE),
            [],
            COMMUNITY_MASTER_VERSION,
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }

    /**
     * Render the [community-master] shortcode output.
     *
     * @param array<string, string>|string $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function render(array|string $atts): string {
        wp_enqueue_style('community-master-frontend');
        wp_enqueue_script('community-master-copy');

        $projects = get_posts([
            'post_type'      => 'community_project',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);

        if (empty($projects)) {
            return '<div class="cm-empty-state">'
                . esc_html__('Noch keine Community-Projekte vorhanden.', 'community-master')
                . '</div>';
        }

        ob_start();
        include COMMUNITY_MASTER_DIR . 'templates/tile-grid.php';
        return (string) ob_get_clean();
    }
}
