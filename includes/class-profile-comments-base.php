<?php
if (!defined('ABSPATH')) {
    exit;
}

class Profile_Comments_Base {
    protected $notification_table;

    public function __construct() {
        global $wpdb;
        $this->notification_table = $wpdb->prefix . 'um_notifications';
    }

    public function init() {
        add_action('init', array($this, 'handle_profile_comment'));
        add_filter('um_notifications_core_log_types', array($this, 'add_profile_comment_notification_types'), 200);
        add_action('um_notification_after_notif_submission', array($this, 'after_notification_sent'), 10, 2);
        add_action('um_notification_after_notif_update', array($this, 'after_notification_sent'), 10, 2);
        add_action('comment_post', array($this, 'notify_admin_of_pending_comment'), 10, 2);
        add_filter('um_notifications_should_send_notification', array($this, 'prevent_post_notifications'), 10, 3);
        add_filter('um_notifications_core_log_types_templates', array($this, 'modify_notification_templates'), 10);
        
        // Yorum eklendiğinde ve durumu değiştiğinde çalışacak hooklar
        add_action('wp_insert_comment', array($this, 'handle_comment_notification'), 10, 2);
        add_action('transition_comment_status', array($this, 'handle_comment_status_change'), 10, 3);

        // Ultimate Member profil sekmesi için hook
        add_filter('um_profile_tabs', array($this, 'add_profile_comments_tab'), 1000);
    }

    public function add_profile_comments_tab($tabs) {
        $tabs['ld_course_list'] = array(
            'name' => 'Yorumlar',
            'icon' => 'um-faicon-comments',
            'default_privacy' => 3,
        );
        
        return $tabs;
    }

    public function modify_notification_templates($templates) {
        $templates['user_comment'] = '{member} profilinize yorum yaptı.';
        $templates['guest_comment'] = 'Bir ziyaretçi profilinize yorum yaptı.';
        return $templates;
    }

    public function handle_comment_status_change($new_status, $old_status, $comment) {
        if ($new_status == 'approved' && $old_status != 'approved') {
            if ($comment->comment_post_ID == PROFILE_COMMENTS_POST_ID) {
                $target_profile_id = get_comment_meta($comment->comment_ID, 'target_profile_id', true);
                if ($target_profile_id) {
                    if ($comment->comment_parent === '0') {
                        $this->send_notification($target_profile_id, $comment->user_id, 'profile_comment', $comment->comment_ID);
                    } else {
                        $parent_comment = get_comment($comment->comment_parent);
                        if ($parent_comment && $parent_comment->user_id != $comment->user_id) {
                            $this->send_notification(
                                $parent_comment->user_id,
                                $comment->user_id,
                                'profile_comment_reply',
                                $comment->comment_ID
                            );
                        }
                    }
                }
            }
        }
    }

    public function handle_comment_notification($comment_id, $comment) {
        if ($comment->comment_post_ID == PROFILE_COMMENTS_POST_ID) {
            remove_action('wp_insert_comment', 'UM()->Notifications_API()->api()->store_notification');
            
            $target_profile_id = get_comment_meta($comment_id, 'target_profile_id', true);
            if ($target_profile_id && $comment->comment_approved == 1) {
                if ($comment->comment_parent === '0') {
                    $this->send_notification($target_profile_id, $comment->user_id, 'profile_comment', $comment_id);
                } else {
                    $parent_comment = get_comment($comment->comment_parent);
                    if ($parent_comment && $parent_comment->user_id != $comment->user_id) {
                        $this->send_notification(
                            $parent_comment->user_id,
                            $comment->user_id,
                            'profile_comment_reply',
                            $comment_id
                        );
                    }
                }
            }
        }
    }

    public function prevent_post_notifications($should_send, $type, $vars) {
        if (in_array($type, array('user_comment', 'guest_comment'))) {
            if (isset($vars['post_id']) && $vars['post_id'] == PROFILE_COMMENTS_POST_ID) {
                return false;
            }
        }
        return $should_send;
    }

