<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
class LLAJS{
	private $pages = array();
	private $js_dir = NULL;
	public function __construct($pages){
		$this->pages = $pages;
		$this->js_dir = trailingslashit(__LLA_PLUGIN_URL__."assets/js");
		add_action( 'admin_enqueue_scripts', array($this, "__enqueue__") );
	}
	public function __enqueue__($hook){
		if(in_array($hook, $this->pages)){
			$scripts = array(
				"loader.js"
			);
			foreach($scripts as $script){
				$script_tag = LLA_SLUG."-".sanitize_title($script);
				wp_enqueue_script(
					$script_tag, 
		    		$this->js_dir.$script, 
		    		array(),
		    		LLA_VERSION
		    	);
		    }
			wp_localize_script( 
				$script_tag,
				'lba_ajax_object',
			    array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) 
		   	);
		}
	}
}
?>
