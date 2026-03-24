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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Enqueue admin scripts for post search autocomplete.
     */
    public function enqueue_admin_scripts(string $hook): void {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'community_project') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_style('wp-jquery-ui-dialog');
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
        $blogpost_id = get_post_meta($post->ID, '_community_master_blogpost_id', true);

        $blogpost_title = '';
        $blogpost_url   = '';
        if ($blogpost_id) {
            $bp = get_post((int) $blogpost_id);
            if ($bp) {
                $blogpost_title = $bp->post_title;
                $blogpost_url   = get_permalink($bp);
            }
        }
        ?>
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
            <tr>
                <th><label for="cm-blogpost-search"><?php esc_html_e('Blogpost', 'community-master'); ?></label></th>
                <td>
                    <div id="cm-blogpost-wrap" style="position:relative;">
                        <input type="hidden" id="cm-blogpost-id" name="_community_master_blogpost_id" value="<?php echo esc_attr($blogpost_id); ?>" />
                        <input type="text" id="cm-blogpost-search" value="<?php echo esc_attr($blogpost_title); ?>" style="width:100%;" placeholder="<?php esc_attr_e('Blogpost suchen…', 'community-master'); ?>" autocomplete="off" />
                        <?php if ($blogpost_id && $blogpost_url) : ?>
                            <p class="description" id="cm-blogpost-preview">
                                <a href="<?php echo esc_url($blogpost_url); ?>" target="_blank" id="cm-blogpost-link">
                                    <?php echo esc_html($blogpost_title); ?> ↗
                                </a>
                                <button type="button" id="cm-blogpost-remove" class="button-link" style="color:#b32d2e;margin-left:8px;">
                                    <?php esc_html_e('Entfernen', 'community-master'); ?>
                                </button>
                            </p>
                        <?php else : ?>
                            <p class="description" id="cm-blogpost-preview" style="display:none;">
                                <a href="#" target="_blank" id="cm-blogpost-link"></a>
                                <button type="button" id="cm-blogpost-remove" class="button-link" style="color:#b32d2e;margin-left:8px;">
                                    <?php esc_html_e('Entfernen', 'community-master'); ?>
                                </button>
                            </p>
                        <?php endif; ?>
                    </div>
                    <script>
                    jQuery(function($) {
                        $('#cm-blogpost-search').autocomplete({
                            source: function(request, response) {
                                $.ajax({
                                    url: '<?php echo esc_url(rest_url('wp/v2/posts')); ?>',
                                    data: {
                                        search: request.term,
                                        per_page: 8,
                                        status: 'publish',
                                        _fields: 'id,title'
                                    },
                                    beforeSend: function(xhr) {
                                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                                    },
                                    success: function(data) {
                                        response($.map(data, function(post) {
                                            return {
                                                label: post.title.rendered,
                                                value: post.title.rendered,
                                                id: post.id
                                            };
                                        }));
                                    }
                                });
                            },
                            minLength: 2,
                            select: function(event, ui) {
                                $('#cm-blogpost-id').val(ui.item.id);
                                var editUrl = '<?php echo esc_url(home_url('/?p=')); ?>' + ui.item.id;
                                $('#cm-blogpost-link').attr('href', editUrl).text(ui.item.label + ' ↗');
                                $('#cm-blogpost-preview').show();
                            }
                        }).autocomplete('instance')._renderItem = function(ul, item) {
                            return $('<li>').append('<div style="padding:4px 8px;">' + item.label + '</div>').appendTo(ul);
                        };

                        $('#cm-blogpost-remove').on('click', function() {
                            $('#cm-blogpost-id').val('');
                            $('#cm-blogpost-search').val('');
                            $('#cm-blogpost-preview').hide();
                        });
                    });
                    </script>
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

        // 5. Sanitize and save installer.
        if (isset($_POST['_community_master_installer'])) {
            $installer = sanitize_text_field(wp_unslash($_POST['_community_master_installer']));
            update_post_meta($post_id, '_community_master_installer', $installer);
        }

        // 6. Save blogpost link.
        if (isset($_POST['_community_master_blogpost_id'])) {
            $blogpost_id = (int) $_POST['_community_master_blogpost_id'];
            if ($blogpost_id > 0) {
                update_post_meta($post_id, '_community_master_blogpost_id', $blogpost_id);
            } else {
                delete_post_meta($post_id, '_community_master_blogpost_id');
            }
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
