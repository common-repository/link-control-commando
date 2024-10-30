<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
class LLACSS{
	private $pages = array();
	private $css_dir = NULL;
	public function __construct($pages){
		$this->pages = $pages;
		$this->css_dir = trailingslashit(__LLA_PLUGIN_URL__."assets/css");
		add_action( 'admin_enqueue_scripts', array($this, "__enqueue__") );
	}
	public function __enqueue__($hook){
		if(in_array($hook, $this->pages)){
			$scripts = array(
				"lla-admin.css"
			);
			foreach($scripts as $script){
				$script_tag = LLA_SLUG."-".sanitize_title($script);
		    	wp_register_style(
		    		$script_tag, 
		    		$this->css_dir.$script, 
		    		false, 
		    		LLA_VERSION
		    	);
		    	wp_enqueue_style($script_tag);
		    }
		}
	}
}
?>
