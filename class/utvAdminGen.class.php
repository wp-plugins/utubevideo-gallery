<?php 

class utvAdminGen
{

	private $_options, $_basePath;

	public function __construct(&$options)
	{
	
		$this->_options = $options;
		
	}
	
	public function setPath()
	{
	
		$this->_basePath = wp_upload_dir();
		$this->_basePath = $this->_basePath['basedir'] . '/utubevideo-cache/';
	
	}
	
	public function saveThumbnail($sourceURL, $destPath, $thumbType, $suppressErrors = false)
	{
	
		$image = wp_get_image_editor($sourceURL);
		
		if(!is_wp_error($image)){
		
			if($thumbType == 'square')
				$image->resize($this->_options['thumbnailWidth'], $this->_options['thumbnailWidth'], true);
			else
				$image->resize($this->_options['thumbnailWidth'], $this->_options['thumbnailWidth']);

			$image->save($this->_basePath . $destPath . '.jpg');
			
			return true;
				
		}else{
		
			if(!$suppressErrors){
			
				$this->thumbnailErrorMessage($image);
			
				return false;
			
			}else{
				
				return true;
				
			}
		
		}

	}
	
	public function queryAPI($query)
	{
	
		$data = wp_remote_get($query);
		
		if($data['response']['code'] != 200){
			
			echo '<div class="error"><p>' . __('Oops... there seems to be a problem querying the appropriate API.') . '</p></div>';
			return false;
		}
		
		return json_decode($data['body'], true);
		
	}
	
	public function parseURL($url, $domain, $type)
	{
	
		$id = false;
	
		if($domain == 'youtube'){
		
			if($type == 'video'){
			
				if(preg_match('/youtu.be\/([0-9A-Za-z]+)/', $url, $matches))
					$id = $matches[1];
				else{
					
					$url = parse_url($url);
				
					if(isset($url['query'])){
					
						parse_str($url['query'], $querystr);
					
						if(isset($querystr['v']))
							$id = $querystr['v'];
							
					}
					
				}
				
			}elseif($type == 'playlist'){
			
				$url = parse_url($url);
				
				if(isset($url['query'])){
				
					parse_str($url['query'], $querystr);
					
					if(isset($querystr['list']))
						$id = $querystr['list'];
						
				}
				
			}
		
		}elseif($domain == 'vimeo'){
		
			if($type == 'video'){
			
				if(preg_match('/vimeo.com\/([0-9]+)/', $url, $matches))
					$id = $matches[1];
			
			}elseif($type == 'playlist'){
			
				if(preg_match('/\/album\/([0-9]+)/', $url, $matches))
					$id = $matches[1];
			
			}
		
		}
		
		return $id;

	}
	
