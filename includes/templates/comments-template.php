<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current user from Ultimate Member
$current_profile_id = um_profile_id();

if ($current_profile_id) {
    global $wpdb;
    
    // Pagination settings
    $comments_per_page = 10;
    $current_page = isset($_GET['cpage']) ? max(1, intval($_GET['cpage'])) : 1;
    $offset = ($current_page - 1) * $comments_per_page;

    // Get total approved comments count
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
        $current_profile_id,
        PROFILE_COMMENTS_POST_ID
    ));

    // Calculate total pages
    $total_pages = ceil($total_comments / $comments_per_page);

    $nonce = wp_create_nonce('profile_comments_nonce');
    echo '<div class="mh-profile-comments-container" data-profile-id="' . esc_attr($current_profile_id) . '" data-nonce="' . esc_attr($nonce) . '">';
    
    // Show pending comments notice if exists
    if (isset($_GET['comment_pending']) && $_GET['comment_pending'] == '1') {
        echo '<div class="mh-pending-notice">Yorumunuz gönderildi ve onay bekliyor.</div>';
    }

    // Show user's pending comments if they are the author
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        $pending_comments = $wpdb->get_results($wpdb->prepare(
            "SELECT c.* 
            FROM {$wpdb->prefix}comments c
            INNER JOIN {$wpdb->prefix}commentmeta cm 
            ON c.comment_ID = cm.comment_id
            AND cm.meta_key = 'target_profile_id'
            AND cm.meta_value = %d
            WHERE c.comment_post_ID = %d
            AND c.comment_type = 'profile_comment'
            AND c.comment_approved = '0'
            AND c.user_id = %d
            ORDER BY c.comment_date DESC",
            $current_profile_id,
            PROFILE_COMMENTS_POST_ID,
            $current_user_id
        ));

        if (!empty($pending_comments)) {
            echo '<div class="mh-pending-comments-section">';
            echo '<h3>Bekleyen Yorumlarınız</h3>';
            foreach ($pending_comments as $comment) {
                ?>
                <div class="mh-single-comment-wrapper mh-pending-comment">
                    <div class="mh-comment-avatar-wrapper">
                        <?php echo get_avatar($comment->user_id, 50); ?>
                    </div>
                    <div class="mh-comment-main-content">
                        <div class="mh-comment-author-info">
                            <?php echo wp_get_current_user()->display_name; ?>
                            <span class="mh-pending-badge">Onay Bekliyor</span>
                        </div>
                        <div class="mh-comment-timestamp">
                            <?php
                            $comment_date = date_i18n('j F Y', strtotime($comment->comment_date));
                            $comment_time = date('H:i', strtotime($comment->comment_date));
                            echo mb_strtoupper($comment_date . ' TARİHİNDE, SAAT ' . $comment_time);
                            ?>
                        </div>
                        <div class="mh-comment-text-content">
                            <?php echo wpautop($comment->comment_content); ?>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        }
    }

    echo '<h2 class="mh-comments-count">' . $total_comments . ' YORUM</h2>';

    // Yorum formu - hem üyeler hem ziyaretçiler için
    ?>
    <div class="mh-comment-form-wrapper" id="mh-main-comment-form">
        <form action="" method="post" class="mh-comment-form">
            <textarea name="comment_text" class="mh-comment-textarea" placeholder="Yorumunuz..." required></textarea>
            
            <?php if (!is_user_logged_in()): ?>
                <div class="mh-guest-fields">
                    <input type="text" name="guest_name" class="mh-guest-input" placeholder="Adınız" required>
                    <input type="email" name="guest_email" class="mh-guest-input" placeholder="E-posta adresiniz" required>
                </div>
            <?php endif; ?>

            <input type="hidden" name="profile_id" value="<?php echo $current_profile_id; ?>">
            <input type="hidden" name="action" value="submit_profile_comment">
            <?php wp_nonce_field('profile_comment_nonce'); ?>
            <?php if (!current_user_can('administrator')): ?>
                <p class="mh-moderation-notice">Yorumunuz onaylandıktan sonra görünür olacaktır.</p>
            <?php endif; ?>
            <button type="submit" class="mh-comment-submit-btn">Yorum Gönder</button>
        </form>
    </div>
    <?php

    // Get approved comments with thread sorting
    $comments = $wpdb->get_results($wpdb->prepare(
        "WITH RECURSIVE CommentHierarchy AS (
            -- Root level comments
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
            
            -- Child comments
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
        $current_profile_id,
        PROFILE_COMMENTS_POST_ID,
        $comments_per_page,
        $offset
    ));

    if (!empty($comments)) {
        echo '<div class="mh-comments-list">';
        
        $comments_by_parent = array();
        foreach ($comments as $comment) {
            $comments_by_parent[$comment->comment_parent][] = $comment;
        }
        
        $display = new Profile_Comments_Display();
        $display->display_comments($comments_by_parent);
        
        echo '</div>';
        
        // Pagination
        $display->display_pagination($current_page, $total_pages);
    } else {
        echo '<p class="mh-no-comments">Henüz yorum yapılmamış.</p>';
    }
    echo '</div>';
}
?>