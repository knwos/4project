<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
/* Base Container */
.mh-profile-comments-container {
    --primary-color: #333;
    --secondary-color: #666;
    --border-color: #ddd;
    --bg-color: #f8f8f8;
    --hover-color: #eee;
    --shadow-color: rgba(0, 0, 0, 0.1);
    --success-bg: #fff3cd;
    --success-border: #ffeeba;
    --success-color: #856404;
    --pending-bg: #ffd700;
    --pending-color: #000;
    --quote-bg: #f5f5f5;
    --quote-border: #ddd;
    --error-color: #dc3545;
    --reply-info-bg: #f0f8ff;
    --reply-info-color: #0066cc;
    
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

/* Headers */
.mh-comments-count {
    font-size: 18px;
    margin-bottom: 20px;
    color: var(--primary-color);
    font-weight: 600;
    letter-spacing: 0.5px;
}

.mh-pending-comments-section h3 {
    margin: 0 0 20px;
    font-size: 16px;
    color: var(--secondary-color);
}

/* Form Elements */
.mh-comment-form-wrapper {
    margin-bottom: 30px;
    background: var(--bg-color);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px var(--shadow-color);
    width: 100%;
    box-sizing: border-box;
}

.mh-comment-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    width: 100%;
}

.mh-comment-textarea-wrapper {
    position: relative;
    width: 100%;
}

.mh-comment-textarea {
    width: 100%;
    min-height: 100px;
    padding: 15px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    margin-bottom: 5px;
    resize: vertical;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.6;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.mh-comment-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 5px var(--shadow-color);
}

.mh-char-counter {
    position: absolute;
    bottom: 10px;
    right: 10px;
    font-size: 12px;
    color: var(--secondary-color);
    background: rgba(255, 255, 255, 0.9);
    padding: 2px 6px;
    border-radius: 3px;
}

.mh-char-counter.mh-limit-reached {
    color: var(--error-color);
    font-weight: bold;
}

/* Guest Fields */
.mh-guest-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
    width: 100%;
}

.mh-guest-input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.mh-guest-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 5px var(--shadow-color);
}

/* Character Limit Warning */
.mh-char-limit-warning {
    color: var(--error-color);
    font-size: 12px;
    margin-top: 5px;
    font-style: italic;
}

/* Buttons */
.mh-comment-submit-btn,
.mh-reply-cancel-btn {
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
    transition: background-color 0.2s ease;
    border: none;
}

.mh-comment-submit-btn {
    background: var(--primary-color);
    color: white;
    align-self: flex-start;
}

.mh-comment-submit-btn:disabled {
    background: var(--secondary-color);
    cursor: not-allowed;
    opacity: 0.7;
}

.mh-comment-submit-btn:hover:not(:disabled) {
    background: #444;
}

.mh-reply-cancel-btn {
    background: var(--hover-color);
    color: var(--secondary-color);
}

.mh-reply-cancel-btn:hover {
    background: var(--border-color);
    color: var(--primary-color);
}

/* Comments */
.mh-single-comment-wrapper {
    display: flex;
    margin-bottom: 20px;
    padding: 20px;
    background: var(--bg-color);
    border-radius: 8px;
    box-shadow: 0 1px 3px var(--shadow-color);
    transition: transform 0.2s ease;
}

.mh-single-comment-wrapper:hover {
    transform: translateY(-1px);
}

/* Quoted Comments */
.mh-quoted-comment {
    display: block;
    margin: 0 0 15px 0;
    padding: 15px;
    background: var(--quote-bg);
    border-left: 3px solid var(--quote-border);
    font-size: 0.95em;
    border-radius: 4px;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.2s ease;
}

.mh-quoted-comment:hover {
    background: var(--hover-color);
    text-decoration: none;
    color: inherit;
}

.mh-quoted-author {
    font-weight: bold;
    color: var(--secondary-color);
    margin-bottom: 5px;
}

.mh-quoted-text {
    color: var(--secondary-color);
    margin: 5px 0;
    font-style: italic;
}

