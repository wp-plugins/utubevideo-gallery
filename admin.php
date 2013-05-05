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
		
		add_options_page('uTubeVideo Settings', 'uTubeVideo', 'manage_options', 'utubevideo_settings', array($this, 'panels'));
		
	}
		
	public function addScripts()
	{
		
		wp_enqueue_style('utv_style', plugins_url('css/admin_style.css', __FILE__), false, null);
		wp_enqueue_script('jquery');
			
	}
	
	public function panels()
	{
	
		//declare globals
		global $wpdb;
		//reload options to be sure correct options are displayed
		$this->_options = get_option('utubevideo_main_opts');
		
	?>
		
		<div class="wrap" id="utubevideo_main_opts">
		
		<?php screen_icon('utubevideo-gallery'); ?>
			
		<h2 id="utMastHead">uTubeVideo Settings</h2>
			
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
						<?php wp_nonce_field('utubevideo_save_gallery'); ?>
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
						<?php wp_nonce_field('utubevideo_save_album'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utCancel">Go Back</a>							
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
					<h3><?php _e('Edit Gallery'); ?></h3>
					<p>
						<label><?php _e('Gallery Name: '); ?></label>
						<input type="text" name="galname" value="<?php echo $rows[0]['DATA_NAME']; ?>"/>
						<span class="utHint"><?php _e(' ex: name of gallery'); ?></span>
					</p>
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="saveGalleryEdit" value="<?php _e('Save Changes') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_gallery'); ?>
						<a href="?page=utubevideo_settings" class="utCancel">Go Back</a>		
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
						<label><?php _e('Album Thumbnail: '); ?></label>
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
						<?php wp_nonce_field('utubevideo_edit_album'); ?>
						<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>			
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
					<h3><?php echo _('Add New Video to') . '<span class="utSubH3"> ( ' . $rows[0]['ALB_NAME'] . ' )</span>'; ?></h3>
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
					<p>
						<label><?php _e('Video Quality: '); ?></label>
						<select name="videoQuality"/>
							<option value="large">480p</option>
							<option value="hd720">720p</option>
							<option value="hd1080">1080p</option>
						</select>
						<span class="utHint"><?php _e(' ex: the starting quality of the video'); ?></span>
					</p>						
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="saveVideo" value="<?php _e('Save New Video') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_save_video'); ?>
						<a href="<?php echo $_POST['prev']; ?>" class="utCancel">Go Back</a>			
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
					<p>
						<label><?php _e('Video Quality: '); ?></label>
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
						<span class="utHint"><?php _e(' ex: the starting quality of the video'); ?></span>
					</p>
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="hidden" name="prev" value="<?php echo $_POST['prev']; ?>"/>
						<input type="submit" name="saveVideoEdit" value="<?php _e('Save Changes') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_video'); ?>
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
				
				$id = $_GET['id'];
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];
							
				$data = $wpdb->get_results('SELECT DATA_NAME, DATA_ALBCOUNT	 FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = "' . $id . '"', ARRAY_A);
		
			?>

				<div class="utFormBox utTopformBox">
					<form method="post" >
						<p class="submit utActionBar">
							<input class="button-secondary" type="submit" name="createAl" value="Create New Album"/>
							<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewdset&id=<?php echo $id; ?>"/>
							<a href="?page=utubevideo_settings" class="utCancel">Go Back</a>
						</p>
					</form>
					<h3>Video Albums for<span class="utSubH3"> ( <?php echo $data[0]['DATA_NAME']; ?> ) - <?php echo $data[0]['DATA_ALBCOUNT']; ?> albums</span></h3>
					<table class="widefat fixed utTable">
						<thead>
							<tr>
								<th class="utThumbCol">Thumbnail</th>
								<th>Name</th>
								<th>Date Added</th>
								<th># Videos</th>
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
								<td colspan="5">No albums found</td>
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
								<td><?php echo stripslashes($value['ALB_NAME']); ?></td>
								<td><?php echo date('M j, Y @ g:ia', $value['ALB_UPDATEDATE']); ?></td>
								<td><?php echo $value['ALB_VIDCOUNT']; ?></td>
								<td>
									<form method="post">
										<input class="utLinkButton" type="submit" name="editAl" value="Edit"/>
										<input class="utLinkButton utConfirm" type="submit" name="delAl" value="Delete"/>
										<input class="utLinkButton" type="submit" name="addVideo" value="Add Video"/>							
										<input type="hidden" name="key" value="<?php echo $value['ALB_ID']; ?>"/>
										<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewdset&id=<?php echo $id; ?>"/>
										<?php wp_nonce_field('utubevideo_delete_album'); ?>
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
								<th>Thumbnail</th>
								<th>Name</th>
								<th>Date Added</th>
								<th># Videos</th>
								<th>Actions</th>
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
							<input class="button-secondary" type="submit" name="addVideo" value="Add Video"/>
							<input type="hidden" name="key" value="<?php echo $data[0]['ALB_ID']; ?>"/>
							<a href="<?php echo (isset($_GET['prev']) ? $_GET['prev'] : $_POST['prev']); ?>" class="utCancel">Go Back</a>
						</p>
					</form>
					<h3>Videos for<span class="utSubH3"> ( <?php echo stripslashes($data[0]['ALB_NAME']); ?> ) - <?php echo $data[0]['ALB_VIDCOUNT']; ?> videos</span></h3>
					<table class="widefat fixed utTable">
						<thead>
							<tr>
								<th>Thumbnail</th>
								<th>Name</th>
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
								<td colspan="4">No videos found</td>
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
										<img src="<?php echo $dir . '/utubevideo-cache/' . $value['VID_URL'] . '.jpg';?>" class="utPrevThumb"/>
									</a>
								</td>
								<td><?php echo stripslashes($value['VID_NAME']); ?></td>
								<td><?php echo date('M j, Y @ g:ia', $value['VID_UPDATEDATE']); ?></td>
								<td>
									<form method="post">
										<input class="utLinkButton" type="submit" name="editVid" value="Edit"/>
										<input class="utLinkButton utConfirm" type="submit" name="delVid" value="Delete"/>
										<input type="hidden" name="key" value="<?php echo $value['VID_ID']; ?>"/>
										<input type="hidden" name="prev" value="?page=utubevideo_settings&act=viewal&id=<?php echo $id; ?>"/>
										<?php wp_nonce_field('utubevideo_delete_video'); ?>
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
								<th>Thumbnail</th>
								<th>Name</th>
								<th>Date Added</th>
								<th>Actions</th>
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
				
			<div class="utFormBox utTopformBox" >
				<form method="post">  
					<h3>General Settings</h3>					
					<p>
						<label><?php _e('Include Fancybox Scripts: '); ?></label>
						<input type="checkbox" name="fancyboxInc" <?php echo ($this->_options['fancyboxInc'] == 'yes' ? 'checked' : ''); ?>/>
						<span class="utHint"><?php _e('ex: check only if not using a fancybox plugin'); ?></span>
					</p> 
					<p>
						<label><?php _e('Max Video Player Dimensions: '); ?></label>
						<input type="text" name="playerWidth" id="playerWidth" value="<?php echo $this->_options['playerWidth']; ?>"/>
						<span> X </span>
						<input type="text" name="playerHeight" id="playerHeight" value="<?php echo $this->_options['playerHeight']; ?>"/>
						<button id="resetWidth" class="button-secondary">Reset</button>
						<span class="utHint"><?php _e('ex: max dimensions of video player'); ?></span>
					</p>
					<p>
						<label><?php _e('Video Player Controlbar Color: '); ?></label>
						<select name="playerProgressColor">
						
						<?php
						
						$opts = array(array('text' => 'Red', 'value' => 'red'), array('text' => 'White', 'value' => 'white'));	
					
						foreach($opts as $value)
						{
							
							if($value['value'] == $this->_options['playerProgressColor'])
								echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
							else
								echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';

						}
							
						?>
						
						</select>
						<span class="utHint"><?php _e("ex: set the color of the player's progress bar"); ?></span>
					</p> 
					<p class="submit">  
						<input type="submit" name="utSaveOpts" value="<?php _e('Save Changes') ?>" class="button-primary"/>  
						<?php wp_nonce_field('utubevideo_update_options'); ?>
					</p> 
				</form>	
			</div>
			<div class="utFormBox">
				<h3>Galleries</h3>
				<table class="widefat fixed utTable">
					<thead>
						<tr>
							<th>Name</th>
							<th>Shortcode</th>
							<th>Date Added</th>
							<th># Albums</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
							
					<?php
							
					$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_dataset ORDER BY DATA_ID', ARRAY_A);
						
					if(empty($rows))
					{
							
					?>
							
						<tr>
							<td colspan="6">No galleries found</td>
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
									<input class="utLinkButton" type="submit" name="editGal" value="Edit"/>
									<input class="utLinkButton utConfirm" type="submit" name="delSet" value="Delete"/>
									<a href="?page=utubevideo_settings&act=viewdset&id=<?php echo $value['DATA_ID']; ?>" class="utBlock">View</a>
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
							<th>Name</th>
							<th>Shortcode</th>
							<th>Date Added</th>
							<th># Albums</th>
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
			<div class="postbox">
				<h3 class="hndle utPostBox"><span><?php _e("FAQ's"); ?></span></h3>
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
							<li>To skip video albums for a gallery add --- skipalbums="true" ---- to the shortcode.</li>
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
					$opts['playerProgressColor'] = htmlentities($_POST['playerProgressColor'], ENT_QUOTES);
						
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
						echo '<div class="error"><p>Oops... something went wrong or there were no changes needed</p></div>';
					
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
						echo '<div class="updated"><p>Gallery created</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
						
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
						echo '<div class="updated"><p>Gallery updated</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
				
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
						echo '<div class="updated"><p>Gallery deleted</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
						
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
					
					//get current album count for gallery//
					$rows = $wpdb->get_results('SELECT DATA_ALBCOUNT FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $key, ARRAY_A);
					$albcnt = $rows[0]['DATA_ALBCOUNT'] + 1;
					
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_album', 
						array(
							'ALB_NAME' => $alname,
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
						echo '<div class="updated"><p>Video album created</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
				
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
					
					}	
					
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
						echo '<div class="updated"><p>Video added to album</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
						
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
						echo '<div class="updated"><p>Video album updated</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
						
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
						echo '<div class="updated"><p>Video album deleted</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
						
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
						echo '<div class="updated"><p>Video updated</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
						
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
						echo '<div class="updated"><p>Video deleted</p></div>';
					else
						echo '<div class="error"><p>Oops... something went wrong</p></div>';
						
				}
			
			}
			
		}
	
	}
			
}
?>