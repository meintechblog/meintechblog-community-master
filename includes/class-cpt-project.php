<?php

defined('ABSPATH') || exit;

/**
 * Custom Post Type registration, meta fields, and capability management.
 */
class CM_CPT_Project {

    /**
     * Register the community_project post type.
     */
    public static function register(): void {
        register_post_type('community_project', [
            'labels' => [
                'name'               => __('Community Projects', 'community-master'),
                'singular_name'      => __('Community Project', 'community-master'),
                'add_new'            => __('Add New', 'community-master'),
                'add_new_item'       => __('Add New Project', 'community-master'),
                'edit_item'          => __('Edit Project', 'community-master'),
                'new_item'           => __('New Project', 'community-master'),
                'view_item'          => __('View Project', 'community-master'),
                'search_items'       => __('Search Projects', 'community-master'),
                'not_found'          => __('No projects found', 'community-master'),
                'not_found_in_trash' => __('No projects found in Trash', 'community-master'),
                'menu_name'          => __('Community Master', 'community-master'),
            ],
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'show_in_rest'    => true,
            'capability_type' => 'community_project',
            'map_meta_cap'    => true,
            'supports'        => ['title', 'thumbnail'],
            'menu_icon'       => 'dashicons-groups',
            'menu_position'   => 25,
            'has_archive'     => false,
        ]);
    }

    /**
     * Register meta fields with sanitize and auth callbacks.
     */
    public static function register_meta_fields(): void {
        register_post_meta('community_project', '_community_master_description', [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'auth_callback'     => fn() => current_user_can('edit_community_projects'),
        ]);

        register_post_meta('community_project', '_community_master_github_url', [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => fn() => current_user_can('edit_community_projects'),
        ]);

        register_post_meta('community_project', '_community_master_installer', [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => fn() => current_user_can('edit_community_projects'),
        ]);
    }

    /**
     * Return the full list of primitive capabilities for the community_project CPT.
     *
     * @return string[]
     */
    public static function get_capabilities(): array {
        return [
            'edit_community_projects',
            'edit_others_community_projects',
            'publish_community_projects',
            'read_private_community_projects',
            'delete_community_projects',
            'delete_private_community_projects',
            'delete_published_community_projects',
            'delete_others_community_projects',
            'edit_private_community_projects',
            'edit_published_community_projects',
            'create_community_projects',
        ];
    }

    /**
     * Grant all CPT capabilities to Administrator and Editor roles.
     */
    public static function add_capabilities(): void {
        $caps = self::get_capabilities();

        foreach (['administrator', 'editor'] as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($caps as $cap) {
                    $role->add_cap($cap);
                }
            }
        }
    }

    /**
     * Remove all CPT capabilities from Administrator and Editor roles.
     */
    public static function remove_capabilities(): void {
        $caps = self::get_capabilities();

        foreach (['administrator', 'editor'] as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($caps as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}
