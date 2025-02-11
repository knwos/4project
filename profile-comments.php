<?php
/**
 * Plugin Name: Profil Yorum
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PC_VERSION', '2.0.0');
define('PROFILE_COMMENTS_POST_ID', 447);

require_once PC_PLUGIN_DIR . 'includes/class-profile-comments.php';
require_once PC_PLUGIN_DIR . 'includes/class-profile-comments-admin.php';

function profile_comments_init() {
    if (!function_exists('UM')) {
        return;
    }

    $profile_comments = new Profile_Comments();
    $profile_comments->init();

    if (is_admin()) {
        $profile_comments_admin = new Profile_Comments_Admin();
        $profile_comments_admin->init();
    }
}
add_action('plugins_loaded', 'profile_comments_init');