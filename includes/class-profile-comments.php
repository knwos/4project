<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once PC_PLUGIN_DIR . 'includes/class-profile-comments-base.php';
require_once PC_PLUGIN_DIR . 'includes/class-profile-comments-display.php';
require_once PC_PLUGIN_DIR . 'includes/class-profile-comments-ajax.php';

class Profile_Comments extends Profile_Comments_Base {
    private $display;
    private $ajax;

    public function __construct() {
        parent::__construct();
        $this->display = new Profile_Comments_Display();
        $this->ajax = new Profile_Comments_Ajax();
    }

    public function init() {
        parent::init();
        $this->display->init();
        $this->ajax->init();
    }
}