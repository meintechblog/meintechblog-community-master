<?php
/**
 * Plugin Name: Community Master
 * Description: Manage and display community projects on meintechblog.de
 * Version:     1.0.0
 * Author:      meintechblog
 * Text Domain: community-master
 * Requires PHP: 7.4
 * Requires at least: 6.6
 */

defined('ABSPATH') || exit;

define('COMMUNITY_MASTER_VERSION', '1.0.0');
define('COMMUNITY_MASTER_FILE', __FILE__);
define('COMMUNITY_MASTER_DIR', plugin_dir_path(__FILE__));

require_once COMMUNITY_MASTER_DIR . 'includes/class-community-master.php';
require_once COMMUNITY_MASTER_DIR . 'includes/class-cpt-project.php';
require_once COMMUNITY_MASTER_DIR . 'includes/class-meta-boxes.php';
require_once COMMUNITY_MASTER_DIR . 'includes/class-admin-columns.php';
require_once COMMUNITY_MASTER_DIR . 'includes/class-shortcode.php';

Community_Master::instance();

// Activation
register_activation_hook(__FILE__, function (): void {
    CM_CPT_Project::register();
    CM_CPT_Project::add_capabilities();
    flush_rewrite_rules();
    update_option('community_master_version', COMMUNITY_MASTER_VERSION);
});

// Deactivation
register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});