    public function handle_profile_comment() {
        if (
            isset($_POST['action']) &&
            $_POST['action'] === 'submit_profile_comment' &&
            isset($_POST['profile_id']) &&
            isset($_POST['comment_text']) &&
            wp_verify_nonce($_POST['_wpnonce'], 'profile_comment_nonce')
        ) {
            $profile_id = intval($_POST['profile_id']);
            $comment_text = sanitize_textarea_field($_POST['comment_text']);
            $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            
            $is_guest = !is_user_logged_in();
            if ($is_guest) {
                $guest_name = isset($_POST['guest_name']) ? sanitize_text_field($_POST['guest_name']) : '';
                $guest_email = isset($_POST['guest_email']) ? sanitize_email($_POST['guest_email']) : '';
                
                if (empty($guest_name) || empty($guest_email)) {
                    wp_die('Lütfen adınızı ve e-posta adresinizi girin.');
                }
            }
            
            if ($profile_id && $comment_text) {
                $is_admin = current_user_can('administrator');
                
                $comment_data = array(
                    'comment_post_ID' => PROFILE_COMMENTS_POST_ID,
                    'comment_content' => $comment_text,
                    'comment_type' => 'profile_comment',
                    'comment_parent' => $parent_id,
                    'comment_approved' => $is_admin ? 1 : 0
                );

                if ($is_guest) {
                    $comment_data['comment_author'] = $guest_name;
                    $comment_data['comment_author_email'] = $guest_email;
                    $comment_data['user_id'] = 0;
                } else {
                    $current_user = wp_get_current_user();
                    $comment_data['comment_author'] = $current_user->display_name;
                    $comment_data['comment_author_email'] = $current_user->user_email;
                    $comment_data['user_id'] = get_current_user_id();
                }
                
                $comment_id = wp_insert_comment($comment_data);
                if ($comment_id) {
                    add_comment_meta($comment_id, 'target_profile_id', $profile_id);
                    
                    $redirect_url = um_get_core_page('user');
                    $redirect_url = add_query_arg('profiletab', 'ld_course_list', $redirect_url);
                    
                    // Hedef kullanıcının bilgilerini al
                    $target_user = get_user_by('id', $profile_id);
                    $redirect_url = add_query_arg('um_user', $target_user->user_login, $redirect_url);
                    
                    if (!$is_admin) {
                        $redirect_url = add_query_arg('comment_pending', '1', $redirect_url);
                    } else {
                        $redirect_url = add_query_arg('comment', $comment_id, $redirect_url);
                    }
                    
                    wp_redirect($redirect_url);
                    exit;
                }
            }
        }
    }

    public function notify_admin_of_pending_comment($comment_id, $comment_approved) {
        if ($comment_approved === 0) {
            $comment = get_comment($comment_id);
            $admin_email = get_option('admin_email');
            
            $subject = sprintf('Yeni Bekleyen Profil Yorumu: %s', get_bloginfo('name'));
            
            $message = sprintf(
                "Yeni bir profil yorumu onay bekliyor.\n\n" .
                "Yazar: %s\n" .
                "E-posta: %s\n" .
                "Yorum: %s\n\n" .
                "Yorumu onaylamak için yönetici paneline gidin:\n%s",
                $comment->comment_author,
                $comment->comment_author_email,
                $comment->comment_content,
                admin_url('edit-comments.php?comment_status=moderated')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
    }

    protected function send_notification($user_id, $from_user_id, $type, $comment_id) {
        global $wpdb;
        
        $content = '';
        $comment = get_comment($comment_id);
        $target_profile_id = get_comment_meta($comment_id, 'target_profile_id', true);
        
        if ($type === 'profile_comment') {
            $content = sprintf(
                '<strong>%s</strong> profilinize yorum yaptı.',
                get_user_by('id', $from_user_id)->display_name
            );
        } else if ($type === 'profile_comment_reply') {
            $content = sprintf(
                '<strong>%s</strong> yorumunuza yanıt verdi.',
                get_user_by('id', $from_user_id)->display_name
            );
        }

        // Hedef kullanıcının bilgilerini al
        $target_user = get_user_by('id', $target_profile_id);
        
        $notification = array(
            'time' => current_time('mysql'),
            'user' => $user_id,
            'status' => 'unread',
            'photo' => get_avatar_url($from_user_id),
            'type' => $type,
            'url' => um_get_core_page('user') . '?profiletab=ld_course_list&um_user=' . $target_user->user_login . '#comment-' . $comment_id,
            'content' => $content
        );
        
        $wpdb->insert(
            $this->notification_table,
            $notification,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        $notification_id = $wpdb->insert_id;
        $this->store_notification_meta($user_id, $notification_id);
        $this->update_unread_count($user_id);
        update_user_meta($user_id, '_um_notification_last_update', current_time('mysql'));
        do_action('um_notification_after_notif_submission', $user_id, $type);
    }

    protected function store_notification_meta($user_id, $notification_id) {
        $new_notifications = get_user_meta($user_id, 'um_new_notifications', true);
        if (empty($new_notifications)) {
            $new_notifications = array();
        }

        $new_notifications[] = $notification_id;
        $new_notifications = array_unique($new_notifications);
        update_user_meta($user_id, 'um_new_notifications', $new_notifications);
    }

    public function after_notification_sent($user_id, $type) {
        $this->update_unread_count($user_id);
    }

    protected function update_unread_count($user_id) {
        global $wpdb;
        
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->notification_table} 
            WHERE user = %d 
            AND status = 'unread'",
            $user_id
        ));

        update_user_meta($user_id, '_um_notification_unread', $unread_count);
        update_user_meta($user_id, '_um_notification_last_update', current_time('mysql'));
    }

    public function add_profile_comment_notification_types($notifications) {
        $notifications['profile_comment'] = array(
            'title' => 'Profil Yorumu',
            'template' => '{member} profilinize yorum yaptı.',
            'account_desc' => 'Birisi profilime yorum yaptığında'
        );
        $notifications['profile_comment_reply'] = array(
            'title' => 'Yorum Yanıtı',
            'template' => '{member} yorumunuza yanıt verdi.',
            'account_desc' => 'Birisi yorumuma yanıt verdiğinde'
        );
        return $notifications;
    }
}