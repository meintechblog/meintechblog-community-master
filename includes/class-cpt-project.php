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
            'supports'        => ['title', 'editor', 'thumbnail'],
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
            'auth_callback'     => function () { return current_user_can('edit_community_projects'); },
        ]);

        register_post_meta('community_project', '_community_master_github_url', [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => function () { return current_user_can('edit_community_projects'); },
        ]);

        register_post_meta('community_project', '_community_master_installer', [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () { return current_user_can('edit_community_projects'); },
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
     * Validate GitHub URL on REST API create/update.
     *
     * Rejects non-github.com URLs with a 400 WP_Error, mirroring the
     * validation in CM_Meta_Boxes::save_meta().
     *
     * @param WP_Post|WP_Error $post    Post object (or error from earlier filter).
     * @param WP_REST_Request  $request REST request.
     * @return WP_Post|WP_Error
     */
    public static function validate_rest_github_url( $post, WP_REST_Request $request ) {
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $meta = $request->get_param( 'meta' );

        if ( is_array( $meta ) && ! empty( $meta['_community_master_github_url'] ) ) {
            $url = $meta['_community_master_github_url'];

            if ( strpos( $url, 'https://github.com/' ) !== 0 ) {
                return new WP_Error(
                    'rest_invalid_github_url',
                    __( 'GitHub URL must start with https://github.com/', 'community-master' ),
                    [ 'status' => 400 ]
                );
            }
        }

        return $post;
    }

    /**
     * Register custom REST fields for the community_project post type.
     *
     * Exposes menu_order as a readable/writable integer field.
     */
    public static function register_rest_fields(): void {
        register_rest_field( 'community_project', 'menu_order', [
            'get_callback'    => function ( $post ) {
                return (int) get_post( $post['id'] )->menu_order;
            },
            'update_callback' => function ( $value, $post ) {
                wp_update_post( [
                    'ID'         => $post->ID,
                    'menu_order' => (int) $value,
                ] );
            },
            'schema'          => [
                'type'        => 'integer',
                'description' => 'Display order for project tiles',
                'context'     => [ 'view', 'edit' ],
            ],
        ] );

        // Expose meta fields via register_rest_field
        // Description uses native 'content' field (Gutenberg editor)
        $meta_fields = [
            'community_master_github_url' => [
                'meta_key'  => '_community_master_github_url',
                'type'      => 'string',
                'desc'      => 'GitHub repository URL',
                'sanitize'  => 'esc_url_raw',
            ],
            'community_master_installer' => [
                'meta_key'  => '_community_master_installer',
                'type'      => 'string',
                'desc'      => 'One-line install command',
                'sanitize'  => 'sanitize_text_field',
            ],
            'community_master_blogpost_id' => [
                'meta_key'  => '_community_master_blogpost_id',
                'type'      => 'integer',
                'desc'      => 'Linked blog post ID',
                'sanitize'  => 'absint',
            ],
        ];

        foreach ( $meta_fields as $field_name => $config ) {
            register_rest_field( 'community_project', $field_name, [
                'get_callback'    => function ( $post ) use ( $config ) {
                    return get_post_meta( $post['id'], $config['meta_key'], true );
                },
                'update_callback' => function ( $value, $post ) use ( $config ) {
                    update_post_meta( $post->ID, $config['meta_key'], call_user_func( $config['sanitize'], $value ) );
                },
                'schema'          => [
                    'type'        => $config['type'],
                    'description' => $config['desc'],
                    'context'     => [ 'view', 'edit' ],
                ],
            ] );
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
