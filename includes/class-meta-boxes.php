<?php

defined('ABSPATH') || exit;

/**
 * Meta box rendering and save handler for community projects.
 */
class CM_Meta_Boxes {

    /**
     * Constructor -- self-registers WordPress hooks.
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_community_project', [$this, 'save_meta'], 10, 2);
    }

    /**
     * Register meta boxes on the community_project edit screen.
     */
    public function register_meta_boxes(): void {
        add_meta_box(
            'community_master_fields',
            __('Project Details', 'community-master'),
            [$this, 'render_fields_meta_box'],
            'community_project',
            'normal',
            'high'
        );

        add_meta_box(
            'community_master_sort_order',
            __('Sortierung', 'community-master'),
            [$this, 'render_sort_order_meta_box'],
            'community_project',
            'side',
            'default'
        );
    }

    /**
     * Render the Project Details meta box.
     */
    public function render_fields_meta_box(WP_Post $post): void {
        wp_nonce_field('community_master_save_meta', 'community_master_nonce');

        $github_url  = get_post_meta($post->ID, '_community_master_github_url', true);
        $installer   = get_post_meta($post->ID, '_community_master_installer', true);
        ?>
        <p class="description" style="margin-bottom:1em;"><?php esc_html_e('Die Beschreibung wird über den Editor oben eingegeben.', 'community-master'); ?></p>
        <table class="form-table">
            <tr>
                <th><label for="cm-github-url"><?php esc_html_e('GitHub URL', 'community-master'); ?></label></th>
                <td>
                    <input type="url" id="cm-github-url" name="_community_master_github_url" value="<?php echo esc_attr($github_url); ?>" style="width:100%;" placeholder="https://github.com/org/repo" />
                </td>
            </tr>
            <tr>
                <th><label for="cm-installer"><?php esc_html_e('One-Line-Installer', 'community-master'); ?></label></th>
                <td>
                    <input type="text" id="cm-installer" name="_community_master_installer" value="<?php echo esc_attr($installer); ?>" style="width:100%;" placeholder="curl -sSL https://example.com/install.sh | bash" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the Sortierung (sort order) meta box.
     */
    public function render_sort_order_meta_box(WP_Post $post): void {
        ?>
        <label for="cm-menu-order"><?php esc_html_e('Reihenfolge (niedrigere Zahl = weiter oben)', 'community-master'); ?></label>
        <input type="number" id="cm-menu-order" name="community_master_menu_order" value="<?php echo esc_attr($post->menu_order); ?>" min="0" step="1" style="width:100%;" />
        <?php
    }

    /**
     * Save meta fields and menu_order on post save.
     */
    public function save_meta(int $post_id, WP_Post $post): void {
        // 1. Verify nonce (SEC-04).
        $nonce = isset($_POST['community_master_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['community_master_nonce']))
            : '';

        if (!wp_verify_nonce($nonce, 'community_master_save_meta')) {
            return;
        }

        // 2. Skip autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 3. Check permissions.
        if (!current_user_can('edit_community_project', $post_id)) {
            return;
        }

        // 4. Sanitize and save GitHub URL (FIELD-04: reject non-github.com URLs).
        if (isset($_POST['_community_master_github_url'])) {
            $github_url = esc_url_raw(wp_unslash($_POST['_community_master_github_url']));

            if ($github_url !== '' && strpos($github_url, 'https://github.com/') !== 0) {
                $github_url = '';
            }

            update_post_meta($post_id, '_community_master_github_url', $github_url);
        }

        // 6. Sanitize and save installer.
        if (isset($_POST['_community_master_installer'])) {
            $installer = sanitize_text_field(wp_unslash($_POST['_community_master_installer']));
            update_post_meta($post_id, '_community_master_installer', $installer);
        }

        // 7. Save menu_order (FIELD-06) with infinite loop prevention.
        if (isset($_POST['community_master_menu_order'])) {
            $order = (int) $_POST['community_master_menu_order'];

            remove_action('save_post_community_project', [$this, 'save_meta']);
            wp_update_post([
                'ID'         => $post_id,
                'menu_order' => $order,
            ]);
            add_action('save_post_community_project', [$this, 'save_meta'], 10, 2);
        }
    }
}
