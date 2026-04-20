<?php

defined('ABSPATH') || exit;

if (!class_exists('WP_Upgrader_Skin')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

/**
 * Upgrader skin that captures feedback into an array instead of echoing HTML.
 * Loaded lazily by CM_REST_Self_Update before instantiating Plugin_Upgrader.
 */
class CM_Silent_Upgrader_Skin extends WP_Upgrader_Skin {

    /** @var string[] */
    private array $captured = [];

    public function feedback($feedback, ...$args) {
        if (is_string($feedback)) {
            $this->captured[] = empty($args) ? $feedback : vsprintf($feedback, $args);
        }
    }

    public function error($errors) {
        if (is_wp_error($errors)) {
            foreach ($errors->get_error_messages() as $m) {
                $this->captured[] = 'ERROR: ' . $m;
            }
        } elseif (is_string($errors)) {
            $this->captured[] = 'ERROR: ' . $errors;
        }
    }

    public function header() {}

    public function footer() {}

    /** @return string[] */
    public function get_messages(): array {
        return $this->captured;
    }
}
