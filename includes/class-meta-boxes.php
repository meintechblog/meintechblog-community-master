<?php

defined('ABSPATH') || exit;

/**
 * Meta box rendering and save handler for community projects.
 */
class CM_Meta_Boxes {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_community_project', [$this, 'save_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts(string $hook): void {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'community_project') {
            return;
        }
        wp_enqueue_script('jquery');
    }

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
            'community_master_blogposts',
            __('Verknüpfte Blogposts', 'community-master'),
            [$this, 'render_blogposts_meta_box'],
            'community_project',
            'normal',
            'default'
        );

    }

    public function render_fields_meta_box(WP_Post $post): void {
        wp_nonce_field('community_master_save_meta', 'community_master_nonce');

        $github_url = get_post_meta($post->ID, '_community_master_github_url', true);
        $installer  = get_post_meta($post->ID, '_community_master_installer', true);
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
                <th><?php esc_html_e('Tags', 'community-master'); ?></th>
                <td>
                    <?php
                    $tags = [
                        'proxmox'   => __('Proxmox', 'community-master'),
                        'wordpress' => __('WordPress', 'community-master'),
                    ];
                    foreach ($tags as $tag_key => $tag_label) :
                        $checked = get_post_meta($post->ID, '_community_master_tag_' . $tag_key, true);
                        ?>
                        <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin-right:16px;">
                            <input type="checkbox" name="_community_master_tags[]" value="<?php echo esc_attr($tag_key); ?>" <?php checked($checked, '1'); ?> />
                            <?php echo esc_html($tag_label); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Zeigt Badges beim Eintrag an.', 'community-master'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function render_blogposts_meta_box(WP_Post $post): void {
        // Get linked post IDs (migrate from old single field if needed)
        $linked_ids = get_post_meta($post->ID, '_community_master_blogpost_ids', true);
        if (!is_array($linked_ids)) {
            $linked_ids = [];
            // Migrate old single blogpost_id
            $old_id = get_post_meta($post->ID, '_community_master_blogpost_id', true);
            if ($old_id) {
                $linked_ids = [(int) $old_id];
            }
        }

        // Get recent unlinked posts
        $recent_posts = get_posts([
            'post_type'      => 'post',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => !empty($linked_ids) ? $linked_ids : [0],
        ]);
        ?>
        <div id="cm-blogposts-wrap">
            <!-- Search field -->
            <div style="margin-bottom:12px;">
                <input type="text" id="cm-blogpost-search" style="width:100%;padding:8px;" placeholder="<?php esc_attr_e('Blogpost suchen und hinzufügen…', 'community-master'); ?>" autocomplete="off" />
                <div id="cm-blogpost-results" style="display:none;border:1px solid #ddd;border-top:none;max-height:200px;overflow-y:auto;background:#fff;"></div>
            </div>

            <!-- Linked posts list -->
            <ul id="cm-blogpost-list" style="margin:0;padding:0;list-style:none;">
                <?php foreach ($linked_ids as $bp_id) :
                    $bp = get_post($bp_id);
                    if (!$bp) continue;
                    ?>
                    <li data-id="<?php echo esc_attr($bp_id); ?>" style="display:flex;align-items:center;gap:8px;padding:6px 8px;margin-bottom:4px;background:rgba(128,128,128,0.05);border-radius:4px;">
                        <span style="flex:1;">
                            <strong><?php echo esc_html($bp->post_title); ?></strong>
                            <small style="opacity:0.5;margin-left:6px;"><?php echo esc_html(get_the_date('d.m.Y', $bp)); ?></small>
                        </span>
                        <a href="<?php echo esc_url(get_permalink($bp)); ?>" target="_blank" style="font-size:12px;opacity:0.5;">↗</a>
                        <button type="button" class="cm-bp-remove button-link" style="color:#b32d2e;font-size:18px;line-height:1;padding:0 4px;" title="Entfernen">&times;</button>
                        <input type="hidden" name="_community_master_blogpost_ids[]" value="<?php echo esc_attr($bp_id); ?>" />
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (empty($linked_ids)) : ?>
                <p id="cm-blogpost-empty" style="opacity:0.5;font-style:italic;margin:4px 0;">Noch keine Blogposts verknüpft.</p>
            <?php endif; ?>

            <!-- Recent unlinked posts -->
            <?php if (!empty($recent_posts)) : ?>
                <div id="cm-blogpost-recent" style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(128,128,128,0.15);">
                    <small style="opacity:0.5;display:block;margin-bottom:6px;"><?php esc_html_e('Letzte Blogposts:', 'community-master'); ?></small>
                    <?php foreach ($recent_posts as $rp) : ?>
                        <div class="cm-bp-suggestion" data-id="<?php echo esc_attr($rp->ID); ?>" data-title="<?php echo esc_attr($rp->post_title); ?>" data-date="<?php echo esc_attr(get_the_date('d.m.Y', $rp)); ?>" data-url="<?php echo esc_url(get_permalink($rp)); ?>" style="display:flex;align-items:center;gap:8px;padding:4px 8px;cursor:pointer;border-radius:4px;">
                            <button type="button" class="cm-bp-add button-link" style="color:#2271b1;font-size:18px;line-height:1;font-weight:bold;padding:0 4px;">+</button>
                            <span style="flex:1;"><?php echo esc_html($rp->post_title); ?></span>
                            <small style="opacity:0.4;"><?php echo esc_html(get_the_date('d.m.Y', $rp)); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(function($) {
            var $list = $('#cm-blogpost-list');
            var $empty = $('#cm-blogpost-empty');
            var $search = $('#cm-blogpost-search');
            var $results = $('#cm-blogpost-results');
            var searchTimer;

            function getLinkedIds() {
                return $list.find('input[name="_community_master_blogpost_ids[]"]').map(function() {
                    return parseInt(this.value);
                }).get();
            }

            function addPost(id, title, date, url) {
                if (getLinkedIds().indexOf(id) !== -1) return;
                var li = '<li data-id="' + id + '" style="display:flex;align-items:center;gap:8px;padding:6px 8px;margin-bottom:4px;background:rgba(128,128,128,0.05);border-radius:4px;">' +
                    '<span style="flex:1;"><strong>' + $('<span>').text(title).html() + '</strong><small style="opacity:0.5;margin-left:6px;">' + date + '</small></span>' +
                    '<a href="' + url + '" target="_blank" style="font-size:12px;opacity:0.5;">↗</a>' +
                    '<button type="button" class="cm-bp-remove button-link" style="color:#b32d2e;font-size:18px;line-height:1;padding:0 4px;" title="Entfernen">&times;</button>' +
                    '<input type="hidden" name="_community_master_blogpost_ids[]" value="' + id + '" /></li>';
                $list.prepend(li);
                $empty.hide();
                // Hide from suggestions
                $('.cm-bp-suggestion[data-id="' + id + '"]').slideUp(150);
            }

            // Remove post
            $list.on('click', '.cm-bp-remove', function() {
                var $li = $(this).closest('li');
                var id = $li.data('id');
                $li.slideUp(150, function() {
                    $(this).remove();
                    if ($list.children().length === 0) $empty.show();
                    // Show in suggestions again
                    $('.cm-bp-suggestion[data-id="' + id + '"]').slideDown(150);
                });
            });

            // Add from suggestions
            $(document).on('click', '.cm-bp-add', function(e) {
                e.stopPropagation();
                var $sug = $(this).closest('.cm-bp-suggestion');
                addPost($sug.data('id'), $sug.data('title'), $sug.data('date'), $sug.data('url'));
            });

            // Search
            $search.on('input', function() {
                clearTimeout(searchTimer);
                var q = this.value.trim();
                if (q.length < 2) { $results.hide(); return; }
                searchTimer = setTimeout(function() {
                    $.ajax({
                        url: '<?php echo esc_url(rest_url('wp/v2/posts')); ?>',
                        data: { search: q, per_page: 6, status: 'publish', _fields: 'id,title,date,link' },
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                        },
                        success: function(data) {
                            var linked = getLinkedIds();
                            var html = '';
                            $.each(data, function(i, p) {
                                if (linked.indexOf(p.id) !== -1) return;
                                var d = new Date(p.date);
                                var dateStr = ('0'+d.getDate()).slice(-2)+'.'+('0'+(d.getMonth()+1)).slice(-2)+'.'+d.getFullYear();
                                html += '<div class="cm-bp-suggestion" data-id="' + p.id + '" data-title="' + $('<span>').text(p.title.rendered).html() + '" data-date="' + dateStr + '" data-url="' + p.link + '" style="display:flex;align-items:center;gap:8px;padding:6px 8px;cursor:pointer;">' +
                                    '<button type="button" class="cm-bp-add button-link" style="color:#2271b1;font-size:18px;line-height:1;font-weight:bold;padding:0 4px;">+</button>' +
                                    '<span style="flex:1;">' + p.title.rendered + '</span>' +
                                    '<small style="opacity:0.4;">' + dateStr + '</small></div>';
                            });
                            $results.html(html || '<div style="padding:8px;opacity:0.5;">Keine Ergebnisse</div>').show();
                        }
                    });
                }, 300);
            });

            $search.on('blur', function() { setTimeout(function() { $results.hide(); }, 200); });
            $search.on('focus', function() { if ($results.html()) $results.show(); });
        });
        </script>
        <?php
    }

    public function save_meta(int $post_id, WP_Post $post): void {
        $nonce = isset($_POST['community_master_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['community_master_nonce']))
            : '';

        if (!wp_verify_nonce($nonce, 'community_master_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_community_project', $post_id)) {
            return;
        }

        // GitHub URL
        if (isset($_POST['_community_master_github_url'])) {
            $github_url = esc_url_raw(wp_unslash($_POST['_community_master_github_url']));
            if ($github_url !== '' && strpos($github_url, 'https://github.com/') !== 0) {
                $github_url = '';
            }
            update_post_meta($post_id, '_community_master_github_url', $github_url);
        }

        // Installer
        if (isset($_POST['_community_master_installer'])) {
            $installer = sanitize_text_field(wp_unslash($_POST['_community_master_installer']));
            update_post_meta($post_id, '_community_master_installer', $installer);
        }

        // Tags (badges)
        $all_tags = ['proxmox', 'wordpress'];
        $selected = isset($_POST['_community_master_tags']) ? array_map('sanitize_key', (array) $_POST['_community_master_tags']) : [];
        foreach ($all_tags as $tag) {
            if (in_array($tag, $selected, true)) {
                update_post_meta($post_id, '_community_master_tag_' . $tag, '1');
                // Keep legacy field for backward compat
                if ($tag === 'proxmox') {
                    update_post_meta($post_id, '_community_master_proxmox', '1');
                }
            } else {
                delete_post_meta($post_id, '_community_master_tag_' . $tag);
                if ($tag === 'proxmox') {
                    delete_post_meta($post_id, '_community_master_proxmox');
                }
            }
        }

        // Blogpost IDs (multiple)
        if (isset($_POST['_community_master_blogpost_ids'])) {
            $ids = array_map('absint', (array) $_POST['_community_master_blogpost_ids']);
            $ids = array_filter($ids);
            update_post_meta($post_id, '_community_master_blogpost_ids', $ids);
        } else {
            update_post_meta($post_id, '_community_master_blogpost_ids', []);
        }

    }
}