/* Avatar */
.mh-comment-avatar-wrapper {
    margin-right: 15px;
    flex-shrink: 0;
}

.mh-comment-avatar-wrapper img {
    border-radius: 50%;
    width: 50px;
    height: 50px;
    box-shadow: 0 2px 4px var(--shadow-color);
}

/* Comment Content */
.mh-comment-main-content {
    flex: 1;
}

.mh-comment-author-info {
    font-weight: bold;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.mh-admin-badge {
    color: #ff4444;
    font-size: 18px;
}

.mh-comment-timestamp {
    font-size: 13px;
    color: var(--secondary-color);
    font-weight: normal;
}

.mh-comment-text-content {
    margin: 10px 0;
    color: var(--primary-color);
    line-height: 1.6;
    font-size: 14px;
}

.mh-comment-text-content p {
    margin: 0 0 10px;
}

.mh-comment-text-content p:last-child {
    margin-bottom: 0;
}

/* Links */
.mh-author-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: bold;
    transition: color 0.2s ease;
}

.mh-author-link:hover {
    color: var(--secondary-color);
}

.mh-author-name {
    color: var(--primary-color);
    font-weight: bold;
}

/* Actions */
.mh-comment-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.mh-reply-button {
    background: none;
    border: none;
    color: var(--secondary-color);
    cursor: pointer;
    font-size: 14px;
    padding: 5px 10px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.mh-reply-button:hover {
    background: var(--hover-color);
    color: var(--primary-color);
}

/* Reply Form */
.mh-reply-form-wrapper {
    margin-top: 15px;
    padding: 15px;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    width: 100%;
    box-sizing: border-box;
}

.mh-reply-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

/* Pending Comments */
.mh-pending-notice {
    background-color: var(--success-bg);
    border: 1px solid var(--success-border);
    color: var(--success-color);
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    text-align: center;
}

.mh-pending-comments-section {
    margin-bottom: 30px;
    background: var(--bg-color);
    padding: 20px;
    border-radius: 8px;
}

.mh-pending-comment {
    background: #fff !important;
    border: 1px solid var(--border-color);
    opacity: 0.8;
}

.mh-pending-badge {
    background: var(--pending-bg);
    color: var(--pending-color);
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 3px;
    margin-left: 10px;
    font-weight: normal;
}

.mh-moderation-notice {
    color: var(--secondary-color);
    font-size: 13px;
    margin: 5px 0;
    font-style: italic;
}

/* Pagination */
.mh-comments-pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.mh-page-link {
    padding: 8px 12px;
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 14px;
    min-width: 35px;
    text-align: center;
}

.mh-page-link:hover {
    background: var(--hover-color);
    border-color: var(--border-color);
}

.mh-page-link.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Loading State */
.mh-profile-comments-container.loading {
    position: relative;
    opacity: 0.6;
    pointer-events: none;
}

.mh-profile-comments-container.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 40px;
    height: 40px;
    margin: -20px 0 0 -20px;
    border: 4px solid var(--bg-color);
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* No Comments */
.mh-no-comments {
    text-align: center;
    color: var(--secondary-color);
    padding: 40px 20px;
    background: var(--bg-color);
    border-radius: 8px;
    font-size: 16px;
    margin: 20px 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .mh-profile-comments-container {
        padding: 15px;
    }

    .mh-single-comment-wrapper {
        flex-direction: column;
        padding: 15px;
    }

    .mh-comment-avatar-wrapper {
        margin-bottom: 10px;
        margin-right: 0;
    }

    .mh-comments-pagination {
        gap: 5px;
    }

    .mh-page-link {
        padding: 6px 10px;
        min-width: 30px;
    }

    .mh-comment-submit-btn,
    .mh-reply-cancel-btn {
        padding: 10px 20px;
        width: 100%;
    }

    .mh-reply-form-actions {
        flex-direction: column;
    }

    .mh-guest-fields {
        grid-template-columns: 1fr;
    }
}
</style>