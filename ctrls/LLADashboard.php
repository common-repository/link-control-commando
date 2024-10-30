<?php
class LLADashboard extends LLAUtils{
    public $menus = NULL;
    public $item = NULL;
	public $prefills = array();
	public $errors = array();
	
	
	public function __construct(){
		add_action( 'admin_menu', array($this, "__menu__") );
	}
	public static function secsToStr($secs) {  
		$r = '';
		if($secs>=86400){$days=floor($secs/86400);$secs=$secs%86400;$r=$days.' day';if($days<>1){$r.='s';}if($secs>0){$r.=', ';}}  
		if($secs>=3600){$hours=floor($secs/3600);$secs=$secs%3600;$r.=$hours.' hour';if($hours<>1){$r.='s';}if($secs>0){$r.=', ';}}  
		if($secs>=60){$minutes=floor($secs/60);$secs=$secs%60;$r.=$minutes.' minute';if($minutes<>1){$r.='s';}if($secs>0){$r.=', ';}}  
		return $r;  
	} 
	public function __menu__(){
		$this->__process_request__();
		$pages[] = add_menu_page(
			__( LLA_LONG_NAME, LLA_NAMESPACE ),
			__( LLA_LONG_NAME, LLA_NAMESPACE ),
			LLA_DEFAULT_CAPABILITY,
			LLA_SLUG,
			array($this, "print_analysis_page"),
			''
		);
		$pages[] = add_submenu_page(
			LLA_SLUG, 
			__( "Analysis", LLA_NAMESPACE ),
			__( "Analysis", LLA_NAMESPACE ),
			LLA_DEFAULT_CAPABILITY,
			LLA_SLUG,
			array($this, "print_analysis_page"),
			''
		);
		$this->add_js($pages);
		$this->add_css($pages);
	}
	
	private function add_js($pages){
		require_once(__LLA_JS_FILE__);
		$class_name = __LLA_JS__;
		$class = new $class_name($pages);
	}
	private function add_css($pages){
		require_once(__LLA_CSS_FILE__);
		$class_name = __LLA_CSS__;
		$class = new $class_name($pages);
	}
	public function print_settings_page(){
		include(__LLA_DIR_VIEWS__."settings.php");
	}
	public function print_indexing_page(){
		include(__LLA_DIR_VIEWS__."indexing.php");
	}
	public function print_analysis_page(){
		if(isset($_REQUEST[LLA_SLUG]) && $_REQUEST[LLA_SLUG] == 'anchors'){
			include(__LLA_DIR_VIEWS__."table-anchor.php");
		} else if(isset($_REQUEST[LLA_SLUG]) && $_REQUEST[LLA_SLUG] == 'posts'){
			include(__LLA_DIR_VIEWS__."table-post.php");
		} else if(isset($_REQUEST[LLA_SLUG]) && $_REQUEST[LLA_SLUG] == 'links'){
			include(__LLA_DIR_VIEWS__."table-links.php");
		} else if(isset($_REQUEST[LLA_SLUG]) && $_REQUEST[LLA_SLUG] == 'unique-links'){
			include(__LLA_DIR_VIEWS__."table-unique-links.php");
		} else if(isset($_REQUEST[LLA_SLUG]) && $_REQUEST[LLA_SLUG] == 'domains'){
			include(__LLA_DIR_VIEWS__."table-domains.php");
		} else {
			include(__LLA_DIR_VIEWS__."analysis.php");
		}
	}
	public function print_license_page(){
		include(__LLA_DIR_VIEWS__."license.php");
	}
	public function __process_request__(){
		global $wpdb;
		if(array_key_exists('lla_rescan', $_REQUEST)){
			$wpdb->update( 
				LLA_DOMAINS_TABLE,
				array('deleted' => 1,'status' => 0, 'count' => 0), 
				array('deleted' => 0)
			);

			$wpdb->update( 
				LLA_UNIQUE_LINKS_TABLE,
				array('deleted' => 1,'status' => 0, 'count' => 0), 
				array('deleted' => 0)
			);
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_TABLE));
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_ANCHORS_TABLE));
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_POST_LINKS_TABLE));
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_STRIP_LINKS_TABLE));

			self::add_option("post_cursor", 0);
			wp_redirect(add_query_arg(array("lla_rescan" => false)));
			exit;
		}
		if(array_key_exists('lla_clear_rescan', $_REQUEST)){
			
			$wpdb->update( 
				LLA_DOMAINS_TABLE,
				array('deleted' => 1,'status' => 0, 'count' => 0), 
				array('deleted' => 0)
			);
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_TABLE));
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_ANCHORS_TABLE));
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_POST_LINKS_TABLE));
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_STRIP_LINKS_TABLE));
			$wpdb->query(sprintf('TRUNCATE TABLE %s', LLA_UNIQUE_LINKS_TABLE));

			self::add_option("post_cursor", 0);
			wp_redirect(add_query_arg(array("lla_clear_rescan" => false)));
			exit;
		}
	}
}
?>
