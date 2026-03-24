<?php

defined('ABSPATH') || exit;

/**
 * Main plugin orchestrator.
 *
 * Singleton that wires all components via WordPress hooks.
 */
class Community_Master {

    /** @var self|null */
    private static ?self $instance = null;

    /**
     * Return the singleton instance.
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor -- hooks all components.
     */
    private function __construct() {
        add_action('init', [CM_CPT_Project::class, 'register']);
        add_action('init', [CM_CPT_Project::class, 'register_meta_fields']);
        add_filter('rest_pre_insert_community_project', [CM_CPT_Project::class, 'validate_rest_github_url'], 10, 2);
        add_action('rest_api_init', [CM_CPT_Project::class, 'register_rest_fields']);

        new CM_Meta_Boxes();
        new CM_Admin_Columns();
        new CM_Shortcode();
    }
}
