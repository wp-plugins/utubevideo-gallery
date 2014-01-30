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
		add_action('wp_print_footer_scripts', array(&$this, 'setupLightbox'));
		add_action('wp_enqueue_scripts', array(&$this, 'addStyles'));
		
		//check for extra lightbox script inclusion
		if($this->_options['skipMagnificPopup'] == 'no')
			add_action('wp_enqueue_scripts', array(&$this, 'addLightboxScripts'));
		
	}
		
	public function addStyles()
	{
		
		//load frontend styles
		wp_enqueue_style('utv_style', plugins_url('css/front_style.min.css', __FILE__), false, null);
			
	}
		
	//setup fancybox call for video galleries
	public function setupLightbox()
	{
   
	?>

		<script>
			
			jQuery(function(){

				jQuery('a.utv-popup').click(function(){
				
					var url = jQuery(this).attr('href');
					var title = jQuery(this).attr('title');
					
					jQuery.magnificPopup.open({
						items: {src: url},
						type: 'iframe',
						iframe: {
							patterns: false,
							markup: '<div class="utv-mfp-iframe-scaler mfp-iframe-scaler">'+
								'<div class="mfp-close"></div>'+
								'<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>'+
								'</div><div class="utv-mfp-bottom-bar">'+
								'<div class="mfp-title"></div></div>'
						},
						key: 'utvid',
						callbacks: {
							open: function() {
								jQuery('.mfp-content').css('maxWidth', '<?php echo $this->_options['playerWidth']; ?>px');
								jQuery('.mfp-title').text(title);
								var $bg = jQuery('.mfp-bg');
								$bg.css('background', '<?php echo $this->_options['fancyboxOverlayColor']; ?>');
								$bg.css('opacity', '<?php echo $this->_options['fancyboxOverlayOpacity']; ?>');
							}
						}
					});
					
					return false;
				
				});

			});

		</script>

	<?php
		
	}
		
	public function addLightboxScripts()
	{
		
		//load jquery and lightbox js / css
		wp_enqueue_script('jquery');	
		wp_enqueue_script('js', '//cdn.jsdelivr.net/jquery.magnific-popup/0.9.3/jquery.magnific-popup.min.js', array('jquery'), null, true);
		wp_enqueue_style('css', '//cdn.jsdelivr.net/jquery.magnific-popup/0.9.3/magnific-popup.css', false, null);
	
	}
		
	public function shortcode($atts)
	{
	
		require_once 'class/utvVideoGen.class.php';
		
		if(get_query_var('albumid') != null)
			$utvVideoGen = new utvVideoGen($atts, $this->_options, 'permalink', get_query_var('albumid'));
		elseif(isset($_GET['aid']))
			$utvVideoGen = new utvVideoGen($atts, $this->_options, 'query', $_GET['aid']);
		else
			$utvVideoGen = new utvVideoGen($atts, $this->_options);

		return $utvVideoGen->printGallery();	
			
	}
				
}
?>