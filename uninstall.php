<?php 

if(defined('WP_UNINSTALL_PLUGIN'))
{

	global $wpdb;
							
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'utubevideo_video');
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'utubevideo_album');
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'utubevideo_dataset');

	delete_option('utubevideo_main_opts');
	
	$dir = wp_upload_dir();
	rrmdir($dir['basedir'] . '/utubevideo-cache');
		
}

function rrmdir($dir) { 

	foreach(glob($dir . '/*') as $file) 
	{
		
		if(is_dir($file)) 
			rrmdir($file); 
		else 
			unlink($file);
				
	} 
		
	rmdir($dir); 
}

?>