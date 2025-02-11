<?php
if (!defined('ABSPATH')) {
    exit;
}

class Profile_Comments_Ajax {
    public function init() {
        add_action('wp_ajax_load_profile_comments', array($this, 'ajax_load_profile_comments'));
        add_action('wp_ajax_nopriv_load_profile_comments', array($this, 'ajax_load_profile_comments'));
    }

    public function ajax_load_profile_comments() {
        check_ajax_referer('profile_comments_nonce', 'security');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $profile_id = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;
        
        if (!$profile_id) {
            wp_send_json_error('Invalid profile ID');
        }
        
        global $wpdb;
        $comments_per_page = 10;
        $offset = ($page - 1) * $comments_per_page;
        
        $total_comments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT c.comment_ID) 
            FROM {$wpdb->prefix}comments c
            INNER JOIN {$wpdb->prefix}commentmeta cm 
            ON c.comment_ID = cm.comment_id
            AND cm.meta_key = 'target_profile_id'
            AND cm.meta_value = %d
            WHERE c.comment_post_ID = %d
            AND c.comment_type = 'profile_comment'
            AND c.comment_approved = '1'",
            $profile_id,
            PROFILE_COMMENTS_POST_ID
        ));
        
        $comments = $wpdb->get_results($wpdb->prepare(
            "WITH RECURSIVE CommentHierarchy AS (
                SELECT 
                    c.comment_ID,
                    c.comment_parent,
                    c.comment_date,
                    c.comment_content,
                    c.user_id,
                    c.comment_author,
                    c.comment_author_email,
                    c.comment_date as thread_last_reply,
                    c.comment_ID as root_comment_id
                FROM {$wpdb->prefix}comments c
                WHERE c.comment_parent = 0
                AND c.comment_post_ID = %d
                AND c.comment_type = 'profile_comment'
                AND c.comment_approved = '1'
                
                UNION ALL
                
                SELECT 
                    c.comment_ID,
                    c.comment_parent,
                    c.comment_date,
                    c.comment_content,
                    c.user_id,
                    c.comment_author,
                    c.comment_author_email,
                    c.comment_date as thread_last_reply,
                    ch.root_comment_id
                FROM {$wpdb->prefix}comments c
                INNER JOIN CommentHierarchy ch ON c.comment_parent = ch.comment_ID
                WHERE c.comment_post_ID = %d
                AND c.comment_type = 'profile_comment'
                AND c.comment_approved = '1'
            ),
            ThreadLastReply AS (
                SELECT 
                    root_comment_id,
                    MAX(thread_last_reply) as last_reply_date
                FROM CommentHierarchy
                GROUP BY root_comment_id
            )
            SELECT DISTINCT c.* 
            FROM {$wpdb->prefix}comments c
            INNER JOIN {$wpdb->prefix}commentmeta cm 
            ON c.comment_ID = cm.comment_id
            AND cm.meta_key = 'target_profile_id'
            AND cm.meta_value = %d
            LEFT JOIN ThreadLastReply tlr
            ON c.comment_ID = tlr.root_comment_id
            WHERE c.comment_post_ID = %d
            AND c.comment_type = 'profile_comment'
            AND c.comment_approved = '1'
            ORDER BY 
                CASE 
                    WHEN c.comment_parent = 0 THEN COALESCE(tlr.last_reply_date, c.comment_date)
                    ELSE c.comment_date
                END DESC,
                c.comment_parent ASC,
                c.comment_date ASC
            LIMIT %d OFFSET %d",
            PROFILE_COMMENTS_POST_ID,
            PROFILE_COMMENTS_POST_ID,
            $profile_id,
            PROFILE_COMMENTS_POST_ID,
            $comments_per_page,
            $offset
        ));
        
        $total_pages = ceil($total_comments / $comments_per_page);
        
        ob_start();
        
        if (!empty($comments)) {
            $comments_by_parent = array();
            foreach ($comments as $comment) {
                $comments_by_parent[$comment->comment_parent][] = $comment;
            }
            
            $display = new Profile_Comments_Display();
            $display->display_comments($comments_by_parent);
        }
        
        $comments_html = ob_get_clean();
        
        ob_start();
        $display = new Profile_Comments_Display();
        $display->display_pagination($page, $total_pages);
        $pagination_html = ob_get_clean();
        
        wp_send_json_success(array(
            'comments' => $comments_html,
            'pagination' => $pagination_html
        ));
    }
}