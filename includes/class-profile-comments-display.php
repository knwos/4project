<?php
if (!defined('ABSPATH')) {
    exit;
}

class Profile_Comments_Display {
    public function init() {
        add_action('wp_head', array($this, 'profile_comments_css'));
        add_action('wp_footer', array($this, 'profile_comments_js'));
        add_shortcode('profile_comments', array($this, 'profile_comments_shortcode'));
    }

    public function profile_comments_css() {
        if (function_exists('um_is_core_page') && um_is_core_page('user')) {
            include PC_PLUGIN_DIR . 'includes/templates/styles.php';
        }
    }

    public function profile_comments_js() {
        if (function_exists('um_is_core_page') && um_is_core_page('user')) {
            include PC_PLUGIN_DIR . 'includes/templates/scripts.php';
        }
    }

    public function profile_comments_shortcode() {
        ob_start();
        include PC_PLUGIN_DIR . 'includes/templates/comments-template.php';
        return ob_get_clean();
    }

    public function display_comments($comments_by_parent) {
        // Düz bir dizi oluştur ve tüm yorumları topla
        $all_comments = array();
        foreach ($comments_by_parent as $parent_id => $comments) {
            foreach ($comments as $comment) {
                $all_comments[] = $comment;
            }
        }

        // Yorumları tarihe göre sırala (en yeni en üstte)
        usort($all_comments, function($a, $b) {
            return strtotime($b->comment_date) - strtotime($a->comment_date);
        });
        
        foreach ($all_comments as $comment) {
            $is_guest = empty($comment->user_id);
            $is_reply = $comment->comment_parent > 0;
            ?>
            <div class="mh-single-comment-wrapper" id="comment-<?php echo $comment->comment_ID; ?>">
                <div class="mh-comment-avatar-wrapper">
                    <?php if (!$is_guest): ?>
                        <a href="<?php echo um_user_profile_url($comment->user_id); ?>">
                            <?php echo get_avatar($comment->user_id, 50); ?>
                        </a>
                    <?php else: ?>
                        <?php echo get_avatar($comment->comment_author_email, 50); ?>
                    <?php endif; ?>
                </div>
                <div class="mh-comment-main-content">
                    <div class="mh-comment-author-info">
                        <?php if (!$is_guest): ?>
                            <a href="<?php echo um_user_profile_url($comment->user_id); ?>" class="mh-author-link">
                                <?php echo $comment->comment_author; ?>
                            </a>
                            <?php
                            if (user_can($comment->user_id, 'administrator')) {
                                echo '<span class="mh-admin-badge">★</span>';
                            }
                            ?>
                        <?php else: ?>
                            <span class="mh-guest-author"><?php echo esc_html($comment->comment_author); ?></span>
                            <span class="mh-guest-badge">Ziyaretçi</span>
                        <?php endif; ?>
                        <span class="mh-comment-timestamp">
                            <?php echo date('d.m.Y H:i', strtotime($comment->comment_date)); ?>
                        </span>
                    </div>
                    <?php if ($is_reply): 
                        $parent_comment = get_comment($comment->comment_parent);
                        if ($parent_comment): 
                            $parent_author = $parent_comment->user_id ? 
                                get_user_by('id', $parent_comment->user_id)->display_name : 
                                $parent_comment->comment_author;
                            ?>
                            <a href="#comment-<?php echo $parent_comment->comment_ID; ?>" class="mh-quoted-comment">
                                <div class="mh-quoted-author">
                                    <?php echo esc_html($parent_author); ?>
                                </div>
                                <div class="mh-quoted-text">
                                    <?php 
                                    $quoted_content = wp_trim_words($parent_comment->comment_content, 20, '...');
                                    echo esc_html($quoted_content); 
                                    ?>
                                </div>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="mh-comment-text-content">
                        <?php echo wpautop($comment->comment_content); ?>
                    </div>
                    <div class="mh-comment-actions">
                        <button class="mh-reply-button" 
                                data-comment-id="<?php echo $comment->comment_ID; ?>"
                                onclick="showReplyForm(<?php echo $comment->comment_ID; ?>)">
                            Yanıtla
                        </button>
                    </div>
                    <div id="reply-form-<?php echo $comment->comment_ID; ?>" class="mh-reply-form-wrapper" style="display: none;">
                        <form action="" method="post" class="mh-comment-form">
                            <textarea name="comment_text" class="mh-comment-textarea" placeholder="Yanıtınız..." required></textarea>
                            
                            <?php if (!is_user_logged_in()): ?>
                                <div class="mh-guest-fields">
                                    <input type="text" name="guest_name" class="mh-guest-input" placeholder="Adınız" required>
                                    <input type="email" name="guest_email" class="mh-guest-input" placeholder="E-posta adresiniz" required>
                                </div>
                            <?php endif; ?>

                            <input type="hidden" name="profile_id" value="<?php echo get_comment_meta($comment->comment_ID, 'target_profile_id', true); ?>">
                            <input type="hidden" name="parent_id" value="<?php echo $comment->comment_ID; ?>">
                            <input type="hidden" name="action" value="submit_profile_comment">
                            <?php wp_nonce_field('profile_comment_nonce'); ?>
                            <div class="mh-reply-form-actions">
                                <button type="submit" class="mh-comment-submit-btn">Yanıt Gönder</button>
                                <button type="button" class="mh-reply-cancel-btn" 
                                        onclick="hideReplyForm(<?php echo $comment->comment_ID; ?>)">
                                    İptal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    public function display_pagination($current_page, $total_pages) {
        if ($total_pages > 1) {
            echo '<div class="mh-comments-pagination">';
            
            if ($current_page > 1) {
                echo '<a href="#" data-page="' . ($current_page - 1) . '" class="mh-page-link">&laquo; Önceki</a>';
            }
            
            for ($i = 1; $i <= $total_pages; $i++) {
                $class = $current_page === $i ? 'mh-page-link active' : 'mh-page-link';
                echo '<a href="#" data-page="' . $i . '" class="' . $class . '">' . $i . '</a>';
            }
            
            if ($current_page < $total_pages) {
                echo '<a href="#" data-page="' . ($current_page + 1) . '" class="mh-page-link">Sonraki &raquo;</a>';
            }
            
            echo '</div>';
        }
    }
}