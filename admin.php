<?php 
/**
 * utvAdmin - Admin section for uTubeVideo Gallery
 *
 * @package uTubeVideo Gallery
 * @author Dustin Scarberry
 *
 * @since 1.3
 */
class utvAdmin
{
	
	private $_options;
		
	public function __construct()
	{
		
		//get plugin options
		$this->_options = get_option('utubevideo_main_opts');
	
		//add hooks
		add_action('admin_init', array(&$this, 'processor'));
		add_action('admin_menu', array(&$this, 'addMenus'));
		add_action('admin_enqueue_scripts', array(&$this, 'addScripts'));
		
	}
		
	public function addMenus()
	{
		
		add_menu_page('uTubeVideo', 'uTubeVideo', 'manage_options', 'utubevideo_settings', array($this, 'option_panel'), plugins_url('utubevideo-gallery/i/utubevideo_icon_16x16.png'));
		add_submenu_page('utubevideo_settings', 'uTubeVideo Galleries', __('Galleries', 'utvg'), 'manage_options', 'utubevideo_settings_galleries', array($this, 'gallery_panel'));
		
	}
		
	public function addScripts()
	{
		
		wp_enqueue_style('utv_style', plugins_url('css/admin_style.css', __FILE__), false, null);
		wp_enqueue_script('jquery');
			
	}
	
