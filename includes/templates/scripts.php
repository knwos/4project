<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<script>
// Define ajaxurl for frontend
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

jQuery(document).ready(function($) {
    const MAX_CHARS = 800;

    function wpautop(text) {
        return '<p>' + text.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>') + '</p>';
    }

    // Add character counter to all textareas
    function initCharCounter(textarea) {
        const wrapper = $('<div class="mh-comment-textarea-wrapper"></div>');
        const counter = $('<div class="mh-char-counter">0/' + MAX_CHARS + '</div>');
        $(textarea).wrap(wrapper).after(counter);

        $(textarea).on('input', function() {
            const remaining = MAX_CHARS - this.value.length;
            const submitBtn = $(this).closest('form').find('button[type="submit"]');
            
            counter.text(this.value.length + '/' + MAX_CHARS);
            
            if (this.value.length > MAX_CHARS) {
                counter.addClass('mh-limit-reached');
                submitBtn.prop('disabled', true);
                this.value = this.value.substring(0, MAX_CHARS);
            } else {
                counter.removeClass('mh-limit-reached');
                submitBtn.prop('disabled', false);
            }
        });
    }

    // Initialize character counters for all textareas
    $('.mh-comment-textarea').each(function() {
        initCharCounter(this);
    });

    // AJAX pagination
    $(document).on('click', '.mh-page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        var container = $('.mh-profile-comments-container');
        
        container.addClass('loading');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_profile_comments',
                page: page,
                profile_id: container.data('profile-id'),
                security: container.data('nonce')
            },
            success: function(response) {
                if (response.success) {
                    $('.mh-comments-list').html(response.data.comments);
                    $('.mh-comments-pagination').html(response.data.pagination);
                    container.removeClass('loading');
                    
                    // Initialize character counters for new textareas
                    $('.mh-comment-textarea').each(function() {
                        if (!$(this).parent().hasClass('mh-comment-textarea-wrapper')) {
                            initCharCounter(this);
                        }
                    });
                    
                    // Update URL without adding comment-page
                    var currentUrl = window.location.href;
                    currentUrl = currentUrl.replace(/([?&])cpage=\d+/g, '')
                                        .replace(/\/comment-page-\d+\//g, '/');
                    currentUrl = currentUrl.replace(/([^:]\/)\/+/g, "$1");
                    var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
                    var newUrl = currentUrl + separator + 'cpage=' + page;
                    window.history.pushState({}, '', newUrl);
                }
            }
        });
    });
});

function showReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).style.display = 'block';
}

function hideReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).style.display = 'none';
}
</script>