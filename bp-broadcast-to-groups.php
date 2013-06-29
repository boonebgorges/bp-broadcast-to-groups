<?php
/*
Plugin Name: BP Broadcast To Groups
Version: 0.1-alpha
Description: Lets your users selectively broadcast their blog posts to BP groups
Author: Boone Gorges
Author URI: http://boone.gorg.es
Text Domain: bpbtg
Domain Path: /languages
*/

function bpbtg_init() {
	if ( bp_is_active( 'groups' ) ) {
		include __DIR__ . '/includes/bpbtg.php';
		buddypress()->bpbtg = new BPBTG();
	}
}
add_action( 'bp_include', 'bpbtg_init' );

