<?php
/*
Plugin Name: Shortcode For Sidebar
Version: 1.0
Plugin URI: http://www.nhost.biz/
Description: Enable inserting Short Code in WordPress Sidebar Widget


Author: S K Tanmoy
Author URI: http://www.nhost.biz/

Lisence Type: GPL

Lisence Detail: http://wordpress.org/about/gpl/
*/

add_filter('widget_text', 'do_shortcode');

?>