	public function deleteVideos($videos, &$wpdb)
	{
		
		//sanitize key array
		$queryString = implode(', ', array_map('intval', $videos));
		
		//query database for ids and video url sets
		$videos = $wpdb->get_results('SELECT VID_URL, ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID IN (' . $queryString . ')', ARRAY_A);
		
		//get current video count for album
		$album = $wpdb->get_results('SELECT ALB_ID, ALB_VIDCOUNT FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $videos[0]['ALB_ID'], ARRAY_A);
		$videoCount = $album[0]['ALB_VIDCOUNT'] - count($videos);
		
		//change album thumb to missing if empty
		if($videoCount < 1){
			if($wpdb->update(
				$wpdb->prefix . 'utubevideo_album',
				array(
					'ALB_THUMB' => 'missing'
				),
				array('ALB_ID' => $album[0]['ALB_ID'])
			) === false)
				return false;
		}
	
		//delete video data and update album count
		if($wpdb->query('DELETE FROM ' . $wpdb->prefix . 'utubevideo_video WHERE VID_ID IN (' . $queryString . ')'
		) === false|| $wpdb->update(
			$wpdb->prefix . 'utubevideo_album', 
			array( 
				'ALB_VIDCOUNT' => $videoCount, 
			), 
			array('ALB_ID' => $album[0]['ALB_ID'])
		) === false)
			return false;
		
		//delete video thumbnails
		foreach($videos as $val)
			unlink($this->_basePath . $val['VID_URL']  . '.jpg');
		
		return true;

	}
	
	public function deleteAlbums($albums, &$wpdb)
	{
	
		//get count of deleted albums//
		$deletedAlbumCount = count($albums);
	
		//sanitize key array
		$queryString = implode(', ', array_map('intval', $albums));
	
		//get videos in album to delete//
		$videos = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID IN (' . $queryString . ')', ARRAY_A);
		
		//get gallery id for albums//
		$galleryId = $wpdb->get_results('SELECT DATA_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID = ' . $albums[0], ARRAY_A);
		$galleryId = $galleryId[0]['DATA_ID'];
	
		//get current album count for gallery//
		$albumCount = $wpdb->get_results('SELECT DATA_ALBCOUNT FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID = ' . $galleryId, ARRAY_A);
		$albumCount = $albumCount[0]['DATA_ALBCOUNT'];
			
		//delete video data and update album count
		if($wpdb->query('DELETE FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID IN (' . $queryString . ')'
		) === false || $wpdb->query('DELETE FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID IN (' . $queryString . ')'
		) === false|| $wpdb->update(
			$wpdb->prefix . 'utubevideo_dataset', 
			array( 
				'DATA_ALBCOUNT' => $albumCount - $deletedAlbumCount, 
			), 
			array('DATA_ID' => $galleryId)
		) === false)
			return false;	
		
		//delete video thumbnails
		foreach($videos as $val)
			unlink($this->_basePath . $val['VID_URL']  . '.jpg');
		
		return true;
				
	}
	
	public function deleteGalleries($galleries, &$wpdb)
	{
	
		$albumIdArray = array();
		
		//sanitize key array
		$galleriesQueryString = implode(', ', array_map('intval', $galleries));
		
		//get albums within gallery//
		$albums = $wpdb->get_results('SELECT ALB_ID FROM ' . $wpdb->prefix . 'utubevideo_album WHERE DATA_ID IN (' . $galleriesQueryString . ')', ARRAY_A);
		
		foreach($albums as $val)
			$albumIdArray .= $val['ALB_ID'];
		
		$albumsQueryString = (count($albumIdArray) == 0 ? 'null' : implode(', ', array_map('intval', $albumIdArray)));

		$videos = $wpdb->get_results('SELECT VID_URL FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID IN (' . $albumsQueryString . ')', ARRAY_A);
		
		//delete video data and update album count
		if($wpdb->query('DELETE FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID IN (' . $albumsQueryString . ')'
		) === false || $wpdb->query('DELETE FROM ' . $wpdb->prefix . 'utubevideo_album WHERE ALB_ID IN (' . $albumsQueryString . ')'
		) === false || !$wpdb->query('DELETE FROM ' . $wpdb->prefix . 'utubevideo_dataset WHERE DATA_ID IN (' . $galleriesQueryString . ')'
		) === false)
			return false;	

		//delete video thumbnails
		foreach($videos as $val)
			unlink($this->_basePath . $val['VID_URL']  . '.jpg');
		
		return true;
	
	}
	
	public static function toggleVideosPublish($videos, $status, &$wpdb)
	{
	
		//sanitize key array
		$queryString = implode(', ', array_map('intval', $videos));
		
		//update videos to chosen status
		if(!$wpdb->query('UPDATE ' . $wpdb->prefix . 'utubevideo_video SET VID_PUBLISH = ' . $status . ' WHERE VID_ID IN (' . $queryString . ')'))
			return false;
			
		return true;
		
	}
	
	public static function toggleAlbumsPublish($albums, $status, &$wpdb)
	{
	
		//sanitize key array
		$queryString = implode(', ', array_map('intval', $albums));
		
		//update videos to chosen status
		if(!$wpdb->query('UPDATE ' . $wpdb->prefix . 'utubevideo_album SET ALB_PUBLISH = ' . $status . ' WHERE ALB_ID IN (' . $queryString . ')'))
			return false;
			
		return true;
		
	}
	
	public function generateSlug($albumName, &$wpdb)
	{
	
		$rawslugs = $wpdb->get_results('SELECT ALB_SLUG FROM ' . $wpdb->prefix . 'utubevideo_album', ARRAY_N);

		foreach($rawslugs as $item)
			$sluglist[] = $item[0];
			
		$mark = 1;
		$slug = strtolower($albumName);
		$slug = str_replace(' ', '-', $slug);
		$slug = html_entity_decode($slug, ENT_QUOTES, 'UTF-8');
		$slug = preg_replace("/[^a-zA-Z0-9-]+/", '', $slug);
		
		if(!empty($sluglist))
			$this->checkslug($slug, $sluglist, $mark);
			
		return $slug;
	
	}
	
	private function thumbnailErrorMessage(&$image)
	{
	
		echo '<div class="error"><p>' . __('Oops... there seems to be a problem saving the video(s) thumbnail. Most likely you need to install a PHP image processing library, such as GD or Imagick. Please send the following information to the developer if the problem persists.', 'utvg') . '</p><p><pre>' . print_r($image, true) . '</pre></p></div>';
	
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