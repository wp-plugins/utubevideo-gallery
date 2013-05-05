<?php 
/**
 * utvFrontend - Frontend section for uTubeVideo Gallery
 *
 * @package uTubeVideo Gallery
 * @author Dustin Scarberry
 *
 * @since 1.3
 */
class utvFrontend
{
	
	private $_options;
		
	public function __construct()
	{
		
		//get plugin options
		$this->_options = get_option('utubevideo_main_opts');
			
		//add hooks
		add_shortcode('utubevideo', array(&$this, 'shortcode'));
		add_action('wp_head', array(&$this, 'setupFancybox'));
		add_action('wp_enqueue_scripts', array(&$this, 'addStyles'));
		
		//check for extra fancybox script inclusion
		if($this->_options['fancyboxInc'] == 'yes')
			add_action('wp_enqueue_scripts', array(&$this, 'addFancyboxScripts'));
		
	}
		
	public function addStyles()
	{
		
		//load frontend styles
		wp_enqueue_style('utv_style', plugins_url('css/front_style.css', __FILE__), false, null);
			
	}
		
	//setup fancybox call for video galleries
	public function setupFancybox()
	{
	
	?>

		<script>
			
			jQuery(function(){

				jQuery('a.utFancyVid').fancybox({
					'cyclic': false,
					'padding': 0,
					'margin': 20,
					'opacity': true,
					'speedIn': 500,
					'speedOut': 500,
					'changeSpeed': 300,
					'overlayShow': true,
					'overlayOpacity': '0.85',
					'overlayColor': '#000',
					'titleShow': true,
					'titlePosition': 'outside',
					'enableEscapeButton': true,
					'showCloseButton': true,
					'showNavArrows': false,				
					'width': <?php echo $this->_options['playerWidth']; ?>,
					'height': <?php echo $this->_options['playerHeight']; ?>,
					'centerOnScroll': true,
					'type': 'iframe'
				});
	
			});

		</script>

	<?php
		
	}
		
	public function addFancyboxScripts()
	{
		
		//load jquery and fancybox js / css
		wp_enqueue_script('jquery');
		wp_enqueue_script('utv_fancybox_script', plugins_url('fancybox/jquery.fancybox-1.3.4.pack.js', __FILE__), array('jquery'), null);
		wp_enqueue_style('utv_fancybox_style', plugins_url('fancybox/jquery.fancybox-1.3.4.css', __FILE__), false, null);
		
	}
		
	public function shortcode($atts)
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
				
				if($check == $id && !isset($skipalbums))
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
				
					$content .= '<div class="utThumb"><a href="http://www.youtube.com/embed/' . $value['VID_URL'] . '?rel=0&showinfo=0&autohide=1&autoplay=1&&iv_load_policy=3&color=' . $this->_options['playerProgressColor'] . '&vq=' . $value['VID_QUALITY'] . '" title="' . stripslashes($value['VID_NAME']) . '" class="utFancyVid"><img src="' . $dir . '/utubevideo-cache/' . $value['VID_URL']  . '.jpg"/></a><span>' . stripslashes($value['VID_NAME']) . '</span></div>';
					
				}
			
			}
			//if the video album is empty :(//
			else
			{
			
				$content .= '<div class="utBreadcrumbs"><a href="' . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . '">Albums</a></div>';
				
				$content .= '<p>Sorry... there appear to be no videos for this album yet.</p>';
				
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
		
				//if skipalbums in set to true//
				if(isset($skipalbums) && $skipalbums == 'true')
				{

					//build array of album ids//
					foreach($rows as $idval)
						$alids[] = $idval['ALB_ID'];
			
					//implode ids to string//
					$alids = implode(', ', $alids);
					//get video info for each all albums in gallery//
					$vids = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'utubevideo_video WHERE ALB_ID IN ('  . $alids . ') ORDER BY VID_UPDATEDATE', ARRAY_A);

					//create html for all videos in gallery//
					foreach($vids as $value)
					{
						
						$content .= '<div class="utThumb"><a href="http://www.youtube.com/embed/' . $value['VID_URL'] . '?rel=0&showinfo=0&autohide=1&autoplay=1&&iv_load_policy=3&color=' . $this->_options['playerProgressColor'] . '&vq=' . $value['VID_QUALITY'] . '" title="' . stripslashes($value['VID_NAME']) . '" class="utFancyVid"><img src="' . $dir . '/utubevideo-cache/' . $value['VID_URL']  . '.jpg"/></a><span>' . stripslashes($value['VID_NAME']) . '</span></div>';
							
					}
		
				}
				//if skipalbums is not set to true//
				else
				{

					//create html for each video album//
					foreach($rows as $value)
					{
						
						$content .= '<div class="utThumb"><a href="?aid=' . $value['ALB_ID'] . '_' . $id . '"><img src="' . $dir . '/utubevideo-cache/' . $value['ALB_THUMB']  . '.jpg"/></a><span>' . stripslashes($value['ALB_NAME']) . '</span></div>';
							
					}
			
				}
				
			}
			//if there are no video albums in the gallery :(//
			else
			{
			
				$content .= '<p>Sorry... there appear to be no video albums yet.</p>';
				
			}
		
		}
						
		$content .= '</div>';

		//return html//
		return $content;
		
	}
			
}
?>