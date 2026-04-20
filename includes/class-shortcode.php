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
     * Dispatches to single-project view when the community_project_slug query
     * var is set (deep-link route: /community-master/<slug>/), otherwise
     * renders the full grid.
     *
     * @param array<string, string>|string $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function render(array|string $atts): string {
        wp_enqueue_style('community-master-frontend');
        wp_enqueue_script('community-master-copy');

        $slug = get_query_var('community_project_slug');
        if (is_string($slug) && $slug !== '') {
            return $this->render_single($slug);
        }

        return $this->render_grid();
    }

    /**
     * Render the full tile grid.
     */
    private function render_grid(): string {
        $projects = get_posts([
            'post_type'      => 'community_project',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
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

    /**
     * Render a single project matched by slug, or a not-found fallback.
     */
    private function render_single(string $slug): string {
        $matches = get_posts([
            'post_type'      => 'community_project',
            'name'           => $slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ]);

        $back_url  = home_url('/community-master/');
        $back_link = '<p class="cm-single__back"><a href="' . esc_url($back_url) . '">&larr; '
            . esc_html__('Alle Projekte', 'community-master') . '</a></p>';

        if (empty($matches)) {
            status_header(404);
            return '<div class="cm-wrapper cm-wrapper--single">'
                . $back_link
                . '<div class="cm-empty-state">'
                . esc_html__('Projekt nicht gefunden.', 'community-master')
                . '</div>'
                . '</div>';
        }

        $project = $matches[0];

        ob_start();
        echo '<div class="cm-wrapper cm-wrapper--single">';
        echo $back_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup, URL is esc_url()'d above
        echo '<div class="cm-grid cm-grid--single">';
        include COMMUNITY_MASTER_DIR . 'templates/single-tile.php';
        echo '</div>';
        echo '</div>';
        return (string) ob_get_clean();
    }
}