	public function gallery_panel()
	{
	
		//declare globals
		global $wpdb;
		
		?>
		
		<div class="wrap" id="utubevideo_main_opts">
		
		<?php screen_icon('utubevideo-gallery'); ?>
			
		<h2 id="utMastHead">uTubeVideo Gallery Settings</h2>
			
		<script>
				
			jQuery(function(){
					
				jQuery('.utConfirm').click(function(){
						
					if(!confirm('<?php _e('Are you sure you want to delete this item?', 'utvg'); ?>'))
						return false;
						
				});
				
				jQuery('div.updated, div.error').delay(3000).queue(function(){jQuery(this).remove();});
					
			});
				
		</script>
			
		<?php	
			
		//display create a gallery form//
		if(isset($_POST['createGallery']))
		{
			
		?>

			<div class="utFormBox utTopformBox">
				<form method="post">  
					<h3><?php _e('Create Gallery', 'utvg'); ?></h3>
					<p>
						<label><?php _e('Gallery Name:', 'utvg'); ?></label>
						<input type="text" name="dsetname"/>
						<span class="utHint"><?php _e('ex: name of gallery for your reference', 'utvg'); ?></span>
					</p>					
					<p class="submit">  
						<input type="submit" name="saveDataset" value="<?php _e('Save New Gallery', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_save_gallery'); ?>
						<a href="?page=utubevideo_settings_galleries" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>							
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
					<h3><?php _e('Create Video Album', 'utvg'); ?></h3>
					<p>
						<label><?php _e('Album Name:', 'utvg'); ?></label>
						<input type="text" name="alname"/>
						<span class="utHint"><?php _e('ex: name of video album', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Video Sorting:', 'utvg'); ?></label>
						<select name="vidSort">
							<option value="desc"><?php _e('Newest First', 'utvg'); ?></option>
							<option value="asc"><?php _e('Oldest First', 'utvg'); ?></option>
						</select>
						<span class="utHint"><?php _e('ex: the order that videos will be displayed', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="submit" name="saveAlbum" value="<?php _e('Save New Album','utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_save_album'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>							
					</p> 
				</form>
			</div>

		<?php
		}
		//display gallery edit form//
		elseif(isset($_POST['editGal']))
		{
		
			$key = sanitize_text_field($_POST['key']);
			$rows = $wpdb->get_results('SELECT DATA_NAME FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $key, ARRAY_A);
				
		?>

			<div class="utFormBox utTopformBox">
				<form method="post">  
					<h3><?php _e('Edit Gallery', 'utvg'); ?></h3>
					<p>
						<label><?php _e('Gallery Name:', 'utvg'); ?></label>
						<input type="text" name="galname" value="<?php echo $rows[0]['DATA_NAME']; ?>"/>
						<span class="utHint"><?php _e('ex: name of gallery', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="saveGalleryEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_gallery'); ?>
						<a href="?page=utubevideo_settings_galleries" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>		
					</p> 
				</form>
			</div>
			
		<?php
			
		}
		//display album edit form//
		elseif(isset($_POST['editAl']))
		{
			
			$key = sanitize_text_field($_POST['key']);
			$dir = wp_upload_dir();
			$dir = $dir['baseurl'];

			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
			$thumbs = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $key, ARRAY_A);
				
		?>
				
			<div class="utFormBox utTopformBox">
				<form method="post">  
					<h3><?php _e('Edit Video Album', 'utvg'); ?></h3>
					<p>
						
						<?php
										
						if($this->_options['useYtThumbs'] != 'yes')
						{
										
						?>
										
						<img src="<?php echo $dir . '/utubevideo-cache/' . $rows[0]['ALB_THUMB'] . '.jpg';?>" class="utPrevThumb"/>
										
						<?php
										
						}
						else
						{
										
						?>
										
						<img src="<?php echo 'http://img.youtube.com/vi/' . $rows[0]['ALB_THUMB']  . '/hqdefault.jpg'; ?>" class="utPrevThumb utYtThumb"/>
										
						<?php
										
						}
										
						?>
						
					</p>
					<p>
						<label><?php _e('Album Name:', 'utvg'); ?></label>
						<input type="text" name="alname" value="<?php echo stripslashes($rows[0]['ALB_NAME']); ?>"/>
						<span class="utHint"><?php _e('ex: name of video album', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Slug:', 'utvg'); ?></label>
						<input type="text" name="slug" value="<?php echo stripslashes($rows[0]['ALB_SLUG']); ?>"/>
						<span class="utHint"><?php _e('ex: slug for video album', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Video Sorting:', 'utvg'); ?></label>
						<select name="vidSort">
							
						<?php
							
						$opts = array(array('text' => __('Newest First', 'utvg'), 'value' => 'desc'), array('text' => __('Oldest First', 'utvg'), 'value' => 'asc'));	
					
						foreach($opts as $value)
						{
							
							if($value['value'] == $rows[0]['ALB_SORT'])
								echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
							else
								echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';

						}
							
						?>
							
						</select>
						<span class="utHint"><?php _e('ex: the order that videos will be displayed', 'utvg'); ?></span>
					</p>						
					<p>
						<label><?php _e('Album Thumbnail:', 'utvg'); ?></label>
						<div id="utThumbSelection">
							
						<?php
							
						if(!empty($thumbs))
						{
							
							foreach($thumbs as $value)
							{
								
							?>
								
								<div>
								
									<?php
										
									if($this->_options['useYtThumbs'] != 'yes')
									{
													
									?>
													
									<img src="<?php echo $dir . '/utubevideo-cache/' . $value['VID_URL'] . '.jpg';?>" class="utPrevThumb"/>
													
									<?php
													
									}
									else
									{
													
									?>
													
									<img src="<?php echo 'http://img.youtube.com/vi/' . $value['VID_URL']  . '/hqdefault.jpg'; ?>" class="utPrevThumb"/>
													
									<?php
													
									}
													
									?>
								
									<input type="radio" name="albumThumbSelect" value="<?php echo $value['VID_URL']; ?>" <?php echo ($rows[0]['ALB_THUMB'] == $value['VID_URL'] ? 'checked' : ''); ?>/>
								</div>
								
							<?php
								
							}
								
						}
						else
							echo '<span class="utAdminError">' . __('Oops, you have not added any videos to this album yet', 'utvg') . '</span>';
								
						?>
						
						</div>
						<span class="utHint"><?php _e('ex: choose the thumbnail for the album', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="hidden" name="prevSlug" value="<?php echo $rows[0]['ALB_SLUG']; ?>"/>
						<input type="submit" name="saveAlbumEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_album'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>			
					</p> 
				</form>
			</div>

		<?php
			
		}
		//display add video form//
		elseif(isset($_POST['addVideo']))
		{
			
			$key = sanitize_text_field($_POST['key']);
			$rows = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
			
		?>
				
			<div class="utFormBox utTopformBox">
				<form method="post">  
					<h3><?php echo __('Add New Video to', 'utvg') . '<span class="utSubH3"> ( ' . $rows[0]['ALB_NAME'] . ' )</span>'; ?></h3>
					<p>
						<label><?php _e('Video URL:', 'utvg'); ?></label>
						<input type="text" name="url"/>
						<span class="utHint"><?php _e('ex: youtube video url', 'utvg'); ?></span>
					</p>		
					<p>
						<label><?php _e('Video Name:', 'utvg'); ?></label>
						<input type="text" name="vidname"/>
						<span class="utHint"><?php _e('ex: the name of the video', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Thumbnail Type:', 'utvg'); ?></label>
						<select name="thumbType"/>
							<option value="rectangle">Rectangle</option>
							<option value="square">Square</option>
						</select>
						<span class="utHint"><?php _e('ex: the type of thumbnail', 'utvg'); ?></span>
					</p>	
					<p>
						<label><?php _e('Video Quality:', 'utvg'); ?></label>
						<select name="videoQuality"/>
							<option value="large">480p</option>
							<option value="hd720">720p</option>
							<option value="hd1080">1080p</option>
						</select>
						<span class="utHint"><?php _e('ex: the starting quality of the video', 'utvg'); ?></span>
					</p>						
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="saveVideo" value="<?php _e('Save New Video', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_save_video'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>			
					</p> 
				</form>
			</div>
				
		<?php

		}
		//display add playlist form//
		elseif(isset($_POST['addPlaylist']))
		{
		
			$key = sanitize_text_field($_POST['key']);
			$rows = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
			
		?>
				
			<div class="utFormBox utTopformBox">
				<form method="post">  
					<h3><?php echo __('Add New Playlist to', 'utvg') . '<span class="utSubH3"> ( ' . $rows[0]['ALB_NAME'] . ' )</span>'; ?></h3>
					<p>
						<label><?php _e('Playlist URL:', 'utvg'); ?></label>
						<input type="text" name="url"/>
						<span class="utHint"><?php _e('ex: youtube playlist url', 'utvg'); ?></span>
					</p>		
					<p>
						<label><?php _e('Thumbnail Type:', 'utvg'); ?></label>
						<select name="thumbType"/>
							<option value="rectangle">Rectangle</option>
							<option value="square">Square</option>
						</select>
						<span class="utHint"><?php _e('ex: the type of thumbnail', 'utvg'); ?></span>
					</p>	
					<p>
						<label><?php _e('Video Quality:', 'utvg'); ?></label>
						<select name="videoQuality"/>
							<option value="large">480p</option>
							<option value="hd720">720p</option>
							<option value="hd1080">1080p</option>
						</select>
						<span class="utHint"><?php _e('ex: the starting quality of the playlist videos', 'utvg'); ?></span>
					</p>						
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="savePlaylist" value="<?php _e('Save New Playlist', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_save_playlist'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>			
					</p> 
				</form>
			</div>
				
		<?php
		}
		//display video edit form//
		elseif(isset($_POST['editVid']))
		{
			
			$key = sanitize_text_field($_POST['key']);
			$dir = wp_upload_dir();
			$dir = $dir['baseurl'];
					
			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
				
		?>
				
			<div class="utFormBox utTopformBox">
				<form method="post">  
					<h3><?php _e('Edit Video', 'utvg'); ?></h3>
					<p>
						
						<?php
										
						if($this->_options['useYtThumbs'] != 'yes')
						{
										
						?>
										
						<img src="<?php echo $dir . '/utubevideo-cache/' . $rows[0]['VID_URL'] . '.jpg';?>" class="utPrevThumb"/>
										
						<?php
										
						}
						else
						{
										
						?>
										
						<img src="<?php echo 'http://img.youtube.com/vi/' . $rows[0]['VID_URL']  . '/hqdefault.jpg'; ?>" class="utPrevThumb utYtThumb"/>
										
						<?php
										
						}
										
						?>
						
					</p>
					<p>
						<label><?php _e('Video Name:', 'utvg'); ?></label>
						<input type="text" name="vidname" value="<?php echo stripslashes($rows[0]['VID_NAME']); ?>"/>
						<span class="utHint"><?php _e('ex: name of video', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Thumbnail Type:', 'utvg'); ?></label>
						<select name="thumbType"/>
							
						<?php
							
						$opts = array(array('text' => __('Rectangle', 'utvg'), 'value' => 'rectangle'), array('text' => __('Square', 'utvg'), 'value' => 'square'));	
					
						foreach($opts as $value)
						{
							
							if($value['value'] == $rows[0]['VID_THUMBTYPE'])
								echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
							else
								echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';

						}
						
						?>
								
						</select>
						<span class="utHint"><?php _e('ex: the type of thumbnail', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Video Quality:', 'utvg'); ?></label>
						<select name="videoQuality"/>
						
						<?php
						
						$opts = array(array('text' => '480p', 'value' => 'large'), array('text' => '720p', 'value' => 'hd720'), array('text' => '1080p', 'value' => 'hd1080'));	
					
						foreach($opts as $value)
						{
							
							if($value['value'] == $rows[0]['VID_QUALITY'])
								echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
							else
								echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';

						}
						
						?>
						
						</select>
						<span class="utHint"><?php _e('ex: the starting quality of the video', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="saveVideoEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_video'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>							
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
				
				$id = $_GET['id'];
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];
							
				$data = $wpdb->get_results('SELECT DATA_NAME, DATA_ALBCOUNT	 FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = "' . $id . '"', ARRAY_A);
		
			?>

				<div class="utFormBox utTopformBox">
					<form method="post" >
						<p class="submit utActionBar">
							<input class="button-secondary" type="submit" name="createAl" value="<?php _e('Create New Album', 'utvg'); ?>"/>
							<a href="?page=utubevideo_settings_galleries" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>
						</p>
					</form>
					<h3><?php _e('Video Albums for gallery', 'utvg'); ?><span class="utSubH3"> ( <?php echo $data[0]['DATA_NAME']; ?> ) - <?php echo $data[0]['DATA_ALBCOUNT'] . ' ' . __('albums', 'utvg'); ?></span></h3>
					<table class="widefat fixed utTable">
						<thead>
							<tr>
								<th class="utThumbCol"><?php _e('Thumbnail', 'utvg'); ?></th>
								<th><?php _e('Name', 'utvg'); ?></th>
								<th><?php _e('Slug', 'utvg'); ?></th>
								<th><?php _e('Date Added', 'utvg'); ?></th>
								<th><?php _e('# Videos', 'utvg'); ?></th>
								<th><?php _e('Actions', 'utvg'); ?></th>
							</tr>
						</thead>
						<tbody>
						
						<?php

						$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $id . ' ORDER BY ALB_ID', ARRAY_A);
					
						if(empty($rows))
						{
							
						?>
						
							<tr>
								<td colspan="6"><?php _e('No albums found', 'utvg'); ?></td>
							</tr>
												
						<?php
						
						}
						else
						{
						
							foreach($rows as $value)
							{
							
							?>
							
							<tr>
								<td>
								
									<?php
										
									if($this->_options['useYtThumbs'] != 'yes')
									{
										
									?>
										
									<img src="<?php echo $dir . '/utubevideo-cache/' . $value['ALB_THUMB'] . '.jpg';?>" class="utPrevThumb"/>
										
									<?php
										
									}
									else
									{
										
									?>
										
									<img src="<?php echo 'http://img.youtube.com/vi/' . $value['ALB_THUMB']  . '/hqdefault.jpg'; ?>" class="utPrevThumb utYtThumb"/>
										
									<?php
										
									}
										
									?>
								
								</td>
								<td><?php echo stripslashes($value['ALB_NAME']); ?></td>
								<td><?php echo $value['ALB_SLUG']; ?></td>
								<td><?php echo date('M j, Y @ g:ia', $value['ALB_UPDATEDATE']); ?></td>
								<td><?php echo $value['ALB_VIDCOUNT']; ?></td>
								<td>
									<form method="post">
										<input class="utLinkButton" type="submit" name="editAl" value="<?php _e('Edit', 'utvg'); ?>"/>
										<input class="utLinkButton utConfirm" type="submit" name="delAl" value="<?php _e('Delete', 'utvg'); ?>"/>
										<input class="utLinkButton" type="submit" name="addVideo" value="<?php _e('Add Video', 'utvg'); ?>"/>	
										<input class="utLinkButton" type="submit" name="addPlaylist" value="<?php _e('Add Playlist', 'utvg'); ?>"/>										
										<input type="hidden" name="key" value="<?php echo $value['ALB_ID']; ?>"/>
										<?php wp_nonce_field('utubevideo_delete_album'); ?>
										<a href="?page=utubevideo_settings_galleries&act=viewal&id=<?php echo $value['ALB_ID']; ?>&prev=<?php echo urlencode('?page=utubevideo_settings_galleries&act=viewdset&id=' . $id); ?>">View</a>
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
								<th><?php _e('Thumbnail', 'utvg'); ?></th>
								<th><?php _e('Name', 'utvg'); ?></th>
								<th><?php _e('Slug', 'utvg'); ?></th>
								<th><?php _e('Date Added', 'utvg'); ?></th>
								<th><?php _e('# Videos', 'utvg'); ?></th>
								<th><?php _e('Actions', 'utvg'); ?></th>
							</tr>
						</tfoot>
					</table>
				</div>

			<?php
			
			}
			//view videos within a video album//
			elseif($_GET['act'] == 'viewal')
			{

				$id = $_GET['id'];
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];
						
				$data = $wpdb->get_results('SELECT ALB_ID, ALB_NAME, ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = "' . $id . '"', ARRAY_A);
			
			?>

				<div class="utFormBox utTopformBox">
					<form method="post">
						<p class="submit utActionBar">
							<input class="button-secondary" type="submit" name="addVideo" value="<?php _e('Add Video', 'utvg'); ?>"/>
							<input class="button-secondary" type="submit" name="addPlaylist" value="<?php _e('Add Playlist', 'utvg'); ?>"/>
							<input type="hidden" name="key" value="<?php echo $data[0]['ALB_ID']; ?>"/>
							<a href="<?php echo (isset($_GET['prev']) ? $_GET['prev'] : '?page=utubevideo_settings_galleries'); ?>" class="utCancel"><?php _e('Go Back', 'utvg'); ?></a>
						</p>
					</form>
					<h3><?php _e('Videos for album', 'utvg'); ?><span class="utSubH3"> ( <?php echo stripslashes($data[0]['ALB_NAME']); ?> ) - <?php echo $data[0]['ALB_VIDCOUNT'] . ' ' . __('videos', 'utvg'); ?></span></h3>
					<table class="widefat fixed utTable">
						<thead>
							<tr>
								<th><?php _e('Thumbnail', 'utvg'); ?></th>
								<th><?php _e('Name', 'utvg'); ?></th>
								<th><?php _e('Date Added', 'utvg'); ?></th>
								<th><?php _e('Actions', 'utvg'); ?></th>
							</tr>
						</thead>
						<tbody>
						
						<?php
						
						$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $id . ' ORDER BY VID_UPDATEDATE', ARRAY_A);
							
						if(empty($rows))
						{
							
						?>
						
							<tr>
								<td colspan="4"><?php _e('No videos found', 'utvg'); ?></td>
							</tr>
							
						<?php
						
						}
						else
						{

							foreach($rows as $value)
							{
							
							?>
							
							<tr>
								<td>
									<a href="http://www.youtube.com/watch?v=<?php echo $value['VID_URL']; ?>" target="_blank" title="Watch On YouTube">
										
										<?php
										
										if($this->_options['useYtThumbs'] != 'yes')
										{
										
										?>
										
										<img src="<?php echo $dir . '/utubevideo-cache/' . $value['VID_URL'] . '.jpg';?>" class="utPrevThumb"/>
										
										<?php
										
										}
										else
										{
										
										?>
										
										<img src="<?php echo 'http://img.youtube.com/vi/' . $value['VID_URL']  . '/hqdefault.jpg'; ?>" class="utPrevThumb utYtThumb"/>
										
										<?php
										
										}
										
										?>
										
									</a>
								</td>
								<td><?php echo stripslashes($value['VID_NAME']); ?></td>
								<td><?php echo date('M j, Y @ g:ia', $value['VID_UPDATEDATE']); ?></td>
								<td>
									<form method="post">
										<input class="utLinkButton" type="submit" name="editVid" value="<?php _e('Edit', 'utvg'); ?>"/>
										<input class="utLinkButton utConfirm" type="submit" name="delVid" value="<?php _e('Delete', 'utvg'); ?>"/>
										<input type="hidden" name="key" value="<?php echo $value['VID_ID']; ?>"/>
										<?php wp_nonce_field('utubevideo_delete_video'); ?>
										<a href="http://www.youtube.com/watch?v=<?php echo $value['VID_URL']; ?>" target="_blank"><?php _e('Watch', 'utvg'); ?></a>
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
								<th><?php _e('Thumbnail', 'utvg'); ?></th>
								<th><?php _e('Name', 'utvg'); ?></th>
								<th><?php _e('Date Added', 'utvg'); ?></th>
								<th><?php _e('Actions', 'utvg'); ?></th>
							</tr>
						</tfoot>
					</table>
				</div>

			<?php
				
			}
				
		}
		//display main options and galleries//
		else
		{

		?>
			
			<div class="utFormBox">
				<h3>Galleries</h3>
				<table class="widefat fixed utTable">
					<thead>
						<tr>
							<th><?php _e('Name', 'utvg'); ?></th>
							<th><?php _e('Shortcode', 'utvg'); ?></th>
							<th><?php _e('Date Added', 'utvg'); ?></th>
							<th><?php _e('# Albums', 'utvg'); ?></th>
							<th><?php _e('Actions', 'utvg'); ?></th>
						</tr>
					</thead>
					<tbody>
							
					<?php
							
					$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_dataset ORDER BY DATA_ID', ARRAY_A);
						
					if(empty($rows))
					{
							
					?>
							
						<tr>
							<td colspan="5"><?php _e('No galleries found', 'utvg'); ?></td>
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
							<td><?php echo $value['DATA_ALBCOUNT']; ?></td>
							<td>
								<form method="post">
									<input class="utLinkButton" type="submit" name="editGal" value="<?php _e('Edit', 'utvg'); ?>"/>
									<input class="utLinkButton utConfirm" type="submit" name="delSet" value="<?php _e('Delete', 'utvg'); ?>"/>
									<a href="?page=utubevideo_settings_galleries&act=viewdset&id=<?php echo $value['DATA_ID']; ?>" class="utBlock"><?php _e('View', 'utvg'); ?></a>
									<input type="hidden" name="key" value="<?php echo $value['DATA_ID']; ?>"/>
									<?php wp_nonce_field('utubevideo_delete_gallery'); ?>
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
							<th><?php _e('Name', 'utvg'); ?></th>
							<th><?php _e('Shortcode', 'utvg'); ?></th>
							<th><?php _e('Date Added', 'utvg'); ?></th>
							<th><?php _e('# Albums', 'utvg'); ?></th>
							<th><?php _e('Actions', 'utvg'); ?></th>
						</tr>
					</tfoot>
				</table>
				<form method="post">
					<p class="submit">
						<input class="button-secondary" type="submit" name="createGallery" value="<?php _e('Create New Gallery', 'utvg'); ?>"/>
					</p>
				</form>
			</div>
			<div class="postbox">
				<h3 class="hndle utPostBox"><span><?php _e("FAQ's", "utvg"); ?></span></h3>
				<div class="inside">
					<div class="utFormBox">
						<ul>
							<li>All videos from youtube may be added into a gallery unless embedding has been disabled for the video on youtube.</li>
							<li>If using a fancybox plugin do not check the 'Include Fancybox Scripts' box, if not using a fancybox plugin do check the box or otherwise the video popup will not work correctly.</li>
							<li>Only YouTube videos can be added to a gallery at this time.</li>
							<li>To create a gallery first click the 'Create New Gallery' button. Once a gallery has been created you can click 'View' in the actions panel to show the video albums within the gallery. Click 'Create New Album' to make a blank video album for the gallery. Once an album has been created it will be given a default missing album art cover. Add videos to the album by clicking 'Add Video' in the actions panel for a video album. Once videos are added to the album you may click 'Edit' on the albums actions pane. To add gallery to a page or post copy and paste the gallery's shortcode onto that page or post.</li>
							<li>You can set the size of the video player by changing the max video player dimensions in the General Settings part of this menu. The video player will automatically retain a 1.77 (16:9) aspect ratio.</li>
							<li>Video Albums can be sorted by either newest or oldest video first.</li>
							<li>Video thumbnails can be either be a square or a rectangle.</li>
							<li>To skip video albums for a gallery add ---- skipalbums="true" ---- to the shortcode.</li>
							<li>Loading thumbnails from Youtube will bypass image upload problems, but will only allow rectangular thumbnails. Square thumbnails will be overrode.</li>
						</ul>
					</div>
				</div>
			</div>	

		<?php
			
		}
			
		?>
			
	</div>
		
	<?php
	
	}
	
	public function option_panel()
	{
	
		//reload options to be sure correct options are displayed
		$this->_options = get_option('utubevideo_main_opts');
		
	?>
		
		<div class="wrap" id="utubevideo_main_opts">
		
		<?php screen_icon('utubevideo-gallery'); ?>
			
		<h2 id="utMastHead">uTubeVideo Settings</h2>
			
		<script>
				
			jQuery(function(){
				
				jQuery('div.updated, div.error').delay(3000).queue(function(){jQuery(this).remove();});
				
				jQuery('#resetWidth').click(function(){
		
					jQuery('#playerWidth').val('950');
					jQuery('#playerHeight').val('537');
					return false;
						
				});
					
				jQuery('#resetOverlayColor').click(function(){
		
					jQuery('#fancyboxOverlayColor').val('#000');
					return false;
						
				});
					
				jQuery('#resetOverlayOpacity').click(function(){
		
					jQuery('#fancyboxOverlayOpacity').val('0.85');
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
				
		<div class="utFormBox utTopformBox" >
			<form method="post">  
				<h3>General Settings</h3>					
				<p>
					<label><?php _e('Include Fancybox Scripts:', 'utvg'); ?></label>
					<input type="checkbox" name="fancyboxInc" <?php echo ($this->_options['fancyboxInc'] == 'yes' ? 'checked' : ''); ?>/>
					<span class="utHint"><?php _e('ex: check only if you need fancybox', 'utvg'); ?></span>
				</p> 
				<p>
					<label><?php _e('Load Thumbnails from Youtube:', 'utvg'); ?></label>
					<input type="checkbox" name="useYtThumbs" <?php echo ($this->_options['useYtThumbs'] == 'yes' ? 'checked' : ''); ?>/>
					<span class="utHint"><?php _e('ex: check ONLY IF thumbnails are not showing for videos', 'utvg'); ?></span>
				</p> 
				<p>
					<label><?php _e('Video Player Controlbar Color:', 'utvg'); ?></label>
					<select name="playerProgressColor">
						
					<?php
						
					$opts = array(array('text' => __('Red', 'utvg'), 'value' => 'red'), array('text' => __('White', 'utvg'), 'value' => 'white'));	
					
					foreach($opts as $value)
					{
							
						if($value['value'] == $this->_options['playerProgressColor'])
							echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
						else
							echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';
					
					}
							
					?>
						
					</select>
					<span class="utHint"><?php _e("ex: set the color of the player's progress bar", "utvg"); ?></span>
				</p> 
				<p>
					<label><?php _e('Max Video Player Dimensions:', 'utvg'); ?></label>
					<input type="text" name="playerWidth" id="playerWidth" value="<?php echo $this->_options['playerWidth']; ?>"/>
					<span> X </span>
					<input type="text" name="playerHeight" id="playerHeight" value="<?php echo $this->_options['playerHeight']; ?>"/>
					<button id="resetWidth" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utHint"><?php _e('ex: max dimensions of video player', 'utvg'); ?></span>
				</p>
				<p>
					<label><?php _e('Fancybox Overlay Color:', 'utvg'); ?></label>
					<input type="text" name="fancyboxOverlayColor" id="fancyboxOverlayColor" value="<?php echo $this->_options['fancyboxOverlayColor']; ?>"/>
					<button id="resetOverlayColor" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utHint"><?php _e('ex: color of fancybox overlay, any hex color', 'utvg'); ?></span>
				</p> 
				<p>
					<label><?php _e('Fancybox Overlay Opacity:', 'utvg'); ?></label>
					<input type="text" name="fancyboxOverlayOpacity" id="fancyboxOverlayOpacity" value="<?php echo $this->_options['fancyboxOverlayOpacity']; ?>"/>
					<button id="resetOverlayOpacity" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utHint"><?php _e('ex: opacity of fancybox overlay [ 0 - 1.0 ]', 'utvg'); ?></span>
				</p> 
				<p>
					
					<?php
						
					global $wp_rewrite;
					$permacheck = '<span class="utOkCode">' . __('Ok', 'utvg') . '</span>';
						
					if(!$wp_rewrite->using_permalinks())
						$permacheck = '<span class="utErrorCode">' . __('Permalinks are not enabled', 'utvg') . '</span>';
					elseif(!in_array('index.php?pagename=$matches[1]&albumid=$matches[2]', $wp_rewrite->wp_rewrite_rules()))
						$permacheck = '<span class="utErrorCode">' . __('Rewrite rules not set', 'utvg') . '</span>';
						
					?>
					
					<label><?php _e('Permalink Status:', 'utvg'); ?></label>
					<?php echo $permacheck; ?>
					<span class="utHint"><?php _e('ex: permalink status check', 'utvg'); ?></span>
				</p>
				<p class="submit">  
					<input type="submit" name="utSaveOpts" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/>  
					<?php wp_nonce_field('utubevideo_update_options'); ?>
				</p> 
			</form>	
		</div>		
	</div>
		
	<?php
		
	}
		
	public function processor()
	{

		if(!empty($_POST))
		{
		
			//declare globals
			global $wpdb;
			
			//save main options script//
			if(isset($_POST['utSaveOpts']))
			{
			
				if(check_admin_referer('utubevideo_update_options'))
				{
				
					$opts['fancyboxInc'] = (isset($_POST['fancyboxInc']) ? 'yes' : 'no');
					$opts['useYtThumbs'] = (isset($_POST['useYtThumbs']) ? 'yes' : 'no');
					$opts['playerProgressColor'] = htmlentities($_POST['playerProgressColor'], ENT_QUOTES);
					$opts['fancyboxOverlayColor'] = (isset($_POST['fancyboxOverlayColor']) ? sanitize_text_field($_POST['fancyboxOverlayColor']) : '#000');
					$opts['fancyboxOverlayOpacity'] = (isset($_POST['fancyboxOverlayOpacity']) ? sanitize_text_field($_POST['fancyboxOverlayOpacity']) : '0.85');
						
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
						echo '<div class="updated"><p>Settings saved</p></div>'; 
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong or there were no changes needed', 'utvg') . '</p></div>';
					
				}
						
			}
			//save new gallery script//
			elseif(isset($_POST['saveDataset']))
			{
			
				if(check_admin_referer('utubevideo_save_gallery'))
				{
				
					$dsetname = htmlentities($_POST['dsetname'], ENT_QUOTES);
					$time = current_time('timestamp');
					
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_dataset', 
						array(
							'DATA_NAME' => $dsetname,
							'DATA_UPDATEDATE' => $time
						)
					))
						echo '<div class="updated"><p>' . __('Gallery created', 'utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}

			}
			//save a gallery edit script//
			elseif(isset($_POST['saveGalleryEdit']))
			{
			
				if(check_admin_referer('utubevideo_edit_gallery'))
				{
				
					$galname = htmlentities($_POST['galname'], ENT_QUOTES);
					$key = sanitize_text_field($_POST['key']);
				
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_dataset', 
						array( 
							'DATA_NAME' => $galname
						), 
						array('DATA_ID' => $key)
					) >= 0)
						echo '<div class="updated"><p>' . __('Gallery updated', 'utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
				
				}
				
			}
			//delete a gallery script//
			elseif(isset($_POST['delSet']))
			{
				if(check_admin_referer('utubevideo_delete_gallery'))
				{
				
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
						echo '<div class="updated"><p>' . __('Gallery deleted','utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}
				
			}
			//save a new album script//
			elseif(isset($_POST['saveAlbum']))
			{
			
				if(check_admin_referer('utubevideo_save_album'))
				{
				
					$key = sanitize_text_field($_GET['id']);
					$alname = htmlentities($_POST['alname'], ENT_QUOTES);	
					$vidsort = ($_POST['vidSort'] == 'desc' ? 'desc' : 'asc');		
					$time = current_time('timestamp');
					
					$rawslugs = $wpdb->get_results('SELECT ALB_SLUG FROM ' . $wpdb->prefix . 'utubevideo_album', ARRAY_N);
					
					foreach($rawslugs as $item)
						$sluglist[] = $item[0];
						
					$mark = 1;
					$slug = strtolower($alname);
					$slug = str_replace(' ', '-', $slug);
					$slug = html_entity_decode($slug, ENT_QUOTES);
					$slug = preg_replace("/[^a-zA-Z0-9-]+/", "", $slug);
					
					if(!empty($sluglist))
					{
					
						$this->checkslug($slug, $sluglist, $mark);
						
					}
					
					//get current album count for gallery//
					$rows = $wpdb->get_results('SELECT DATA_ALBCOUNT FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $key, ARRAY_A);
					$albcnt = $rows[0]['DATA_ALBCOUNT'] + 1;
					
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_album', 
						array(
							'ALB_NAME' => $alname,
							'ALB_SLUG' => $slug,
							'ALB_THUMB' => 'missing',
							'ALB_SORT' => $vidsort,
							'ALB_UPDATEDATE' => $time,
							'DATA_ID' => $key
						)
					) && $wpdb->update(
						$wpdb->prefix . 'utubevideo_dataset', 
						array( 
							'DATA_ALBCOUNT' => $albcnt
						), 
						array('DATA_ID' => $key)
					) >= 0)
						echo '<div class="updated"><p>' . __('Video album created', 'utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
				
				}
				
			}
			//save a new video script//
			elseif(isset($_POST['saveVideo']))
			{
			
				if(check_admin_referer('utubevideo_save_video'))
				{
				
					$url = sanitize_text_field($_POST['url']);
					$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
					$thumbType = htmlentities($_POST['thumbType'], ENT_QUOTES);
					$quality = htmlentities($_POST['videoQuality'], ENT_QUOTES);
					$time = current_time('timestamp');
					$key = sanitize_text_field($_POST['key']);
					
					$dir = wp_upload_dir();
					$dir = $dir['basedir'];		
					
					//get current video count for album//
					$rows = $wpdb->get_results('SELECT ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
					$vidcnt = $rows[0]['ALB_VIDCOUNT'] + 1;
					
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
					
						//insert video and update video count for album//
						if($wpdb->insert(
							$wpdb->prefix . 'utubevideo_video', 
							array(
								'VID_NAME' => $vidname,
								'VID_URL' => $v,
								'VID_THUMBTYPE' => $thumbType,
								'VID_QUALITY' => $quality,
								'VID_UPDATEDATE' => $time,
								'ALB_ID' => $key
							)
						) && $wpdb->update(
							$wpdb->prefix . 'utubevideo_album', 
							array( 
								'ALB_VIDCOUNT' => $vidcnt
							), 
							array('ALB_ID' => $key)
						) >= 0)
							echo '<div class="updated"><p>' . __('Video added to album', 'utvg') . '</p></div>';
						else
							echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';

					}
					else
						echo '<div class="error"><p>' . __('Oops... something seems to be wrong with the Wordpress Image Editor', 'utvg') . '</p></div>';
							
				}			
		
			}
			//save an playlist script//
			elseif(isset($_POST['savePlaylist']))
			{
			
				if(check_admin_referer('utubevideo_save_playlist'))
				{
				
					$url = sanitize_text_field($_POST['url']);
					$thumbType = htmlentities($_POST['thumbType'], ENT_QUOTES);
					$quality = htmlentities($_POST['videoQuality'], ENT_QUOTES);
					$time = current_time('timestamp');
					$key = sanitize_text_field($_POST['key']);
					$count = 0;
					$pos = 51;
					
					$dir = wp_upload_dir();
					$dir = $dir['basedir'];		
					
					//get current video count for album//
					$rows = $wpdb->get_results('SELECT ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
					
					//parse video url to get video id//
					$url = parse_url($url);
					parse_str($url['query']);
					
					$data = wp_remote_get('http://gdata.youtube.com/feeds/api/playlists/' . $list . '?v=2&alt=json&max-results=50');
					
					if($data['response']['code'] == 200)
					{
					
						$data = json_decode($data['body'], true);
						$maxvids = $data['feed']['openSearch$totalResults']['$t'];
						$data = $data['feed']['entry'];
						
						//more requests for data
						while($maxvids >= $pos)
						{
						
							$ndata = wp_remote_get('http://gdata.youtube.com/feeds/api/playlists/' . $list . '?v=2&alt=json&start-index=' . $pos . '&max-results=50');
							$ndata = json_decode($ndata['body'], true);
							$ndata = $ndata['feed']['entry'];
							
							$data = array_merge($data, $ndata);
							$pos = $pos + 50;		
						
						}
						
						foreach($data as $val)
						{
						
							if(!isset($val['app$control']))
							{
						
								$name = $val['media$group']['media$title']['$t'];
								$v = $val['media$group']['yt$videoid']['$t'];
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
									
									$wpdb->insert(
										$wpdb->prefix . 'utubevideo_video', 
										array(
											'VID_NAME' => $name,
											'VID_URL' => $v,
											'VID_THUMBTYPE' => $thumbType,
											'VID_QUALITY' => $quality,
											'VID_UPDATEDATE' => $time,
											'ALB_ID' => $key
										)
									);
									
									$count++;
									
								}	
				
							}
							
						}
						
						$vidcnt = $rows[0]['ALB_VIDCOUNT'] + $count;
			
						if($wpdb->update(
							$wpdb->prefix . 'utubevideo_album', 
							array( 
								'ALB_VIDCOUNT' => $vidcnt
							), 
							array('ALB_ID' => $key)
						) >= 0)
							echo '<div class="updated"><p>' . __('Playlist added to album', 'utvg') . '</p></div>';
						else
							echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
					}
					else
						echo '<div class="error"><p>' . __('Oops... The Youtube Api seems to be down', 'utvg') . '</p></div>';
						
				}
		
			}
			//save an album edit script//
			elseif(isset($_POST['saveAlbumEdit']))
			{
			
				if(check_admin_referer('utubevideo_edit_album'))
				{
				
					$alname = htmlentities($_POST['alname'], ENT_QUOTES);
					$vidsort = ($_POST['vidSort'] == 'desc' ? 'desc' : 'asc');	
					$thumb = (isset($_POST['albumThumbSelect']) ? $_POST['albumThumbSelect'] : 'missing');
					$prevslug = $_POST['prevSlug'];
					$slug = $_POST['slug'];
					$key = sanitize_text_field($_POST['key']);
					
					if($slug != $prevslug)
					{
					
						$rawslugs = $wpdb->get_results('SELECT ALB_SLUG FROM ' . $wpdb->prefix . 'utubevideo_album', ARRAY_N);
						
						foreach($rawslugs as $item)
							$sluglist[] = $item[0];
							
						$mark = 1;
						$slug = strtolower($slug);
						$slug = str_replace(' ', '-', $slug);
						$slug = html_entity_decode($slug, ENT_QUOTES);
						$slug = preg_replace("/[^a-zA-Z0-9-]+/", "", $slug); 
						
						if(!empty($sluglist))
						{
						
							$this->checkslug($slug, $sluglist, $mark);
							
						}
					
					}
					
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_album', 
						array( 
							'ALB_NAME' => $alname,
							'ALB_SLUG' => $slug,
							'ALB_THUMB' => $thumb,
							'ALB_SORT' => $vidsort
						), 
						array('ALB_ID' => $key)
					) >= 0)
						echo '<div class="updated"><p>' . __('Video album updated', 'utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}
			
			}
			//delete an album script//
			elseif(isset($_POST['delAl']))
			{
			
				if(check_admin_referer('utubevideo_delete_album'))
				{
				
					$key = sanitize_text_field($_POST['key']);
					$dir = wp_upload_dir();
					$dir = $dir['basedir'];
					
					//get videos in album to delete//
					$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $key, ARRAY_A);
					
					//get videos in album to delete//
					$galid = $wpdb->get_results('SELECT DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
					$galid = $galid[0]['DATA_ID'];
					
					//get current album count for gallery//
					$rows = $wpdb->get_results('SELECT DATA_ALBCOUNT FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $galid, ARRAY_A);
					$albcnt = $rows[0]['DATA_ALBCOUNT'] - 1;
					
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
					) && $wpdb->update(
						$wpdb->prefix . 'utubevideo_dataset', 
						array( 
							'DATA_ALBCOUNT' => $albcnt
						), 
						array('DATA_ID' => $galid)
					) >= 0)
						echo '<div class="updated"><p>' . __('Video album deleted', 'utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}
				
			}
			//save a video edit script//
			elseif(isset($_POST['saveVideoEdit']))
			{
			
				if(check_admin_referer('utubevideo_edit_video'))
				{
				
					$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
					$thumbType = htmlentities($_POST['thumbType'], ENT_QUOTES);
					$quality = htmlentities($_POST['videoQuality'], ENT_QUOTES);
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
							'VID_THUMBTYPE' => $thumbType,
							'VID_QUALITY' => $quality
						), 
						array('VID_ID' => $key)
					) >= 0)
						echo '<div class="updated"><p>' . __('Video updated', 'utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}
			
			}
			//delete a video script//
			elseif(isset($_POST['delVid']))
			{
				
				if(check_admin_referer('utubevideo_delete_video'))
				{
				
					$key = sanitize_text_field($_POST['key']);
					$dir = wp_upload_dir();
					$dir = $dir['basedir'];
					
					//get thumbnail name for video//
					$data = $wpdb->get_results('SELECT VID_URL, ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
					
					//get current video count for album//
					$rows = $wpdb->get_results('SELECT ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $data[0]['ALB_ID'], ARRAY_A);
					$vidcnt = $rows[0]['ALB_VIDCOUNT'] - 1;
					
					//delete video thumbnail//
					unlink($dir . '/utubevideo-cache/' . $data[0]['VID_URL']  . '.jpg');
					
					if($wpdb->query( 
						$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_video WHERE VID_ID = %d", $key)
					) && $wpdb->update(
						$wpdb->prefix . 'utubevideo_album', 
						array( 
							'ALB_VIDCOUNT' => $vidcnt, 
						), 
						array('ALB_ID' => $data[0]['ALB_ID'])
					) >= 0)
						echo '<div class="updated"><p>' . __('Video deleted', 'utvg') . '</p></div>';
					else
						echo '<div class="error"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}
			
			}
			
		}
	
	}
	
	//recursive function for making sure slugs are unique
	private function checkslug(&$slug, &$sluglist, &$mark)
	{
		
		if(in_array($slug, $sluglist))
		{
					
			$slug = $slug . '-' . $mark;
			$mark++;
			$this->checkslug($slug, $sluglist, $mark);
						
		}
		else
			return;
		
	}
			
}
?>