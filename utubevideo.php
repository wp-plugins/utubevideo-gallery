<?php 

/*
Plugin Name: uTubeVideo Gallery	
Plugin URI: http://www.codeclouds.net/
Description: This plugin allows you to create YouTube video galleries to embed in a WordPress site.
Version: 1.3
Author: Dustin Scarberry
Author URI: http://www.codeclouds.net/
License: GPL2
*/

/*  2013 Dustin Scarberry webmaster@codeclouds.net

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!class_exists('utvGallery'))
{

	class utvGallery
	{
	
		private $_utvadmin, $_utvfrontend, $_options;
	
		public function __construct()
		{

			//load external files
			$this->load_dependencies();
		
			//activation hook
			register_activation_hook(__FILE__, array(&$this, 'activate'));
			
		}
		
		//activate plugin
		public function activate()
		{
		
			//set up globals
			global $wpdb;
			
			//create database tables for plugin
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			$tbname[0] = $wpdb->prefix . 'utubevideo_dataset';
			$tbname[1] = $wpdb->prefix . 'utubevideo_album';
			$tbname[2] = $wpdb->prefix . 'utubevideo_video';
			
			$sql = "CREATE TABLE $tbname[0] (
				DATA_ID int(11) NOT NULL AUTO_INCREMENT,
				DATA_NAME varchar(40) NOT NULL,
				DATA_UPDATEDATE int(11) NOT NULL,
				DATA_ALBCOUNT int(4),
				UNIQUE KEY DATA_ID (DATA_ID)
			);
			CREATE TABLE $tbname[1] (
				ALB_ID int(11) NOT NULL AUTO_INCREMENT,
				ALB_NAME varchar(50) NOT NULL,
				ALB_THUMB varchar(40) NOT NULL,
				ALB_SORT varchar(4) DEFAULT 'desc' NOT NULL,
				ALB_UPDATEDATE int(11) NOT NULL,
				ALB_VIDCOUNT int(4),
				DATA_ID int(11) NOT NULL,
				UNIQUE KEY ALB_ID (ALB_ID)
			);
			CREATE TABLE $tbname[2] (
				VID_ID int(11) NOT NULL AUTO_INCREMENT,
				VID_NAME varchar(50) NOT NULL,
				VID_URL varchar(40) NOT NULL,
				VID_THUMBTYPE varchar(9) DEFAULT 'rectangle' NOT NULL,
				VID_UPDATEDATE int(11) NOT NULL,
				ALB_ID int(11) NOT NULL,
				UNIQUE KEY VID_ID (VID_ID)
			);";
			
			dbDelta($sql);
			
			//set up main option defaults if needed
			$main = get_option('utubevideo_main_opts');
			
			//initalize main if empty
			if(empty($main))
				$main = array();
		
			if(!isset($main['countSet']))
			{
			
				$galids = $wpdb->get_results('SELECT DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_dataset', ARRAY_A);
				
				foreach($galids as $value)
				{
				
					$albs = $wpdb->get_results('SELECT ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $value['DATA_ID'], ARRAY_A);
					$count = count($albs);

					$wpdb->update($wpdb->prefix . 'utubevideo_dataset', 
						array( 
							'DATA_ALBCOUNT' => $count
						), 
						array('DATA_ID' => $value['DATA_ID'])
					);
				
				}
				
				$alids = $wpdb->get_results('SELECT ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_album', ARRAY_A);
				
				foreach($alids as $value)
				{
				
					$vids = $wpdb->get_results('SELECT VID_ID FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $value['ALB_ID'], ARRAY_A);
					$count = count($vids);

					$wpdb->update($wpdb->prefix . 'utubevideo_album', 
						array( 
							'ALB_VIDCOUNT' => $count
						), 
						array('ALB_ID' => $value['ALB_ID'])
					);
				
				}
				
				$dft['countSet'] = 'ok';
			
			}
				
			$dft['fancyboxInc'] = 'no';
			$dft['playerWidth'] = 950;
			$dft['playerHeight'] = 537;
			
			$opts = $main + $dft;
			
			update_option('utubevideo_main_opts', $opts);
			
			//create photo cache directory if needed
			$dir = wp_upload_dir();
			$dir = $dir['basedir'];
			
			wp_mkdir_p($dir . '/utubevideo-cache');
			
			//copy 'missing.jpg' into cache directory
			copy(plugins_url('missing.jpg', __FILE__), $dir . '/utubevideo-cache/missing.jpg');
		
		}
		
		//load dependencies for plugin
		private function load_dependencies()
		{
		
			//load backend or frontend dependencies
			if(is_admin())
			{
			
				require dirname(__FILE__) . '/admin.php';
				$this->_utvadmin = new utvAdmin();

			}
			else
			{
			
				require dirname(__FILE__) . '/frontend.php';
				$this->_utvfrontend = new utvFrontend();
			
			}
		
		}
		
	}

	$utvg = new utvGallery();

}
?>