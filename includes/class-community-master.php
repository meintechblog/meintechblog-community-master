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
    }
}
