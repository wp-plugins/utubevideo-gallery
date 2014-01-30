<?php 

class utvVideoGen
{

	private $_validAlbum = false;
	private $_aid, $_dir, $_atts, $_options, $_gallery, $_content = '';

	public function __construct($atts, &$options, $type = null, $albumId = null)
	{
	
		global $wpdb;
	
		//set atts array
		$this->_atts = shortcode_atts(array(
			'id' => null,
			'align' => 'left'
		), $atts, 'utubevideo');
		
		$this->_gallery = $wpdb->get_results('SELECT DATA_THUMBWIDTH, DATA_THUMBPADDING, DATA_SORT, DATA_DISPLAYTYPE FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = "' . $this->_atts['id'] . '"', ARRAY_A);
		
		//set thumbnail cache folder location
		$temp = wp_upload_dir();
		$this->_dir = $temp['baseurl'];
		$this->_options = $options;
		$albumId = sanitize_text_field($albumId);
		
		//check for valid album id
		if($type == 'permalink' && $albumId != null)
		{
		
			$meta = $wpdb->get_results('SELECT ALB_ID, DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_SLUG = "' . $albumId . '"', ARRAY_A);
		
			$this->_aid = $meta[0]['ALB_ID'];
		
			if($meta[0]['DATA_ID'] == $this->_atts['id'] && $this->_gallery[0]['DATA_DISPLAYTYPE'] == 'album')
				$this->_validAlbum = true;
				
		}
		elseif($type == 'query' && $albumId != null)
		{
		
			$args = explode('-', $albumId);
			
			//if valid aid token
			if(count($args) == 2)
			{
			
				$this->_aid = $args[0];
				$check = $args[1];
				
				if($check == $this->_atts['id'] && $this->_gallery[0]['DATA_DISPLAYTYPE'] == 'album')
					$this->_validAlbum = true;
			
			}
			
		}

	}
	
