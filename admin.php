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
		add_action('wp_ajax_utv_videoorderupdate', array(&$this, 'updateVideoOrder'));
		add_action('wp_ajax_utv_albumorderupdate', array(&$this, 'updateAlbumOrder'));
		add_action('wp_ajax_ut_deletevideo', array(&$this, 'deleteVideo'));
		add_action('wp_ajax_ut_deletealbum', array(&$this, 'deleteAlbum'));
		add_action('wp_ajax_ut_deletegallery', array(&$this, 'deleteGallery'));
		add_action('wp_ajax_ut_publishvideo', array(&$this, 'toggleVideoPublish'));
		add_action('wp_ajax_ut_publishalbum', array(&$this, 'toggleAlbumPublish'));
		
	}
		
	public function addMenus()
	{
		
		add_menu_page('uTubeVideo', 'uTubeVideo', 'manage_options', 'utubevideo_settings', array($this, 'option_panel'), plugins_url('utubevideo-gallery/i/utubevideo_icon_16x16.png'));
		add_submenu_page('utubevideo_settings', 'uTubeVideo Galleries', __('Galleries', 'utvg'), 'manage_options', 'utubevideo_settings_galleries', array($this, 'gallery_panel'));
		
	}
		
	public function addScripts()
	{
		
		wp_enqueue_style('utv_style', plugins_url('css/admin_style.min.css', __FILE__), false, null);
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_style('jquery-ui-sortable', plugins_url('css/jquery-ui-1.10.3.custom.min.css', __FILE__), false, null);
			
	}
	
	public function updateVideoOrder()
	{
		global $wpdb;
		$data = explode(',', $_POST['order']);
		
		$cnt = count($data);
		
		for($i = 0; $i < $cnt; $i++)
		{
	
			$wpdb->update(
				$wpdb->prefix . 'utubevideo_video', 
				array( 
					'VID_POS' => $i
				), 
				array('VID_ID' => $data[$i])
			);
						
		}

		die();
	
	}
	
	public function updateAlbumOrder()
	{
		global $wpdb;
		$data = explode(',', $_POST['order']);
		
		$cnt = count($data);
		
		for($i = 0; $i < $cnt; $i++)
		{
	
			$wpdb->update(
				$wpdb->prefix . 'utubevideo_album', 
				array( 
					'ALB_POS' => $i
				), 
				array('ALB_ID' => $data[$i])
			);
						
		}

		die();
	
	}
	
	//delete a video script//
	public function deleteVideo()
	{
	
		check_ajax_referer('ut-delete-video', 'nonce');

		$key = sanitize_text_field($_POST['key']);
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		global $wpdb;
		
		//get thumbnail name for video//
		$data = $wpdb->get_results('SELECT VID_URL, ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
		
		//get current video count for album//
		$rows = $wpdb->get_results('SELECT ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $data[0]['ALB_ID'], ARRAY_A);
		$vidcnt = $rows[0]['ALB_VIDCOUNT'] - 1;
		
		//delete video thumbnail//
		unlink($dir . '/utubevideo-cache/' . $data[0]['VID_URL']  . '.jpg');
		
		//change album thumb to missing if empty
		if($vidcnt == 0){
			$wpdb->update(
				$wpdb->prefix . 'utubevideo_album',
				array(
					'ALB_THUMB' => 'missing'
				),
				array('ALB_ID' => $data[0]['ALB_ID'])
			);
		}
		
		if($wpdb->query( 
			$wpdb->prepare("DELETE FROM " . $wpdb->prefix . "utubevideo_video WHERE VID_ID = %d", $key)
		) && $wpdb->update(
			$wpdb->prefix . 'utubevideo_album', 
			array( 
				'ALB_VIDCOUNT' => $vidcnt, 
			), 
			array('ALB_ID' => $data[0]['ALB_ID'])
		) >= 0)
			echo 'true';
		else
			echo 'false';
			
		die();
				
	}
	
	//delete an album script//
	public function deleteAlbum()
	{
		
		check_ajax_referer('ut-delete-album', 'nonce');		
				
		$key = sanitize_text_field($_POST['key']);
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		global $wpdb;
		
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
			unlink($dir . '/utubevideo-cache/' . $value['VID_URL']  . '.jpg');
				
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
			echo 'true';
		else
			echo 'false';
			
		die();
							
	}
	
	//delete a gallery script//
	public function deleteGallery()
	{
		
		check_ajax_referer('ut-delete-gallery', 'nonce');
		
		$key = sanitize_text_field($_POST['key']);
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		global $wpdb;
		
		//get albums within gallery//
		$rows = $wpdb->get_results('SELECT ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $key, ARRAY_A);	
		
		//for each album get videos and delete thumbnails and references of videos / album//
		foreach($rows as $value)
		{
		
			$data = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $value['ALB_ID'], ARRAY_A);
			
			foreach($data as $nvalue)
				unlink($dir . '/utubevideo-cache/' . $nvalue['VID_URL']  . '.jpg');
			
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
			echo 'true';
		else
			echo 'false';
			
		die();
				
	}
	
	public function toggleVideoPublish()
	{
	
		check_ajax_referer('ut-publish-video', 'nonce');
		
		$key = sanitize_text_field($_POST['key']);
		$changeto = sanitize_text_field($_POST['changeto']);
		
		global $wpdb;
		
		if($wpdb->update(
			$wpdb->prefix . 'utubevideo_video', 
			array( 
				'VID_PUBLISH' => $changeto, 
			), 
			array('VID_ID' => $key)
		) >= 0)
			echo 'true';
		else
			echo 'false';
			
		die();
		
	}
	
	public function toggleAlbumPublish()
	{
	
		check_ajax_referer('ut-publish-album', 'nonce');
		
		$key = sanitize_text_field($_POST['key']);
		$changeto = sanitize_text_field($_POST['changeto']);
		
		global $wpdb;
		
		if($wpdb->update(
			$wpdb->prefix . 'utubevideo_album', 
			array( 
				'ALB_PUBLISH' => $changeto, 
			), 
			array('ALB_ID' => $key)
		) >= 0)
			echo 'true';
		else
			echo 'false';
			
		die();
		
	}

	public function gallery_panel()
	{
	
		//declare globals
		global $wpdb;
		
		?>
		
		<div class="wrap" id="utv-settings">
		
		<?php screen_icon('utubevideo-gallery'); ?>
			
		<h2 id="utv-masthead">uTubeVideo Gallery Settings</h2>
			
		<script>
		
			function utCheckTextField(field)
			{
				if(field.val() == ''){
					field.addClass('utv-invalid-field');
					return true;
				}
				else
					field.removeClass('utv-invalid-field');
			}

			function utCheckForm(form)
			{
			
				var issues = 0;

				form.find('input[type="text"].utv-required').each(function(){
					if(utCheckTextField(jQuery(this)))
						issues++;
				});
				
				if(issues > 0)
					return true;
			}
				
			jQuery(function(){
					
				jQuery('div.updated, div.e-message').delay(3000).queue(function(){jQuery(this).remove();});
					
			});
				
		</script>
			
		<?php	
			
		//display create a gallery form//
		if(isset($_POST['addGalleryForm']))
		{
			
		?>
		
			<script>
			
				jQuery(function(){
			
					jQuery('#createGallery').click(function(){
			
						var form = jQuery(this).parents('form');
						if(utCheckForm(form))
							return false;
						
					});
					
				});
			
			</script>

			<div class="utv-formbox utv-top-formbox">
				<form method="post">  
					<h3><?php _e('Create Gallery', 'utvg'); ?></h3>
					<p>
						<label for="galleryName"><?php _e('Gallery Name:', 'utvg'); ?></label>
						<input type="text" name="galleryName" class="utv-required"/>
						<span class="utv-hint"><?php _e('ex: name of gallery for your reference', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Thumbnail Width:', 'utvg'); ?></label>
						<input type="number" name="thumbWidth" class="utv-required" min="0" max="700" value="150"/>
						<span class="utv-hint"><?php _e('ex: the size of thumbnails in pixels', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Thumbnail Padding:', 'utvg'); ?></label>
						<input type="number" name="thumbPadding" class="utv-required" min="0" max="100" value="10"/>
						<span class="utv-hint"><?php _e('ex: the horizontal space between thumbnails in pixels', 'utvg'); ?></span>
					</p>
					<p>
						<label for="albumSort"><?php _e('Album Sorting:', 'utvg'); ?></label>
						<select name="albumSort">
							<option value="asc"><?php _e('Top to Bottom', 'utvg'); ?></option>
							<option value="desc"><?php _e('Bottom to Top', 'utvg'); ?></option>
						</select>
						<span class="utv-hint"><?php _e('ex: the order that albums will be displayed', 'utvg'); ?></span>
					</p>
					<p>
						<label for="displayType"><?php _e('Display Type:', 'utvg'); ?></label>
						<select name="displayType">
							<option value="album"><?php _e('Albums', 'utvg'); ?></option>
							<option value="video"><?php _e('Just Videos', 'utvg'); ?></option>
						</select>
						<span class="utv-hint"><?php _e('ex: display albums or just videos in gallery', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="submit" id="createGallery" name="createGallery" value="<?php _e('Save New Gallery', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_save_gallery'); ?>
						<a href="?page=utubevideo_settings_galleries" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>							
					</p> 
				</form>
			</div>
			
		<?php
			
		}
		//display gallery edit form//
		elseif(isset($_POST['editGalleryForm']))
		{
		
			$key = sanitize_text_field($_POST['key']);
			$gallery = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $key, ARRAY_A);
				
		?>
		
			<script>
			
				jQuery(function(){
			
					jQuery('#saveGalleryEdit').click(function(){
						
						var form = jQuery(this).parents('form');
						if(utCheckForm(form))
							return false;
						
					});
					
				});
			
			</script>

			<div class="utv-formbox utv-top-formbox">
				<form method="post">  
					<h3><?php _e('Edit Gallery', 'utvg'); ?></h3>
					<p>
						<label><?php _e('Gallery Name:', 'utvg'); ?></label>
						<input type="text" name="galname" class="utv-required" value="<?php echo $gallery[0]['DATA_NAME']; ?>"/>
						<span class="utv-hint"><?php _e('ex: name of gallery', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Thumbnail Width:', 'utvg'); ?></label>
						<input type="number" name="thumbWidth" class="utv-required" value="<?php echo $gallery[0]['DATA_THUMBWIDTH']; ?>" min="0" max="700"/>
						<span class="utv-hint"><?php _e('ex: the size of thumbnails in pixels', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Thumbnail Padding:', 'utvg'); ?></label>
						<input type="number" name="thumbPadding" class="utv-required" value="<?php echo $gallery[0]['DATA_THUMBPADDING']; ?>" min="0" max="100"/>
						<span class="utv-hint"><?php _e('ex: the horizontal space between thumbnails in pixels', 'utvg'); ?></span>
					</p>
					<p>
						<label for="albumSort"><?php _e('Album Sorting:', 'utvg'); ?></label>
						<select name="albumSort">
							
							<?php
							
							$opts = array(array('text' => __('Top to Bottom', 'utvg'), 'value' => 'asc'), array('text' => __('Bottom to Top', 'utvg'), 'value' => 'desc'));	
					
							foreach($opts as $val)
							{
								
								if($val['value'] == $gallery[0]['DATA_SORT'])
									echo '<option value="' . $val['value'] . '" selected>' . $val['text'] . '</option>';
								else
									echo '<option value="' . $val['value'] . '">' . $val['text'] . '</option>';

							}
								
							?>
							
						</select>
						<span class="utv-hint"><?php _e('ex: the order that albums will be displayed', 'utvg'); ?></span>
					</p>
					<p>
						<label for="displayType"><?php _e('Display Type:', 'utvg'); ?></label>
						<select name="displayType">
							
							<?php
							
							$opts = array(array('text' => __('Albums', 'utvg'), 'value' => 'album'), array('text' => __('Just Videos', 'utvg'), 'value' => 'video'));	
					
							foreach($opts as $val)
							{
								
								if($val['value'] == $gallery[0]['DATA_DISPLAYTYPE'])
									echo '<option value="' . $val['value'] . '" selected>' . $val['text'] . '</option>';
								else
									echo '<option value="' . $val['value'] . '">' . $val['text'] . '</option>';

							}
								
							?>
							
						</select>
						<span class="utv-hint"><?php _e('ex: display albums or just videos in gallery', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="hidden" name="oldThumbWidth" value="<?php echo $gallery[0]['DATA_THUMBWIDTH']; ?>"/>
						<input type="submit" id="saveGalleryEdit" name="saveGalleryEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_gallery'); ?>
						<a href="?page=utubevideo_settings_galleries" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>		
					</p> 
				</form>
			</div>
			
		<?php
			
		}
		
		//display create album form//
		elseif(isset($_POST['addAlbumForm']))
		{
		
		?>
		
			<script>
			
				jQuery(function(){
			
					jQuery('#saveAlbum').click(function(){
						
						var form = jQuery(this).parents('form');
						if(utCheckForm(form))
							return false;
						
					});
					
				});
			
			</script>
			
			<div class="utv-formbox utv-top-formbox">
				<form method="post">  
					<h3><?php _e('Create Video Album', 'utvg'); ?></h3>
					<p>
						<label for="alname"><?php _e('Album Name:', 'utvg'); ?></label>
						<input type="text" name="alname" class="utv-required"/>
						<span class="utv-hint"><?php _e('ex: name of video album', 'utvg'); ?></span>
					</p>
					<p>
						<label for="vidsort"><?php _e('Video Sorting:', 'utvg'); ?></label>
						<select name="vidSort">
							<option value="asc"><?php _e('Top to Bottom', 'utvg'); ?></option>
							<option value="desc"><?php _e('Bottom to Top', 'utvg'); ?></option>
						</select>
						<span class="utv-hint"><?php _e('ex: the order that videos will be displayed', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="submit" id="saveAlbum" name="saveAlbum" value="<?php _e('Save New Album','utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_save_album'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>							
					</p> 
				</form>
			</div>

		<?php
		}
		//display album edit form//
		elseif(isset($_POST['editAlbumForm']))
		{
			
			$key = sanitize_text_field($_POST['key']);
			$dir = wp_upload_dir();
			$dir = $dir['baseurl'];

			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
			$thumbs = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $key, ARRAY_A);
				
		?>
		
			<script>
			
				jQuery(function(){
			
					jQuery('#saveAlbumEdit').click(function(){
						
						var form = jQuery(this).parents('form');
						if(utCheckForm(form))
							return false;
						
					});
					
				});
			
			</script>
				
			<div class="utv-formbox utv-top-formbox">
				<form method="post">  
					<h3><?php _e('Edit Video Album', 'utvg'); ?></h3>
					<p>				
						<img src="<?php echo $dir . '/utubevideo-cache/' . $rows[0]['ALB_THUMB'] . '.jpg';?>" class="utv-preview-thumb"/>
					</p>
					<p>
						<label><?php _e('Album Name:', 'utvg'); ?></label>
						<input type="text" name="alname" class="utv-required" value="<?php echo stripslashes($rows[0]['ALB_NAME']); ?>"/>
						<span class="utv-hint"><?php _e('ex: name of album', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Slug:', 'utvg'); ?></label>
						<input type="text" name="slug" class="utv-required" value="<?php echo stripslashes($rows[0]['ALB_SLUG']); ?>"/>
						<span class="utv-hint"><?php _e('ex: permalink slug for album', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Video Sorting:', 'utvg'); ?></label>
						<select name="vidSort">
							
						<?php
							
						$opts = array(array('text' => __('Top to Bottom', 'utvg'), 'value' => 'asc'), array('text' => __('Bottom to Top', 'utvg'), 'value' => 'desc'));	
					
						foreach($opts as $value)
						{
							
							if($value['value'] == $rows[0]['ALB_SORT'])
								echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
							else
								echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';

						}
							
						?>
							
						</select>
						<span class="utv-hint"><?php _e('ex: the order that videos will be displayed', 'utvg'); ?></span>
					</p>						
					<p>
						<label><?php _e('Album Thumbnail:', 'utvg'); ?></label>
						<div id="utv-thumbnail-select">
							
						<?php
							
						if(!empty($thumbs))
						{
							
							foreach($thumbs as $value)
							{
								
							?>
								
								<div>		
									<img src="<?php echo $dir . '/utubevideo-cache/' . $value['VID_URL'] . '.jpg';?>" class="utv-preview-thumb"/>
									<input type="radio" name="albumThumbSelect" value="<?php echo $value['VID_URL']; ?>" <?php echo ($rows[0]['ALB_THUMB'] == $value['VID_URL'] ? 'checked' : ''); ?>/>
								</div>
								
							<?php
								
							}
								
						}
						else
							echo '<span class="utv-admin-error">' . __('Oops, you have not added any videos to this album yet', 'utvg') . '</span>';
								
						?>
						
						</div>
						<span class="utv-hint"><?php _e('ex: choose the thumbnail for the album', 'utvg'); ?></span>
					</p>
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="hidden" name="prevSlug" value="<?php echo $rows[0]['ALB_SLUG']; ?>"/>
						<input type="submit" name="saveAlbumEdit" id="saveAlbumEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_album'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>			
					</p> 
				</form>
			</div>

		<?php
			
		}
		//display add video form//
		elseif(isset($_POST['addVideoForm']))
		{
			
			$key = sanitize_text_field($_POST['key']);
			$rows = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
			
		?>
		
			<script>
			
				jQuery(function(){
			
					jQuery('#addVideo').click(function(){
						
						var form = jQuery(this).parents('form');
						if(utCheckForm(form))
							return false;
						
					});
					
				});
			
			</script>
				
			<div class="utv-formbox utv-top-formbox">
				<form method="post">  
					<h3><?php echo __('Add New Video to', 'utvg') . '<span class="utv-sub-h3"> ( ' . stripslashes($rows[0]['ALB_NAME']) . ' )</span>'; ?></h3>
					<p>
						<label><?php _e('Video URL:', 'utvg'); ?></label>
						<input type="text" name="url" class="utv-required"/>
						<span class="utv-hint"><?php _e('ex: youtube video url', 'utvg'); ?></span>
					</p>		
					<p>
						<label><?php _e('Video Name:', 'utvg'); ?></label>
						<input type="text" name="vidname" class="utv-required"/>
						<span class="utv-hint"><?php _e('ex: the name of the video', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Thumbnail Type:', 'utvg'); ?></label>
						<select name="thumbType"/>
							<option value="rectangle">Rectangle</option>
							<option value="square">Square</option>
						</select>
						<span class="utv-hint"><?php _e('ex: the type of thumbnail', 'utvg'); ?></span>
					</p>	
					<p>
						<label><?php _e('Video Quality:', 'utvg'); ?></label>
						<select name="videoQuality"/>
							<option value="large">480p</option>
							<option value="hd720">720p</option>
							<option value="hd1080">1080p</option>
						</select>
						<span class="utv-hint"><?php _e('ex: the starting quality of the video', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Chromeless Video:', 'utvg'); ?></label>
						<input type="checkbox" name="videoChrome" />
						<span class="utv-hint"><?php _e('ex: hide the playback controls of the video', 'utvg'); ?></span>
					</p>					
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="addVideo" id="addVideo" value="<?php _e('Save New Video', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_add_video'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>			
					</p> 
				</form>
			</div>
				
		<?php

		}
		//display add playlist form//
		elseif(isset($_POST['addPlaylistForm']))
		{
		
			$key = sanitize_text_field($_POST['key']);
			$rows = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
			
		?>
		
			<script>
			
				jQuery(function(){
			
					jQuery('#addPlaylist').click(function(){
						
						var form = jQuery(this).parents('form');
						if(utCheckForm(form))
							return false;
						
					});
					
				});
			
			</script>
				
			<div class="utv-formbox utv-top-formbox">
				<form method="post">  
					<h3><?php echo __('Add New Playlist to', 'utvg') . '<span class="utv-sub-h3"> ( ' . $rows[0]['ALB_NAME'] . ' )</span>'; ?></h3>
					<p>
						<label><?php _e('Playlist URL:', 'utvg'); ?></label>
						<input type="text" name="url" class="utv-required"/>
						<span class="utv-hint"><?php _e('ex: youtube playlist url', 'utvg'); ?></span>
					</p>		
					<p>
						<label><?php _e('Thumbnail Type:', 'utvg'); ?></label>
						<select name="thumbType"/>
							<option value="rectangle">Rectangle</option>
							<option value="square">Square</option>
						</select>
						<span class="utv-hint"><?php _e('ex: the type of thumbnail', 'utvg'); ?></span>
					</p>	
					<p>
						<label><?php _e('Video Quality:', 'utvg'); ?></label>
						<select name="videoQuality"/>
							<option value="large">480p</option>
							<option value="hd720">720p</option>
							<option value="hd1080">1080p</option>
						</select>
						<span class="utv-hint"><?php _e('ex: the starting quality of the playlist videos', 'utvg'); ?></span>
					</p>						
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="addPlaylist" id="addPlaylist" value="<?php _e('Save New Playlist', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_add_playlist'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>			
					</p> 
				</form>
			</div>
				
		<?php
		}
		//display video edit form//
		elseif(isset($_POST['editVideoForm']))
		{
			
			$key = sanitize_text_field($_POST['key']);
			$dir = wp_upload_dir();
			$dir = $dir['baseurl'];
					
			$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
				
		?>
		
			<script>
			
				jQuery(function(){
			
					jQuery('#saveVideoEdit').click(function(){
						
						var form = jQuery(this).parents('form');
						if(utCheckForm(form))
							return false;
						
					});
					
				});
			
			</script>
				
			<div class="utv-formbox utv-top-formbox">
				<form method="post">  
					<h3><?php _e('Edit Video', 'utvg'); ?></h3>
					<p>				
						<img src="<?php echo $dir . '/utubevideo-cache/' . $rows[0]['VID_URL'] . '.jpg';?>" class="utv-preview-thumb"/>
					</p>
					<p>
						<label><?php _e('Video Name:', 'utvg'); ?></label>
						<input type="text" name="vidname" class="utv-required" value="<?php echo stripslashes($rows[0]['VID_NAME']); ?>"/>
						<span class="utv-hint"><?php _e('ex: name of video', 'utvg'); ?></span>
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
						<span class="utv-hint"><?php _e('ex: the type of thumbnail', 'utvg'); ?></span>
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
						<span class="utv-hint"><?php _e('ex: the starting quality of the video', 'utvg'); ?></span>
					</p>
					<p>
						<label><?php _e('Chromeless Video:', 'utvg'); ?></label>
						<input type="checkbox" name="videoChrome"  <?php echo ($rows[0]['VID_CHROME'] == '0' ? 'checked' : ''); ?>/>
						<span class="utv-hint"><?php _e('ex: hide the playback controls of the video', 'utvg'); ?></span>
					</p>	
					<p class="submit">  
						<input type="hidden" name="key" value="<?php echo $key; ?>"/>
						<input type="submit" name="saveVideoEdit" id="saveVideoEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
						<?php wp_nonce_field('utubevideo_edit_video'); ?>
						<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>							
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
							
				$gallery = $wpdb->get_results('SELECT DATA_NAME, DATA_ALBCOUNT	 FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = "' . $id . '"', ARRAY_A);
		
			?>
			
				<script>
				
					jQuery(function(){
					
						jQuery('.utv-sortable-table tbody').sortable({
							placeholder: 'utv-sortable-placeholder',
							handle: '.utv-sortable-handle',
							opacity: .8,
							containment: 'parent',
							stop: function(event, ui){
				
								var ordering = jQuery('.utv-sortable-table tbody').sortable('toArray').toString();

								var data = 
								{
									action: 'utv_albumorderupdate',
									order: ordering
								};

								jQuery.post(ajaxurl, data, function(response) {});
							
							}
						}).disableSelection();
							
						jQuery('.ut-delete-album').click(function(){
							
							if(!confirm('<?php _e('Are you sure you want to delete this album?', 'utvg'); ?>'))
								return false;
							
							var $item = jQuery(this).parents('tr');
							var key = $item.attr('id');
							
							var data = 
							{
								action: 'ut_deletealbum',
								key: key,
								nonce: '<?php echo wp_create_nonce('ut-delete-album'); ?>'
							};

							jQuery.post(ajaxurl, data, function(response) {

								if(response == 'true')
									$item.fadeOut(400, function(){ $item.remove(); });
	
							});
							
							return false;
						});
						
						jQuery('.utv-publish, .utv-unpublish').click(function(){
							
							var key = jQuery(this).parents('tr').attr('id');
							var $item = jQuery(this);
							var changeto = ($item.hasClass('utv-publish') ? '0' : '1');
							
							var data = 
							{
								action: 'ut_publishalbum',
								key: key,
								changeto: changeto,
								nonce: '<?php echo wp_create_nonce('ut-publish-album'); ?>'
							};

							jQuery.post(ajaxurl, data, function(response) {

								if(response == 'true'){
									if($item.hasClass('utv-publish')){
										$item.addClass('utv-unpublish');
										$item.removeClass('utv-publish');
									}else{
										$item.addClass('utv-publish');
										$item.removeClass('utv-unpublish');
									}
								
								}
									
							});
							
							return false;
						});
			
					});
						
				</script>

				<div class="utv-formbox utv-top-formbox">
					<form method="post" >
						<p class="submit utv-actionbar">
							<input class="button-secondary" type="submit" name="addAlbumForm" value="<?php _e('Create New Album', 'utvg'); ?>"/>
							<a href="?page=utubevideo_settings_galleries&act=viewdset&id=<?php echo $id; ?>" class="utv-ok" title="Show order albums will display in"><?php _e('Clear Sorting', 'utvg'); ?></a>
							<a href="?page=utubevideo_settings_galleries" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>
						</p>
					</form>
					<h3><?php _e('Video Albums for gallery', 'utvg'); ?><span class="utv-sub-h3"> ( <?php echo $gallery[0]['DATA_NAME']; ?> ) - <?php echo $gallery[0]['DATA_ALBCOUNT'] . ' ' . __('albums', 'utvg'); ?></span></h3>
					
					<?php
				
					require_once(plugin_dir_path(__FILE__) . 'class/utvAlbumListTable.class.php');
					
					$data = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $id . ' ORDER BY ALB_POS', ARRAY_A);
					
					$cells = array();
					
					foreach($data as $val)
					{
					
						array_push($cells, array(
							'ID' => $val['ALB_ID'],
							'albthumbnail' => '<img src="' . $dir . '/utubevideo-cache/' . $val['ALB_THUMB'] . '.jpg" class="utv-preview-thumb"/><span class="utv-sortable-handle">::</span>',
							'name' => stripslashes($val['ALB_NAME']),
							'published' => $val['ALB_PUBLISH'] == '1' ? '<a href="" class="utv-publish" title="Click to change"/>' : '<a href="" class="utv-unpublish" title="Click to change"/>',
										
							'dateadd' => date('Y/m/d', $val['ALB_UPDATEDATE']),
							'videos' => $val['ALB_VIDCOUNT'],
							'actions' => '<form method="post">
											<input class="utv-link-button" type="submit" name="editAlbumForm" value="' .  __('Edit', 'utvg') . '"/>
											<input class="utv-link-button ut-delete-album" type="submit" name="delAl" value="' . __('Delete', 'utvg') . '"/>
											<input class="utv-link-button" type="submit" name="addVideoForm" value="' . __('Add Video', 'utvg') . '"/>	
											<input class="utv-link-button" type="submit" name="addPlaylistForm" value="' . __('Add Playlist', 'utvg') . '"/>										
											<input type="hidden" name="key" class="ut-key" value="' . $val['ALB_ID'] . '"/>
											<a href="?page=utubevideo_settings_galleries&act=viewal&id=' . $val['ALB_ID'] . '&prev=' . urlencode('?page=utubevideo_settings_galleries&act=viewdset&id=' . $id) . '">View</a>
										</form>'
						));
						
					}
					
					$albums = new utvAlbumListTable($cells);
					$albums->prepare_items(); 
					$albums->display(); 

					?>
					
				</div>

			<?php
			
			}
			//view videos within a video album//
			elseif($_GET['act'] == 'viewal')
			{

				$id = $_GET['id'];
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];
						
				$album = $wpdb->get_results('SELECT ALB_ID, ALB_NAME, ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = "' . $id . '"', ARRAY_A);
			
			?>
			
				<script>
				
					jQuery(function(){
					
						jQuery('.utv-sortable-table tbody').sortable({
							placeholder: 'utv-sortable-placeholder',
							handle: '.utv-sortable-handle',
							opacity: .8,
							containment: 'parent',
							stop: function(event, ui){
				
								var ordering = jQuery('.utv-sortable-table tbody').sortable('toArray').toString();

								var data = 
								{
									action: 'utv_videoorderupdate',
									order: ordering
								};

								jQuery.post(ajaxurl, data, function(response) {});
							
							}
						}).disableSelection();
							
						jQuery('.ut-delete-video').click(function(){
							
							if(!confirm('<?php _e('Are you sure you want to delete this video?', 'utvg'); ?>'))
								return false;
							
							var $item = jQuery(this).parents('tr');
							var key = $item.attr('id');
							
							var data = 
							{
								action: 'ut_deletevideo',
								key: key,
								nonce: '<?php echo wp_create_nonce('ut-delete-video'); ?>'
							};

							jQuery.post(ajaxurl, data, function(response) {

								if(response == 'true')
									$item.fadeOut(400, function(){ $item.remove(); });
									
							});
							
							return false;
						});
						
						jQuery('.utv-publish, .utv-unpublish').click(function(){
							
							var key = jQuery(this).parents('tr').attr('id');
							var $item = jQuery(this);
							var changeto = ($item.hasClass('utv-publish') ? '0' : '1');
							
							var data = 
							{
								action: 'ut_publishvideo',
								key: key,
								changeto: changeto,
								nonce: '<?php echo wp_create_nonce('ut-publish-video'); ?>'
							};

							jQuery.post(ajaxurl, data, function(response) {

								if(response == 'true'){
									if($item.hasClass('utv-publish')){
										$item.addClass('utv-unpublish');
										$item.removeClass('utv-publish');
									}else{
										$item.addClass('utv-publish');
										$item.removeClass('utv-unpublish');
									}
								
								}
									
							});
							
							return false;
						});
						
						
			
					});
						
				</script>

				<div class="utv-formbox utv-top-formbox">
					<form method="post">
						<p class="submit utv-actionbar">
							<input class="button-secondary" type="submit" name="addVideoForm" value="<?php _e('Add Video', 'utvg'); ?>"/>
							<input class="button-secondary" type="submit" name="addPlaylistForm" value="<?php _e('Add Playlist', 'utvg'); ?>"/>
							<input type="hidden" name="key" value="<?php echo $album[0]['ALB_ID']; ?>"/>
							<a href="?page=utubevideo_settings_galleries&act=viewal&id=<?php echo $id; ?>" class="utv-ok" title="Show order videos will display in"><?php _e('Clear Sorting', 'utvg'); ?></a>
							<a href="<?php echo (isset($_GET['prev']) ? $_GET['prev'] : '?page=utubevideo_settings_galleries'); ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>
						</p>
					</form>
					<h3><?php _e('Videos for album', 'utvg'); ?><span class="utv-sub-h3"> ( <?php echo stripslashes($album[0]['ALB_NAME']); ?> ) - <?php echo $album[0]['ALB_VIDCOUNT'] . ' ' . __('videos', 'utvg'); ?></span></h3>
					
					<?php
				
					require_once(plugin_dir_path(__FILE__) . 'class/utvVideoListTable.class.php');
					
					$data = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $id . ' ORDER BY VID_POS', ARRAY_A);
					
					$cells = array();
					
					foreach($data as $val)
					{
					
						array_push($cells, array(
							'ID' => $val['VID_ID'],
							'vidthumbnail' => '<a href="http://www.youtube.com/watch?v=' . $val['VID_URL'] . '" target="_blank" title="Watch On YouTube">
									<img src="' . $dir . '/utubevideo-cache/' . $val['VID_URL'] . '.jpg" class="utv-preview-thumb"/>
								</a>
								<span class="utv-sortable-handle">::</span>',
							'name' => stripslashes($val['VID_NAME']),
							'published' => $val['VID_PUBLISH'] == '1' ? '<a href="" class="utv-publish" title="Click to change"/>' : '<a href="" class="utv-unpublish" title="Click to change"/>',		
							'dateadd' => date('Y/m/d', $val['VID_UPDATEDATE']),
							'actions' => '<form method="post">
									<input class="utv-link-button" type="submit" name="editVideoForm" value="' . __('Edit', 'utvg') . '"/>
									<input class="utv-link-button ut-delete-video" type="submit" name="delVid" value="' . __('Delete', 'utvg') . '"/>
									<input type="hidden" name="key" class="ut-key" value="' . $val['VID_ID'] . '"/>
									<a href="http://www.youtube.com/watch?v=' . $val['VID_URL'] . '" target="_blank">' . __('Watch', 'utvg') . '</a>
								</form>'
						));
						
					}
					
					$videos = new utvVideoListTable($cells);
					$videos->prepare_items(); 
					$videos->display(); 

					?>
			
				</div>

			<?php
				
			}
				
		}
		//display main options and galleries//
		else
		{

		?>
			
			<script>
				
				jQuery(function(){
						
					jQuery('.ut-delete-gallery').click(function(){
						
						if(!confirm('<?php _e('Are you sure you want to delete this gallery?', 'utvg'); ?>'))
							return false;
						
						var $item = jQuery(this).parents('tr');
						var key = $item.attr('id');
						
						var data = 
						{
							action: 'ut_deletegallery',
							key: key,
							nonce: '<?php echo wp_create_nonce('ut-delete-gallery'); ?>'
						};

						jQuery.post(ajaxurl, data, function(response) {

							if(response == 'true')
								$item.fadeOut(400, function(){ $item.remove(); });
								
						});
						
						return false;
					});
		
				});
					
			</script>
				
			<div class="utv-formbox">
				<h3>Galleries</h3>
				
				<?php
				
				require_once(plugin_dir_path(__FILE__) . 'class/utvGalleryListTable.class.php');
				
				$data = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_dataset ORDER BY DATA_ID', ARRAY_A);
				
				$cells = array();
				
				foreach($data as $val)
				{
				
					array_push($cells, array(
						'ID' => $val['DATA_ID'],
						'name' => $val['DATA_NAME'],
						'shortcode' => '[utubevideo id="' . $val['DATA_ID'] . '"]',
						'dateadd' => date('Y/m/d', $val['DATA_UPDATEDATE']),
						'albums' => $val['DATA_ALBCOUNT'],
						'actions' => '<form method="post">
										<input class="utv-link-button" type="submit" name="editGalleryForm" value="' . __('Edit', 'utvg') . '"/>
										<input class="utv-link-button ut-delete-gallery" type="submit" name="delSet" value="' . __('Delete', 'utvg') . '"/>
										<a href="?page=utubevideo_settings_galleries&act=viewdset&id=' . $val['DATA_ID'] . '">' . __('View', 'utvg') . '</a>
										<input type="hidden" name="key" class="ut-key" value="' .  $val['DATA_ID'] . '"/>
									 </form>'
					));
					
				}
				
				$galleries = new utvGalleryListTable($cells);
				$galleries->prepare_items(); 
				$galleries->display(); 

				?>
			
				<form method="post">
					<p class="submit">
						<input class="button-secondary" type="submit" name="addGalleryForm" value="<?php _e('Create New Gallery', 'utvg'); ?>"/>
					</p>
				</form>
			</div>
			<div class="postbox">
				<h3 class="hndle utv-postbox"><span><?php _e("FAQ's", "utvg"); ?></span></h3>
				<div class="inside">
					<div class="utv-formbox">
						<ul>
							<li>For extra help with using uTubeVideo Gallery visit the <a href="http://www.codeclouds.net/utubevideo-gallery-documentation/" target="_blank">documentation page</a>.</li>
							<li>For any additional help or issues you may also contact me at <a href="mailto:dustin@codeclouds.net">dustin@codeclouds.net</a> or <a hef="http://codeclouds.net/contact/">via my website</a>.</li>
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
		
		<div class="wrap" id="utv-settings">
		
		<?php screen_icon('utubevideo-gallery'); ?>
			
		<h2 id="utv-masthead">uTubeVideo Settings</h2>
			
		<script>
				
			jQuery(function(){
				
				jQuery('div.updated, div.e-message').delay(3000).queue(function(){jQuery(this).remove();});
				
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
				
		<div class="utv-formbox utv-top-formbox" >
			<form method="post">  
				<h3>General Settings</h3>					
				<p>
					<label><?php _e('Remove Magnific Popup Scripts:', 'utvg'); ?></label>
					<input type="checkbox" name="skipMagnificPopup" <?php echo ($this->_options['skipMagnificPopup'] == 'yes' ? 'checked' : ''); ?>/>
					<span class="utv-hint"><?php _e('ex: check only if you are already loading the Magnific Popup scripts elsewhere', 'utvg'); ?></span>
				</p> 
				<p>
					<label><?php _e('Do not use permalinks:', 'utvg'); ?></label>
					<input type="checkbox" name="skipSlugs" <?php echo ($this->_options['skipSlugs'] == 'yes' ? 'checked' : ''); ?>/>
					<span class="utv-hint"><?php _e('ex: check to use "?aid=" for album links instead of permalinks', 'utvg'); ?></span>
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
					<span class="utv-hint"><?php _e("ex: color of the player's progress bar", "utvg"); ?></span>
				</p> 
				<p>
					<label><?php _e('Max Video Player Dimensions:', 'utvg'); ?></label>
					<input type="text" name="playerWidth" id="playerWidth" value="<?php echo $this->_options['playerWidth']; ?>"/>
					<span> X </span>
					<input type="text" name="playerHeight" id="playerHeight" value="<?php echo $this->_options['playerHeight']; ?>"/>
					<button id="resetWidth" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utv-hint"><?php _e('ex: max dimensions of video player', 'utvg'); ?></span>
				</p>
				<p>
					<label><?php _e('Overlay Color:', 'utvg'); ?></label>
					<input type="text" name="fancyboxOverlayColor" id="fancyboxOverlayColor" value="<?php echo $this->_options['fancyboxOverlayColor']; ?>"/>
					<button id="resetOverlayColor" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utv-hint"><?php _e('ex: color of lightbox overlay, any hex color', 'utvg'); ?></span>
				</p> 
				<p>
					<label><?php _e('Overlay Opacity:', 'utvg'); ?></label>
					<input type="text" name="fancyboxOverlayOpacity" id="fancyboxOverlayOpacity" value="<?php echo $this->_options['fancyboxOverlayOpacity']; ?>"/>
					<button id="resetOverlayOpacity" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utv-hint"><?php _e('ex: opacity of lightbox overlay [ 0 - 1.0 ]', 'utvg'); ?></span>
				</p> 
				<p>
					
					<?php
						
					global $wp_rewrite;
					$permacheck = '<span class="utv-ok-code">' . __('Ok', 'utvg') . '</span>';
						
					if(!$wp_rewrite->using_permalinks())
						$permacheck = '<span class="utv-error-code">' . __('Permalinks are not enabled, please enable permalinks for site', 'utvg') . '</span>';
					elseif(!in_array('index.php?pagename=$matches[1]&albumid=$matches[2]', $wp_rewrite->wp_rewrite_rules()))
						$permacheck = '<span class="utv-error-code">' . __('Rewrite rules not set, please disable and re-enable plugin to fix', 'utvg') . '</span>';
						
					?>
					
					<label><?php _e('Permalink Status:', 'utvg'); ?></label>
					<?php echo $permacheck; ?>
					<span class="utv-hint"><?php _e('ex: permalink status check', 'utvg'); ?></span>
				</p>
				<p class="submit">  
					<input type="submit" name="utSaveOpts" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
					<input type="submit" value="Fix Permalinks" class="button-secondary" name="resetPermalinks"/>					
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
				
					$opts['skipMagnificPopup'] = (isset($_POST['skipMagnificPopup']) ? 'yes' : 'no');
					$opts['skipSlugs'] = (isset($_POST['skipSlugs']) ? 'yes' : 'no');
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
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong or there were no changes needed', 'utvg') . '</p></div>';
					
				}
						
			}
			//save new gallery script//
			elseif(isset($_POST['createGallery']))
			{
			
				if(check_admin_referer('utubevideo_save_gallery'))
				{
				
					$shortname = htmlentities($_POST['galleryName'], ENT_QUOTES);
					$thumbwidth = htmlentities($_POST['thumbWidth'], ENT_QUOTES);
					$thumbpadding = htmlentities($_POST['thumbPadding'], ENT_QUOTES);
					$albumsort = htmlentities($_POST['albumSort'], ENT_QUOTES);
					$displaytype = htmlentities($_POST['displayType'], ENT_QUOTES);
					$time = current_time('timestamp');
					
					if(empty($shortname) || empty($albumsort) || empty($displaytype) || !isset($thumbwidth) || !isset($thumbpadding))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					if(preg_match("/[^0-9]/", $thumbwidth) || preg_match("/[^0-9]/", $thumbpadding))
					{
						
						echo '<div class="error e-message"><p>' . __('Oops... thumbnail width and padding must contain only numbers.', 'utvg') . '</p></div>';
						return;
						
					}
					
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_dataset', 
						array(
							'DATA_NAME' => $shortname,
							'DATA_THUMBWIDTH' => $thumbwidth,
							'DATA_THUMBPADDING' => $thumbpadding,
							'DATA_SORT' => $albumsort,
							'DATA_DISPLAYTYPE' => $displaytype,
							'DATA_UPDATEDATE' => $time
						)
					))
						echo '<div class="updated"><p>' . __('Gallery created', 'utvg') . '</p></div>';
					else
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}

			}
			//save a gallery edit script//
			elseif(isset($_POST['saveGalleryEdit']))
			{
			
				if(check_admin_referer('utubevideo_edit_gallery'))
				{
				
					$galname = htmlentities($_POST['galname'], ENT_QUOTES);
					$thumbwidth = htmlentities($_POST['thumbWidth'], ENT_QUOTES);
					$thumbpadding = htmlentities($_POST['thumbPadding'], ENT_QUOTES);
					$oldthumbwidth = htmlentities($_POST['oldThumbWidth'], ENT_QUOTES);
					$albumsort = htmlentities($_POST['albumSort'], ENT_QUOTES);
					$displaytype = htmlentities($_POST['displayType'], ENT_QUOTES);
					$key = sanitize_text_field($_POST['key']);
					
					if(empty($galname) || !isset($key) || !isset($thumbwidth) || !isset($thumbpadding) || !isset($oldthumbwidth) || empty($albumsort) || empty($displaytype))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					if(preg_match("/[^0-9]/", $thumbwidth) || preg_match("/[^0-9]/", $thumbpadding))
					{
						
						echo '<div class="error e-message"><p>' . __('Oops... thumbnail width and padding must contain only numbers.', 'utvg') . '</p></div>';
						return;
						
					}
					
					if($thumbwidth != $oldthumbwidth)
					{
					
						$dir = wp_upload_dir();
						$dir = $dir['basedir'];	
					
						$ids = $wpdb->get_results('SELECT ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID = ' . $key, ARRAY_A);
						
						$text = '';
						
						foreach($ids as $val)
							$text .= ',' . $val['ALB_ID'];
						
						$text = substr($text, 1);
						
						$data = $wpdb->get_results('SELECT VID_URL, VID_THUMBTYPE FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID IN (' . $text . ')', ARRAY_A);
					
						foreach($data as $val)
						{
						
						
							$yurl = 'http://img.youtube.com/vi/' . $val['VID_URL'] . '/0.jpg';
					
							//save image for video into cache//
							$image = wp_get_image_editor($yurl);

							$spath = $dir . '/utubevideo-cache/' . $val['VID_URL'] . '.jpg';
								
							if(!is_wp_error($image))
							{
							
								if($val['VID_THUMBTYPE'] == 'square')
									$image->resize($thumbwidth, $thumbwidth, true);
								else
									$image->resize($thumbwidth, $thumbwidth);

								$image->save($spath);
							
							}
							//break for image processing errors//
							else
							{
								
								echo '<div class="error"><p>' . __('Oops... there seems to be a problem updating the video thumbnail(s). Most likely you need to install a PHP image processing library, such as GD or Imagick. Please send the following information to the developer if the problem persists.', 'utvg') . '</p><p><pre>' . print_r($image, true) . '</pre></p></div>';
								return;
									
							}
						}

					}
					
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_dataset', 
						array( 
							'DATA_NAME' => $galname,
							'DATA_THUMBWIDTH' => $thumbwidth,
							'DATA_THUMBPADDING' => $thumbpadding,
							'DATA_SORT' => $albumsort,
							'DATA_DISPLAYTYPE' => $displaytype
						), 
						array('DATA_ID' => $key)
					) >= 0)
						echo '<div class="updated"><p>' . __('Gallery updated', 'utvg') . '</p></div>';
					else
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
				
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

					if(empty($alname) || empty($vidsort) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
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
						$this->checkslug($slug, $sluglist, $mark);

					//get current album count for gallery//
					$gallery = $wpdb->get_results('SELECT DATA_ALBCOUNT FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $key, ARRAY_A);
					$albcnt = $gallery[0]['DATA_ALBCOUNT'] + 1;
					
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
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
				
				}
				
			}
			//save a new video script//
			elseif(isset($_POST['addVideo']))
			{
			
				if(check_admin_referer('utubevideo_add_video'))
				{
						
					$url = sanitize_text_field($_POST['url']);
					$vidname = htmlentities($_POST['vidname'], ENT_QUOTES);
					$thumbType = htmlentities($_POST['thumbType'], ENT_QUOTES);
					$quality = htmlentities($_POST['videoQuality'], ENT_QUOTES);
					$chrome = isset($_POST['videoChrome']) ? 0 : 1;
					$key = sanitize_text_field($_POST['key']);
					
					if(empty($url) || empty($vidname) || empty($thumbType) || empty($quality) || !isset($chrome) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					$dir = wp_upload_dir();
					$dir = $dir['basedir'];		
					$time = current_time('timestamp');
					
					//get current video count for album//
					$album = $wpdb->get_results('SELECT ALB_VIDCOUNT, DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
					$vidcnt = $album[0]['ALB_VIDCOUNT'] + 1;
					
					//get gallery info//
					$gallery = $wpdb->get_results('SELECT DATA_THUMBWIDTH FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $album[0]['DATA_ID'], ARRAY_A);
					$thumbwidth = $gallery[0]['DATA_THUMBWIDTH'];
					
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
							$image->resize($thumbwidth, $thumbwidth, true);
						else
							$image->resize($thumbwidth, $thumbwidth);

						$image->save($spath);
					
					}
					//break for image processing errors//
					else
					{
						
						echo '<div class="error"><p>' . __('Oops... there seems to be a problem saving the thumbnail. Most likely you need to install a PHP image processing library, such as GD or Imagick. Please send the following information to the developer if the problem persists.', 'utvg') . '</p><p><pre>' . print_r($image, true) . '</pre></p></div>';
						return;
							
					}
					
					//insert video and update video count for album//
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_video', 
						array(
							'VID_NAME' => $vidname,
							'VID_URL' => $v,
							'VID_THUMBTYPE' => $thumbType,
							'VID_QUALITY' => $quality,
							'VID_CHROME' => $chrome,
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
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong. Try again.', 'utvg') . '</p></div>';
								
				}			
		
			}
			//save an playlist script//
			elseif(isset($_POST['addPlaylist']))
			{
			
				if(check_admin_referer('utubevideo_add_playlist'))
				{
				
					$url = sanitize_text_field($_POST['url']);
					$thumbType = htmlentities($_POST['thumbType'], ENT_QUOTES);
					$quality = htmlentities($_POST['videoQuality'], ENT_QUOTES);
					$key = sanitize_text_field($_POST['key']);
					
					if(empty($url) || empty($thumbType) || empty($quality) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					$time = current_time('timestamp');
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

								}
								//break for image processing errors//
								else
								{
									
									echo '<div class="error"><p>' . __('Oops... there seems to be a problem saving the thumbnail. Most likely you need to install a PHP image processing library, such as GD or Imagick. Please send the following information to the developer if the problem persists.', 'utvg') . '</p><p><pre>' . print_r($image, true) . '</pre></p></div>';
									return;
										
								}								
								
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
							echo '<div class="error e-message"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
					}
					else
						echo '<div class="error e-message"><p>' . __('Oops... The Youtube Api seems to be down. Try again later.', 'utvg') . '</p></div>';
						
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
					
					if(empty($alname) || empty($vidsort) || empty($thumb) || empty($prevslug) || empty($slug) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					
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
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
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
					$chrome = isset($_POST['videoChrome']) ? 0 : 1;
					$key = sanitize_text_field($_POST['key']);
					
					if(empty($vidname) || empty($thumbType) || empty($quality) || !isset($chrome) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					$dir = wp_upload_dir();
					$dir = $dir['basedir'];
					
					$video = $wpdb->get_results('SELECT VID_URL, ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
					
					$album = $wpdb->get_results('SELECT DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $video[0]['ALB_ID'], ARRAY_A);
					
					$gallery = $wpdb->get_results('SELECT DATA_THUMBWIDTH FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $album[0]['DATA_ID'], ARRAY_A);
					$thumbwidth = $gallery[0]['DATA_THUMBWIDTH'];
					
					$yurl = 'http://img.youtube.com/vi/' . $video[0]['VID_URL'] . '/0.jpg';

					//save image for video into cache//
					$image = wp_get_image_editor($yurl);

					$spath = $dir . '/utubevideo-cache/' . $video[0]['VID_URL'] . '.jpg';
					
					if(!is_wp_error($image))
					{
				
						if($thumbType == 'square')
							$image->resize($thumbwidth, $thumbwidth, true);
						else
							$image->resize($thumbwidth, $thumbwidth);

						$image->save($spath);
					
					}	
					
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_video', 
						array( 
							'VID_NAME' => $vidname, 
							'VID_THUMBTYPE' => $thumbType,
							'VID_QUALITY' => $quality,
							'VID_CHROME' => $chrome
						), 
						array('VID_ID' => $key)
					) >= 0)
						echo '<div class="updated"><p>' . __('Video updated', 'utvg') . '</p></div>';
					else
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';
						
				}
			
			}
			elseif(isset($_POST['resetPermalinks']))
			{
			
				//setup rewrite rule for video albums
				add_rewrite_rule('([^/]+)/album/([^/]+)$', 'index.php?pagename=$matches[1]&albumid=$matches[2]', 'top');
			
				global $wp_rewrite;
				$wp_rewrite->flush_rules(false);
				
				echo '<div class="updated"><p>' . __('Permalinks updated', 'utvg') . '</p></div>';

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