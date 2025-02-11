<?php
if (!defined('ABSPATH')) {
    exit;
}

class Profile_Comments_Admin {
    public function init() {
        // Admin hooks
        add_filter('manage_edit-comments_columns', array($this, 'add_profile_comment_columns'));
        add_action('manage_comments_custom_column', array($this, 'manage_profile_comment_columns'), 10, 2);
        add_filter('manage_edit-comments_sortable_columns', array($this, 'profile_comments_sortable_columns'));
        add_filter('comments_clauses', array($this, 'profile_comments_orderby'), 10, 2);
        add_action('restrict_manage_comments', array($this, 'add_comment_filters'));
        add_action('restrict_manage_comments', array($this, 'add_comment_type_filter'));
        add_filter('comments_clauses', array($this, 'filter_comments_by_profile'));
    }

    public function add_profile_comment_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'comment') {
                $new_columns['profile_owner'] = 'Profil Sahibi';
            }
        }
        return $new_columns;
    }

    public function manage_profile_comment_columns($column, $comment_id) {
        if ($column === 'profile_owner') {
            $target_profile_id = get_comment_meta($comment_id, 'target_profile_id', true);
            if ($target_profile_id) {
                $profile_user = get_user_by('id', $target_profile_id);
                if ($profile_user) {
                    echo '<strong>' . esc_html($profile_user->display_name) . '</strong><br>';
                    echo '<small>(@' . esc_html($profile_user->user_login) . ')</small>';
                    
                    if (function_exists('um_user_profile_url')) {
                        $profile_url = um_user_profile_url($target_profile_id);
                        echo '<br><a href="' . esc_url($profile_url) . '" target="_blank">Profili Görüntüle</a>';
                    }
                }
            } else {
                echo '—';
            }
        }
    }

    public function profile_comments_sortable_columns($columns) {
        $columns['profile_owner'] = 'profile_owner';
        return $columns;
    }

    public function profile_comments_orderby($clauses, $wp_query) {
        global $wpdb;

        if (is_admin() && isset($wp_query->query['orderby']) && $wp_query->query['orderby'] == 'profile_owner') {
            $clauses['join'] .= " LEFT JOIN {$wpdb->commentmeta} cm ON {$wpdb->comments}.comment_ID = cm.comment_id AND cm.meta_key = 'target_profile_id'";
            $clauses['join'] .= " LEFT JOIN {$wpdb->users} u ON cm.meta_value = u.ID";
            $clauses['orderby'] = " u.display_name " . ($wp_query->get('order') ? $wp_query->get('order') : "ASC");
        }

        return $clauses;
    }

    public function add_comment_filters() {
        $screen = get_current_screen();
        if ($screen->id != 'edit-comments') return;

        $profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
        
        global $wpdb;
        $profile_owners = $wpdb->get_results("
            SELECT DISTINCT u.ID, u.display_name 
            FROM {$wpdb->users} u 
            INNER JOIN {$wpdb->commentmeta} cm ON u.ID = cm.meta_value 
            WHERE cm.meta_key = 'target_profile_id'
            ORDER BY u.display_name ASC
        ");

        if ($profile_owners) {
            echo '<select name="profile_id">';
            echo '<option value="">Tüm Profiller</option>';
            foreach ($profile_owners as $owner) {
                printf(
                    '<option value="%d" %s>%s</option>',
                    $owner->ID,
                    selected($profile_id, $owner->ID, false),
                    esc_html($owner->display_name)
                );
            }
            echo '</select>';
        }
    }

    public function add_comment_type_filter() {
        $screen = get_current_screen();
        if ($screen->id != 'edit-comments') return;

        $comment_type = isset($_GET['comment_type']) ? $_GET['comment_type'] : '';
        ?>
        <select name="comment_type">
            <option value="">Tüm yorum tipleri</option>
            <option value="profile_comment" <?php selected($comment_type, 'profile_comment'); ?>>Profil Yorumları</option>
        </select>
        <?php
    }

    public function filter_comments_by_profile($clauses) {
        global $wpdb;

        if (is_admin() && isset($_GET['profile_id']) && !empty($_GET['profile_id'])) {
            $profile_id = intval($_GET['profile_id']);
            
            $clauses['join'] .= " LEFT JOIN {$wpdb->commentmeta} cm ON {$wpdb->comments}.comment_ID = cm.comment_id";
            $clauses['where'] .= $wpdb->prepare(" AND cm.meta_key = 'target_profile_id' AND cm.meta_value = %d", $profile_id);
        }

        return $clauses;
    }
}