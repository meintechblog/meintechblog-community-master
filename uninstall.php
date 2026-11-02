<?php
/**
 * Community Master uninstall routine.
 *
 * Fires when the plugin is deleted via the WordPress admin or a REST
 * DELETE /wp/v2/plugins call.
 *
 * SAFE DEFAULT: Does NOT delete community_project posts or their meta. Data
 * deletion is opt-in via the `community_master_delete_data_on_uninstall`
 * option (set to the string "1" to enable). This protects users whose data
 * would otherwise be wiped when:
 *   - a duplicate plugin folder (e.g. community-master-2/) is deleted and
 *     WordPress runs this script, hard-deleting posts shared with the other
 *     active copy;
 *   - the plugin is uninstalled for a migration rather than a true removal.
 *
 * Self-contained — does not require any plugin class files.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

$delete_data = get_option('community_master_delete_data_on_uninstall') === '1';

if ($delete_data) {
    $posts = get_posts([
        'post_type'   => 'community_project',
        'numberposts' => -1,
        'post_status' => 'any',
    ]);
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

// Remove capabilities from roles (safe regardless — caps are re-added on activate).
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

if ($delete_data) {
    delete_option('community_master_version');
    delete_option('community_master_intro');
    delete_option('community_master_delete_data_on_uninstall');
}
