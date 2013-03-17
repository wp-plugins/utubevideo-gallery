<?php 

/*
Plugin Name: uTubeVideo Gallery	
Plugin URI: http://www.codeclouds.net/
Description: This plugin allows you to create YouTube video galleries to embed in a WordPress site.
Version: 1.2.5
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

//if at dashboard//
if(is_admin())
{

	//run when plugin is activated to setup stuff//
	function utubevideo_activate()
	{

		//create database tables for plugin//
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		$tbname[0] = $wpdb->prefix . 'utubevideo_dataset';
		$tbname[1] = $wpdb->prefix . 'utubevideo_album';
		$tbname[2] = $wpdb->prefix . 'utubevideo_video';
		
		$sql = "CREATE TABLE $tbname[0] (
			DATA_ID int(11) NOT NULL AUTO_INCREMENT,
			DATA_NAME varchar(40) NOT NULL,
			DATA_UPDATEDATE int(11) NOT NULL,
			UNIQUE KEY DATA_ID (DATA_ID)
		);
		CREATE TABLE $tbname[1] (
			ALB_ID int(11) NOT NULL AUTO_INCREMENT,
			ALB_NAME varchar(50) NOT NULL,
			ALB_THUMB varchar(40) NOT NULL,
			ALB_SORT varchar(4) DEFAULT 'desc' NOT NULL,
			ALB_UPDATEDATE int(11) NOT NULL,
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
		
		//set up main option defaults if needed//
		$main = get_option('utubevideo_main_opts');
		
		if(empty($main))
			$main = array();
		
		$dft['fancyboxInc'] = 'no';
		$dft['playerWidth'] = 950;
		$dft['playerHeight'] = 537;
		
		$opts = $main + $dft;
		
		update_option('utubevideo_main_opts', $opts);
		
		//create photo cache directory if needed//
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		
		wp_mkdir_p($dir . '/utubevideo-cache');
		
		//copy 'missing.jpg' into cache directory//
		copy(plugins_url('missing.jpg', __FILE__), $dir . '/utubevideo-cache/missing.jpg');

	}
	
	//save main options script//
	if(isset($_POST['utSaveOpts']))
	{
		
		$opts['version'] = '1.0';
		$opts['fancyboxInc'] = (isset($_POST['fancyboxInc']) ? 'yes' : 'no');
			
		if(!empty($_POST['playerWidth']) && !empty($_POST['playerHeight']))
		{
		
			$opts['playerWidth'] = sanitize_text_field($_POST['playerWidth']);
			$opts['playerHeight'] = sanitize_text_field($_POST['playerHeight']);
			
		}
		else
		{
		
			$opts['playerWidth'] = 950;
			$opts['playerHeight'] = 537;
			
		}

		if(update_option('utubevideo_main_opts', $opts))
			echo '<div class="updated fade"><p>Settings saved</p></div>'; 
		else
			echo '<div class="error fade"><p>Oops... something went wrong or there were no changes needed</p></div>';
				
	}
	//save new gallery script//
	elseif(isset($_POST['saveDataset']))
	{
	
		global $wpdb;
		$dsetname = sanitize_text_field($_POST['dsetname']);
		$time = current_time('timestamp');
		
		if($wpdb->insert(
			$wpdb->prefix . 'utubevideo_dataset', 
			array(
				'DATA_NAME' => $dsetname,
				'DATA_UPDATEDATE' => $time
			)
		))
			echo '<div class="updated fade"><p>Gallery created</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';

	}
	//save a gallery edit script//
	elseif(isset($_POST['saveGalleryEdit']))
	{
	
		global $wpdb;
		$galname = htmlentities($_POST['galname'], ENT_QUOTES);
		$key = sanitize_text_field($_POST['key']);
	
		if($wpdb->update(
			$wpdb->prefix . 'utubevideo_dataset', 
			array( 
				'DATA_NAME' => $galname
			), 
			array('DATA_ID' => $key)
		) >= 0)
			echo '<div class="updated fade"><p>Gallery updated</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	//delete a gallery script//
	elseif(isset($_POST['delSet']))
	{
	
		global $wpdb;
		$key = sanitize_text_field($_POST['key']);
		
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		
		//get albums within gallery//
		$rows = $wpdb->get_results('SELECT ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $key, ARRAY_A);	
		
		//for each album get videos and delete thumbnails and references of videos / album//
		foreach($rows as $value)
		{
		
			$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $value['ALB_ID'], ARRAY_A);
			
			foreach($data as $nvalue)
			{
			
				unlink($dir . '/utubevideo-cache/' . $nvalue['VID_URL']  . '.jpg');
			
			}
	
			$wpdb->query( 
				$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_video WHERE ALB_ID = %d", $value['ALB_ID'])
			);
		
			$wpdb->query( 
				$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_album WHERE ALB_ID = %d", $value['ALB_ID'])
			);
	
		}
			
		if($wpdb->query( 
			$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_dataset WHERE DATA_ID = %d", $key)
        ))
			echo '<div class="updated fade"><p>Gallery deleted</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
		
	}
	//save a new album script//
	elseif(isset($_POST['saveAlbum']))
	{
	
		$dataid = sanitize_text_field($_GET['id']);
		$alname = htmlentities($_POST['alname'], ENT_QUOTES);	
		$vidsort = ($_POST['vidSort'] == 'desc' ? 'desc' : 'asc');		
		$time = current_time('timestamp');
		
		global $wpdb;
		
		if($wpdb->insert(
			$wpdb->prefix . 'utubevideo_album', 
			array(
				'ALB_NAME' => $alname,
				'ALB_THUMB' => 'missing',
				'ALB_SORT' => $vidsort,
				'ALB_UPDATEDATE' => $time,
				'DATA_ID' => $dataid
			)
		))
			echo '<div class="updated fade"><p>Video album created</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
		
	}
	//save a new video script//
	elseif(isset($_POST['saveVideo']))
	{
	
		$url = sanitize_text_field($_POST['url']);
		$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
		$thumbType = htmlentities($_POST['thumbType'], ENT_QUOTES);
		$time = current_time('timestamp');
		$key = sanitize_text_field($_POST['key']);
		
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];		
		
		//parse video url to get video id//
		$url = parse_url($url);
		parse_str($url['query']);
		
		$yurl = 'http://img.youtube.com/vi/' . $v . '/0.jpg';
		

		//save image for video into cache//
		$image = wp_get_image_editor($yurl);

		$spath = $dir . '/utubevideo-cache/' . $v . '.jpg';
		
		if(!is_wp_error($image))
		{
	
			if($thumbType == 'square')
				$image->resize(150, 150, true);
			else
				$image->resize(150, 150);

			$image->save($spath);
		
		}	
		
		global $wpdb;
		
		if($wpdb->insert(
			$wpdb->prefix . 'utubevideo_video', 
			array(
				'VID_NAME' => $vidname,
				'VID_URL' => $v,
				'VID_THUMBTYPE' => $thumbType,
				'VID_UPDATEDATE' => $time,
				'ALB_ID' => $key
			)
		))
			echo '<div class="updated fade"><p>Video added to album</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	//save an album edit script//
	elseif(isset($_POST['saveAlbumEdit']))
	{
	
		global $wpdb;
		$alname = htmlentities($_POST['alname'], ENT_QUOTES);
		$vidsort = ($_POST['vidSort'] == 'desc' ? 'desc' : 'asc');	
		$thumb = (isset($_POST['albumThumbSelect']) ? $_POST['albumThumbSelect'] : 'missing');
		
		$key = sanitize_text_field($_POST['key']);
	
		if($wpdb->update(
			$wpdb->prefix . 'utubevideo_album', 
			array( 
				'ALB_NAME' => $alname,
				'ALB_THUMB' => $thumb,
				'ALB_SORT' => $vidsort
			), 
			array('ALB_ID' => $key)
		) >= 0)
			echo '<div class="updated fade"><p>Video album updated</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	//delete an album script//
	elseif(isset($_POST['delAl']))
	{
	
		global $wpdb;
		$key = sanitize_text_field($_POST['key']);
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		
		//get videos in album to delete//
		$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $key, ARRAY_A);
		
		//for each video in album delete thumbnail from cache//
		foreach($data as $value)
		{
		
			unlink($dir . '/utubevideo-cache/' . $value['VID_URL']  . '.jpg');
		
		}
				
		$wpdb->query( 
			$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_video WHERE ALB_ID = %d", $key)
        );	
				
		if($wpdb->query( 
			$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_album WHERE ALB_ID = %d", $key)
        ))
			echo '<div class="updated fade"><p>Video album deleted</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
		
	}
	//save a video edit script//
	elseif(isset($_POST['saveVideoEdit']))
	{
	
		global $wpdb;
		$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
		$thumbType = htmlentities($_POST['thumbType'], ENT_QUOTES);
		$key = sanitize_text_field($_POST['key']);
		
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		
		$rows = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
		
		$yurl = 'http://img.youtube.com/vi/' . $rows[0]['VID_URL'] . '/0.jpg';

		//save image for video into cache//
		$image = wp_get_image_editor($yurl);

		$spath = $dir . '/utubevideo-cache/' . $rows[0]['VID_URL'] . '.jpg';
		
		if(!is_wp_error($image))
		{
	
			if($thumbType == 'square')
				$image->resize(150, 150, true);
			else
				$image->resize(150, 150);

			$image->save($spath);
		
		}	
		
		if($wpdb->update(
			$wpdb->prefix . 'utubevideo_video', 
			array( 
				'VID_NAME' => $vidname, 
				'VID_THUMBTYPE' => $thumbType
			), 
			array('VID_ID' => $key)
		) >= 0)
			echo '<div class="updated fade"><p>Video updated</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	//delete a video script//
	elseif(isset($_POST['delVid']))
	{
		
		global $wpdb;
		$key = sanitize_text_field($_POST['key']);
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		
		//get thumbnail name for video//
		$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
		
		//delete video thumbnail//
		unlink($dir . '/utubevideo-cache/' . $data[0]['VID_URL']  . '.jpg');
		
		if($wpdb->query( 
			$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_video WHERE VID_ID = %d", $key)
        ))
			echo '<div class="updated fade"><p>Video deleted</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	
	//main admin settings forms//
	function utubevideo_main_settings()
	{
	
	?>
		
		<div class="wrap" id="utubevideo_main_opts">
		
			<?php screen_icon(); ?>
			
			<h2>uTubeVideo Settings</h2>
			
			<script>
				
				jQuery(function(){
					
					jQuery('.utConfirm').click(function(){
						
						if(!confirm('Are you sure you want to delete this item?'))
							return false;
						
					});
					
				});
				
			</script>
			
			<?php	
			
			//display create a gallery form//
			if(isset($_POST['createDataset']))
			{
			
			?>

				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Create Gallery'); ?></h3>
						<p>
							<label><?php _e('Gallery Name: '); ?></label>
							<input type="text" name="dsetname"/>
							<span class="utHint"><?php _e(' ex: name of gallery for your reference'); ?></span>
						</p>			
						<p class="submit">  
							<input type="submit" name="saveDataset" value="<?php _e('Save New Gallery') ?>" class="button-primary"/> 
							<a href="?page=utubevideo_settings" class="utCancel">Go Back</a>							
						</p> 
					</form>
				</div>
			
			<?php
			
			}
			//display create album form//
			elseif(isset($_POST['createAl']))
			{
			
			?>
			
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Create Video Album'); ?></h3>
						<p>
							<label><?php _e('Album Name: '); ?></label>
							<input type="text" name="alname"/>
							<span class="utHint"><?php _e(' ex: name of video album'); ?></span>
						</p>
						<p>
							<label><?php _e('Video Sorting: '); ?></label>
							<select name="vidSort">
								<option value="desc">Newest First</option>
								<option value="asc">Oldest First</option>
							</select>
							<span class="utHint"><?php _e(' ex: the order that videos will be displayed'); ?></span>
						</p>
						<p class="submit">  
							<input type="submit" name="saveAlbum" value="<?php _e('Save New Album') ?>" class="button-primary"/> 
							<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel">Go Back</a>							
						</p> 
					</form>
				</div>

			<?php
			}
			//display gallery edit form//
			elseif(isset($_POST['editGal']))
			{
			
				global $wpdb;
				$key = sanitize_text_field($_POST['key']);
				$rows = $wpdb->get_results('SELECT DATA_NAME FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $key, ARRAY_A);
				
			?>

				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Edit Gallery'); ?></h3>
						<p>
							<label><?php _e('Gallery Name: '); ?></label>
							<input type="text" name="galname" value="<?php echo $rows[0]['DATA_NAME']; ?>"/>
							<span class="utHint"><?php _e(' ex: name of gallery'); ?></span>
						</p>
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $key; ?>"/>
							<input type="submit" name="saveGalleryEdit" value="<?php _e('Save Changes') ?>" class="button-primary"/> 
							<a href="?page=utubevideo_settings" class="utCancel">Go Back</a>		
						</p> 
					</form>
				</div>
			
			<?php
			
			}
			//display album edit form//
			elseif(isset($_POST['editAl']))
			{
			
				global $wpdb;
				$key = sanitize_text_field($_POST['key']);
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];

				$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
				$thumbs = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $key, ARRAY_A);
				
			?>
				
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Edit Video Album'); ?></h3>
						<p>
							<img src="<?php echo $dir . '/utubevideo-cache/' . $rows[0]['ALB_THUMB'] . '.jpg'; ?>" class="utPrevThumb"/>
						</p>
						<p>
							<label><?php _e('Album Name: '); ?></label>
							<input type="text" name="alname" value="<?php echo stripslashes($rows[0]['ALB_NAME']); ?>"/>
							<span class="utHint"><?php _e(' ex: name of video album'); ?></span>
						</p>
						<p>
							<label><?php _e('Video Sorting: '); ?></label>
							<select name="vidSort">
							
							<?php
							
							$opts = array(array('text' => 'Newest First', 'value' => 'desc'), array('text' => 'Oldest First', 'value' => 'asc'));	
					
							foreach($opts as $value)
							{
							
								if($value['value'] == $rows[0]['ALB_SORT'])
									echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
								else
									echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';

							}
							
							?>
							
							</select>
							<span class="utHint"><?php _e(' ex: the order that videos will be displayed'); ?></span>
						</p>						
						<p>
							<label><?php _e('Select Album Thumbnail: '); ?></label>
							<div id="utThumbSelection">
							
							<?php
							
							
							if(!empty($thumbs))
							{
							
								foreach($thumbs as $value)
								{
								
								?>
								
									<div>
										<img src="<?php echo $dir . '/utubevideo-cache/' . $value['VID_URL'] . '.jpg'; ?>" class="utPrevThumb"/>
										<input type="radio" name="albumThumbSelect" value="<?php echo $value['VID_URL']; ?>" <?php echo ($rows[0]['ALB_THUMB'] == $value['VID_URL'] ? 'checked' : ''); ?>/>
									</div>
								
								<?php
								
								}
								
							}
							else
								echo '<span class="utAdminError">Oops, you have not added any videos to this album yet</span>';
								
							?>
						
							</div>
							<span class="utHint"><?php _e(' ex: choose the thumbnail for the album'); ?></span>
						</p>
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $key; ?>"/>
							<input type="submit" name="saveAlbumEdit" value="<?php _e('Save Changes') ?>" class="button-primary"/> 
							<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>			
						</p> 
					</form>
				</div>

			<?php
			
			}
			//display add video form//
			elseif(isset($_POST['addVideo']))
			{
			
				global $wpdb;
				$key = sanitize_text_field($_POST['key']);
				$rows = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
			
			?>
				
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php echo _('Add New Video to [') . $rows[0]['ALB_NAME'] . ']'; ?></h3>
						<p>
							<label><?php _e('Video URL: '); ?></label>
							<input type="text" name="url"/>
							<span class="utHint"><?php _e(' ex: youtube video url'); ?></span>
						</p>		
						<p>
							<label><?php _e('Video Name: '); ?></label>
							<input type="text" name="vidname"/>
							<span class="utHint"><?php _e(' ex: the name of the video'); ?></span>
						</p>
						<p>
							<label><?php _e('Thumbnail Type: '); ?></label>
							<select name="thumbType"/>
								<option value="rectangle">Rectangle</option>
								<option value="square">Square</option>
							</select>
							<span class="utHint"><?php _e(' ex: the type of thumbnail'); ?></span>
						</p>						
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $key; ?>"/>
							<input type="submit" name="saveVideo" value="<?php _e('Save New Video') ?>" class="button-primary"/> 
							<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>			
						</p> 
					</form>
				</div>
				
			<?php

			}
			//display video edit form//
			elseif(isset($_POST['editVid']))
			{
			
				global $wpdb;
				$key = sanitize_text_field($_POST['key']);
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];
						
				$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
				
			?>
				
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Edit Video'); ?></h3>
						<p>
							<img src="<?php echo $dir . '/utubevideo-cache/' . $rows[0]['VID_URL'] . '.jpg';?>" class="utPrevThumb"/>
						</p>
						<p>
							<label><?php _e('Video Name: '); ?></label>
							<input type="text" name="vidname" value="<?php echo stripslashes($rows[0]['VID_NAME']); ?>"/>
							<span class="utHint"><?php _e(' ex: name of video'); ?></span>
						</p>
						<p>
							<label><?php _e('Thumbnail Type: '); ?></label>
							<select name="thumbType"/>
							
							<?php
							
							$opts = array(array('text' => 'Rectangle', 'value' => 'rectangle'), array('text' => 'Square', 'value' => 'square'));	
					
							foreach($opts as $value)
							{
							
								if($value['value'] == $rows[0]['VID_THUMBTYPE'])
									echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
								else
									echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';

							}
							
							?>
								
							</select>
							<span class="utHint"><?php _e(' ex: the type of thumbnail'); ?></span>
						</p>
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $key; ?>"/>
							<input type="hidden" name="prev" value="<?php echo $_POST['prev']; ?>"/>
							<input type="submit" name="saveVideoEdit" value="<?php _e('Save Changes') ?>" class="button-primary"/> 
							<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>							
						</p> 
					</form>
				</div>

			<?php
			
			}
			//if act parameter is set//
			elseif(isset($_GET['act']))
			{
			
				//view video albums in a gallery//
				if($_GET['act'] == 'viewdset')
				{
				
					global $wpdb;
					$id = $_GET['id'];
					$dir = wp_upload_dir();
					$dir = $dir['baseurl'];
							
					$data = $wpdb->get_results('SELECT DATA_NAME FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = "' . $id . '"', ARRAY_A);
			
				?>

					<div class="utFormBox utTopformBox">
						<h3><?php echo 'Video Albums for [' . $data[0]['DATA_NAME'] . ']'; ?></h3>
						<table class="widefat fixed utTable">
							<thead>
								<tr>
									<th>Album Thumbnail</th>
									<th>Album Name</th>
									<th>Date Added</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
						
							<?php

							$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $id . ' ORDER BY ALB_ID', ARRAY_A);
					
							if(empty($rows))
							{
							
							?>
						
								<tr>
									<td>No albums found</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
												
							<?php
						
							}
							else
							{
						
								foreach($rows as $value)
								{
							
								?>
							
								<tr>
									<td><img src="<?php echo $dir . '/utubevideo-cache/' . $value['ALB_THUMB'] . '.jpg';?>" class="utPrevThumb"/></td>
									<td><?php echo $value['ALB_NAME']; ?></td>
									<td><?php echo date('M j, Y @ g:ia', $value['ALB_UPDATEDATE']); ?></td>
									<td>
										<form method="post">
											<input class="linkButton" type="submit" name="editAl" value="Edit"/>
											<input class="linkButton" type="submit" name="addVideo" value="Add Video"/>
											<input class="linkButton utConfirm" type="submit" name="delAl" value="Delete"/>
											<input type="hidden" name="key" value="<?php echo $value['ALB_ID']; ?>"/>
											<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewdset&id=<?php echo $id; ?>"/>
											<a href="?page=utubevideo_settings&act=viewal&id=<?php echo $value['ALB_ID']; ?>&prev=<?php echo urlencode('?page=utubevideo_settings&act=viewdset&id=' . $id); ?>">View</a>
										</form>
									</td>
								</tr>

								<?php
							
								}
						
							}
						
							?>
						
							</tbody>
							<tfoot>
								<tr>
									<th>Album Thumbnail</th>
									<th>Album Name</th>
									<th>Date Added</th>
									<th>Actions</th>
								</tr>
							</tfoot>
						</table>
						<form method="post">
							<p class="submit">
								<input class="button-secondary" type="submit" name="createAl" value="Create New Album"/>
								<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewdset&id=<?php echo $id; ?>"/>
								<a href="?page=utubevideo_settings" class="utCancel">Go Back</a>
							</p>
						</form>
					</div>

				<?php
			
				}
				//view videos within a video album//
				elseif($_GET['act'] == 'viewal')
				{

					global $wpdb;
					$id = $_GET['id'];
					$dir = wp_upload_dir();
					$dir = $dir['baseurl'];
							
					$data = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = "' . $id . '"', ARRAY_A);
			
				?>

					<div class="utFormBox utTopformBox">
						<h3><?php echo 'Videos for [' . $data[0]['ALB_NAME'] . ']'; ?></h3>
						<table class="widefat fixed utTable">
							<thead>
								<tr>
									<th>Video Thumbnail</th>
									<th>Video Name</th>
									<th>Date Added</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
						
							<?php
						
							$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $id . ' ORDER BY VID_UPDATEDATE', ARRAY_A);
							
							if(empty($rows))
							{
							
							?>
						
								<tr>
									<td>No videos found</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>

							<?php
						
							}
							else
							{

								foreach($rows as $value)
								{
							
								?>
							
								<tr>
									<td><img src="<?php echo $dir . '/utubevideo-cache/' . $value['VID_URL'] . '.jpg';?>" class="utPrevThumb"/></td>
									<td><?php echo stripslashes($value['VID_NAME']); ?></td>
									<td><?php echo date('M j, Y @ g:ia', $value['VID_UPDATEDATE']); ?></td>
									<td>
										<form method="post">
											<input class="linkButton" type="submit" name="editVid" value="Edit"/>
											<input class="linkButton utConfirm" type="submit" name="delVid" value="Delete"/>
											<input type="hidden" name="key" value="<?php echo $value['VID_ID']; ?>"/>
											<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewal&id=<?php echo $id; ?>"/>
											<a href="http://www.youtube.com/watch?v=<?php echo $value['VID_URL']; ?>" target="_blank">Watch</a>
										</form>
									</td>
								</tr>
								
								<?php
								
								}
						
							}
						
							?>
						
							</tbody>
							<tfoot>
								<tr>
									<th>Video Thumbnail</th>
									<th>Video Name</th>
									<th>Date Added</th>
									<th>Actions</th>
								</tr>
							</tfoot>
						</table>
						<p class="submit">
							<a href="<?php echo (isset($_GET['prev']) ? $_GET['prev'] : $_POST['prev']); ?>" class="utCancel">Go Back</a>
						</p>
					</div>

				<?php
				
				}
				
			}
			//display main options and galleries//
			else
			{
				
				$main = get_option('utubevideo_main_opts');

			?>
			
				<script>
				
					//javascript for changing and resetting values for player size//
					jQuery(function(){
					
						jQuery('#resetWidth').click(function(){
		
							jQuery('#playerWidth').val('950');
							jQuery('#playerHeight').val('537');
							return false;
						
						});
						
						jQuery('#playerWidth').keyup(function(){
						
							jQuery('#playerHeight').val(Math.round(jQuery('#playerWidth').val() / 1.77));
						
						});
						
						jQuery('#playerHeight').keyup(function(){
						
							jQuery('#playerWidth').val(Math.round(jQuery('#playerHeight').val() * 1.77));
						
						});
					
					});
				
				</script>
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3>General Settings</h3>					
						<p>
							<label><?php _e('Include Fancybox Scripts: '); ?></label>
							<input type="checkbox" name="fancyboxInc" <?php echo ($main['fancyboxInc'] == 'yes' ? 'checked' : ''); ?>/>
							<span class="utHint"><?php _e(' ex: check only if not using a fancybox plugin'); ?></span>
						</p> 
						<p>
							<label><?php _e('Max Video Player Dimensions: '); ?></label>
							<input type="text" name="playerWidth" id="playerWidth" value="<?php echo $main['playerWidth']; ?>"/>
							<span> X </span>
							<input type="text" name="playerHeight" id="playerHeight" value="<?php echo $main['playerHeight']; ?>"/>
							<button id="resetWidth" class="button-secondary">Reset</button>
							<span class="utHint"><?php _e(' ex: set max dimensions of video player, aspect ratio is 1.77 (16:9)'); ?></span>
						</p>
						<p class="submit">  
							<input type="submit" name="utSaveOpts" value="<?php _e('Save Changes') ?>" class="button-primary"/>  
						</p> 
					</form>	
				</div>
				<div class="utFormBox">
					<h3>uTubeVideo Galleries</h3>
					<table class="widefat fixed utTable">
						<thead>
							<tr>
								<th>Gallery	Name</th>
								<th>Shortcode</th>
								<th>Date Added</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							
						<?php
						
						global $wpdb;
							
						$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_dataset ORDER BY DATA_ID', ARRAY_A);
							
						if(empty($rows))
						{
							
						?>
							
							<tr>
								<td>No galleries found</td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
							
						<?php
							
						}
						else
						{
						
							foreach($rows as $value)
							{

							?>
							
							<tr>
								<td><?php echo $value['DATA_NAME']; ?></td>
								<td>[utubevideo id="<?php echo $value['DATA_ID']; ?>"]</td>
								<td><?php echo date('M j, Y @ g:ia', $value['DATA_UPDATEDATE']); ?></td>
								<td>
									<form method="post">
										<a href="?page=utubevideo_settings&act=viewdset&id=<?php echo $value['DATA_ID']; ?>" class="utBlock">View</a>
										<input class="linkButton utConfirm" type="submit" name="delSet" value="Delete"/>
										<input class="linkButton" type="submit" name="editGal" value="Edit"/>
										<input type="hidden" name="key" value="<?php echo $value['DATA_ID']; ?>"/>
									</form>
								</td>	
							</tr>
								
							<?php
							
							}	
						
						}
						
						?>
							
						</tbody>
						<tfoot>
							<tr>
								<th>Gallery Name</th>
								<th>Shortcode</th>
								<th>Date Added</th>
								<th>Actions</th>
							</tr>
						</tfoot>
					</table>
					<form method="post">
						<p class="submit">
							<input class="button-secondary" type="submit" name="createDataset" value="Create New Gallery"/>
						</p>
					</form>
				</div>
				<div class="utFormBox">
					<h3>FAQ's</h3>
					<ul>
						<li>All videos from youtube may be added into a gallery unless embedding has been disabled for the video on youtube.</li>
						<li>If using a fancybox plugin do not check the 'Include Fancybox Scripts' box, if not using a fancybox plugin do check the box or otherwise the video popup will not work correctly.</li>
						<li>Only YouTube videos can be added to a gallery at this time.</li>
						<li>To create a gallery first click the 'Create New Gallery' button. Once a gallery has been created you can click 'View' in the actions panel to show the video albums within the gallery. Click 'Create New Album' to make a blank video album for the gallery. Once an album has been created it will be given a default missing album art cover. Add videos to the album by clicking 'Add Video' in the actions panel for a video album. Once videos are added to the album you may click 'Edit' on the albums actions pane. To add gallery to a page or post copy and paste the gallery's shortcode onto that page or post.</li>
						<li>You can set the size of the video player by changing the max video player dimensions in the General Settings part of this menu. The video size will automatically retain a 1.77 (16:9) aspect ratio.</li>
						<li>Video Albums can be sorted by either newest or oldest video first.</li>
						<li>Video thumbnails can be either be a square or a rectangle.</li>
					</ul>
				</div>

			<?php
			
			}
			
			?>
			
		</div>
	
	<?php
	
	}
	
	//setup hooks for administration//
	register_activation_hook( __FILE__, 'utubevideo_activate');
	add_action('admin_menu', create_function('', 'add_options_page("uTube Video Settings", "uTubeVideo", "manage_options", "utubevideo_settings", "utubevideo_main_settings");'));
	add_action('admin_head', 'utubevideo_style_setup');
	add_action('admin_head', 'utubevideo_admin_scripts_setup');

}
//if at frontend//
else
{
	
	//shortcode function for frontend//
	function utubevideo_shortcode($atts)
	{

		extract($atts);
		
		global $wpdb;
		$dir = wp_upload_dir();
		$dir = $dir['baseurl'];
		$valid = false;
		
		$content = '<div class="utVideoContainer">';
		
		//check each shortcode for valid access of videos... only one should show videos, others should show albums//
		if(isset($_GET['aid']))
		{
		
			$raw = sanitize_text_field($_GET['aid']);
			$args = explode('_', $raw);
			
			//if valid aid token//
			if(count($args) == 2)
			{
			
				$aid = $args[0];
				$check = $args[1];
				
				if($check == $id)
					$valid = true;
			
			}
			
		}
		
		//display videos from album//
		if($valid)
		{
		
			//get name of video album//
			$meta = $wpdb->get_results('SELECT ALB_NAME, ALB_SORT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $aid, ARRAY_A);
		
			//get videos in album//
			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $aid . ' ORDER BY VID_UPDATEDATE ' . $meta[0]['ALB_SORT'], ARRAY_A);
			
			//if there are videos in the video album//
			if(!empty($rows))
			{
			
				//create html for breadcrumbs//
				$content .= '<div class="utBreadcrumbs"><a href="' . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . '">Albums</a><span> > ' . stripslashes($meta[0]['ALB_NAME']) . '</span></div>';
			
				//create html for each video//
				foreach($rows as $value)
				{
				
					$content .= '<div class="utThumb"><a href="http://www.youtube.com/embed/' . $value['VID_URL'] . '?rel=0&showinfo=0&autohide=1&autoplay=1&&iv_load_policy=3" title="' . stripslashes($value['VID_NAME']) . '" class="utFancyVid"><img src="' . $dir . '/utubevideo-cache/' . $value['VID_URL']  . '.jpg"/></a><span>' . stripslashes($value['VID_NAME']) . '</span></div>';
					
				}
			
			}
			//if the video album is empty :(//
			else
			{
			
				echo 'Sorry... there appear to be no videos for this album yet.';
				
			}
		
		}
		//display video albums//
		else
		{
		
			//get video albums in the gallery//
			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $id . ' ORDER BY ALB_ID', ARRAY_A);
			
			//if there are video albums in the gallery//
			if(!empty($rows))
			{
		
				//create html for each video album//
				foreach($rows as $value)
				{
			
					$content .= '<div class="utThumb"><a href="?aid=' . $value['ALB_ID'] . '_' . $id . '"><img src="' . $dir . '/utubevideo-cache/' . $value['ALB_THUMB']  . '.jpg"/></a><span>' . $value['ALB_NAME'] . '</span></div>';
			
				}
		
			}
			//if there are no video albums in the gallery :(//
			else
			{
			
				echo 'Sorry... there appear to be no video albums yet.';
				
			}
		
		}
						
		$content .= '</div>';

		//return html//
		return $content;

	}

	//insert fancybox calls for videogalleries
	function utubevideo_fancybox_call()
	{
	
		//get options//
		$main = get_option('utubevideo_main_opts');
	
	?>

		<script>
		
			jQuery(function(){

				jQuery('a.utFancyVid').fancybox({
					'cyclic': false,
					'padding': 0,
					'opacity': true,
					'speedIn': 500,
					'speedOut': 500,
					'changeSpeed': 300,
					'overlayShow': true,
					'overlayOpacity': '0.8',
					'overlayColor': '#000',
					'titleShow': true,
					'titlePosition': 'outside',
					'enableEscapeButton': true,
					'showCloseButton': true,
					'showNavArrows': false,				
					'width': <?php echo $main['playerWidth']; ?>,
					'height': <?php echo $main['playerHeight']; ?>,
					'centerOnScroll': true,
					'type': 'iframe'
				});
					
			});

		</script>

	<?php

	}
	
	//embed fancybox scripts if needed//
	function utubevideo_scripts_setup()
	{
	
		//get options//
		$main = get_option('utubevideo_main_opts');
		
		if($main['fancyboxInc'] == 'yes')
		{
		

			wp_register_script('utubevideo_fancybox_script', plugins_url('fancybox/jquery.fancybox-1.3.4.pack.js', __FILE__), array('jquery'), null);
			wp_register_style('utubevideo_fancybox_style', plugins_url('fancybox/jquery.fancybox-1.3.4.css', __FILE__), false, null);

			wp_enqueue_script('jquery');
			wp_enqueue_script('utubevideo_fancybox_script');
			wp_enqueue_style('utubevideo_fancybox_style');

		}
	
	}
	
	//frontend hooks//
	add_shortcode('utubevideo', 'utubevideo_shortcode');
	add_action('wp_head', 'utubevideo_fancybox_call');
	add_action('wp_enqueue_scripts', 'utubevideo_style_setup');
	add_action('wp_enqueue_scripts', 'utubevideo_scripts_setup');

}

//add stylesheet to pages//
function utubevideo_style_setup()
{

	wp_register_style('utubevideo_style', plugins_url('style.css', __FILE__), false, null);
	wp_enqueue_style('utubevideo_style');

}

//add jquery to administration page//
function utubevideo_admin_scripts_setup()
{

	wp_enqueue_script('jquery');
	
}

?>