	public function printGallery()
	{
	
		global $wpdb;
		$this->printOpeningTags();
		
		if($this->_validAlbum)
		{
		
			//get name of video album
			$meta = $wpdb->get_results('SELECT ALB_NAME, ALB_SORT, ALB_PUBLISH FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $this->_aid, ARRAY_A);
			
			echo $meta[0]['ALB_SORT'];
		
			//get videos in album
			if($meta != null && $meta[0]['ALB_PUBLISH'] == 1)
				$data = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID = ' . $this->_aid . ' && VID_PUBLISH = 1 ORDER BY VID_POS ' . $meta[0]['ALB_SORT'], ARRAY_A);
			
			global $post;
			
			//if there are videos in the video album
			if(!empty($data))
			{
			
				//create html for breadcrumbs
				$this->_content .= '<div class="utv-breadcrumb"><a href="' . get_permalink($post->ID) . '">' . __('Albums', 'utvg') . '</a><span class="utv-albumcrumb"> | ' . stripslashes($meta[0]['ALB_NAME']) . '</span></div>';
			
				$this->printOpeningContainer();
			
				//create html for each video
				foreach($data as $val)
					$this->_content .= $this->printVideo($val);
					
				$this->printClosingContainer();
				
			}
			//if the video album is empty
			else
			{
			
				$this->_content .= '<div class="utv-breadcrumb"><a href="' . get_permalink($post->ID) . '">' . __('Go Back', 'utvg') . '</a></div>';
				
				$this->_content .= '<p>' . __('Sorry... there appear to be no videos for this album yet.', 'utvg') . '</p>';
				
			}
		
		}
		else
		{
		
			//get video albums in the gallery
			$data = $wpdb->get_results('SELECT ' . $wpdb->prefix . 'utubevideo_album.ALB_ID, ALB_SLUG, ALB_NAME, ALB_THUMB, VID_THUMBTYPE FROM ' . $wpdb->prefix . 'utubevideo_album LEFT JOIN ' . $wpdb->prefix . 'utubevideo_video ON ALB_THUMB = VID_URL WHERE DATA_ID = ' . $this->_atts['id'] . ' && ALB_PUBLISH = 1 ORDER BY ' . $wpdb->prefix . 'utubevideo_album.ALB_POS ' . $this->_gallery[0]['DATA_SORT'], ARRAY_A);
			
			//if there are video albums in the gallery
			if(!empty($data))
			{
		
				//if skipalbums in set to true
				if($this->_gallery[0]['DATA_DISPLAYTYPE'] == 'video')
				{
				
					$this->printOpeningContainer();

					//build array of album ids
					foreach($data as $idval)
						$alids[] = $idval['ALB_ID'];
			
					//implode ids to string//
					$alids = implode(', ', $alids);
					//get video info for each all albums in gallery//
					$vids = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID IN ('  . $alids . ') && VID_PUBLISH = 1 ORDER BY VID_POS', ARRAY_A);

					//create html for all videos in gallery
					foreach($vids as $val)
						$this->_content .= $this->printVideo($val);
						
				}
				//if display type is album
				else
				{
				
					//create html for breadcrumbs
					$this->_content .= '<div class="utv-breadcrumb"><span class="utv-albumcrumb">' . __('Albums', 'utvg') . '</span></div>';
				
					$this->printOpeningContainer();
				
					//create html for each video album
					foreach($data as $val)
					{
						
						//use permalinks for pages, else use GET parameters
						if(is_page() && $this->_options['skipSlugs'] == 'no')
							$this->_content .= $this->printAlbum($val, 'permalink');
						else
							$this->_content .= $this->printAlbum($val);
							
					}
			
				}
				
				$this->printClosingContainer();
				
			}
			//if there are no video albums in the gallery
			else
				$this->_content .= '<p>' . __('Sorry... there appear to be no video albums yet.', 'utvg') . '</p>';

		}
		
		$this->printClosingTags();
	
	
		return $this->_content;
	
	}
	
	private function printOpeningTags()
	{
		$this->_content .= '<div class="utv-container">';
	}
	
	private function printClosingTags()
	{
		$this->_content .= '</div>';
	}
	
	private function printOpeningContainer()
	{
	
		$css = '';
	
		if($this->_atts['align'] == 'center')
			$css = ' class="utv-align-center"';
		elseif($this->_atts['align'] == 'right')
			$css = ' class="utv-align-right"';
	
		$this->_content .= '<div' . $css . '>';
	}
	
	private function printClosingContainer()
	{		
		$this->_content .= '</div>';
	}
	
	private function printVideo(&$data)
	{
	
	
		if($data['VID_THUMBTYPE'] == 'square')
			$style = 'width:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px; height:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px;';
		else
			$style = 'width:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px; height:' . round($this->_gallery[0]['DATA_THUMBWIDTH'] / 1.339) . 'px;';

		return '<div class="utv-thumb" style="width:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px; margin:10px ' . $this->_gallery[0]['DATA_THUMBPADDING'] . 'px;">
			<a href="http://www.youtube.com/embed/' . $data['VID_URL'] . '?rel=0&showinfo=0&autohide=1&autoplay=1&iv_load_policy=3&color=' . $this->_options['playerProgressColor'] . '&vq=' . $data['VID_QUALITY'] . '&controls=' . $data['VID_CHROME'] . '" title="' . stripslashes($data['VID_NAME']) . '" class="utv-popup ' . ($data['VID_THUMBTYPE'] == 'square' ? 'utv-square' : 'utv-rect') . '" style="background-image: url(' . $this->_dir . '/utubevideo-cache/' . $data['VID_URL']  . '.jpg); ' . $style . '">	
				<span class="utv-play-btn"></span>
			</a>
			<span>' . stripslashes($data['VID_NAME']) . '</span>
		</div>';
							
	}
	
	private function printAlbum(&$data, $linkType = '')
	{
	
		if($data['VID_THUMBTYPE'] == 'square')
			$style = 'width:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px; height:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px;';
		else
			$style = 'width:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px; height:' . round($this->_gallery[0]['DATA_THUMBWIDTH'] / 1.339) . 'px;';
	
		if($linkType == 'permalink')
			$link = get_site_url() . '/' . get_query_var('pagename') . '/album/' . $data['ALB_SLUG'] . '/';
		else
			$link = '?aid=' . $data['ALB_ID'] . '-' . $this->_atts['id'];
			
		return '<div class="utv-thumb utv-album" style="width:' . $this->_gallery[0]['DATA_THUMBWIDTH'] . 'px; margin:10px ' . $this->_gallery[0]['DATA_THUMBPADDING'] . 'px;">
			<a href="' . $link . '" class="' . ($data['VID_THUMBTYPE'] == 'square' ? 'utv-square' : 'utv-rect') . '" style="' . $style . '">
				<img src="' . $this->_dir . '/utubevideo-cache/' . $data['ALB_THUMB']  . '.jpg"/>
			</a>
			<span>' . stripslashes($data['ALB_NAME']) . '</span>
		</div>';
	
	}
	
}

?>