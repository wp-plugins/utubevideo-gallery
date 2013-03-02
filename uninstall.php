<?php 

if(defined('WP_UNINSTALL_PLUGIN'))
{

	global $wpdb;
							
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'utubevideo_video');
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'utubevideo_album');
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'utubevideo_dataset');

	delete_option('utubevideo_main_opts');
	
}

?>