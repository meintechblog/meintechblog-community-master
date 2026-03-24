<?php

defined('ABSPATH') || exit;

/**
 * Custom admin columns for the community_project list table.
 */
class CM_Admin_Columns {

    /**
     * Constructor -- self-registers WordPress hooks.
     */
    public function __construct() {
        add_filter('manage_community_project_posts_columns', [$this, 'set_columns']);
        add_action('manage_community_project_posts_custom_column', [$this, 'render_column'], 10, 2);
        add_filter('manage_edit-community_project_sortable_columns', [$this, 'set_sortable_columns']);
    }

    /**
     * Insert custom columns after the Title column.
     *
     * @param array<string, string> $columns Existing columns.
     * @return array<string, string> Modified columns.
     */
    public function set_columns(array $columns): array {
        $new_columns = [];

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;

            if ($key === 'title') {
                $new_columns['cm_logo']   = __('Logo', 'community-master');
                $new_columns['cm_github'] = __('GitHub URL', 'community-master');
                $new_columns['cm_sort']   = __('Sortierung', 'community-master');
            }
        }

        return $new_columns;
    }

    /**
     * Render content for custom columns.
     */
    public function render_column(string $column, int $post_id): void {
        switch ($column) {
            case 'cm_logo':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [40, 40]);
                } else {
                    echo '&mdash;';
                }
                break;

            case 'cm_github':
                $url = get_post_meta($post_id, '_community_master_github_url', true);
                if ($url !== '') {
                    echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a>';
                } else {
                    echo '&mdash;';
                }
                break;

            case 'cm_sort':
                $post = get_post($post_id);
                echo esc_html((string) $post->menu_order);
                break;
        }
    }

    /**
     * Make the Sortierung column sortable.
     *
     * @param array<string, string> $columns Sortable columns.
     * @return array<string, string> Modified sortable columns.
     */
    public function set_sortable_columns(array $columns): array {
        $columns['cm_sort'] = 'menu_order';
        return $columns;
    }
}
