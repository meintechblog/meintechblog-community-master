<?php
/**
 * Community Master uninstall routine.
 *
 * Fires when the plugin is deleted via the WordPress admin.
 * Self-contained -- does not require any plugin class files.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

// Delete all community_project posts (including trashed).
$posts = get_posts([
    'post_type'   => 'community_project',
    'numberposts' => -1,
    'post_status' => 'any',
]);
foreach ($posts as $post) {
    wp_delete_post($post->ID, true); // Force delete, skip trash
}

// Remove capabilities from roles.
// Duplicated inline -- uninstall.php must be self-contained.
$caps = [
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
foreach (['administrator', 'editor'] as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        foreach ($caps as $cap) {
            $role->remove_cap($cap);
        }
    }
}

// Delete plugin options.
delete_option('community_master_version');
