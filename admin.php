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
		add_action('admin_init', array($this, 'processor'));
		add_action('admin_menu', array($this, 'addMenus'));
		add_action('admin_enqueue_scripts', array($this, 'addScripts'));
		add_action('wp_ajax_utv_videoorderupdate', array($this, 'updateVideoOrder'));
		add_action('wp_ajax_utv_albumorderupdate', array($this, 'updateAlbumOrder'));
		add_action('wp_ajax_ut_deletevideo', array($this, 'deleteVideo'));
		add_action('wp_ajax_ut_deletealbum', array($this, 'deleteAlbum'));
		add_action('wp_ajax_ut_deletegallery', array($this, 'deleteGallery'));
		add_action('wp_ajax_ut_publishvideo', array($this, 'toggleVideoPublish'));
		add_action('wp_ajax_ut_publishalbum', array($this, 'toggleAlbumPublish'));
		
	}
		
	public function addMenus()
	{
		
		add_menu_page(__('uTubeVideo Galleries', 'utvg'), 'uTubeVideo', 'edit_pages', 'utubevideo', array($this, 'gallery_panel'), plugins_url('utubevideo-gallery/i/utubevideo_icon_16x16.png'));
		add_submenu_page('utubevideo', 'uTubeVideo Settings', __('Settings', 'utvg'), 'edit_pages', 'utubevideo_settings', array($this, 'option_panel'));
			
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
		
		$key = array(sanitize_key($_POST['key']));
		
		global $wpdb;
		require_once 'class/utvAdminGen.class.php';
			
		$utvAdminGen = new utvAdminGen($this->_options);
		$utvAdminGen->setPath();
				
		if($utvAdminGen->deleteVideos($key, $wpdb))
			echo 1;

		die();
				
	}

	//delete an album script//
	public function deleteAlbum()
	{
		
		check_ajax_referer('ut-delete-album', 'nonce');		
				
		$key = array(sanitize_key($_POST['key']));
		
		global $wpdb;
		require_once 'class/utvAdminGen.class.php';
		
		$utvAdminGen = new utvAdminGen($this->_options);
		$utvAdminGen->setPath();
		
		if($utvAdminGen->deleteAlbums($key, $wpdb))
			echo 1;

		die();
							
	}

	//delete a gallery script//
	public function deleteGallery()
	{
		
		check_ajax_referer('ut-delete-gallery', 'nonce');
		
		$key = array(sanitize_key($_POST['key']));
		
		global $wpdb;
		require_once 'class/utvAdminGen.class.php';
		
		$utvAdminGen = new utvAdminGen($this->_options);
		$utvAdminGen->setPath();
		
		if($utvAdminGen->deleteGalleries($key, $wpdb))
			echo 1;
		else
			echo 0;

		die();
					
	}

	public function toggleVideoPublish()
	{

		check_ajax_referer('ut-publish-video', 'nonce');
		
		$key = array(sanitize_key($_POST['key']));
		$changeTo = sanitize_text_field($_POST['changeTo']);
		
		global $wpdb;
		require_once 'class/utvAdminGen.class.php';
					
		if(utvAdminGen::toggleVideosPublish($key, $changeTo, $wpdb))
			echo 1;

		die();
		
	}

	public function toggleAlbumPublish()
	{

		check_ajax_referer('ut-publish-album', 'nonce');
		
		$key = array(sanitize_key($_POST['key']));
		$changeTo = sanitize_text_field($_POST['changeTo']);
		
		global $wpdb;
		require_once 'class/utvAdminGen.class.php';
			
		if(utvAdminGen::toggleAlbumsPublish($key, $changeTo, $wpdb))
			echo 1;

		die();
		
	}
	
	public function gallery_panel()
	{
	
		//declare globals
		global $wpdb;
		
		?>
		
		<div class="wrap" id="utv-settings">
		
		<?php screen_icon('utubevideo-gallery'); ?>
			
		<h2 id="utv-masthead">uTubeVideo <?php _e('Galleries', 'utvg'); ?></h2>
			
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
					
				jQuery('div.updated, div.e-message').delay(6000).queue(function(){jQuery(this).remove();});
			
			});
				
		</script>
			
		<?php	
		//if view parameter is set//
		if(isset($_GET['view']))
		{
			
			//view video albums in a gallery//
			if($_GET['view'] == 'gallery')
			{
				
				$id = sanitize_key($_GET['id']);
				
				require_once 'class/utvAlbumListTable.class.php';
					
				$albums = new utvAlbumListTable($id);
				$albums->prepare_items(); 
						
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

								if(response)
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
								changeTo: changeto,
								nonce: '<?php echo wp_create_nonce('ut-publish-album'); ?>'
							};

							jQuery.post(ajaxurl, data, function(response) {

								if(response){
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
							<a href="?page=utubevideo&view=albumcreate&id=<?php echo $id; ?>" class="utv-link-submit-button"><?php _e('Create New Album', 'utvg'); ?></a>
							<a href="?page=utubevideo&view=gallery&id=<?php echo $id; ?>" class="utv-ok"><?php _e('Clear Sorting', 'utvg'); ?></a>
							<a href="?page=utubevideo" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>
						</p>
					</form>
					<h3 class="utv-h3"><?php _e('Video Albums for Gallery', 'utvg'); ?></h3>
					<span class="utv-sub-h3"> ( <?php echo $gallery[0]['DATA_NAME']; ?> ) - <span id="utv-album-count"><?php echo $gallery[0]['DATA_ALBCOUNT']; ?></span> <?php _e('albums', 'utvg'); ?></span>
					<form method="post">
					
						<?php $albums->display(); ?>
					
					</form>
				</div>

			<?php
			
			}
			//display create a gallery form//
			elseif($_GET['view'] == 'gallerycreate')
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
							<a href="?page=utubevideo" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>							
						</p> 
					</form>
				</div>
				
			<?php
				
			}
			//display gallery edit form//
			elseif($_GET['view'] == 'galleryedit')
			{
			
				$id = sanitize_key($_GET['id']);
				$gallery = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $id, ARRAY_A);
					
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
							<input type="hidden" name="key" value="<?php echo $id; ?>"/>
							<input type="hidden" name="oldThumbWidth" value="<?php echo $gallery[0]['DATA_THUMBWIDTH']; ?>"/>
							<input type="submit" id="saveGalleryEdit" name="saveGalleryEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
							<?php wp_nonce_field('utubevideo_edit_gallery'); ?>
							<a href="?page=utubevideo" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>		
						</p> 
					</form>
				</div>
				
			<?php
				
			}
			//view videos within a video album//
			elseif($_GET['view'] == 'album')
			{

				$id = sanitize_key($_GET['id']);
				$pid = sanitize_key($_GET['pid']);
				
				require_once 'class/utvVideoListTable.class.php';
					
				$videos = new utvVideoListTable($id);
				$videos->prepare_items(); 
			
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
							var $counter = jQuery('#utv-video-count');
							
							var data = 
							{
								action: 'ut_deletevideo',
								key: key,
								nonce: '<?php echo wp_create_nonce('ut-delete-video'); ?>'
							};

							jQuery.post(ajaxurl, data, function(response) {
							
								if(response){
								
									$item.fadeOut(400, function(){ $item.remove(); });
									$counter.text($counter.text() - 1);
									
								}
									
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
								changeTo: changeto,
								nonce: '<?php echo wp_create_nonce('ut-publish-video'); ?>'
							};

							jQuery.post(ajaxurl, data, function(response) {

								if(response){
								
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
							<a href="?page=utubevideo&view=videoadd&id=<?php echo $id; ?>&pid=<?php echo $pid; ?>" class="utv-link-submit-button"><?php _e('Add Video', 'utvg'); ?></a>
							<a href="?page=utubevideo&view=playlistadd&id=<?php echo $id; ?>&pid=<?php echo $pid; ?>" class="utv-link-submit-button"><?php _e('Add Playlist', 'utvg'); ?></a>						
							<a href="?page=utubevideo&view=album&id=<?php echo $id; ?>" class="utv-ok"><?php _e('Clear Sorting', 'utvg'); ?></a>
							<a href="?page=utubevideo&view=gallery&id=<?php echo $pid; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>
						</p>
					</form>
					<h3 class="utv-h3"><?php _e('Videos for Album', 'utvg'); ?></h3>
					<span class="utv-sub-h3"> ( <?php echo stripslashes($album[0]['ALB_NAME']); ?> ) - <span id="utv-video-count"><?php echo $album[0]['ALB_VIDCOUNT']; ?></span> <?php _e('videos', 'utvg'); ?></span>
					<form method="post">
					
						<?php $videos->display(); ?>
					
					</form>
				</div>

			<?php
				
			}
			//display create album form//
			elseif($_GET['view'] == 'albumcreate')
			{
			
				$id = sanitize_key($_GET['id']);
			
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
							<input type="hidden" name="key" value="<?php echo $id; ?>"/>
							<?php wp_nonce_field('utubevideo_save_album'); ?>
							<a href="?page=utubevideo&view=gallery&id=<?php echo $id; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>							
						</p> 
					</form>
				</div>

			<?php
			}
			//display album edit form//
			elseif($_GET['view'] == 'albumedit')
			{
				
				$id = sanitize_key($_GET['id']);
				$pid = sanitize_key($_GET['pid']);
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];

				$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $id, ARRAY_A);
				$thumbs = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $id, ARRAY_A);
					
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
							<input type="hidden" name="key" value="<?php echo $id; ?>"/>
							<input type="hidden" name="prevSlug" value="<?php echo $rows[0]['ALB_SLUG']; ?>"/>
							<input type="submit" name="saveAlbumEdit" id="saveAlbumEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
							<?php wp_nonce_field('utubevideo_edit_album'); ?>
							<a href="?page=utubevideo&view=gallery&id=<?php echo $pid; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>			
						</p> 
					</form>
				</div>

			<?php
				
			}
			//display add video form//
			elseif($_GET['view'] == 'videoadd')
			{
				
				$id = sanitize_key($_GET['id']);
				$pid = sanitize_key($_GET['pid']);
				$rows = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $id, ARRAY_A);
				
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
						<h3><?php echo __('Add New Video to', 'utvg') . ' ' . '<span class="utv-sub-h3">( ' . stripslashes($rows[0]['ALB_NAME']) . ' )</span>'; ?></h3>
						<p>
							<label><?php _e('Video Source:', 'utvg'); ?></label>
							<select name="videoSource"/>
								<option value="youtube">Youtube</option>
								<option value="vimeo">Vimeo</option>
							</select>
							<span class="utv-hint"><?php _e('ex: the source of the video', 'utvg'); ?></span>
						</p>
						<p>
							<label><?php _e('Video URL:', 'utvg'); ?></label>
							<input type="text" name="url" class="utv-required"/>
							<span class="utv-hint"><?php _e('ex: video url', 'utvg'); ?></span>
						</p>		
						<p>
							<label><?php _e('Video Name:', 'utvg'); ?></label>
							<input type="text" name="vidname" class="utv-required"/>
							<span class="utv-hint"><?php _e('ex: the name for the video', 'utvg'); ?></span>
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
							<span class="utv-hint"><?php _e('ex: the starting quality of the video (applies only to YouTube)', 'utvg'); ?></span>
						</p>						
						<p>
							<label><?php _e('Chromeless Video:', 'utvg'); ?></label>
							<input type="checkbox" name="videoChrome" />
							<span class="utv-hint"><?php _e('ex: hide the playback controls of the video (applies only to YouTube)', 'utvg'); ?></span>
						</p>					
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $id; ?>"/>
							<input type="submit" name="addVideo" id="addVideo" value="<?php _e('Save New Video', 'utvg') ?>" class="button-primary"/> 
							<?php wp_nonce_field('utubevideo_add_video'); ?>
							<a href="?page=utubevideo&view=album&id=<?php echo $id; ?>&pid=<?php echo $pid; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>			
						</p> 
					</form>
				</div>
					
			<?php

			}
			//display add playlist form//
			elseif($_GET['view'] == 'playlistadd')
			{
			
				$id = sanitize_key($_GET['id']);
				$pid = sanitize_key($_GET['pid']);
				$rows = $wpdb->get_results('SELECT ALB_NAME FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $id, ARRAY_A);
				
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
						<h3><?php echo __('Add New Playlist to', 'utvg') . ' ' . '<span class="utv-sub-h3">( ' . stripslashes($rows[0]['ALB_NAME']) . ' )</span>'; ?></h3>
						<p>
							<label><?php _e('Playlist Source:', 'utvg'); ?></label>
							<select name="playlistSource"/>
								<option value="youtube">Youtube</option>
								<option value="vimeo">Vimeo</option>
							</select>
							<span class="utv-hint"><?php _e('ex: the source of the video', 'utvg'); ?></span>
						</p>
						<p>
							<label><?php _e('Playlist URL:', 'utvg'); ?></label>
							<input type="text" name="url" class="utv-required"/>
							<span class="utv-hint"><?php _e('ex: YouTube or Vimeo playlist url', 'utvg'); ?></span>
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
							<span class="utv-hint"><?php _e('ex: the starting quality of the playlist videos (applies only to YouTube)', 'utvg'); ?></span>
						</p>						
						<p>
							<label><?php _e('Chromeless Video:', 'utvg'); ?></label>
							<input type="checkbox" name="videoChrome" />
							<span class="utv-hint"><?php _e('ex: hide the playback controls of the video (applies only to YouTube)', 'utvg'); ?></span>
						</p>						
						<p class="submit">  
							<input type="hidden" name="key" value="<?php echo $id; ?>"/>
							<input type="submit" name="addPlaylist" id="addPlaylist" value="<?php _e('Save New Playlist', 'utvg') ?>" class="button-primary"/> 
							<?php wp_nonce_field('utubevideo_add_playlist'); ?>
							<a href="?page=utubevideo&view=album&id=<?php echo $id; ?>&pid=<?php echo $pid; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>			
						</p> 
					</form>
				</div>
					
			<?php
			}
			//display video edit form//
			elseif($_GET['view'] == 'videoedit')
			{
				
				$id = sanitize_key($_GET['id']);
				$pid = sanitize_key($_GET['pid']);
				$pid = explode('-', $pid);
				
				$dir = wp_upload_dir();
				$dir = $dir['baseurl'];
						
				$rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $id, ARRAY_A);
					
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
							<input type="hidden" name="key" value="<?php echo $id; ?>"/>
							<input type="submit" name="saveVideoEdit" id="saveVideoEdit" value="<?php _e('Save Changes', 'utvg') ?>" class="button-primary"/> 
							<?php wp_nonce_field('utubevideo_edit_video'); ?>
							<a href="?page=utubevideo&view=album&id=<?php echo $pid[0]; ?>&pid=<?php echo $pid[1]; ?>" class="utv-cancel"><?php _e('Go Back', 'utvg'); ?></a>							
						</p> 
					</form>
				</div>

			<?php
				
			}
				
		}
		//display main options and galleries//
		else
		{
		
			require_once 'class/utvGalleryListTable.class.php';
					
			$galleries = new utvGalleryListTable();
			$galleries->prepare_items(); 
				
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

							if(response)
								$item.fadeOut(400, function(){ $item.remove(); });
								
						});
						
						return false;
					});
		
				});
					
			</script>
				
			<div class="utv-formbox">
				<form method="post">
					<p class="submit utv-actionbar">
						<a href="?page=utubevideo&view=gallerycreate" class="utv-link-submit-button"><?php _e('Create New Gallery', 'utvg'); ?></a>
					</p>
				</form>
				<h3>Galleries</h3>
				
				<form method="post">
				
					<?php $galleries->display(); ?>
				
				</form>
			</div>
			<div class="postbox">
				<h3 class="hndle utv-postbox"><span><?php _e('FAQ\'s', 'utvg'); ?></span></h3>
				<div class="inside">
					<div class="utv-formbox">
						<ul>
							<li>For extra help with using uTubeVideo Gallery visit the <a href="http://www.codeclouds.net/utubevideo-gallery-documentation/" target="_blank">documentation page</a>.</li>
							<li>For any additional help or issues you may also contact me at <a href="mailto:dustin@codeclouds.net">dustin@codeclouds.net</a> or <a href="http://codeclouds.net/contact/">via my website</a>.</li>
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
			
		<h2 id="utv-masthead">uTubeVideo <?php _e('Settings', 'utvg'); ?></h2>
			
		<script>
				
			jQuery(function(){
			
				var playerWidth = jQuery('#playerWidth');
				var playerHeight = jQuery('#playerHeight');
				
				jQuery('div.updated, div.e-message').delay(3000).queue(function(){jQuery(this).remove();});

				jQuery('#resetOverlayColor').click(function(){
		
					jQuery('#fancyboxOverlayColor').val('#000');
					return false;
						
				});
					
				jQuery('#resetOverlayOpacity').click(function(){
		
					jQuery('#fancyboxOverlayOpacity').val('0.85');
					return false;
						
				});
				
				jQuery('#resetThumbnailWidth').click(function(){
				
					jQuery('#thumbnailWidth').val('150');
					return false;
					
				});
				
				jQuery('#resetThumbnailPadding').click(function(){
				
					jQuery('#thumbnailPadding').val('10');
					return false;
					
				});
				
				jQuery('#resetThumbnailBorderRadius').click(function(){
				
					jQuery('#thumbnailBorderRadius').val('3');
					return false;
					
				});
				
				jQuery('#resetWidth').click(function(){
		
					playerWidth.val('950');
					playerHeight.val('537');
					return false;
						
				});
						
				playerWidth.keyup(function(){
						
					playerHeight.val(Math.round(playerWidth.val() / 1.77));
						
				});
						
				playerHeight.keyup(function(){
						
					playerWidth.val(Math.round(playerHeight.val() * 1.77));
					
				});
					
			});
				
		</script>
				
		<div class="utv-formbox utv-top-formbox" >
			<form method="post">  
				<h3>General Settings</h3>					
				<p>
					<label><?php _e('Video Player Controls Theme:', 'utvg'); ?></label>
					<select name="playerControlTheme">
						
					<?php
						
					$opts = array(array('text' => __('Dark', 'utvg'), 'value' => 'dark'), array('text' => __('Light', 'utvg'), 'value' => 'light'));	
					
					foreach($opts as $value)
					{
							
						if($value['value'] == $this->_options['playerControlTheme'])
							echo '<option value="' . $value['value'] . '" selected>' . $value['text'] . '</option>';
						else
							echo '<option value="' . $value['value'] . '">' . $value['text'] . '</option>';
					
					}
							
					?>
						
					</select>
					<span class="utv-hint"><?php _e("ex: theme of the player's controls (if shown - YouTube only)", "utvg"); ?></span>
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
					<span class="utv-hint"><?php _e("ex: color of the player's progress bar (YouTube only)", "utvg"); ?></span>
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
					<label><?php _e('Thumbnail Width:', 'utvg'); ?></label>
					<input type="number" name="thumbnailWidth" id="thumbnailWidth" value="<?php echo $this->_options['thumbnailWidth']; ?>"/>
					<button id="resetThumbnailWidth" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utv-hint"><?php _e('ex: width of video thumbnails', 'utvg'); ?></span>
				</p> 
				<p>
					<label><?php _e('Thumbnail Padding:', 'utvg'); ?></label>
					<input type="number" name="thumbnailPadding" id="thumbnailPadding" value="<?php echo $this->_options['thumbnailPadding']; ?>"/>
					<button id="resetThumbnailPadding" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utv-hint"><?php _e('ex: padding for video thumbnails', 'utvg'); ?></span>
				</p>
				<p>
					<label><?php _e('Thumbnail Border Radius:', 'utvg'); ?></label>
					<input type="number" name="thumbnailBorderRadius" id="thumbnailBorderRadius" value="<?php echo $this->_options['thumbnailBorderRadius']; ?>"/>
					<button id="resetThumbnailBorderRadius" class="button-secondary"><?php _e('Reset', 'utvg'); ?></button>
					<span class="utv-hint"><?php _e('ex: roundness of thumbnail corners, set to 0 to disable', 'utvg'); ?></span>
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
					$opts['playerControlTheme'] = sanitize_text_field($_POST['playerControlTheme']);
					$opts['playerProgressColor'] = sanitize_text_field($_POST['playerProgressColor']);
					$opts['fancyboxOverlayColor'] = (isset($_POST['fancyboxOverlayColor']) ? sanitize_text_field($_POST['fancyboxOverlayColor']) : '#000');
					$opts['fancyboxOverlayOpacity'] = (isset($_POST['fancyboxOverlayOpacity']) ? sanitize_text_field($_POST['fancyboxOverlayOpacity']) : '0.85');
					$opts['thumbnailWidth'] = (isset($_POST['thumbnailWidth']) ? sanitize_text_field($_POST['thumbnailWidth']) : '150');
					$opts['thumbnailPadding'] = (isset($_POST['thumbnailPadding']) ? sanitize_text_field($_POST['thumbnailPadding']) : '10');
					$opts['thumbnailBorderRadius'] = (isset($_POST['thumbnailBorderRadius']) ? sanitize_text_field($_POST['thumbnailBorderRadius']) : '3');
						
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
					
					if(preg_match("/[^0-9]/", $opts['thumbnailWidth']) || preg_match("/[^0-9]/", $opts['thumbnailPadding']) || preg_match("/[^0-9]/", $opts['thumbnailBorderRadius']))
					{
						
						echo '<div class="error e-message"><p>' . __('Oops... thumbnail width, padding, and radius must contain only numbers.', 'utvg') . '</p></div>';
						return;
						
					}
					
					if($opts['thumbnailWidth'] != $this->_options['thumbnailWidth'])
					{
					
						require_once 'class/utvAdminGen.class.php';
						$utvAdminGen = new utvAdminGen($opts);
						$utvAdminGen->setPath();
					
						$data = $wpdb->get_results('SELECT VID_URL, VID_SOURCE, VID_THUMBTYPE FROM ' . $wpdb->prefix . 'utubevideo_video', ARRAY_A);
						
						foreach($data as $val)
						{
						
							if($val['VID_SOURCE'] == 'vimeo')
							{
							
								$data = $utvAdminGen->queryAPI('https://vimeo.com/api/v2/video/' . $val['VID_URL'] . '.json');
								$sourceURL = $data[0]['thumbnail_large'];
							
							}
							else
								$sourceURL = 'http://img.youtube.com/vi/' . $val['VID_URL'] . '/0.jpg';
											
							$utvAdminGen->saveThumbnail($sourceURL, $val['VID_URL'], $val['VID_THUMBTYPE'], true);
								
						}
						
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
				
					$shortname = sanitize_text_field($_POST['galleryName']);
					$albumsort = sanitize_text_field($_POST['albumSort']);
					$displaytype = sanitize_text_field($_POST['displayType']);
					$time = current_time('timestamp');
					
					if(empty($shortname) || empty($albumsort) || empty($displaytype))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_dataset', 
						array(
							'DATA_NAME' => $shortname,
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
				
					$galleryName = sanitize_text_field($_POST['galname']);
					$albumSort = sanitize_text_field($_POST['albumSort']);
					$displayType = sanitize_text_field($_POST['displayType']);
					$key = sanitize_key($_POST['key']);
					
					if(empty($galleryName) || !isset($key) || empty($albumSort) || empty($displayType))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_dataset', 
						array( 
							'DATA_NAME' => $galleryName,
							'DATA_SORT' => $albumSort,
							'DATA_DISPLAYTYPE' => $displayType
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
				
					$albumName = sanitize_text_field($_POST['alname']);	
					$videoSort = ($_POST['vidSort'] == 'desc' ? 'desc' : 'asc');
					$key = sanitize_key($_POST['key']);
					$time = current_time('timestamp');
										
					if(empty($albumName) || empty($videoSort) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
							
					require_once 'class/utvAdminGen.class.php';
					$utvAdminGen = new utvAdminGen($this->_options);
					$slug = $utvAdminGen->generateSlug($albumName, $wpdb);
					
					//get current album count for gallery//
					$gallery = $wpdb->get_results('SELECT DATA_ALBCOUNT FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $key, ARRAY_A);
					$albcnt = $gallery[0]['DATA_ALBCOUNT'] + 1;
					
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_album', 
						array(
							'ALB_NAME' => $albumName,
							'ALB_SLUG' => $slug,
							'ALB_THUMB' => 'missing',
							'ALB_SORT' => $videoSort,
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
					$videoName = sanitize_text_field($_POST['vidname']);
					$thumbType = sanitize_text_field($_POST['thumbType']);
					$quality = sanitize_text_field($_POST['videoQuality']);
					$chrome = isset($_POST['videoChrome']) ? 0 : 1;
					$videoSource = sanitize_text_field($_POST['videoSource']);
					$key = sanitize_key($_POST['key']);
					$time = current_time('timestamp');
					
					if(empty($url) || empty($videoName) || empty($thumbType) || empty($quality) || !isset($chrome) || empty($videoSource) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value.', 'utvg') . '</p></div>';
						return;
						
					}
					
					//get current video count for album//
					$album = $wpdb->get_results('SELECT ALB_VIDCOUNT, DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
					$vidcnt = $album[0]['ALB_VIDCOUNT'] + 1;
					
					require_once 'class/utvAdminGen.class.php';
					$utvAdminGen = new utvAdminGen($this->_options);
					$utvAdminGen->setPath();
					
					if(!$vID = $utvAdminGen->parseURL($url, $videoSource, 'video')){
						
						echo '<div class="error e-message"><p>' . __('Invalid URL.', 'utvg') . '</p></div>';
						return;
					
					}
					
					if($videoSource == 'youtube'){
						
						$sourceURL = 'http://img.youtube.com/vi/' . $vID . '/0.jpg';
					
					}elseif($videoSource == 'vimeo'){
					
						$data = $utvAdminGen->queryAPI('https://vimeo.com/api/v2/video/' . $vID . '.json');
						$sourceURL = $data[0]['thumbnail_large'];
	
					}
					
					if(!$utvAdminGen->saveThumbnail($sourceURL, $vID, $thumbType))
						return;
					
					//insert video and update video count for album//
					if($wpdb->insert(
						$wpdb->prefix . 'utubevideo_video', 
						array(
							'VID_SOURCE' => $videoSource,
							'VID_NAME' => $videoName,
							'VID_URL' => $vID,
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
						echo '<div class="updated"><p>' . __('Video added to album.', 'utvg') . '</p></div>';
					else
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong. Try again.', 'utvg') . '</p></div>';
								
				}			
		
			}
			//save an playlist script//
			elseif(isset($_POST['addPlaylist']))
			{
			
				if(check_admin_referer('utubevideo_add_playlist'))
				{
				
					$playlistSource = sanitize_text_field($_POST['playlistSource']);
					$url = sanitize_text_field($_POST['url']);
					$thumbType = sanitize_text_field($_POST['thumbType']);
					$quality = sanitize_text_field($_POST['videoQuality']);
					$chrome = isset($_POST['videoChrome']) ? 0 : 1;
					$key = sanitize_key($_POST['key']);
					$time = current_time('timestamp');					
					
					if(empty($url) || empty($thumbType) || empty($quality) || empty($playlistSource) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					require_once 'class/utvAdminGen.class.php';
					$utvAdminGen = new utvAdminGen($this->_options);
					$utvAdminGen->setPath();
					
					//get current video count for album//
					$album = $wpdb->get_results('SELECT ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $key, ARRAY_A);
					$addedcount = 0;
					
					if($playlistSource == 'youtube')
					{
					
						$pos = 51;
						$blockedVideos = array('private', 'blocked', 'suspended', 'requesterRegion');

						//parse video url to get video id//
						if(!$listID = $utvAdminGen->parseURL($url, $playlistSource, 'playlist')){
						
							echo '<div class="error e-message"><p>' . __('Invalid URL.', 'utvg') . '</p></div>';
							return;
							
						}
						
						if(!$data = $utvAdminGen->queryAPI('http://gdata.youtube.com/feeds/api/playlists/' . $listID . '?v=2&alt=json&max-results=50')) 
							return;
							
						$totalVideos = $data['feed']['openSearch$totalResults']['$t'];
						$data = $data['feed']['entry'];
						
						//more requests for data
						while($totalVideos >= $pos)
						{
						
							if(!$ndata = $utvAdminGen->queryAPI('http://gdata.youtube.com/feeds/api/playlists/' . $listID . '?v=2&alt=json&start-index=' . $pos . '&max-results=50'))
								return;

							$ndata = $ndata['feed']['entry'];
							
							$data = array_merge($data, $ndata);
							$pos = $pos + 50;		
						
						}
						
						foreach($data as $val)
						{
						
							//check to make sure video is not deleted or private
							if(isset($val['app$control']['yt$state']['reasonCode']) && !in_array($val['app$control']['yt$state']['reasonCode'], $blockedVideos) || isset($val['media$group']['yt$duration']['seconds']) && $val['media$group']['yt$duration']['seconds'] > 0)
							{			

								$name = $val['media$group']['media$title']['$t'];
								$v = $val['media$group']['yt$videoid']['$t'];
								$sourceURL = 'http://img.youtube.com/vi/' . $v . '/0.jpg';
							
								$utvAdminGen->saveThumbnail($sourceURL, $v, $thumbType, true);
								
								$wpdb->insert(
									$wpdb->prefix . 'utubevideo_video', 
									array(
										'VID_SOURCE' => $playlistSource,
										'VID_NAME' => $name,
										'VID_URL' => $v,
										'VID_THUMBTYPE' => $thumbType,
										'VID_QUALITY' => $quality,
										'VID_CHROME' => $chrome,
										'VID_UPDATEDATE' => $time,
										'ALB_ID' => $key
									)
								);
								
								$addedcount++;

							}
							
						}
						
					}
					elseif($playlistSource == 'vimeo')
					{
					
						if(!$albumID = $utvAdminGen->parseURL($url, $playlistSource, 'playlist')){
						
							echo '<div class="error e-message"><p>' . __('Invalid URL.', 'utvg') . '</p></div>';
							return;
						
						}
						
						$data = Array();
						
						if(!$albumData = $utvAdminGen->queryAPI('https://vimeo.com/api/v2/album/' . $albumID . '/info.json'))
							return;

						if($albumData['total_videos'] >= 60)
							$pages = 3;
						else
							$pages = ceil($albumData['total_videos'] / 20);
					
						for($i = 1; $i <= $pages; $i++)
						{
						
							if(!$ndata = $utvAdminGen->queryAPI('https://vimeo.com/api/v2/album/' . $albumID . '/videos.json?page=' . $i))
								return;
								
							$data = array_merge($data, $ndata);

						}
							
						foreach($data as $val)
						{
						
							$name = $val['title'];
							$v = $val['id'];
							$sourceURL = $val['thumbnail_large'];
							
							$utvAdminGen->saveThumbnail($sourceURL, $v, $thumbType, true);
								
							$wpdb->insert(
								$wpdb->prefix . 'utubevideo_video', 
								array(
									'VID_SOURCE' => $playlistSource,
									'VID_NAME' => $name,
									'VID_URL' => $v,
									'VID_THUMBTYPE' => $thumbType,
									'VID_QUALITY' => $quality,
									'VID_CHROME' => $chrome,
									'VID_UPDATEDATE' => $time,
									'ALB_ID' => $key
								)
							);
							
							$addedcount++;
							
						}
						
					}
					
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_album', 
						array( 
							'ALB_VIDCOUNT' => $album[0]['ALB_VIDCOUNT'] + $addedcount
						), 
						array('ALB_ID' => $key)
					) >= 0)
						echo '<div class="updated"><p>' . __('Playlist added to album', 'utvg') . '</p></div>';
					else
						echo '<div class="error e-message"><p>' . __('Oops... something went wrong', 'utvg') . '</p></div>';

				}
		
			}
			//save an album edit script//
			elseif(isset($_POST['saveAlbumEdit']))
			{
			
				if(check_admin_referer('utubevideo_edit_album'))
				{
				
					$albumName = sanitize_text_field($_POST['alname']);
					$videoSort = ($_POST['vidSort'] == 'desc' ? 'desc' : 'asc');	
					$thumb = (isset($_POST['albumThumbSelect']) ? $_POST['albumThumbSelect'] : 'missing');
					$prevSlug = sanitize_text_field($_POST['prevSlug']);
					$slug = sanitize_text_field($_POST['slug']);
					$key = sanitize_key($_POST['key']);
					
					if(empty($albumName) || empty($videoSort) || empty($thumb) || empty($prevSlug) || empty($slug) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					if($slug != $prevSlug)
					{
					
						require_once 'class/utvAdminGen.class.php';
						$utvAdminGen = new utvAdminGen($this->_options);
						$slug = $utvAdminGen->generateSlug($albumName, $wpdb);
							
					}
					
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_album', 
						array( 
							'ALB_NAME' => $albumName,
							'ALB_SLUG' => $slug,
							'ALB_THUMB' => $thumb,
							'ALB_SORT' => $videoSort
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
				
					$videoName = sanitize_text_field($_POST['vidname']);
					$thumbType = sanitize_text_field($_POST['thumbType']);
					$quality = sanitize_text_field($_POST['videoQuality']);
					$chrome = isset($_POST['videoChrome']) ? 0 : 1;
					$key = sanitize_key($_POST['key']);
					
					if(empty($videoName) || empty($thumbType) || empty($quality) || !isset($chrome) || !isset($key))
					{
					
						echo '<div class="error e-message"><p>' . __('Oops... all form fields must have a value', 'utvg') . '</p></div>';
						return;
						
					}
					
					$video = $wpdb->get_results('SELECT VID_SOURCE, VID_URL, VID_THUMBTYPE, ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID = ' . $key, ARRAY_A);
					
					$album = $wpdb->get_results('SELECT DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $video[0]['ALB_ID'], ARRAY_A);
					
					if($thumbType != $video[0]['VID_THUMBTYPE'])
					{

						require_once 'class/utvAdminGen.class.php';
						$utvAdminGen = new utvAdminGen($this->_options);
						$utvAdminGen->setPath();
						
						if($video[0]['VID_SOURCE'] == 'youtube'){
					
							$sourceURL = 'http://img.youtube.com/vi/' . $video[0]['VID_URL'] . '/0.jpg';
					
						}elseif($video[0]['VID_SOURCE'] == 'vimeo'){
						
							$data = $utvAdminGen->queryAPI('https://vimeo.com/api/v2/video/' . $video[0]['VID_URL'] . '.json');
							$sourceURL = $data[0]['thumbnail_large'];
		
						}

						if(!$utvAdminGen->saveThumbnail($sourceURL, $video[0]['VID_URL'], $thumbType))
							return;
						
					}
					
					if($wpdb->update(
						$wpdb->prefix . 'utubevideo_video', 
						array( 
							'VID_NAME' => $videoName, 
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
			
}

?>