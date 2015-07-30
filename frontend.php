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
		add_shortcode('utubevideo', array($this, 'shortcode'));
		add_action('wp_print_footer_scripts', array($this, 'addJS'));
		add_action('wp_enqueue_scripts', array($this, 'addStyles'));
		
		//check for extra lightbox script inclusion
		if($this->_options['skipMagnificPopup'] == 'no')
			add_action('wp_enqueue_scripts', array($this, 'addLightboxScripts'));
		
	}
	
	//insert styles for galleries
	public function addStyles()
	{
		
		//load frontend styles
		wp_enqueue_style('utv_style', plugins_url('css/front_style.min.css', __FILE__), false, null);
		
		if($this->_options['thumbnailBorderRadius'] > 0){
		
			$css = '.utv-thumb a, .utv-thumb img{border-radius:' . $this->_options['thumbnailBorderRadius'] . 'px!important;-moz-border-radius:' . $this->_options['thumbnailBorderRadius'] . 'px!important;-webkit-border-radius:' . $this->_options['thumbnailBorderRadius'] . 'px!important}';
			wp_add_inline_style('utv_style', $css);
		}
	}
		
	//insert javascript for galleries
	public function addJS()
	{
   
	?>

		<script>
		
			var utv_vars = {
				galleries: new Array(),
				thumbwidth: <?php echo $this->_options['thumbnailWidth']; ?>,
				thumbpadding: <?php echo $this->_options['thumbnailPadding']; ?>
			}
			
			function utvGallery(outercontainer, innercontainer){
			
				this.outercontainer = outercontainer;
				this.innercontainer = innercontainer;
			}
			
			function setupGalleryObjects(containers){
			
				containers.each(function(){
					var outercontainer = jQuery(this);
					var innercontainer = outercontainer.find('.utv-inner-wrapper');		
					utv_vars.galleries.push(new utvGallery(outercontainer, innercontainer));
				});
				
				setGalleryFlow();
				
				containers.each(function(){
					jQuery(this).css('visibility', 'visible');
				});
			}
			
			function setGalleryFlow(){
			
				for(var i = 0; i < utv_vars.galleries.length; i++){
					var outerwidth = utv_vars.galleries[i].outercontainer.width();
					var blocks = Math.floor(outerwidth / (utv_vars.thumbwidth + (utv_vars.thumbpadding * 2)));
					var size = (utv_vars.thumbwidth + (utv_vars.thumbpadding * 2)) * blocks;
					utv_vars.galleries[i].innercontainer.css('width', size + 'px');
				}
			}
			
			jQuery(function(){
			
				var containers = jQuery('.utv-container');

				containers.on('click', 'a.utv-popup', function(){
			
					var url = jQuery(this).attr('href');
					var title = jQuery(this).attr('title');
					
					jQuery.magnificPopup.open({
						items: {src: url},
						type: 'iframe',
						iframe: {
							patterns: new Array(),
							markup: '<div class="utv-mfp-iframe-scaler mfp-iframe-scaler">'+
								'<div class="mfp-close"></div>'+
								'<iframe class="mfp-iframe" frameborder="0" width="<?php echo $this->_options['playerWidth']; ?>" height="<?php echo $this->_options['playerHeight']; ?>" allowfullscreen></iframe>'+
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
				
				setupGalleryObjects(containers);

			});
			
			jQuery(window).resize(function(){
			
				if(utv_vars.galleries.length > 0)
					setGalleryFlow();
			});

		</script>

	<?php
		
	}
		
	public function addLightboxScripts()
	{
		
		//load jquery and lightbox js / css
		wp_enqueue_script('jquery');	
		wp_enqueue_script('js', '//cdn.jsdelivr.net/jquery.magnific-popup/1.0.0/jquery.magnific-popup.min.js', array('jquery'), null, true);
		wp_enqueue_style('css', '//cdn.jsdelivr.net/jquery.magnific-popup/1.0.0/magnific-popup.css', false, null);
	
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