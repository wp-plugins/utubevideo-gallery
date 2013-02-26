<?php 

/*
Plugin Name: uTube Video Gallery	
Plugin URI: http://www.codeclouds.net/
Description: This plugin allows you to create youtube video galleries right in your wordpress site.
Version: 1.1
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

if(is_admin())
{

	//run when plugin is activated to setup stuff//
	function utubevideo_activate()
	{

		//create database tables//
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
			ALB_UPDATEDATE int(11) NOT NULL,
			DATA_ID int(11) NOT NULL,
			UNIQUE KEY ALB_ID (ALB_ID)
		);
		CREATE TABLE $tbname[2] (
			VID_ID int(11) NOT NULL AUTO_INCREMENT,
			VID_NAME varchar(50) NOT NULL,
			VID_URL varchar(40) NOT NULL,
			VID_UPDATEDATE int(11) NOT NULL,
			ALB_ID int(11) NOT NULL,
			UNIQUE KEY VID_ID (VID_ID)
		);";
		
		dbDelta($sql);
		
		//set up main option defaults if needed//
		$main = get_option('utubevideo_main_opts');
		
		if(empty($main))
			$main = array();
		
		$dft['version'] = '1.0';
		$dft['fancyboxInc'] = 'no';
		
		$opts = $main + $dft;
		
		update_option('utubevideo_main_opts', $opts);
		
	}
	
	//save main options//
	if(isset($_POST['utSaveOpts']))
	{
		
		$opts['version'] = '1.0';
		
		if(isset($_POST['fancyboxInc']))
			$opts['fancyboxInc'] = 'yes';
		else
			$opts['fancyboxInc'] = 'no';
				
		if(update_option('utubevideo_main_opts', $opts))
			echo '<div class="updated fade"><p>Settings saved</p></div>'; 
		else
			echo '<div class="error fade"><p>Oops... something went wrong or there were no changes needed</p></div>';
				
	}
	//save new dataset or shortcode//
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
			echo '<div class="updated fade"><p>Dataset created</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';

	}
	//delete a dataset//
	elseif(isset($_POST['delSet']))
	{
	
		global $wpdb;
		$key = sanitize_text_field($_POST['key']);
		
		$rows = $wpdb->get_results('SELECT ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $key, ARRAY_A);	
		
		foreach($rows as $value)
		{
		
			$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $value['ALB_ID'], ARRAY_A);
			
			foreach($data as $nvalue)
			{
			
				unlink(plugin_dir_path(__FILE__) . 'cache/' . $nvalue['VID_URL']  . '.jpg');
			
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
			echo '<div class="updated fade"><p>Dataset deleted</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
		
	}
	//save a new album//
	elseif(isset($_POST['saveAlbum']))
	{
	
		$dataid = sanitize_text_field($_GET['id']);
		$alname = htmlentities($_POST['alname'], ENT_QUOTES);
		$url = sanitize_text_field($_POST['url']);
		$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
		$time = current_time('timestamp');
		
		$url = parse_url($url);
		parse_str($url['query']);
		
		$yurl = 'http://img.youtube.com/vi/' . $v . '/0.jpg';
		
		$image = wp_get_image_editor($yurl); 
	
		$spath = plugin_dir_path(__FILE__) . 'cache/' . $v . '.jpg';
		
		if(!is_wp_error($image))
		{
		
			$image->resize(150, 150);
			$image->save($spath);
		
		}	

		global $wpdb;
		
		$wpdb->insert(
			$wpdb->prefix . 'utubevideo_album', 
			array(
				'ALB_NAME' => $alname,
				'ALB_THUMB' => $v,
				'ALB_UPDATEDATE' => $time,
				'DATA_ID' => $dataid
			)
		);
		
		$key = $wpdb->insert_id;
		
		if($wpdb->insert(
			$wpdb->prefix . 'utubevideo_video', 
			array(
				'VID_NAME' => $vidname,
				'VID_URL' => $v,
				'VID_UPDATEDATE' => $time,
				'ALB_ID' => $key
			)
		))
			echo '<div class="updated fade"><p>Video album created</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';

	}
	//save a new video//
	elseif(isset($_POST['saveVideo']))
	{
	
		$url = sanitize_text_field($_POST['url']);
		$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
		$time = current_time('timestamp');
		$key = sanitize_text_field($_POST['key']);
		
		$url = parse_url($url);
		parse_str($url['query']);
		
		$yurl = 'http://img.youtube.com/vi/' . $v . '/0.jpg';
		
		$image = wp_get_image_editor($yurl); 

		$spath = plugin_dir_path(__FILE__) . 'cache/' . $v . '.jpg';
		
		if(!is_wp_error($image))
		{
		
			$image->resize(150, 150);
			$image->save($spath);
		
		}	
		
		global $wpdb;
		
		if($wpdb->insert(
			$wpdb->prefix . 'utubevideo_video', 
			array(
				'VID_NAME' => $vidname,
				'VID_URL' => $v,
				'VID_UPDATEDATE' => $time,
				'ALB_ID' => $key
			)
		))
			echo '<div class="updated fade"><p>Video added to album</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	//save an album edit//
	elseif(isset($_POST['saveAlbumEdit']))
	{
	
		global $wpdb;
		$alname = htmlentities($_POST['alname'], ENT_QUOTES);
		$key = sanitize_text_field($_POST['key']);
	
		if($wpdb->update(
			$wpdb->prefix . 'utubevideo_album', 
			array( 
				'ALB_NAME' => $alname
			), 
			array('ALB_ID' => $key)
		) >= 0)
			echo '<div class="updated fade"><p>Video album updated</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	//delete an album//
	elseif(isset($_POST['delAl']))
	{
	
		global $wpdb;
		$key = sanitize_text_field($_POST['key']);
		
		$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $key, ARRAY_A);
		
		foreach($data as $value)
		{
		
			unlink(plugin_dir_path(__FILE__) . 'cache/' . $value['VID_URL']  . '.jpg');
		
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
	//save a video edit//
	elseif(isset($_POST['saveVideoEdit']))
	{
	
		global $wpdb;
		$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
		$key = sanitize_text_field($_POST['key']);
		
		if(isset($_POST['useAlbumThumb']))
		{

			$rows = $wpdb->get_results('SELECT VID_URL, ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
			
			$wpdb->update(
				$wpdb->prefix . 'utubevideo_album', 
				array( 
					'ALB_THUMB' => $rows[0]['VID_URL']
				), 
				array('ALB_ID' => $rows[0]['ALB_ID'])
			);

		}
			
		if($wpdb->update(
			$wpdb->prefix . 'utubevideo_video', 
			array( 
				'VID_NAME' => $vidname
			), 
			array('VID_ID' => $key)
		) >= 0)
			echo '<div class="updated fade"><p>Video updated</p></div>';
		else
			echo '<div class="error fade"><p>Oops... something went wrong</p></div>';
	
	}
	//delete a video//
	elseif(isset($_POST['delVid']))
	{
		
		global $wpdb;
		$key = sanitize_text_field($_POST['key']);
		
		$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
		
		unlink(plugin_dir_path(__FILE__) . 'cache/' . $data[0]['VID_URL']  . '.jpg');
		
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
			
			<h2>uTube Video Settings</h2>
			
			<?php	
			
			//create a dataset form//
			if(isset($_POST['createDataset']))
			{
			
			?>
			
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Create Dataset'); ?></h3>
						<p>
							<label><?php _e('Dataset Name: '); ?></label>
							<input type="text" name="dsetname"/>
							<span class="utHint"><?php _e(' ex: name of dataset for your reference'); ?></span>
						</p>			
						<p class="submit">  
							<input type="submit" name="saveDataset" value="<?php _e('Save Dataset') ?>" class="button-primary"/> 
							<a href="?page=utubevideo_settings" class="utCancel">Go Back</a>							
						</p> 
					</form>
				</div>
			
			<?php
			
			}
			//create an album form//
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
							<label><?php _e('Video URL: '); ?></label>
							<input type="text" name="url"/>
							<span class="utHint"><?php _e(' ex: first youtube video for album'); ?></span>
						</p>		
						<p>
							<label><?php _e('Video Name: '); ?></label>
							<input type="text" name="vidname"/>
							<span class="utHint"><?php _e(' ex: the name of the video'); ?></span>
						</p>							
						<p class="submit">  
							<input type="submit" name="saveAlbum" value="<?php _e('Save Album') ?>" class="button-primary"/> 
							<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel">Go Back</a>							
						</p> 
					</form>
				</div>

			<?php
			}
			//edit an album form//
			elseif(isset($_POST['editAl']))
			{
			
				global $wpdb;
				$key = sanitize_text_field($_POST['key']);

				$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
				
			?>
				
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Edit Video Album'); ?></h3>
						<p>
							<img src="<?php echo plugin_dir_url(__FILE__) . 'cache/' . $rows[0]['ALB_THUMB'] . '.jpg';?>" class="utPrevThumb"/>
						</p>
						<p>
							<label><?php _e('Album Name: '); ?></label>
							<input type="text" name="alname" value="<?php echo stripslashes($rows[0]['ALB_NAME']); ?>"/>
							<span class="utHint"><?php _e(' ex: name of video album'); ?></span>
						</p>						
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $key; ?>"/>
							<input type="submit" name="saveAlbumEdit" value="<?php _e('Save Album') ?>" class="button-primary"/> 
							<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>			
						</p> 
					</form>
				</div>

			<?php
			
			}
			//add a video form//
			elseif(isset($_POST['addVideo']))
			{
			
				$key = sanitize_text_field($_POST['key']);
				
			?>
				
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Add New Video'); ?></h3>
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
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $key; ?>"/>
							<input type="submit" name="saveVideo" value="<?php _e('Save Video') ?>" class="button-primary"/> 
							<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>			
						</p> 
					</form>
				</div>
				
			<?php

			}
			//edit a video form//
			elseif(isset($_POST['editVid']))
			{
			
				global $wpdb;
				$key = sanitize_text_field($_POST['key']);
						
				$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
				
			?>
				
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3><?php _e('Edit Video'); ?></h3>
						<p>
							<img src="<?php echo plugin_dir_url(__FILE__) . 'cache/' . $rows[0]['VID_URL'] . '.jpg';?>" class="utPrevThumb"/>
						</p>
						<p>
							<label><?php _e('Video Name: '); ?></label>
							<input type="text" name="vidname" value="<?php echo stripslashes($rows[0]['VID_NAME']); ?>"/>
							<span class="utHint"><?php _e(' ex: name of video'); ?></span>
						</p>
						<p>
							<label><?php _e('Use as Album Cover: '); ?></label>
							<input type="checkbox" name="useAlbumThumb"/>
							<span class="utHint"><?php _e(' ex: use as album thumb'); ?></span>
						</p>
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $key; ?>"/>
							<input type="hidden" name="prev" value="<?php echo $_POST['prev']; ?>"/>
							<input type="submit" name="saveVideoEdit" value="<?php _e('Save Video') ?>" class="button-primary"/> 
							<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>							
						</p> 
					</form>
				</div>

			<?php
			
			}
			//if act parameter is set//
			elseif(isset($_GET['act']))
			{
			
				//view a dataset//
				if($_GET['act'] == 'viewdset')
				{
				
					global $wpdb;
					$id = $_GET['id'];
							
					$data = $wpdb->get_results('SELECT DATA_NAME FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = "' . $id . '"', ARRAY_A);
			
				?>

					<div class="utFormBox utTopformBox">
						<h3><?php echo 'Video Albums for ' . $data[0]['DATA_NAME']; ?></h3>
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
									<td><img src="<?php echo plugin_dir_url(__FILE__) . 'cache/' . $value['ALB_THUMB'] . '.jpg';?>" class="utPrevThumb"/></td>
									<td><?php echo $value['ALB_NAME']; ?></td>
									<td><?php echo date('M j, Y @ g:ia', $value['ALB_UPDATEDATE']); ?></td>
									<td>
										<form method="post">
											<input class="linkButton" type="submit" name="editAl" value="Edit"/>
											<input class="linkButton" type="submit" name="addVideo" value="Add Video"/>
											<input class="linkButton" type="submit" name="delAl" value="Delete"/>
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
								<input class="button-secondary" type="submit" name="createAl" value="Create Album"/>
								<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewdset&id=<?php echo $id; ?>"/>
								<a href="?page=utubevideo_settings" class="utCancel">Go Back</a>
							</p>
						</form>
					</div>

				<?php
			
				}
				//view an album//
				elseif($_GET['act'] == 'viewal')
				{

					global $wpdb;
					$id = $_GET['id'];
							
					$data = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = "' . $id . '"', ARRAY_A);
			
				?>

					<div class="utFormBox utTopformBox">
						<h3><?php echo 'Videos for ' . $data[0]['ALB_NAME']; ?></h3>
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
									<td><img src="<?php echo plugin_dir_url(__FILE__) . 'cache/' . $value['VID_URL'] . '.jpg';?>" class="utPrevThumb"/></td>
									<td><?php echo stripslashes($value['VID_NAME']); ?></td>
									<td><?php echo date('M j, Y @ g:ia', $value['VID_UPDATEDATE']); ?></td>
									<td>
										<form method="post">
											<input class="linkButton" type="submit" name="editVid" value="Edit"/>
											<input class="linkButton" type="submit" name="delVid" value="Delete"/>
											<input type="hidden" name="key" value="<?php echo $value['VID_ID']; ?>"/>
											<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewal&id=<?php echo $id; ?>"/>
											<a href="http://www.youtube.com/watch?v=<?php echo $value['VID_URL']; ?>" target="_blank">View</a>
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
			//display main options and datasets//
			else
			{
				
				$main = get_option('utubevideo_main_opts');

			?>
			
				<div class="utFormBox utTopformBox">
					<form method="post">  
						<h3>General Settings</h3>					
						<p>
							<label><?php _e('Include Fancybox Scripts: '); ?></label>
							<input type="checkbox" name="fancyboxInc" <?php echo ($main['fancyboxInc'] == 'yes' ? 'checked' : ''); ?>/>
							<span class="utHint"><?php _e(' ex: check only if not using a fancybox plugin'); ?></span>
						</p> 
						<p class="submit">  
							<input type="submit" name="utSaveOpts" value="<?php _e('Save Changes') ?>" class="button-primary"/>  
						</p> 
					</form>	
				</div>
				<div class="utFormBox">
					<h3>uTube Video Datasets</h3>
					<table class="widefat fixed utTable">
						<thead>
							<tr>
								<th>Dataset Name</th>
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
								<td>No datasets found</td>
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
										<input class="linkButton" type="submit" name="delSet" value="Delete"/>
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
								<th>Dataset Name</th>
								<th>Shortcode</th>
								<th>Date Added</th>
								<th>Actions</th>
							</tr>
						</tfoot>
					</table>
					<form method="post">
						<p class="submit">
							<input class="button-secondary" type="submit" name="createDataset" value="Create New Dataset"/>
						</p>
					</form>
				</div>

			<?php
			
			}
			
			?>
			
		</div>
	
	<?php
	
	}
	
	//setup hooks for administration//
	register_activation_hook( __FILE__, 'utubevideo_activate');
	add_action('admin_menu', create_function('', 'add_options_page("uTube Video Settings", "uTube", "manage_options", "utubevideo_settings", "utubevideo_main_settings");'));
	add_action('admin_head', 'utubevideo_style_setup');

}
else
{
	
	//shortcode function for frontend//
	function utubevideo_shortcode($atts)
	{

	
		extract($atts);
		global $wpdb;
		
		$content = '<div class="utVideoContainer">';
		
		if(isset($_GET['aid']))
		{
		
			$aid = sanitize_text_field($_GET['aid']);
		
			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $aid . ' ORDER BY VID_UPDATEDATE', ARRAY_A);
			
			$name = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $aid, ARRAY_A);
		
			if(!empty($rows))
			{
			
				$content .= '<div class="utBreadcrumbs"><a href="' . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . '">Albums</a><span> > ' . stripslashes($name[0]['ALB_NAME']) . '</span></div>';
			
				foreach($rows as $value)
				{
				
					$content .= '<div class="utThumb"><a href="http://www.youtube.com/embed/' . $value['VID_URL'] . '?rel=0&showinfo=0&autohide=1&autoplay=1&&iv_load_policy=3" title="' . stripslashes($value['VID_NAME']) . '" class="utFancyVid"><img src="' . plugin_dir_url(__FILE__) . 'cache/' . $value['VID_URL']  . '.jpg"/></a><span>' . stripslashes($value['VID_NAME']) . '</span></div>';
					
				}
			
			}
			else
			{
			
				echo 'Sorry... the page you were looking for no longer exists.';
				
			}
		
		}
		else
		{
		
			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $id . ' ORDER BY ALB_ID', ARRAY_A);
			
			if(!empty($rows))
			{
		
				foreach($rows as $value)
				{
			
					$content .= '<div class="utThumb"><a href="?aid=' . $value['ALB_ID'] . '"><img src="' . plugin_dir_url(__FILE__) . 'cache/' . $value['ALB_THUMB']  . '.jpg"/></a><span>' . $value['ALB_NAME'] . '</span></div>';
			
				}
		
			}
			else
			{
			
				echo 'Sorry... the page you were looking for no longer exists.';
				
			}
		
		}
						
		$content .= '</div>';

		return $content;

	}

	//insert fancybox calls for videogalleries
	function utubevideo_fancybox_call()
	{
	
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
					'overlayOpacity': '0.7',
					'overlayColor': '#000',
					'titleShow': true,
					'titlePosition': 'outside',
					'enableEscapeButton': true,
					'showCloseButton': true,
					'showNavArrows': false,				
					'width': 950,
					'height': 560,
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

		$main = get_option('utubevideo_main_opts');
		
		if($main['fancyboxInc'] == 'yes')
		{
		

			wp_register_script('utubevideo_fancybox_script', plugins_url('fancybox/jquery.fancybox-1.3.4.pack.js', __FILE__));
			wp_register_style('utubevideo_fancybox_style', plugins_url('fancybox/jquery.fancybox-1.3.4.css', __FILE__));

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

	wp_register_style('utubevideo_style', plugins_url('style.css', __FILE__));
	wp_enqueue_style('utubevideo_style');

}

?>